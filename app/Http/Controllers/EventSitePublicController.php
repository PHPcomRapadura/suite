<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventScheduleItem;
use App\Models\EventSiteConfig;
use App\Models\Talk;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EventSitePublicController extends Controller
{
    public function show(string $slug): View|Response
    {
        $event = Event::where('slug', $slug)->with(['site', 'sponsors', 'schedule.talk.speaker.user', 'talks.speaker.user'])->first();

        /** @var EventSiteConfig|null $eventSite */
        $eventSite = $event?->site;

        if (! $event || ! $eventSite || ! $eventSite->is_published) {
            abort(404);
        }

        $levelOrder = ['rapadura_com_castanha', 'rapadura_com_coco', 'rapadura_tradicional'];
        $grouped = $event->sponsors->groupBy('level');

        $sponsorsByLevel = [];
        foreach ($levelOrder as $level) {
            if ($grouped->has($level)) {
                $sponsorsByLevel[$level] = $grouped[$level]->values()->toArray();
            }
        }

        $scheduleByDay = [];
        foreach ($event->schedule as $item) {
            /** @var EventScheduleItem $item */
            /** @var Talk|null $talk */
            $talk = $item->talk;
            $day = $item->starts_at->toDateString();
            $scheduleByDay[$day][] = [
                'id' => $item->id,
                'title' => $item->title ?? $talk?->title,
                'speaker_name' => $item->speaker_name ?? $talk?->speaker?->user?->name,
                'starts_at' => $item->starts_at->toIso8601String(),
                'duration' => $item->duration,
                'room' => $item->room,
                'type' => $item->type,
            ];
        }
        ksort($scheduleByDay);

        $seenSpeakers = [];
        $speakers = [];
        foreach ($event->talks as $talk) {
            /** @var Talk $talk */
            if ($talk->status !== 'aprovada') {
                continue;
            }
            $speaker = $talk->speaker;
            if (! $speaker || in_array($speaker->id, $seenSpeakers)) {
                continue;
            }
            $seenSpeakers[] = $speaker->id;
            $speakers[] = [
                'id' => $speaker->id,
                'name' => $speaker->user?->name,
                'avatar_url' => $speaker->avatar_url,
                'city' => $speaker->city,
                'state' => $speaker->state,
                'twitter' => $speaker->twitter,
                'github' => $speaker->github,
                'linkedin' => $speaker->linkedin,
                'website' => $speaker->website,
            ];
        }

        $eventData = json_encode(
            [
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'slug' => $event->slug,
                    'edition' => $event->edition,
                    'description' => $event->description,
                    'starts_at' => $event->starts_at,
                    'ends_at' => $event->ends_at,
                    'location' => $event->location,
                    'is_online' => $event->is_online,
                    'cover_image' => $event->cover_image,
                    'logo' => $event->logo,
                    'is_accepting_talks' => $event->is_accepting_talks,
                ],
                'site' => $eventSite,
                'sponsors' => $sponsorsByLevel,
                'schedule' => $scheduleByDay,
                'speakers' => $speakers,
            ],
            // Escapa <, >, &, ', " para impedir quebra do bloco <script> (XSS armazenado)
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
        );

        return view('event-site', ['eventData' => $eventData]);
    }
}
