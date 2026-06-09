<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Expenses\StoreExpenseRequest;
use App\Http\Requests\Admin\Expenses\UpdateExpenseRequest;
use App\Models\Event;
use App\Models\EventExpense;
use App\Services\EventExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EventExpenseController extends Controller
{
    public function __construct(private EventExpenseService $service) {}

    public function index(Request $request, Event $event): JsonResponse
    {
        $filters = $request->only(['category', 'is_paid', 'date_from', 'date_to']);
        $paginator = $this->service->list($event, $filters);
        $summary = $this->service->summary($event, $filters);

        $items = collect($paginator->items())->map(fn ($e) => $this->service->formatExpense($e));

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'summary' => $summary,
        ]);
    }

    public function store(StoreExpenseRequest $request, Event $event): JsonResponse
    {
        $data = $request->safe()->except(['receipt']);
        $receipt = $request->file('receipt');

        $expense = $this->service->create($event, $data, $receipt, Auth::id());

        return response()->json(
            ['data' => $this->service->formatExpense($expense)],
            Response::HTTP_CREATED
        );
    }

    public function show(Event $event, EventExpense $expense): JsonResponse
    {
        abort_if($expense->event_id !== $event->id, Response::HTTP_NOT_FOUND);

        return response()->json(['data' => $this->service->formatExpense($expense->load(['creator:id,name', 'updater:id,name']))]);
    }

    public function update(UpdateExpenseRequest $request, Event $event, EventExpense $expense): JsonResponse
    {
        abort_if($expense->event_id !== $event->id, Response::HTTP_NOT_FOUND);

        $data = $request->safe()->except(['receipt']);
        $receipt = $request->file('receipt');

        $updated = $this->service->update($expense, $data, $receipt, Auth::id());

        return response()->json(['data' => $this->service->formatExpense($updated)]);
    }

    public function destroy(Event $event, EventExpense $expense): JsonResponse
    {
        abort_if($expense->event_id !== $event->id, Response::HTTP_NOT_FOUND);

        $this->service->delete($expense);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
