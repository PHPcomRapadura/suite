<?php

use App\Models\Event;
use App\Models\EventCfp;
use App\Models\User;

// ─── Acesso ───────────────────────────────────────────────────────────────────

it('guest recebe 401 ao acessar CFP', function () {
    $event = Event::factory()->create();
    $this->getJson("/admin/api/events/{$event->id}/cfp")->assertUnauthorized();
});

it('colaborador acessa CFP', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->colaborador()->create())
        ->getJson("/admin/api/events/{$event->id}/cfp")
        ->assertOk();
});

// ─── Show ─────────────────────────────────────────────────────────────────────

it('retorna null quando CFP não está configurado', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/cfp")
        ->assertOk()
        ->assertJson(['data' => null]);
});

it('retorna CFP com contagem de palestras por status', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    EventCfp::factory()->create(['event_id' => $event->id]);

    $this->actingAs($admin)
        ->getJson("/admin/api/events/{$event->id}/cfp")
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'opens_at', 'closes_at', 'status', 'talks_count']]);
});

// ─── Store ────────────────────────────────────────────────────────────────────

it('admin cria CFP para um evento', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/cfp", [
            'opens_at'  => '2026-06-15 09:00:00',
            'closes_at' => '2026-07-31 23:59:59',
        ])
        ->assertCreated()
        ->assertJsonPath('event_id', $event->id);
});

it('retorna 422 ao criar segundo CFP para o mesmo evento', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    EventCfp::factory()->create(['event_id' => $event->id]);

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/cfp", [
            'opens_at'  => '2026-08-01 09:00:00',
            'closes_at' => '2026-08-31 23:59:59',
        ])
        ->assertUnprocessable();
});

it('retorna 422 quando closes_at é anterior a opens_at', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/cfp", [
            'opens_at'  => '2026-07-31 09:00:00',
            'closes_at' => '2026-06-15 09:00:00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['closes_at']);
});

// ─── Update ───────────────────────────────────────────────────────────────────

it('admin edita CFP existente', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    $cfp   = EventCfp::factory()->create(['event_id' => $event->id]);

    $this->actingAs($admin)
        ->putJson("/admin/api/events/{$event->id}/cfp", [
            'opens_at'      => $cfp->opens_at->toDateTimeString(),
            'closes_at'     => $cfp->closes_at->toDateTimeString(),
            'speaker_guide' => '## Guia para palestrantes',
        ])
        ->assertOk()
        ->assertJsonPath('speaker_guide', '## Guia para palestrantes');
});

it('retorna 404 ao editar CFP inexistente', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->putJson("/admin/api/events/{$event->id}/cfp", [
            'opens_at'  => '2026-06-15 09:00:00',
            'closes_at' => '2026-07-31 23:59:59',
        ])
        ->assertNotFound();
});
