<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EventSitePublicController extends Controller
{
    public function show(string $slug): View|Response
    {
        $event = Event::where('slug', $slug)->with(['site', 'sponsors', 'schedule.talk.speaker.user'])->first();

        /** @var \App\Models\EventSiteConfig|null $eventSite */
        $eventSite = $event?->site;

        if (! $event || ! $eventSite || ! $eventSite->is_published) {
            abort(404);
        }

        $levelOrder = ['rapadura_com_castanha', 'rapadura_com_coco', 'rapadura_tradicional'];
        $grouped    = $event->sponsors->groupBy('level');

        $sponsorsByLevel = [];
        foreach ($levelOrder as $level) {
            if ($grouped->has($level)) {
                $sponsorsByLevel[$level] = $grouped[$level]->values()->toArray();
            }
        }

        $scheduleByDay = [];
        foreach ($event->schedule as $item) {
            /** @var \App\Models\EventScheduleItem $item */
            /** @var \App\Models\Talk|null $talk */
            $talk = $item->talk;
            $day  = $item->starts_at->toDateString();
            $scheduleByDay[$day][] = [
                'id'           => $item->id,
                'title'        => $item->title ?? $talk?->title,
                'speaker_name' => $item->speaker_name ?? $talk?->speaker?->user?->name,
                'starts_at'    => $item->starts_at->toIso8601String(),
                'duration'     => $item->duration,
                'room'         => $item->room,
                'type'         => $item->type,
            ];
        }
        ksort($scheduleByDay);

        $eventData = json_encode([
            'event' => [
                'id'          => $event->id,
                'name'        => $event->name,
                'slug'        => $event->slug,
                'edition'     => $event->edition,
                'description' => $event->description,
                'starts_at'   => $event->starts_at,
                'ends_at'     => $event->ends_at,
                'location'    => $event->location,
                'is_online'   => $event->is_online,
                'cover_image'        => $event->cover_image,
                'logo'               => $event->logo,
                'is_accepting_talks' => $event->is_accepting_talks,
            ],
            'site'     => $eventSite,
            'sponsors' => $sponsorsByLevel,
            'schedule' => $scheduleByDay,
        ]);

        return view('event-site', ['eventData' => $eventData]);
    }
}
