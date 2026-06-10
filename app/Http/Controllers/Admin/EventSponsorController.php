<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventSite\StoreEventSponsorRequest;
use App\Http\Requests\Admin\EventSite\UpdateEventSponsorRequest;
use App\Models\Event;
use App\Models\EventSponsor;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class EventSponsorController extends Controller
{
    public function __construct(private EventService $eventService) {}

    public function index(Event $event): JsonResponse
    {
        return response()->json(['data' => $event->sponsors]);
    }

    public function store(StoreEventSponsorRequest $request, Event $event): JsonResponse
    {
        $data = $request->validated();
        unset($data['logo']);

        /** @var EventSponsor $sponsor */
        $sponsor = $event->sponsors()->create($data);

        if ($request->hasFile('logo')) {
            /** @var UploadedFile $logo */
            $logo = $request->file('logo');
            $path = "events/{$event->id}/sponsors/{$sponsor->id}/logo.{$logo->extension()}";
            $sponsor->update(['logo_url' => $this->eventService->uploadImage($logo, $path)]);
        }

        return response()->json(['data' => $sponsor->fresh()], Response::HTTP_CREATED);
    }

    public function update(UpdateEventSponsorRequest $request, Event $event, EventSponsor $sponsor): JsonResponse
    {
        abort_if($sponsor->event_id !== $event->id, Response::HTTP_NOT_FOUND);

        $data = $request->validated();
        unset($data['logo']);

        if ($request->hasFile('logo')) {
            /** @var UploadedFile $logo */
            $logo = $request->file('logo');
            $this->eventService->deleteImage($sponsor->logo_url);
            $path = "events/{$event->id}/sponsors/{$sponsor->id}/logo.{$logo->extension()}";
            $data['logo_url'] = $this->eventService->uploadImage($logo, $path);
        }

        $sponsor->update($data);

        return response()->json(['data' => $sponsor->fresh()]);
    }

    public function destroy(Event $event, EventSponsor $sponsor): JsonResponse
    {
        abort_if($sponsor->event_id !== $event->id, Response::HTTP_NOT_FOUND);

        $this->eventService->deleteImage($sponsor->logo_url);
        $sponsor->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function reorder(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['items'] as $item) {
            EventSponsor::where('id', $item['id'])
                ->where('event_id', $event->id)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['data' => $event->sponsors()->get()]);
    }
}
