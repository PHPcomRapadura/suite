<?php

namespace App\Services;

use App\Models\Speaker;
use Illuminate\Pagination\LengthAwarePaginator;

class SpeakerService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return Speaker::with('user')
            ->withCount([
                'talks',
                'talks as talks_approved_count' => fn ($q) => $q->where('status', 'aprovada'),
            ])
            ->whereHas('user', fn ($q) => $q->where('role', 'palestrante'))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->whereHas('user', fn ($q2) => $q2
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($filters['city'] ?? null, fn ($q, $city) => $q->where('city', 'like', "%{$city}%"))
            ->when($filters['state'] ?? null, fn ($q, $state) => $q->where('state', $state))
            ->orderByRaw('(SELECT name FROM users WHERE users.id = speakers.user_id)')
            ->paginate(12);
    }

    public function detail(Speaker $speaker): array
    {
        $speaker->load(['user', 'talks.event:id,name']);

        return $this->formatDetail($speaker);
    }

    public function formatSummary(Speaker $speaker): array
    {
        return [
            'id' => $speaker->id,
            'name' => $speaker->user->name,
            'email' => $speaker->user->email,
            'avatar_url' => $speaker->avatar_url,
            'company' => $speaker->company,
            'city' => $speaker->city,
            'state' => $speaker->state,
            'phone_number' => $speaker->phone_number,
            'is_active' => $speaker->user->is_active,
            'talks_count' => $speaker->talks_count ?? 0,
            'talks_approved' => $speaker->talks_approved_count ?? 0,
        ];
    }

    public function formatDetail(Speaker $speaker): array
    {
        return array_merge($this->formatSummary($speaker), [
            'bio' => $speaker->bio,
            'website' => $speaker->website,
            'twitter' => $speaker->twitter,
            'github' => $speaker->github,
            'linkedin' => $speaker->linkedin,
            'last_login_at' => $speaker->user->last_login_at?->toIso8601String(),
            'talks' => $speaker->talks->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'event' => $t->event?->name,
                'status' => $t->status,
                'level' => $t->level,
                'duration' => $t->duration,
                'submitted_at' => $t->submitted_at?->toIso8601String(),
            ])->values()->all(),
        ]);
    }
}
