<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventSocialAsset\GenerateEventSocialAssetRequest;
use App\Models\Event;
use App\Models\EventSocialAsset;
use App\Services\EventSocialAssetService;
use Illuminate\Http\JsonResponse;

class EventSocialAssetController extends Controller
{
    public function __construct(private readonly EventSocialAssetService $service) {}

    public function show(Event $event): JsonResponse
    {
        $assets = $event->socialAssets->mapWithKeys(
            fn (EventSocialAsset $asset) => [$asset->format => $this->formatAsset($asset)]
        );

        return response()->json([
            'data' => [
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'location' => $event->location,
                    'starts_at' => $event->starts_at?->toIso8601String(),
                ],
                'formats' => ['story', 'post'],
                'assets' => $assets,
            ],
        ]);
    }

    public function generate(GenerateEventSocialAssetRequest $request, Event $event): JsonResponse
    {
        $asset = $this->service->generate($event, $request->validated('format'));

        return response()->json(['data' => $this->formatAsset($asset)]);
    }

    private function formatAsset(EventSocialAsset $asset): array
    {
        return [
            'format' => $asset->format,
            'asset_url' => $asset->url,
            'generated_at' => $asset->updated_at?->toIso8601String(),
        ];
    }
}
