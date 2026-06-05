<?php

use App\Models\Event;
use App\Models\EventScheduleItem;
use App\Models\User;

// ─── Acesso ───────────────────────────────────────────────────────────────────

it('guest recebe 401 ao acessar grade', function () {
    $event = Event::factory()->create();
    $this->getJson("/admin/api/events/{$event->id}/site/schedule")->assertUnauthorized();
});

it('palestrante recebe 403 ao acessar grade', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->palestrante()->create())
        ->getJson("/admin/api/events/{$event->id}/site/schedule")
        ->assertForbidden();
});

// ─── Index ────────────────────────────────────────────────────────────────────

it('retorna lista vazia quando não há itens', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/site/schedule")
        ->assertOk()
        ->assertJson(['data' => []]);
});

it('retorna itens da grade de programação', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    EventScheduleItem::factory()->create([
        'event_id' => $event->id,
        'title'    => 'Abertura do Evento',
        'type'     => 'abertura',
    ]);

    $this->actingAs($admin)
        ->getJson("/admin/api/events/{$event->id}/site/schedule")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Abertura do Evento')
        ->assertJsonPath('data.0.type', 'abertura');
});

// ─── Store ────────────────────────────────────────────────────────────────────

it('admin cria item na grade', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/site/schedule", [
            'title'      => 'Palestra sobre Laravel',
            'starts_at'  => '2025-10-15 09:00:00',
            'type'       => 'palestra',
            'duration'   => 50,
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Palestra sobre Laravel')
        ->assertJsonPath('data.type', 'palestra')
        ->assertJsonPath('data.duration', 50);

    $this->assertDatabaseHas('event_schedule_items', [
        'event_id' => $event->id,
        'title'    => 'Palestra sobre Laravel',
    ]);
});

it('colaborador pode criar item na grade', function () {
    $colab = User::factory()->colaborador()->create();
    $event = Event::factory()->create();

    $this->actingAs($colab)
        ->postJson("/admin/api/events/{$event->id}/site/schedule", [
            'title'     => 'Intervalo',
            'starts_at' => '2025-10-15 12:00:00',
            'type'      => 'intervalo',
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'intervalo');
});

it('falha ao criar item sem starts_at', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/site/schedule", [
            'title' => 'Palestra',
            'type'  => 'palestra',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['starts_at']);
});

it('falha ao criar item com tipo inválido', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/site/schedule", [
            'title'     => 'Palestra',
            'starts_at' => '2025-10-15 09:00:00',
            'type'      => 'tipo_invalido',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

// ─── Update ───────────────────────────────────────────────────────────────────

it('admin edita item da grade', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    $item  = EventScheduleItem::factory()->create(['event_id' => $event->id, 'type' => 'palestra']);

    $this->actingAs($admin)
        ->putJson("/admin/api/events/{$event->id}/site/schedule/{$item->id}", [
            'title'     => 'Título Atualizado',
            'starts_at' => '2025-10-15 14:00:00',
            'type'      => 'encerramento',
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Título Atualizado')
        ->assertJsonPath('data.type', 'encerramento');
});

// ─── Destroy ──────────────────────────────────────────────────────────────────

it('admin remove item da grade', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    $item  = EventScheduleItem::factory()->create(['event_id' => $event->id]);

    $this->actingAs($admin)
        ->deleteJson("/admin/api/events/{$event->id}/site/schedule/{$item->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('event_schedule_items', ['id' => $item->id]);
});

it('guest recebe 401 ao tentar remover item', function () {
    $event = Event::factory()->create();
    $item  = EventScheduleItem::factory()->create(['event_id' => $event->id]);

    $this->deleteJson("/admin/api/events/{$event->id}/site/schedule/{$item->id}")
        ->assertUnauthorized();
});
