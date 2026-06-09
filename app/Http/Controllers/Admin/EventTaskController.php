<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tasks\StoreTaskRequest;
use App\Http\Requests\Admin\Tasks\UpdateTaskRequest;
use App\Models\Event;
use App\Models\EventTask;
use App\Services\EventTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class EventTaskController extends Controller
{
    public function __construct(private EventTaskService $service) {}

    public function index(Event $event): JsonResponse
    {
        return response()->json($this->service->board($event));
    }

    public function store(StoreTaskRequest $request, Event $event): JsonResponse
    {
        $task = $this->service->create($event, $request->validated(), Auth::id());

        return response()->json(
            ['data' => $this->service->formatTask($task)],
            Response::HTTP_CREATED
        );
    }

    public function show(Event $event, EventTask $task): JsonResponse
    {
        abort_if($task->event_id !== $event->id, Response::HTTP_NOT_FOUND);

        return response()->json(['data' => $this->service->formatTask($task->load(['assignee:id,name', 'creator:id,name']))]);
    }

    public function update(UpdateTaskRequest $request, Event $event, EventTask $task): JsonResponse
    {
        abort_if($task->event_id !== $event->id, Response::HTTP_NOT_FOUND);

        $updated = $this->service->update($task, $request->validated());

        return response()->json(['data' => $this->service->formatTask($updated)]);
    }

    public function updateStatus(Request $request, Event $event, EventTask $task): JsonResponse
    {
        abort_if($task->event_id !== $event->id, Response::HTTP_NOT_FOUND);

        $data = $request->validate([
            'status' => ['required', Rule::in(['a_fazer', 'em_andamento', 'em_revisao', 'impedimento', 'concluida'])],
        ], [
            'status.required' => 'Informe o status.',
            'status.in' => 'Status inválido.',
        ]);

        $updated = $this->service->updateStatus($task, $data['status']);

        return response()->json([
            'id' => $updated->id,
            'status' => $updated->status,
            'sort_order' => $updated->sort_order,
        ]);
    }

    public function reorder(Request $request, Event $event): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $this->service->reorder($event, $data['items']);

        return response()->json(['ok' => true]);
    }

    public function destroy(Event $event, EventTask $task): JsonResponse
    {
        abort_if($task->event_id !== $event->id, Response::HTTP_NOT_FOUND);

        $this->service->delete($task);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function trash(Event $event): JsonResponse
    {
        return response()->json(['data' => $this->service->trash($event)]);
    }

    public function restore(Event $event, int $taskId): JsonResponse
    {
        $task = EventTask::where('event_id', $event->id)
            ->onlyTrashed()
            ->findOrFail($taskId);

        $restored = $this->service->restore($task);

        return response()->json(['data' => $this->service->formatTask($restored)]);
    }
}
