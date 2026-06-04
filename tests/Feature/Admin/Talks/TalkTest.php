<?php

use App\Models\Event;
use App\Models\Talk;
use App\Models\User;

// ─── Listagem ─────────────────────────────────────────────────────────────────

it('lista palestras do evento com dados do palestrante', function () {
    $event = Event::factory()->create();
    Talk::factory()->count(3)->for($event)->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/talks")
        ->assertOk();

    expect($response->json('data'))->toHaveCount(3);
    expect($response->json('data.0'))->toHaveKey('speaker');
});

it('filtro por status retorna apenas palestras do status informado', function () {
    $event = Event::factory()->create();
    Talk::factory()->submetida()->count(2)->for($event)->create();
    Talk::factory()->aprovada()->count(1)->for($event)->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/talks?status=submetida")
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2);
    collect($response->json('data'))
        ->each(fn ($t) => expect($t['status'])->toBe('submetida'));
});

it('retorna 404 ao acessar palestra de outro evento', function () {
    $event       = Event::factory()->create();
    $outroEvento = Event::factory()->create();
    $talk        = Talk::factory()->for($outroEvento)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/talks/{$talk->id}")
        ->assertNotFound();
});

// ─── Transições de status ─────────────────────────────────────────────────────

it('coloca palestra em análise', function () {
    $event = Event::factory()->create();
    $talk  = Talk::factory()->submetida()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/talks/{$talk->id}/status", [
            'status' => 'em_analise',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'em_analise');
});

it('aprova palestra em análise', function () {
    $event = Event::factory()->create();
    $talk  = Talk::factory()->emAnalise()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/talks/{$talk->id}/status", [
            'status' => 'aprovada',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'aprovada');
});

it('rejeita palestra com feedback', function () {
    $event = Event::factory()->create();
    $talk  = Talk::factory()->emAnalise()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/talks/{$talk->id}/status", [
            'status'   => 'rejeitada',
            'feedback' => 'O tema não está alinhado com o público deste evento.',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'rejeitada');
});

it('rejeitar sem feedback retorna 422', function () {
    $event = Event::factory()->create();
    $talk  = Talk::factory()->emAnalise()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/talks/{$talk->id}/status", [
            'status' => 'rejeitada',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['feedback']);
});

it('reabre palestra rejeitada para análise', function () {
    $event = Event::factory()->create();
    $talk  = Talk::factory()->rejeitada()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/talks/{$talk->id}/status", [
            'status' => 'em_analise',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'em_analise');
});

it('transição inválida retorna 422', function () {
    $event = Event::factory()->create();
    $talk  = Talk::factory()->cancelada()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/talks/{$talk->id}/status", [
            'status' => 'aprovada',
        ])
        ->assertUnprocessable();
});

it('retorna 404 ao avaliar palestra de outro evento', function () {
    $event       = Event::factory()->create();
    $outroEvento = Event::factory()->create();
    $talk        = Talk::factory()->for($outroEvento)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/talks/{$talk->id}/status", [
            'status' => 'em_analise',
        ])
        ->assertNotFound();
});
