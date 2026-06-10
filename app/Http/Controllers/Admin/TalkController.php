<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Talk;
use App\Services\TalkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class TalkController extends Controller
{
    public function __construct(private TalkService $talkService) {}

    public function index(Request $request, Event $event): JsonResponse
    {
        $paginator = $this->talkService->list($event, $request->only(['status', 'search']));

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

    public function show(Event $event, Talk $talk): JsonResponse
    {
        if ($talk->event_id !== $event->id) {
            return response()->json(['message' => 'Palestra não encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $talk->load('speaker.user');

        return response()->json($talk);
    }

    public function updateStatus(Request $request, Event $event, Talk $talk): JsonResponse
    {
        if ($talk->event_id !== $event->id) {
            return response()->json(['message' => 'Palestra não encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $request->validate([
            'status' => ['required', 'string', 'in:em_analise,aprovada,rejeitada,cancelada'],
            'feedback' => ['required_if:status,rejeitada', 'nullable', 'string'],
        ]);

        try {
            $updated = $this->talkService->updateStatus($talk, $request->status, $request->feedback);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['status' => $updated->status, 'feedback' => $updated->feedback]);
    }
}
