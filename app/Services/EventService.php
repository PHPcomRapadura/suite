<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EventService
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'rascunho' => ['publicado', 'cancelado'],
        'publicado' => ['encerrado', 'cancelado'],
        'encerrado' => [],
        'cancelado' => [],
    ];

    private const ADMIN_ONLY_TRANSITIONS = ['publicado', 'cancelado'];

    public function list(array $filters): LengthAwarePaginator
    {
        return Event::query()
            ->when(
                $filters['search'] ?? null,
                fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                    ->orWhere('slug', 'like', "%{$s}%")
            )
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when(
                $filters['year'] ?? null,
                fn ($q, $y) => $q->whereYear('starts_at', $y)
            )
            ->orderBy('starts_at', 'desc')
            ->paginate(9);
    }

    public function create(array $data, ?UploadedFile $coverImage, ?UploadedFile $logo, int $createdBy): Event
    {
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        $event = Event::create([
            ...$data,
            'status' => 'rascunho',
            'created_by' => $createdBy,
        ]);

        if ($coverImage) {
            $event->update(['cover_image' => $this->uploadImage($coverImage, "events/{$event->id}/cover.{$coverImage->extension()}")]);
        }

        if ($logo) {
            $event->update(['logo' => $this->uploadImage($logo, "events/{$event->id}/logo.{$logo->extension()}")]);
        }

        return $event->fresh();
    }

    public function update(Event $event, array $data, ?UploadedFile $coverImage, ?UploadedFile $logo): Event
    {
        if ($event->isFinished()) {
            throw new InvalidArgumentException('Eventos encerrados ou cancelados não podem ser editados.');
        }

        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name'], $event->id);
        }

        $event->update($data);

        if ($coverImage) {
            $this->deleteImage($event->cover_image);
            $event->update(['cover_image' => $this->uploadImage($coverImage, "events/{$event->id}/cover.{$coverImage->extension()}")]);
        }

        if ($logo) {
            $this->deleteImage($event->logo);
            $event->update(['logo' => $this->uploadImage($logo, "events/{$event->id}/logo.{$logo->extension()}")]);
        }

        return $event->fresh();
    }

    public function updateStatus(Event $event, string $newStatus, User $actor): Event
    {
        $allowed = self::TRANSITIONS[$event->status];

        if (! in_array($newStatus, $allowed)) {
            throw new InvalidArgumentException(
                "Transição de '{$event->status}' para '{$newStatus}' não é permitida."
            );
        }

        if (in_array($newStatus, self::ADMIN_ONLY_TRANSITIONS) && ! $actor->isAdmin()) {
            throw new AccessDeniedHttpException('Apenas administradores podem realizar esta ação.');
        }

        $event->update(['status' => $newStatus]);

        return $event->fresh();
    }

    public function toggleTalks(Event $event): Event
    {
        if (! $event->isPublished()) {
            throw new InvalidArgumentException('O CFP só pode ser ativado em eventos publicados.');
        }

        $event->update(['is_accepting_talks' => ! $event->is_accepting_talks]);

        return $event->fresh();
    }

    public function generateSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (
            Event::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function uploadImage(UploadedFile $file, string $path): string
    {
        Storage::disk('r2')->putFileAs('', $file, $path, 'public');

        return Storage::disk('r2')->url($path);
    }

    public function deleteImage(?string $url): void
    {
        if (! $url) {
            return;
        }

        try {
            $baseUrl = rtrim(Storage::disk('r2')->url(''), '/');
        } catch (\RuntimeException) {
            return;
        }

        if ($baseUrl && str_starts_with($url, $baseUrl)) {
            $path = ltrim(substr($url, strlen($baseUrl)), '/');
            Storage::disk('r2')->delete($path);
        }
    }
}
