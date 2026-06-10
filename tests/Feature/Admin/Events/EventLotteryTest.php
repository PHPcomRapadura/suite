<?php

use App\Models\Event;
use App\Models\EventLotteryWinner;
use App\Models\EventParticipant;
use App\Models\User;

// ─── Acesso ──────────────────────────────────────────────────────────────────

it('guest recebe 401 ao acessar o sorteio', function () {
    $event = Event::factory()->create();
    $this->getJson("/admin/api/events/{$event->id}/lottery")->assertUnauthorized();
});

it('admin visualiza sorteio com winners e stats', function () {
    $event       = Event::factory()->create();
    $participant = EventParticipant::factory()->checkedIn()->for($event)->create();
    EventLotteryWinner::create([
        'event_id'       => $event->id,
        'participant_id' => $participant->id,
        'position'       => 1,
        'drawn_at'       => now(),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/lottery")
        ->assertOk()
        ->assertJsonStructure(['winners', 'stats'])
        ->assertJsonCount(1, 'winners');
});

it('colaborador visualiza sorteio', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->getJson("/admin/api/events/{$event->id}/lottery")
        ->assertOk()
        ->assertJsonStructure(['winners', 'stats']);
});

// ─── Stats ───────────────────────────────────────────────────────────────────

it('stats retornam total_pool, total_drawn e remaining corretamente', function () {
    $event = Event::factory()->create();

    $p1 = EventParticipant::factory()->checkedIn()->for($event)->create();
    $p2 = EventParticipant::factory()->checkedIn()->for($event)->create();
    EventParticipant::factory()->for($event)->create(); // sem check-in

    EventLotteryWinner::create([
        'event_id' => $event->id, 'participant_id' => $p1->id, 'position' => 1, 'drawn_at' => now(),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/lottery")
        ->assertOk()
        ->assertJson(['stats' => [
            'total_pool'  => 2,
            'total_drawn' => 1,
            'remaining'   => 1,
        ]]);
});

it('pool considera somente participantes com check-in', function () {
    $event = Event::factory()->create();
    EventParticipant::factory()->for($event)->create();          // sem check-in
    EventParticipant::factory()->for($event)->create();          // sem check-in
    EventParticipant::factory()->checkedIn()->for($event)->create(); // com check-in

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/lottery")
        ->assertOk()
        ->assertJson(['stats' => ['total_pool' => 1]]);
});

// ─── Draw ─────────────────────────────────────────────────────────────────────

it('admin sorteia participante do pool e recebe winner', function () {
    $event       = Event::factory()->create();
    EventParticipant::factory()->checkedIn()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/lottery/draw")
        ->assertOk()
        ->assertJsonStructure(['winner' => ['position', 'drawn_at', 'participant' => ['id', 'full_name', 'email_obfuscated']]]);
});

it('sorteado não retorna mais no pool após ser sorteado', function () {
    $event = Event::factory()->create();
    $p1    = EventParticipant::factory()->checkedIn()->for($event)->create();
    $p2    = EventParticipant::factory()->checkedIn()->for($event)->create();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/lottery/draw")
        ->assertOk();

    $drawnId = EventLotteryWinner::where('event_id', $event->id)->value('participant_id');
    $remainingId = $drawnId === $p1->id ? $p2->id : $p1->id;

    $res = $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/lottery/draw")
        ->assertOk()
        ->json('winner');

    expect($res['participant']['id'])->toBe($remainingId);
});

it('position incrementa corretamente', function () {
    $event = Event::factory()->create();
    EventParticipant::factory()->checkedIn()->count(3)->for($event)->create();

    $admin = User::factory()->admin()->create();

    for ($i = 1; $i <= 3; $i++) {
        $this->actingAs($admin)
            ->postJson("/admin/api/events/{$event->id}/lottery/draw")
            ->assertOk()
            ->assertJson(['winner' => ['position' => $i]]);
    }
});

it('sortear com pool vazio retorna 422', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/lottery/draw")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('draw');
});

it('colaborador tenta sortear e recebe 403', function () {
    $event = Event::factory()->create();
    EventParticipant::factory()->checkedIn()->for($event)->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->postJson("/admin/api/events/{$event->id}/lottery/draw")
        ->assertForbidden();
});

it('sortear quando não há participantes com check-in retorna 422', function () {
    $event = Event::factory()->create();
    EventParticipant::factory()->for($event)->create(); // sem check-in

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/lottery/draw")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('draw');
});

// ─── Reset ────────────────────────────────────────────────────────────────────

it('admin reseta o sorteio com sucesso', function () {
    $event       = Event::factory()->create();
    $participant = EventParticipant::factory()->checkedIn()->for($event)->create();
    EventLotteryWinner::create([
        'event_id' => $event->id, 'participant_id' => $participant->id, 'position' => 1, 'drawn_at' => now(),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->deleteJson("/admin/api/events/{$event->id}/lottery")
        ->assertOk()
        ->assertJson(['reset' => true]);
});

it('após reset pool volta ao total de check-ins e winners fica vazio', function () {
    $event = Event::factory()->create();
    $p     = EventParticipant::factory()->checkedIn()->for($event)->create();
    EventLotteryWinner::create([
        'event_id' => $event->id, 'participant_id' => $p->id, 'position' => 1, 'drawn_at' => now(),
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->deleteJson("/admin/api/events/{$event->id}/lottery")->assertOk();

    $this->actingAs($admin)
        ->getJson("/admin/api/events/{$event->id}/lottery")
        ->assertOk()
        ->assertJson(['winners' => [], 'stats' => ['total_pool' => 1, 'total_drawn' => 0, 'remaining' => 1]]);
});

it('colaborador tenta resetar e recebe 403', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->deleteJson("/admin/api/events/{$event->id}/lottery")
        ->assertForbidden();
});

it('reset não afeta winners de outros eventos', function () {
    $event1 = Event::factory()->create();
    $event2 = Event::factory()->create();

    $p1 = EventParticipant::factory()->checkedIn()->for($event1)->create();
    $p2 = EventParticipant::factory()->checkedIn()->for($event2)->create();

    EventLotteryWinner::create(['event_id' => $event1->id, 'participant_id' => $p1->id, 'position' => 1, 'drawn_at' => now()]);
    EventLotteryWinner::create(['event_id' => $event2->id, 'participant_id' => $p2->id, 'position' => 1, 'drawn_at' => now()]);

    $this->actingAs(User::factory()->admin()->create())
        ->deleteJson("/admin/api/events/{$event1->id}/lottery")
        ->assertOk();

    expect(EventLotteryWinner::where('event_id', $event2->id)->count())->toBe(1);
});
