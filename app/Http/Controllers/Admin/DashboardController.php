<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\Speaker;
use App\Models\Talk;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $today = now()->toDateString();

        return response()->json([
            'events_published' => Event::where('status', 'publicado')->count(),
            'events_cfp_open'  => Event::where('is_accepting_talks', true)->count(),
            'talks_pending'    => Talk::whereIn('status', ['submetida', 'em_analise'])->count(),
            'speakers_total'   => Speaker::count(),
            'tasks_urgent'     => EventTask::where(function ($q) use ($today) {
                $q->where('status', 'impedimento')
                    ->orWhere(fn ($q2) => $q2
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', $today)
                        ->where('status', '!=', 'concluida'));
            })->count(),
            'users_total'    => User::count(),
            'users_inactive' => User::where('is_active', false)->count(),
        ]);
    }

    public function nextEvent(): JsonResponse
    {
        /** @var \App\Models\Event|null $event */
        $event = Event::where('status', 'publicado')
            ->where('starts_at', '>=', now())
            ->withCount([
                'participants',
                'participants as participants_checkedin_count' => fn ($q) => $q->where('checked_in', true),
                'talks as talks_pending_count'                 => fn ($q) => $q->whereIn('status', ['submetida', 'em_analise']),
            ])
            ->orderBy('starts_at')
            ->first();

        if (! $event) {
            return response()->json(null);
        }

        /** @var \Carbon\Carbon $startsAt */
        $startsAt = $event->starts_at;

        /** @var \Carbon\Carbon|null $endsAt */
        $endsAt = $event->ends_at;

        return response()->json([
            'id'                     => $event->id,
            'name'                   => $event->name,
            'starts_at'              => $startsAt->toIso8601String(),
            'ends_at'                => $endsAt?->toIso8601String(),
            'location'               => $event->location,
            'is_online'              => $event->is_online,
            'is_accepting_talks'     => $event->is_accepting_talks,
            'participants_count'     => (int) $event->getAttribute('participants_count'),
            'participants_checkedin' => (int) $event->getAttribute('participants_checkedin_count'),
            'talks_pending'          => (int) $event->getAttribute('talks_pending_count'),
        ]);
    }

    public function activity(): JsonResponse
    {
        $today = now()->toDateString();

        $talks = Talk::with(['speaker.user:id,name', 'event:id,name'])
            ->whereIn('status', ['submetida', 'em_analise'])
            ->orderByDesc('submitted_at')
            ->limit(5)
            ->get()
            ->map(fn ($t) => $this->formatTalkActivity($t));

        $tasks = EventTask::with('event:id,name')
            ->where(function ($q) use ($today) {
                $q->where('status', 'impedimento')
                    ->orWhere(fn ($q2) => $q2
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', $today)
                        ->where('status', '!=', 'concluida'));
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn ($t) => $this->formatTaskActivity($t));

        $activity = $talks->concat($tasks)
            ->sortByDesc('at')
            ->values()
            ->take(8);

        return response()->json($activity);
    }

    /** @return array<string, mixed> */
    private function formatTalkActivity(Talk $t): array
    {
        /** @var \App\Models\Event|null $talkEvent */
        $talkEvent = $t->event;

        /** @var \Carbon\Carbon|null $submittedAt */
        $submittedAt = $t->submitted_at;

        return [
            'type'         => 'talk',
            'id'           => $t->id,
            'title'        => $t->title,
            'speaker_name' => $t->speaker?->user?->name,
            'event_name'   => $talkEvent?->name,
            'event_id'     => $t->event_id,
            'status'       => $t->status,
            'at'           => $submittedAt?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function formatTaskActivity(EventTask $t): array
    {
        /** @var \App\Models\Event|null $taskEvent */
        $taskEvent = $t->event;

        return [
            'type'       => 'task',
            'id'         => $t->id,
            'title'      => $t->title,
            'event_name' => $taskEvent?->name,
            'event_id'   => $t->event_id,
            'status'     => $t->status,
            'is_overdue' => $t->isOverdue(),
            'at'         => $t->updated_at->toIso8601String(),
        ];
    }
}
