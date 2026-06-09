<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventTask;
use App\Models\User;

class EventTaskService
{
    private const STATUSES = ['a_fazer', 'em_andamento', 'em_revisao', 'impedimento', 'concluida'];

    private const PRIORITY_LABELS = [
        'baixa' => 'Baixa',
        'media' => 'Média',
        'alta' => 'Alta',
    ];

    public function board(Event $event): array
    {
        $tasks = EventTask::where('event_id', $event->id)
            ->with(['assignee:id,name', 'creator:id,name'])
            ->withCount('comments')
            ->orderBy('sort_order')
            ->get();

        $grouped = [];
        foreach (self::STATUSES as $status) {
            $grouped[$status] = $tasks
                ->where('status', $status)
                ->values()
                ->map(fn ($t) => $this->formatTask($t))
                ->all();
        }

        $total = $tasks->count();
        $concluded = $tasks->where('status', 'concluida')->count();
        $overdue = $tasks->filter(fn ($t) => $t->isOverdue())->count();

        $assignees = User::whereIn('role', ['admin', 'colaborador'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        return [
            'data' => $grouped,
            'summary' => [
                'total' => $total,
                'concluida' => $concluded,
                'overdue' => $overdue,
            ],
            'assignees' => $assignees,
        ];
    }

    public function create(Event $event, array $data, int $createdBy): EventTask
    {
        $status = $data['status'] ?? 'a_fazer';

        $task = EventTask::create([
            ...$data,
            'event_id' => $event->id,
            'status' => $status,
            'sort_order' => $this->nextSortOrder($event->id, $status),
            'created_by' => $createdBy,
        ]);

        return $task->load(['assignee:id,name', 'creator:id,name']);
    }

    public function update(EventTask $task, array $data): EventTask
    {
        $task->update($data);

        return $task->load(['assignee:id,name', 'creator:id,name']);
    }

    public function updateStatus(EventTask $task, string $newStatus): EventTask
    {
        $task->update([
            'status' => $newStatus,
            'sort_order' => $this->nextSortOrder($task->event_id, $newStatus),
        ]);

        return $task;
    }

    public function reorder(Event $event, array $items): void
    {
        $eventId = $event->id;

        foreach ($items as $item) {
            EventTask::where('id', $item['id'])
                ->where('event_id', $eventId)
                ->update(['sort_order' => $item['sort_order']]);
        }
    }

    public function delete(EventTask $task): void
    {
        $task->delete();
    }

    public function restore(EventTask $task): EventTask
    {
        $task->restore();
        $task->update([
            'sort_order' => $this->nextSortOrder($task->event_id, $task->status),
        ]);

        return $task->load(['assignee:id,name', 'creator:id,name']);
    }

    public function trash(Event $event): array
    {
        $tasks = EventTask::where('event_id', $event->id)
            ->onlyTrashed()
            ->with(['assignee:id,name', 'creator:id,name'])
            ->orderByDesc('deleted_at')
            ->get();

        return $tasks->map(fn ($t) => $this->formatTask($t))->all();
    }

    public function nextSortOrder(int $eventId, string $status): int
    {
        $max = EventTask::where('event_id', $eventId)
            ->where('status', $status)
            ->max('sort_order');

        return $max === null ? 0 : $max + 1;
    }

    public function formatTask(EventTask $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'priority_label' => self::PRIORITY_LABELS[$task->priority] ?? $task->priority,
            'due_date' => $task->due_date?->format('Y-m-d'),
            'is_overdue' => $task->isOverdue(),
            'sort_order' => $task->sort_order,
            'assigned_to' => $task->assigned_to,
            'assignee' => $task->assignee ? ['id' => $task->assignee->id, 'name' => $task->assignee->name] : null,
            'comments_count' => $task->comments_count ?? 0,
            'created_by' => $task->created_by,
            'deleted_at' => $task->deleted_at?->toISOString(),
            'created_at' => $task->created_at?->toISOString(),
            'updated_at' => $task->updated_at?->toISOString(),
        ];
    }
}
