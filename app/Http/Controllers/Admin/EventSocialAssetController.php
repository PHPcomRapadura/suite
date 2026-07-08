<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventSocialAsset\GenerateEventSocialAssetRequest;
use App\Models\Event;
use App\Models\EventSocialAsset;
use App\Models\EventSponsor;
use App\Models\Talk;
use App\Services\EventSocialAssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventSocialAssetController extends Controller
{
    public function __construct(private readonly EventSocialAssetService $service) {}

    public function show(Event $event): JsonResponse
    {
        $assets = $event->socialAssets->map(fn (EventSocialAsset $asset) => $this->formatAsset($asset))->values();

        return response()->json([
            'data' => [
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'location' => $event->location,
                    'starts_at' => $event->starts_at?->toIso8601String(),
                ],
                'formats' => ['story', 'post'],
                'types' => ['announcement', 'speaker', 'sponsor', 'selling_out', 'tomorrow'],
                'assets' => $assets,
            ],
        ]);
    }

    public function generate(GenerateEventSocialAssetRequest $request, Event $event): JsonResponse
    {
        $type = $request->type();
        $talk = null;
        $sponsor = null;

        if ($type === 'speaker') {
            $talk = Talk::with('speaker.user')->findOrFail($request->validated('talk_id'));
            abort_if($talk->event_id !== $event->id, Response::HTTP_NOT_FOUND);
            abort_if($talk->status !== 'aprovada', Response::HTTP_UNPROCESSABLE_ENTITY, 'Só é possível divulgar palestras aprovadas.');
        }

        if ($type === 'sponsor') {
            $sponsor = EventSponsor::findOrFail($request->validated('sponsor_id'));
            abort_if($sponsor->event_id !== $event->id, Response::HTTP_NOT_FOUND);
        }

        $asset = $this->service->generate($event, $request->validated('format'), $type, $talk, $sponsor);

        return response()->json(['data' => $this->formatAsset($asset)]);
    }

    public function download(Event $event, EventSocialAsset $asset): StreamedResponse
    {
        abort_if($asset->event_id !== $event->id, Response::HTTP_NOT_FOUND);

        $filename = "{$event->slug}-{$asset->type}-{$asset->format}.png";

        return Storage::disk('r2')->download($asset->path, $filename);
    }

    private function formatAsset(EventSocialAsset $asset): array
    {
        return [
            'id' => $asset->id,
            'type' => $asset->type,
            'format' => $asset->format,
            'subject_key' => $asset->subject_key,
            'talk_id' => $asset->talk_id,
            'sponsor_id' => $asset->sponsor_id,
            'asset_url' => $asset->url,
            'generated_at' => $asset->updated_at?->toIso8601String(),
        ];
    }
}
