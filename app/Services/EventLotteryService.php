<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventLotteryWinner;
use App\Models\EventParticipant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class EventLotteryService
{
    public function state(Event $event): array
    {
        $winners = EventLotteryWinner::where('event_id', $event->id)
            ->with('participant:id,first_name,last_name,email')
            ->orderBy('position')
            ->get();

        $totalPool = EventParticipant::where('event_id', $event->id)
            ->where('checked_in', true)
            ->count();

        $totalDrawn = $winners->count();

        return [
            'winners' => $winners->map(fn ($w) => $this->formatWinner($w))->values()->all(),
            'stats' => [
                'total_pool' => $totalPool,
                'total_drawn' => $totalDrawn,
                'remaining' => $totalPool - $totalDrawn,
            ],
        ];
    }

    public function draw(Event $event): EventLotteryWinner
    {
        $participant = $this->eligiblePool($event)->inRandomOrder()->first();

        if (! $participant) {
            throw ValidationException::withMessages([
                'draw' => 'Não há participantes disponíveis para sortear.',
            ]);
        }

        $winner = EventLotteryWinner::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'position' => $this->nextPosition($event),
            'drawn_at' => now(),
        ]);

        $winner->setRelation('participant', $participant);

        return $winner;
    }

    public function reset(Event $event): void
    {
        EventLotteryWinner::where('event_id', $event->id)->delete();
    }

    public function formatWinner(EventLotteryWinner $winner): array
    {
        return [
            'position' => $winner->position,
            'drawn_at' => $winner->drawn_at?->toIso8601String(),
            'participant' => [
                'id' => $winner->participant->id,
                'full_name' => $winner->participant->full_name,
                'email_obfuscated' => $this->obfuscateEmail($winner->participant->email),
            ],
        ];
    }

    private function nextPosition(Event $event): int
    {
        return (EventLotteryWinner::where('event_id', $event->id)->max('position') ?? 0) + 1;
    }

    private function eligiblePool(Event $event): Builder
    {
        return EventParticipant::where('event_id', $event->id)
            ->where('checked_in', true)
            ->whereNotIn('id', function ($q) use ($event) {
                $q->select('participant_id')
                    ->from('event_lottery_winners')
                    ->where('event_id', $event->id);
            });
    }

    private function obfuscateEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);

        $prefix = mb_substr($local, 0, 2, 'UTF-8');

        return "{$prefix}*****@{$domain}";
    }
}
