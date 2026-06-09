<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Participants\UploadParticipantsRequest;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Services\EventParticipantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EventParticipantController extends Controller
{
    public function __construct(private EventParticipantService $service) {}

    public function index(Request $request, Event $event): JsonResponse
    {
        $filters = $request->only(['search', 'ticket_type', 'payment_status', 'checked_in']);
        $paginator = $this->service->list($event, $filters);
        $summary = $this->service->summary($event);

        $items = collect($paginator->items())->map(fn ($p) => $this->service->formatParticipant($p));

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

    public function upload(UploadParticipantsRequest $request, Event $event): JsonResponse
    {
        try {
            $result = $this->service->import($event, $request->file('csv'));
        } catch (ValidationException $e) {
            throw $e;
        }

        return response()->json($result);
    }

    public function destroy(Event $event, EventParticipant $participant): JsonResponse
    {
        abort_if($participant->event_id !== $event->id, Response::HTTP_NOT_FOUND);

        $this->service->delete($participant);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function clear(Event $event): JsonResponse
    {
        $deleted = $this->service->clear($event);

        return response()->json(['deleted' => $deleted]);
    }
}
