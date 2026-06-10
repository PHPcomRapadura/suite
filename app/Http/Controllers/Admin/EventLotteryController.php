<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\EventLotteryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class EventLotteryController extends Controller
{
    public function __construct(private EventLotteryService $service) {}

    public function index(Event $event): JsonResponse
    {
        return response()->json($this->service->state($event));
    }

    public function draw(Event $event): JsonResponse
    {
        try {
            $winner = $this->service->draw($event);
        } catch (ValidationException $e) {
            throw $e;
        }

        return response()->json(['winner' => $this->service->formatWinner($winner)]);
    }

    public function reset(Event $event): JsonResponse
    {
        $this->service->reset($event);

        return response()->json(['reset' => true]);
    }
}
