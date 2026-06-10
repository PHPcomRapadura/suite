<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventCfp;
use App\Models\Talk;
use InvalidArgumentException;

class CfpService
{
    /** @return array<string, mixed>|null */
    public function getWithStats(Event $event): ?array
    {
        /** @var EventCfp|null $cfp */
        $cfp = $event->cfp;
        if (! $cfp) {
            return null;
        }

        $statuses = ['submetida', 'em_analise', 'aprovada', 'rejeitada', 'cancelada'];

        /** @var array<string, int> $counts */
        $counts = Talk::where('event_id', $event->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        /** @var array<string, int> $talksCount */
        $talksCount = ['total' => (int) array_sum($counts)];
        foreach ($statuses as $s) {
            $talksCount[$s] = $counts[$s] ?? 0;
        }

        return [
            'id' => $cfp->id,
            'event_id' => $cfp->event_id,
            'opens_at' => $cfp->opens_at,
            'closes_at' => $cfp->closes_at,
            'speaker_guide' => $cfp->speaker_guide,
            'max_talks_per_speaker' => $cfp->max_talks_per_speaker,
            'status' => $cfp->status(),
            'talks_count' => $talksCount,
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(Event $event, array $data, int $createdBy): EventCfp
    {
        if ($event->cfp()->exists()) {
            throw new InvalidArgumentException('Este evento já possui um CFP configurado.');
        }

        return EventCfp::create([
            ...$data,
            'event_id' => $event->id,
            'created_by' => $createdBy,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function update(EventCfp $cfp, array $data): EventCfp
    {
        $cfp->update($data);

        return $cfp->fresh() ?? $cfp;
    }
}
