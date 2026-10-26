<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventCalendarService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class EventCalendarController extends Controller
{
    public function __invoke(string $slug, EventCalendarService $calendar): Response
    {
        $event = Event::where('slug', $slug)
            ->whereHas('site', fn ($query) => $query->where('is_published', true))
            ->firstOrFail();

        abort_if($event->isCancelled(), 404);

        return response($calendar->icalendar($event))
            ->header('Content-Type', 'text/calendar; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.Str::slug($event->slug).'.ics"');
    }
}
