<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventSocialAsset\GenerateEventSocialAssetRequest;
use App\Models\Event;
use App\Services\EventSocialAssetService;
use Illuminate\Http\JsonResponse;

class EventSocialAssetController extends Controller
{
    public function __construct(private readonly EventSocialAssetService $service)
    {
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json([
            'data' => [
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'location' => $event->location,
                    'starts_at' => $event->starts_at?->toIso8601String(),
                ],
                'formats' => ['story', 'post'],
            ],
        ]);
    }

    public function generate(GenerateEventSocialAssetRequest $request, Event $event): JsonResponse
    {
        $result = $this->service->generate($event, $request->validated('format'));

        return response()->json(['data' => $result]);
    }
}
