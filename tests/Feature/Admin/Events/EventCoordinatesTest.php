<?php

use App\Models\Event;
use App\Models\EventSiteConfig;
use App\Models\User;

it('cria evento sem exigir coordenadas', function () {
    $response = $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/events', ['name' => 'Evento sem coordenadas', 'starts_at' => '2026-11-14 14:00:00'])
        ->assertCreated()
        ->assertJsonPath('latitude', null)
        ->assertJsonPath('longitude', null);
    expect(Event::findOrFail($response->json('id'))->latitude)->toBeNull();
});

it('salva e retorna coordenadas numéricas inclusive zero e limites válidos', function (float $latitude, float $longitude) {
    $response = $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/events', [
            'name' => 'Evento com coordenadas',
            'starts_at' => '2026-11-14 14:00:00',
            'latitude' => $latitude,
            'longitude' => $longitude,
        ])->assertCreated();
    $event = Event::findOrFail($response->json('id'));
    expect($event->latitude)->toBe($latitude)
        ->and($event->longitude)->toBe($longitude)
        ->and((float) $response->json('latitude'))->toBe($latitude)
        ->and((float) $response->json('longitude'))->toBe($longitude);
})->with([
    'negativas' => [-3.7654321, -38.5432109],
    'zero' => [0.0, 0.0],
    'limites positivos' => [90.0, 180.0],
    'limites negativos' => [-90.0, -180.0],
]);

it('rejeita par incompleto e coordenadas inválidas na criação e edição', function (array $coordinates, string $field) {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->admin()->create());
    $data = array_merge(['name' => $event->name, 'starts_at' => $event->starts_at->format('Y-m-d H:i:s')], $coordinates);
    $this->postJson('/admin/api/events', $data)->assertUnprocessable()->assertJsonValidationErrors($field);
    $this->putJson("/admin/api/events/{$event->id}", $data)->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'só latitude' => [['latitude' => -3.7], 'longitude'],
    'só longitude' => [['longitude' => -38.5], 'latitude'],
    'zero sem longitude' => [['latitude' => 0], 'longitude'],
    'latitude acima do limite' => [['latitude' => 91, 'longitude' => 10], 'latitude'],
    'latitude abaixo do limite' => [['latitude' => -91, 'longitude' => 10], 'latitude'],
    'longitude acima do limite' => [['latitude' => 10, 'longitude' => 181], 'longitude'],
    'longitude abaixo do limite' => [['latitude' => 10, 'longitude' => -181], 'longitude'],
    'texto' => [['latitude' => 'Fortaleza', 'longitude' => -38.5], 'latitude'],
]);

it('atualiza e remove coordenadas pelo mesmo envio multipart usado pelo formulário', function () {
    $event = Event::factory()->create(['latitude' => -3.7, 'longitude' => -38.5]);
    $this->actingAs(User::factory()->admin()->create());
    $base = ['_method' => 'PUT', 'name' => $event->name, 'starts_at' => $event->starts_at->format('Y-m-d H:i:s')];
    $this->post("/admin/api/events/{$event->id}", array_merge($base, [
        'latitude' => '-3.7654321', 'longitude' => '-38.5432109',
    ]), ['Accept' => 'application/json'])->assertOk();
    expect($event->fresh()->latitude)->toBe(-3.7654321);

    $this->post("/admin/api/events/{$event->id}", array_merge($base, [
        'latitude' => '', 'longitude' => '',
    ]), ['Accept' => 'application/json'])->assertOk();
    expect($event->fresh()->latitude)->toBeNull()->and($event->fresh()->longitude)->toBeNull();
});

it('mantém coordenadas ao editar outros campos sem enviá-las', function () {
    $event = Event::factory()->create(['latitude' => -3.7, 'longitude' => -38.5]);
    $this->actingAs(User::factory()->admin()->create())
        ->putJson("/admin/api/events/{$event->id}", [
            'name' => 'Nome atualizado', 'starts_at' => $event->starts_at->format('Y-m-d H:i:s'),
        ])->assertOk();
    expect($event->fresh()->latitude)->toBe(-3.7)->and($event->fresh()->longitude)->toBe(-38.5);
});

it('inclui as coordenadas nos dados públicos dos três layouts', function (int $layout) {
    $event = Event::factory()->create(['latitude' => -3.7654321, 'longitude' => -38.5432109]);
    EventSiteConfig::factory()->published()->create(['event_id' => $event->id, 'layout' => $layout]);
    $html = $this->get(route('event.site', $event->slug))->assertOk()->getContent();
    preg_match('/<script id="event-site-data" type="application\/json">(.*?)<\/script>/s', $html, $matches);
    $data = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    expect($data['event']['latitude'])->toBe(-3.7654321)
        ->and($data['event']['longitude'])->toBe(-38.5432109);
})->with([1, 2, 3]);
