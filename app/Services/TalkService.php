<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Talk;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class TalkService
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'submetida'  => ['em_analise', 'cancelada'],
        'em_analise' => ['aprovada', 'rejeitada', 'cancelada'],
        'aprovada'   => ['cancelada'],
        'rejeitada'  => ['em_analise'],
        'cancelada'  => [],
    ];

    /** @param array<string, mixed> $filters */
    public function list(Event $event, array $filters): LengthAwarePaginator
    {
        return Talk::with('speaker.user')
            ->where('event_id', $event->id)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->orderBy('submitted_at', 'desc')
            ->paginate(9);
    }

    public function updateStatus(Talk $talk, string $newStatus, ?string $feedback): Talk
    {
        $allowed = self::TRANSITIONS[$talk->status];

        if (! in_array($newStatus, $allowed)) {
            throw new InvalidArgumentException(
                "Transição de '{$talk->status}' para '{$newStatus}' não é permitida."
            );
        }

        $talk->update(['status' => $newStatus, 'feedback' => $feedback]);

        return $talk->fresh() ?? $talk;
    }
}
