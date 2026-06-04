<?php

use App\Models\Event;
use App\Models\EventCfp;
use App\Models\Speaker;
use App\Models\Talk;
use App\Models\User;

// ─── GET /cfp/api/events/{event} ──────────────────────────────────────────────

it('retorna dados do evento com CFP para a página de submissão', function () {
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);

    $this->getJson("/cfp/api/events/{$event->id}")
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'name', 'cfp' => ['status', 'opens_at', 'closes_at']]]);
});

it('retorna 404 para evento sem CFP', function () {
    $event = Event::factory()->create();
    $this->getJson("/cfp/api/events/{$event->id}")->assertNotFound();
});

// ─── GET /cfp/api/speaker/profile ─────────────────────────────────────────────

it('guest recebe 401 ao acessar perfil do speaker', function () {
    $this->getJson('/cfp/api/speaker/profile')->assertUnauthorized();
});

it('admin recebe 403 ao acessar perfil de speaker', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/cfp/api/speaker/profile')->assertForbidden();
});

it('retorna null quando palestrante não tem perfil ainda', function () {
    $this->actingAs(User::factory()->palestrante()->create())
        ->getJson('/cfp/api/speaker/profile')
        ->assertOk()
        ->assertJsonPath('data', null);
});

it('retorna perfil do palestrante', function () {
    $user = User::factory()->palestrante()->create();
    Speaker::factory()->create(['user_id' => $user->id, 'company' => 'Acme']);

    $this->actingAs($user)
        ->getJson('/cfp/api/speaker/profile')
        ->assertOk()
        ->assertJsonPath('data.company', 'Acme');
});

// ─── PATCH /cfp/api/speaker/profile ───────────────────────────────────────────

it('palestrante atualiza perfil com sucesso', function () {
    $user = User::factory()->palestrante()->create();

    $this->actingAs($user)
        ->patchJson('/cfp/api/speaker/profile', [
            'bio'     => 'Desenvolvedor PHP há 10 anos com foco em arquitetura.',
            'company' => 'Nova Empresa',
        ])
        ->assertOk()
        ->assertJsonPath('data.company', 'Nova Empresa');

    $this->assertDatabaseHas('speakers', ['user_id' => $user->id, 'company' => 'Nova Empresa']);
});

it('retorna 422 quando bio está vazia no update do perfil', function () {
    $user = User::factory()->palestrante()->create();

    $this->actingAs($user)
        ->patchJson('/cfp/api/speaker/profile', ['bio' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['bio']);
});

// ─── PATCH /cfp/api/account ───────────────────────────────────────────────────

it('palestrante atualiza nome e email', function () {
    $user = User::factory()->palestrante()->create();

    $this->actingAs($user)
        ->patchJson('/cfp/api/account', [
            'name'             => 'Novo Nome',
            'email'            => $user->email,
            'current_password' => 'password',
        ])
        ->assertOk();

    $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Novo Nome']);
});

it('retorna 422 quando senha atual está errada', function () {
    $user = User::factory()->palestrante()->create();

    $this->actingAs($user)
        ->patchJson('/cfp/api/account', [
            'name'             => $user->name,
            'email'            => $user->email,
            'current_password' => 'senha-errada',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);
});

it('retorna 422 quando e-mail já está em uso', function () {
    User::factory()->create(['email' => 'outro@exemplo.com']);
    $user = User::factory()->palestrante()->create();

    $this->actingAs($user)
        ->patchJson('/cfp/api/account', [
            'name'             => $user->name,
            'email'            => 'outro@exemplo.com',
            'current_password' => 'password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

// ─── POST /cfp/api/events/{event}/talks ───────────────────────────────────────

it('guest recebe 401 ao submeter palestra', function () {
    $event = Event::factory()->create();
    $this->postJson("/cfp/api/events/{$event->id}/talks", [])->assertUnauthorized();
});

it('admin recebe 403 ao submeter palestra', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/cfp/api/events/{$event->id}/talks", [])
        ->assertForbidden();
});

it('palestrante submete palestra com sucesso', function () {
    $user  = User::factory()->palestrante()->create();
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);

    $this->actingAs($user)
        ->postJson("/cfp/api/events/{$event->id}/talks", [
            'title'    => 'Arquitetura Hexagonal em PHP',
            'abstract' => str_repeat('a', 100),
            'duration' => '50',
            'level'    => 'intermediario',
        ])
        ->assertCreated()
        ->assertJsonPath('status', 'submetida');

    $this->assertDatabaseHas('talks', ['title' => 'Arquitetura Hexagonal em PHP']);
});

it('submeter palestra cria perfil de speaker automaticamente', function () {
    $user  = User::factory()->palestrante()->create();
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);

    $this->actingAs($user)
        ->postJson("/cfp/api/events/{$event->id}/talks", [
            'title'    => 'Minha Palestra',
            'abstract' => str_repeat('a', 100),
            'duration' => '25',
            'level'    => 'iniciante',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('speakers', ['user_id' => $user->id]);
});

it('retorna 422 quando CFP não está aberto', function () {
    $user  = User::factory()->palestrante()->create();
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->encerrado()->create(['event_id' => $event->id]);

    $this->actingAs($user)
        ->postJson("/cfp/api/events/{$event->id}/talks", [
            'title'    => 'Palestra',
            'abstract' => str_repeat('a', 100),
            'duration' => '25',
            'level'    => 'iniciante',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'O período de submissão não está aberto.');
});

it('retorna 422 quando limite de propostas foi atingido', function () {
    $user    = User::factory()->palestrante()->create();
    $speaker = Speaker::factory()->create(['user_id' => $user->id]);
    $event   = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id, 'max_talks_per_speaker' => 1]);
    Talk::factory()->submetida()->create(['event_id' => $event->id, 'speaker_id' => $speaker->id]);

    $this->actingAs($user)
        ->postJson("/cfp/api/events/{$event->id}/talks", [
            'title'    => 'Segunda Palestra',
            'abstract' => str_repeat('a', 100),
            'duration' => '25',
            'level'    => 'iniciante',
        ])
        ->assertUnprocessable();
});

it('proposta cancelada não conta no limite', function () {
    $user    = User::factory()->palestrante()->create();
    $speaker = Speaker::factory()->create(['user_id' => $user->id]);
    $event   = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id, 'max_talks_per_speaker' => 1]);
    Talk::factory()->cancelada()->create(['event_id' => $event->id, 'speaker_id' => $speaker->id]);

    $this->actingAs($user)
        ->postJson("/cfp/api/events/{$event->id}/talks", [
            'title'    => 'Nova Palestra',
            'abstract' => str_repeat('a', 100),
            'duration' => '25',
            'level'    => 'iniciante',
        ])
        ->assertCreated();
});

it('retorna 422 quando resumo tem menos de 100 caracteres', function () {
    $user  = User::factory()->palestrante()->create();
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);

    $this->actingAs($user)
        ->postJson("/cfp/api/events/{$event->id}/talks", [
            'title'    => 'Palestra',
            'abstract' => 'Curto demais.',
            'duration' => '25',
            'level'    => 'iniciante',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['abstract']);
});

// ─── PUT /cfp/api/talks/{talk} ────────────────────────────────────────────────

it('palestrante edita própria proposta submetida', function () {
    $user    = User::factory()->palestrante()->create();
    $speaker = Speaker::factory()->create(['user_id' => $user->id]);
    $event   = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);
    $talk    = Talk::factory()->submetida()->create(['event_id' => $event->id, 'speaker_id' => $speaker->id]);

    $this->actingAs($user)
        ->putJson("/cfp/api/talks/{$talk->id}", [
            'title'    => 'Título Atualizado',
            'abstract' => str_repeat('a', 100),
            'duration' => '25',
            'level'    => 'iniciante',
        ])
        ->assertOk()
        ->assertJsonPath('title', 'Título Atualizado');
});

it('palestrante não pode editar proposta de outro', function () {
    $user  = User::factory()->palestrante()->create();
    $other = Speaker::factory()->create();
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);
    $talk  = Talk::factory()->submetida()->create(['event_id' => $event->id, 'speaker_id' => $other->id]);

    $this->actingAs($user)
        ->putJson("/cfp/api/talks/{$talk->id}", [
            'title'    => 'Tentativa',
            'abstract' => str_repeat('a', 100),
            'duration' => '25',
            'level'    => 'iniciante',
        ])
        ->assertForbidden();
});

it('não permite editar proposta aprovada', function () {
    $user    = User::factory()->palestrante()->create();
    $speaker = Speaker::factory()->create(['user_id' => $user->id]);
    $event   = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);
    $talk    = Talk::factory()->aprovada()->create(['event_id' => $event->id, 'speaker_id' => $speaker->id]);

    $this->actingAs($user)
        ->putJson("/cfp/api/talks/{$talk->id}", [
            'title'    => 'Tentativa',
            'abstract' => str_repeat('a', 100),
            'duration' => '25',
            'level'    => 'iniciante',
        ])
        ->assertUnprocessable();
});

// ─── GET /cfp/api/events/{event}/my-talks ─────────────────────────────────────

it('retorna propostas do palestrante para o evento', function () {
    $user    = User::factory()->palestrante()->create();
    $speaker = Speaker::factory()->create(['user_id' => $user->id]);
    $event   = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);
    Talk::factory()->count(2)->submetida()->create(['event_id' => $event->id, 'speaker_id' => $speaker->id]);

    $response = $this->actingAs($user)
        ->getJson("/cfp/api/events/{$event->id}/my-talks")
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2);
});

it('não retorna propostas de outros palestrantes', function () {
    $user  = User::factory()->palestrante()->create();
    $other = Speaker::factory()->create();
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);
    Talk::factory()->submetida()->create(['event_id' => $event->id, 'speaker_id' => $other->id]);

    $this->actingAs($user)
        ->getJson("/cfp/api/events/{$event->id}/my-talks")
        ->assertOk()
        ->assertJsonPath('data', []);
});

// ─── GET /cfp/api/events/{event}/my-talks/count ───────────────────────────────

it('retorna contagem de propostas excluindo canceladas', function () {
    $user    = User::factory()->palestrante()->create();
    $speaker = Speaker::factory()->create(['user_id' => $user->id]);
    $event   = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);
    Talk::factory()->count(2)->submetida()->create(['event_id' => $event->id, 'speaker_id' => $speaker->id]);
    Talk::factory()->cancelada()->create(['event_id' => $event->id, 'speaker_id' => $speaker->id]);

    $this->actingAs($user)
        ->getJson("/cfp/api/events/{$event->id}/my-talks/count")
        ->assertOk()
        ->assertJsonPath('count', 2);
});
