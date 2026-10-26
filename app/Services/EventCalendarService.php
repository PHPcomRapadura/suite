<?php

namespace App\Services;

use App\Models\Event;
use Carbon\CarbonImmutable;

class EventCalendarService
{
    private const TIMEZONE = 'America/Fortaleza';

    public function links(Event $event): ?array
    {
        if ($event->getAttribute('starts_at') === null || $event->isCancelled()) {
            return null;
        }

        [$start, $end] = $this->period($event);
        $description = $this->description($event);
        $location = $event->is_online ? 'Online' : (string) $event->location;

        return [
            'google' => 'https://calendar.google.com/calendar/render?'.http_build_query([
                'action' => 'TEMPLATE',
                'text' => $event->name,
                'dates' => $start->format('Ymd\THis\Z').'/'.$end->format('Ymd\THis\Z'),
                'ctz' => self::TIMEZONE,
                'details' => $description,
                'location' => $location,
            ], '', '&', PHP_QUERY_RFC3986),
            'apple' => route('event.calendar', ['slug' => $event->slug]),
            'microsoft' => 'https://outlook.live.com/calendar/0/deeplink/compose?'.http_build_query([
                'path' => '/calendar/action/compose',
                'rru' => 'addevent',
                'subject' => $event->name,
                'startdt' => $start->toIso8601ZuluString(),
                'enddt' => $end->toIso8601ZuluString(),
                'body' => $description,
                'location' => $location,
                'allday' => 'false',
            ], '', '&', PHP_QUERY_RFC3986),
        ];
    }

    public function icalendar(Event $event): string
    {
        [$start, $end] = $this->period($event);
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//PHP com Rapadura//Eventos//PT-BR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$event->id.'@phpcomrapadura.org',
            'DTSTAMP:'.CarbonImmutable::now('UTC')->format('Ymd\THis\Z'),
            'DTSTART:'.$start->format('Ymd\THis\Z'),
            'DTEND:'.$end->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escape($event->name),
            'DESCRIPTION:'.$this->escape($this->description($event)),
            'LOCATION:'.$this->escape($event->is_online ? 'Online' : (string) $event->location),
            'URL:'.route('event.site', ['slug' => $event->slug]),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map($this->fold(...), $lines))."\r\n";
    }

    private function period(Event $event): array
    {
        // O cadastro armazena o horário local em campos UTC, sem conversão de fuso.
        // Reinterpreta os mesmos números como horário do Ceará antes de exportar em UTC.
        $start = CarbonImmutable::parse($event->starts_at->format('Y-m-d H:i:s'), self::TIMEZONE)->utc();
        $end = $event->ends_at
            ? CarbonImmutable::parse($event->ends_at->format('Y-m-d H:i:s'), self::TIMEZONE)->utc()
            : $start->addHour();

        return [$start, $end->greaterThan($start) ? $end : $start->addHour()];
    }

    private function description(Event $event): string
    {
        return trim(strip_tags((string) $event->description))."\n\n".route('event.site', ['slug' => $event->slug]);
    }

    private function escape(string $text): string
    {
        return str_replace(
            ['\\', "\r\n", "\r", "\n", ';', ','],
            ['\\\\', '\\n', '\\n', '\\n', '\\;', '\\,'],
            $text,
        );
    }

    private function fold(string $line): string
    {
        $parts = [];
        $limit = 75;
        while (strlen($line) > $limit) {
            $part = mb_strcut($line, 0, $limit, 'UTF-8');
            $parts[] = $part;
            $line = substr($line, strlen($part));
            $limit = 74; // A continuação começa com um espaço.
        }
        $parts[] = $line;

        return implode("\r\n ", $parts);
    }
}
