<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventSite\StoreEventScheduleItemRequest;
use App\Http\Requests\Admin\EventSite\UpdateEventScheduleItemRequest;
use App\Models\Event;
use App\Models\EventScheduleItem;
use App\Models\Talk;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EventScheduleController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        /** @var Collection<int, EventScheduleItem> $items */
        $items = $event->schedule()->with('talk.speaker.user')->get();

        return response()->json(['data' => $this->formatItems($items)]);
    }

    public function store(StoreEventScheduleItemRequest $request, Event $event): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        /** @var EventScheduleItem $item */
        $item = $event->schedule()->create($data);
        $item->load('talk.speaker.user');

        return response()->json(['data' => $this->formatItem($item)], Response::HTTP_CREATED);
    }

    public function update(UpdateEventScheduleItemRequest $request, Event $event, EventScheduleItem $item): JsonResponse
    {
        abort_if($item->event_id !== $event->id, Response::HTTP_NOT_FOUND);

        $item->update($request->validated());
        $item->refresh()->load('talk.speaker.user');

        return response()->json(['data' => $this->formatItem($item)]);
    }

    public function destroy(Event $event, EventScheduleItem $item): JsonResponse
    {
        abort_if($item->event_id !== $event->id, Response::HTTP_NOT_FOUND);

        $item->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /** @param Collection<int, EventScheduleItem> $items */
    private function formatItems(Collection $items): array
    {
        return $items->map(fn ($item) => $this->formatItem($item))->values()->toArray();
    }

    private function formatItem(EventScheduleItem $item): array
    {
        /** @var Talk|null $talk */
        $talk = $item->talk;

        return [
            'id' => $item->id,
            'talk_id' => $item->talk_id,
            'title' => $item->title ?? $talk?->title,
            'speaker_name' => $item->speaker_name ?? $talk?->speaker?->user?->name,
            'starts_at' => $item->starts_at->toIso8601String(),
            'duration' => $item->duration,
            'room' => $item->room,
            'type' => $item->type,
            'sort_order' => $item->sort_order,
        ];
    }
}
