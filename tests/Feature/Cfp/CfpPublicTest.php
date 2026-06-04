<?php

use App\Models\Event;
use App\Models\EventCfp;
use App\Models\User;

// ─── GET /cfp/api/events ──────────────────────────────────────────────────────

it('endpoint público não requer autenticação', function () {
    $this->getJson('/cfp/api/events')->assertOk();
});

it('retorna lista vazia quando não há CFPs abertos', function () {
    $this->getJson('/cfp/api/events')
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('retorna eventos com CFP aberto', function () {
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);

    $response = $this->getJson('/cfp/api/events')->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.id'))->toBe($event->id);
    expect($response->json('data.0.cfp.status'))->toBe('aberto');
});

it('retorna eventos com CFP aguardando', function () {
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->create([
        'event_id'  => $event->id,
        'opens_at'  => now()->addDays(10),
        'closes_at' => now()->addDays(40),
    ]);

    $response = $this->getJson('/cfp/api/events')->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.cfp.status'))->toBe('aguardando');
});

it('não retorna eventos com CFP encerrado', function () {
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->encerrado()->create(['event_id' => $event->id]);

    $this->getJson('/cfp/api/events')
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('não retorna eventos cancelados mesmo com CFP aberto', function () {
    $event = Event::factory()->cancelado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);

    $this->getJson('/cfp/api/events')
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('não retorna eventos encerrados mesmo com CFP aberto', function () {
    $event = Event::factory()->encerrado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);

    $this->getJson('/cfp/api/events')
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('retorna os campos esperados no card do evento', function () {
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);

    $data = $this->getJson('/cfp/api/events')->assertOk()->json('data.0');

    expect($data)->toHaveKeys([
        'id', 'name', 'slug', 'edition',
        'starts_at', 'ends_at', 'location', 'is_online', 'cover_image',
        'cfp',
    ]);
    expect($data['cfp'])->toHaveKeys([
        'opens_at', 'closes_at', 'speaker_guide', 'max_talks_per_speaker', 'status',
    ]);
});

// ─── POST /cfp/login ──────────────────────────────────────────────────────────

it('palestrante realiza login com sucesso', function () {
    $user = User::factory()->palestrante()->create();

    $this->postJson('/cfp/login', [
        'email'    => $user->email,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('user.role', 'palestrante');
});

it('admin também pode fazer login pelo endpoint cfp', function () {
    $admin = User::factory()->admin()->create();

    $this->postJson('/cfp/login', [
        'email'    => $admin->email,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('user.role', 'admin');
});

it('credenciais inválidas retornam 401', function () {
    User::factory()->palestrante()->create(['email' => 'test@example.com']);

    $this->postJson('/cfp/login', [
        'email'    => 'test@example.com',
        'password' => 'senha-errada',
    ])->assertUnauthorized();
});

it('usuário inativo recebe 403 ao tentar login', function () {
    $user = User::factory()->palestrante()->create(['is_active' => false]);

    $this->postJson('/cfp/login', [
        'email'    => $user->email,
        'password' => 'password',
    ])->assertForbidden();
});

// ─── GET /cfp/api/me ──────────────────────────────────────────────────────────

it('me retorna null quando não autenticado', function () {
    $this->getJson('/cfp/api/me')
        ->assertOk()
        ->assertJsonPath('user', null);
});

it('me retorna usuário autenticado', function () {
    $user = User::factory()->palestrante()->create();

    $this->actingAs($user)
        ->getJson('/cfp/api/me')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.role', 'palestrante');
});
