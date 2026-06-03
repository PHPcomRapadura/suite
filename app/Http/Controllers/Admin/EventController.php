<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\StoreEventRequest;
use App\Http\Requests\Admin\Events\UpdateEventRequest;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EventController extends Controller
{
    public function __construct(
        private EventService $eventService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->eventService->list(
            $request->only(['search', 'status', 'year'])
        );

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->eventService->create(
            $request->safe()->except(['cover_image', 'logo']),
            $request->file('cover_image'),
            $request->file('logo'),
            Auth::id(),
        );

        return response()->json($event, Response::HTTP_CREATED);
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json($event);
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        try {
            $updated = $this->eventService->update(
                $event,
                $request->safe()->except(['cover_image', 'logo']),
                $request->file('cover_image'),
                $request->file('logo'),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json($updated);
    }

    public function updateStatus(Request $request, Event $event): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:publicado,encerrado,cancelado'],
        ]);

        try {
            $updated = $this->eventService->updateStatus($event, $request->status, Auth::user());
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['status' => $updated->status]);
    }

    public function toggleTalks(Event $event): JsonResponse
    {
        try {
            $updated = $this->eventService->toggleTalks($event);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['is_accepting_talks' => $updated->is_accepting_talks]);
    }
}
