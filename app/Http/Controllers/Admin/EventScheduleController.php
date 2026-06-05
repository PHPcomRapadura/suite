<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventSite\StoreEventScheduleItemRequest;
use App\Http\Requests\Admin\EventSite\UpdateEventScheduleItemRequest;
use App\Models\Event;
use App\Models\EventScheduleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EventScheduleController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, EventScheduleItem> $items */
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
        $item->update($request->validated());
        $item->refresh()->load('talk.speaker.user');

        return response()->json(['data' => $this->formatItem($item)]);
    }

    public function destroy(Event $event, EventScheduleItem $item): JsonResponse
    {
        $item->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /** @param \Illuminate\Database\Eloquent\Collection<int, EventScheduleItem> $items */
    private function formatItems(\Illuminate\Database\Eloquent\Collection $items): array
    {
        return $items->map(fn ($item) => $this->formatItem($item))->values()->toArray();
    }

    private function formatItem(EventScheduleItem $item): array
    {
        /** @var \App\Models\Talk|null $talk */
        $talk = $item->talk;

        return [
            'id'           => $item->id,
            'talk_id'      => $item->talk_id,
            'title'        => $item->title ?? $talk?->title,
            'speaker_name' => $item->speaker_name ?? $talk?->speaker?->user?->name,
            'starts_at'    => $item->starts_at->toIso8601String(),
            'duration'     => $item->duration,
            'room'         => $item->room,
            'type'         => $item->type,
            'sort_order'   => $item->sort_order,
        ];
    }
}
