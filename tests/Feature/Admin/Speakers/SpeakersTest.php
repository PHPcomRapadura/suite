<?php

use App\Models\Speaker;
use App\Models\Talk;
use App\Models\User;

// ─── Acesso ──────────────────────────────────────────────────────────────────

it('guest recebe 401 ao listar palestrantes', function () {
    $this->getJson('/admin/api/speakers')->assertUnauthorized();
});

it('guest recebe 401 ao ver detalhes de palestrante', function () {
    $speaker = Speaker::factory()->create();
    $this->getJson("/admin/api/speakers/{$speaker->id}")->assertUnauthorized();
});

it('palestrante recebe 403 ao listar palestrantes', function () {
    $speaker = Speaker::factory()->create();
    $this->actingAs($speaker->user)
        ->getJson('/admin/api/speakers')
        ->assertForbidden();
});

it('admin visualiza lista de palestrantes', function () {
    Speaker::factory()->count(3)->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->getJson('/admin/api/speakers')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta'])
        ->assertJsonCount(3, 'data');
});

it('colaborador visualiza lista de palestrantes', function () {
    Speaker::factory()->count(2)->create();
    $colaborador = User::factory()->colaborador()->create();

    $this->actingAs($colaborador)
        ->getJson('/admin/api/speakers')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

// ─── Listagem / formato ───────────────────────────────────────────────────────

it('retorna campos corretos no resumo do palestrante', function () {
    $speaker = Speaker::factory()->create([
        'company' => 'Acme Corp',
        'city'    => 'Fortaleza',
        'state'   => 'CE',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/admin/api/speakers')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'email', 'avatar_url', 'company', 'city', 'state', 'phone_number', 'is_active', 'talks_count', 'talks_approved']]]);
});

it('retorna metadados de paginação', function () {
    Speaker::factory()->count(3)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/admin/api/speakers')
        ->assertOk()
        ->assertJsonStructure(['meta' => ['current_page', 'last_page', 'per_page', 'total']]);
});

it('contabiliza talks_count e talks_approved corretamente', function () {
    $speaker = Speaker::factory()->create();
    Talk::factory()->for($speaker)->create(['status' => 'aprovada']);
    Talk::factory()->for($speaker)->create(['status' => 'submetida']);
    Talk::factory()->for($speaker)->create(['status' => 'rejeitada']);

    $res = $this->actingAs(User::factory()->admin()->create())
        ->getJson('/admin/api/speakers')
        ->assertOk()
        ->json('data.0');

    expect($res['talks_count'])->toBe(3)
        ->and($res['talks_approved'])->toBe(1);
});

// ─── Filtros ─────────────────────────────────────────────────────────────────

it('filtra palestrantes por nome', function () {
    $alice = Speaker::factory()->create();
    $alice->user->update(['name' => 'Alice Silva']);

    $bob = Speaker::factory()->create();
    $bob->user->update(['name' => 'Bob Marley']);

    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/admin/api/speakers?search=Alice')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alice Silva');
});

it('filtra palestrantes por e-mail', function () {
    $speaker = Speaker::factory()->create();
    $speaker->user->update(['email' => 'unico@teste.com']);
    Speaker::factory()->count(2)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/admin/api/speakers?search=unico@teste.com')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filtra palestrantes por cidade', function () {
    Speaker::factory()->create(['city' => 'Fortaleza']);
    Speaker::factory()->create(['city' => 'Recife']);

    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/admin/api/speakers?city=Fortaleza')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.city', 'Fortaleza');
});

it('filtra palestrantes por estado', function () {
    Speaker::factory()->create(['state' => 'CE']);
    Speaker::factory()->create(['state' => 'SP']);
    Speaker::factory()->create(['state' => 'CE']);

    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/admin/api/speakers?state=CE')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('busca sem resultados retorna lista vazia', function () {
    Speaker::factory()->count(3)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/admin/api/speakers?search=xyzNaoExiste')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

// ─── Detalhe ─────────────────────────────────────────────────────────────────

it('admin visualiza detalhes completos do palestrante', function () {
    $speaker = Speaker::factory()->create([
        'bio'     => 'Desenvolvedor PHP',
        'website' => 'https://exemplo.com',
        'twitter' => 'alice',
        'github'  => 'alice-dev',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/speakers/{$speaker->id}")
        ->assertOk()
        ->assertJsonStructure(['id', 'name', 'email', 'bio', 'website', 'twitter', 'github', 'linkedin', 'last_login_at', 'talks']);
});

it('detalhe inclui lista de palestras do palestrante', function () {
    $speaker = Speaker::factory()->create();
    Talk::factory()->for($speaker)->count(2)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/speakers/{$speaker->id}")
        ->assertOk()
        ->assertJsonCount(2, 'talks');
});

it('detalhe de palestrante inexistente retorna 404', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/admin/api/speakers/99999')
        ->assertNotFound();
});
