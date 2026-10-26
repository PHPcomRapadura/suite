<?php

use App\Models\Event;
use App\Models\EventSiteConfig;
use App\Services\EventCalendarService;

function calendarEvent(array $attributes = []): Event
{
    $event = Event::factory()->create(array_merge([
        'name' => 'Encontro PHP & café',
        'starts_at' => '2026-11-14 14:00:00',
        'ends_at' => '2026-11-14 18:00:00',
        'location' => 'Rua São João, 42 — Fortaleza, CE',
    ], $attributes));
    EventSiteConfig::factory()->published()->create(['event_id' => $event->id]);

    return $event;
}

it('envia links de calendário com os horários do Ceará convertidos para UTC', function () {
    $event = calendarEvent();
    $html = $this->get(route('event.site', $event->slug))->assertOk()->getContent();
    preg_match('/<script id="event-site-data" type="application\/json">(.*?)<\/script>/s', $html, $matches);
    $calendar = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR)['event']['calendar'];
    parse_str(parse_url($calendar['google'], PHP_URL_QUERY), $google);
    parse_str(parse_url($calendar['microsoft'], PHP_URL_QUERY), $outlook);

    expect($google['dates'])->toBe('20261114T170000Z/20261114T210000Z')
        ->and($google['ctz'])->toBe('America/Fortaleza')
        ->and($google['text'])->toBe($event->name)
        ->and($google['location'])->toBe($event->location)
        ->and($google['details'])->toContain(route('event.site', $event->slug))
        ->and($outlook['startdt'])->toBe('2026-11-14T17:00:00Z')
        ->and($outlook['enddt'])->toBe('2026-11-14T21:00:00Z')
        ->and($outlook['subject'])->toBe($event->name)
        ->and($calendar['apple'])->toBe(route('event.calendar', $event->slug));
});

it('exporta um arquivo ICS com título, horários, endereço e link da página', function () {
    $event = calendarEvent();
    $response = $this->get(route('event.calendar', $event->slug))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="'.$event->slug.'.ics"');
    $ics = $response->getContent();

    expect($ics)->toContain("BEGIN:VCALENDAR\r\n", "VERSION:2.0\r\n", "BEGIN:VEVENT\r\n")
        ->toContain('UID:'.$event->id.'@phpcomrapadura.org')
        ->toContain('DTSTART:20261114T170000Z', 'DTEND:20261114T210000Z')
        ->toContain('SUMMARY:Encontro PHP & café', 'LOCATION:Rua São João\\, 42 — Fortaleza\\, CE')
        ->toContain('URL:'.route('event.site', $event->slug))
        ->toEndWith("END:VCALENDAR\r\n");
});

it('escapa texto e dobra linhas em UTF-8 sem permitir injeção de propriedades ICS', function () {
    $event = calendarEvent([
        'name' => str_repeat('Café, colaboração; ', 12),
        'description' => "Conversa \\ PHP\r\nBEGIN:VEVENT\nLOCATION:Outro local",
    ]);
    $ics = app(EventCalendarService::class)->icalendar($event);
    $unfolded = str_replace("\r\n ", '', $ics);

    expect(substr_count($ics, "BEGIN:VEVENT\r\n"))->toBe(1)
        ->and($unfolded)->toContain('Café\\, colaboração\\; ')
        ->toContain('Conversa \\\\ PHP\\nBEGIN:VEVENT\\nLOCATION:Outro local');
    foreach (explode("\r\n", $ics) as $line) {
        expect(strlen($line))->toBeLessThanOrEqual(75)
            ->and(mb_check_encoding($line, 'UTF-8'))->toBeTrue();
    }
});

it('usa uma hora de duração quando o evento não informa término', function () {
    $event = calendarEvent(['starts_at' => '2026-12-31 23:30:00', 'ends_at' => null]);
    $ics = $this->get(route('event.calendar', $event->slug))->assertOk()->getContent();
    expect($ics)->toContain('DTSTART:20270101T023000Z', 'DTEND:20270101T033000Z');
});

it('mantém o período completo em eventos com mais de um dia', function () {
    $event = calendarEvent(['ends_at' => '2026-11-16 18:00:00', 'is_online' => true]);
    $ics = $this->get(route('event.calendar', $event->slug))->assertOk()->getContent();
    expect($ics)->toContain('DTSTART:20261114T170000Z', 'DTEND:20261116T210000Z', 'LOCATION:Online');
});

it('não permite exportar calendários de sites não publicados ou eventos cancelados', function (string $state) {
    $event = calendarEvent();
    if ($state === 'unpublished') {
        $event->site->update(['is_published' => false]);
    } elseif ($state === 'missing-site') {
        $event->site->delete();
    } else {
        $event->update(['status' => 'cancelado']);
        expect(app(EventCalendarService::class)->links($event))->toBeNull();
    }
    $this->get(route('event.calendar', $event->slug))->assertNotFound();
})->with(['unpublished', 'missing-site', 'cancelled']);

it('não exibe calendário sem início e retorna 404 para slug inexistente', function () {
    expect(app(EventCalendarService::class)->links(new Event))->toBeNull();
    $this->get(route('event.calendar', 'evento-inexistente'))->assertNotFound();
});
