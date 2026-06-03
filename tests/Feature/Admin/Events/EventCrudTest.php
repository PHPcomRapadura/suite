<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ─── Acesso ───────────────────────────────────────────────────────────────────

it('guest recebe 401 na listagem de eventos', function () {
    $this->getJson('/admin/api/events')->assertUnauthorized();
});

it('colaborador acessa a listagem de eventos', function () {
    $this->actingAs(User::factory()->colaborador()->create())
        ->getJson('/admin/api/events')
        ->assertOk();
});

it('colaborador recebe 403 ao publicar evento', function () {
    $event = Event::factory()->rascunho()->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->patchJson("/admin/api/events/{$event->id}/status", ['status' => 'publicado'])
        ->assertForbidden();
});

it('colaborador recebe 403 ao acionar toggle-talks', function () {
    $event = Event::factory()->publicado()->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->patchJson("/admin/api/events/{$event->id}/toggle-talks")
        ->assertForbidden();
});

// ─── Listagem ─────────────────────────────────────────────────────────────────

it('retorna 9 eventos por página', function () {
    Event::factory()->count(12)->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson('/admin/api/events')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(9);
    expect($response->json('meta.per_page'))->toBe(9);
});

it('filtro search encontra evento pelo nome', function () {
    $admin = User::factory()->admin()->create();
    Event::factory()->create(['name' => 'Encontro de Devs 2026', 'slug' => 'encontro-de-devs-2026']);
    Event::factory()->create(['name' => 'Outro Evento', 'slug' => 'outro-evento']);

    $response = $this->actingAs($admin)
        ->getJson('/admin/api/events?search=Encontro')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.name'))->toBe('Encontro de Devs 2026');
});

it('filtro status retorna apenas eventos do status informado', function () {
    $admin = User::factory()->admin()->create();
    Event::factory()->publicado()->count(2)->create();
    Event::factory()->rascunho()->count(3)->create();

    $response = $this->actingAs($admin)
        ->getJson('/admin/api/events?status=publicado')
        ->assertOk();

    collect($response->json('data'))
        ->each(fn ($e) => expect($e['status'])->toBe('publicado'));
});

it('filtro year retorna apenas eventos do ano informado', function () {
    $admin = User::factory()->admin()->create();
    Event::factory()->create(['starts_at' => '2026-06-15 09:00:00', 'slug' => 'evento-2026']);
    Event::factory()->create(['starts_at' => '2025-03-10 09:00:00', 'slug' => 'evento-2025']);

    $response = $this->actingAs($admin)
        ->getJson('/admin/api/events?year=2026')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

// ─── Criação ──────────────────────────────────────────────────────────────────

it('admin cria evento com status rascunho', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/admin/api/events', [
            'name' => 'PHP com Rapadura 2026',
            'starts_at' => '2026-08-20 09:00:00',
            'is_online' => false,
        ])
        ->assertCreated()
        ->assertJsonPath('status', 'rascunho')
        ->assertJsonPath('created_by', $admin->id);

    $this->assertDatabaseHas('events', ['name' => 'PHP com Rapadura 2026']);
});

it('slug é gerado automaticamente a partir do nome', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/events', [
            'name' => 'Encontro de Devs 2026',
            'starts_at' => '2026-06-15 09:00:00',
            'is_online' => false,
        ])
        ->assertCreated()
        ->assertJsonPath('slug', 'encontro-de-devs-2026');
});

it('slug informado manualmente é persistido', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/events', [
            'name' => 'Evento Teste',
            'slug' => 'meu-slug-customizado',
            'starts_at' => '2026-06-15 09:00:00',
            'is_online' => false,
        ])
        ->assertCreated()
        ->assertJsonPath('slug', 'meu-slug-customizado');
});

it('retorna 422 ao criar com slug duplicado', function () {
    Event::factory()->create(['slug' => 'slug-existente']);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/events', [
            'name' => 'Novo Evento',
            'slug' => 'slug-existente',
            'starts_at' => '2026-06-15 09:00:00',
            'is_online' => false,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});

it('retorna 422 quando ends_at é anterior a starts_at', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/events', [
            'name' => 'Evento Inválido',
            'starts_at' => '2026-06-15 09:00:00',
            'ends_at' => '2026-06-14 09:00:00',
            'is_online' => false,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ends_at']);
});

it('colaborador pode criar evento', function () {
    $this->actingAs(User::factory()->colaborador()->create())
        ->postJson('/admin/api/events', [
            'name' => 'Evento do Colaborador',
            'starts_at' => '2026-09-10 09:00:00',
            'is_online' => true,
        ])
        ->assertCreated();
});

// ─── Edição ───────────────────────────────────────────────────────────────────

it('admin atualiza dados do evento', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->rascunho()->create(['name' => 'Nome Antigo']);

    $this->actingAs($admin)
        ->putJson("/admin/api/events/{$event->id}", [
            'name' => 'Nome Atualizado',
            'starts_at' => $event->starts_at->toDateTimeString(),
            'is_online' => false,
        ])
        ->assertOk()
        ->assertJsonPath('name', 'Nome Atualizado');
});

it('slug é regenerado quando nome muda e slug não é enviado', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create(['name' => 'Nome Original', 'slug' => 'nome-original']);

    $this->actingAs($admin)
        ->putJson("/admin/api/events/{$event->id}", [
            'name' => 'Nome Completamente Diferente',
            'starts_at' => $event->starts_at->toDateTimeString(),
            'is_online' => false,
        ])
        ->assertOk()
        ->assertJsonPath('slug', 'nome-completamente-diferente');
});

it('não gera erro de slug duplicado ao manter o slug do próprio evento', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create(['slug' => 'meu-evento']);

    $this->actingAs($admin)
        ->putJson("/admin/api/events/{$event->id}", [
            'name' => $event->name,
            'slug' => 'meu-evento',
            'starts_at' => $event->starts_at->toDateTimeString(),
            'is_online' => false,
        ])
        ->assertOk();
});

it('não permite editar evento cancelado', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->cancelado()->create();

    $this->actingAs($admin)
        ->putJson("/admin/api/events/{$event->id}", [
            'name' => 'Tentativa',
            'starts_at' => $event->starts_at->toDateTimeString(),
            'is_online' => false,
        ])
        ->assertUnprocessable();
});

it('não permite editar evento encerrado', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->encerrado()->create();

    $this->actingAs($admin)
        ->putJson("/admin/api/events/{$event->id}", [
            'name' => 'Tentativa',
            'starts_at' => $event->starts_at->toDateTimeString(),
            'is_online' => false,
        ])
        ->assertUnprocessable();
});

// ─── Transições de status ─────────────────────────────────────────────────────

it('admin publica evento rascunho', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->rascunho()->create();

    $this->actingAs($admin)
        ->patchJson("/admin/api/events/{$event->id}/status", ['status' => 'publicado'])
        ->assertOk()
        ->assertJsonPath('status', 'publicado');

    expect($event->fresh()->status)->toBe('publicado');
});

it('colaborador não pode publicar evento', function () {
    $event = Event::factory()->rascunho()->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->patchJson("/admin/api/events/{$event->id}/status", ['status' => 'publicado'])
        ->assertForbidden();
});

it('admin e colaborador podem encerrar evento publicado', function () {
    $event = Event::factory()->publicado()->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->patchJson("/admin/api/events/{$event->id}/status", ['status' => 'encerrado'])
        ->assertOk()
        ->assertJsonPath('status', 'encerrado');
});

it('admin cancela evento publicado', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->publicado()->create();

    $this->actingAs($admin)
        ->patchJson("/admin/api/events/{$event->id}/status", ['status' => 'cancelado'])
        ->assertOk()
        ->assertJsonPath('status', 'cancelado');
});

it('colaborador não pode cancelar evento', function () {
    $event = Event::factory()->publicado()->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->patchJson("/admin/api/events/{$event->id}/status", ['status' => 'cancelado'])
        ->assertForbidden();
});

it('transição inválida retorna 422', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->encerrado()->create();

    $this->actingAs($admin)
        ->patchJson("/admin/api/events/{$event->id}/status", ['status' => 'publicado'])
        ->assertUnprocessable();
});

// ─── Toggle de palestras ──────────────────────────────────────────────────────

it('admin ativa is_accepting_talks em evento publicado', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->publicado()->create(['is_accepting_talks' => false]);

    $this->actingAs($admin)
        ->patchJson("/admin/api/events/{$event->id}/toggle-talks")
        ->assertOk()
        ->assertJsonPath('is_accepting_talks', true);
});

it('toggle-talks em evento não publicado retorna 422', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->rascunho()->create();

    $this->actingAs($admin)
        ->patchJson("/admin/api/events/{$event->id}/toggle-talks")
        ->assertUnprocessable();
});

it('colaborador não pode acionar toggle-talks', function () {
    $event = Event::factory()->publicado()->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->patchJson("/admin/api/events/{$event->id}/toggle-talks")
        ->assertForbidden();
});

// ─── Imagens (R2) ─────────────────────────────────────────────────────────────

it('cria evento com cover_image e persiste a URL no banco', function () {
    Storage::fake('r2');
    $admin = User::factory()->admin()->create();
    $file = UploadedFile::fake()->image('capa.jpg', 1280, 720)->size(500);

    $response = $this->actingAs($admin)
        ->post('/admin/api/events', [
            'name' => 'Evento com Capa',
            'starts_at' => '2026-06-15 09:00:00',
            'is_online' => false,
            'cover_image' => $file,
        ])
        ->assertCreated();

    expect($response->json('cover_image'))->not->toBeNull();
    $this->assertDatabaseHas('events', ['name' => 'Evento com Capa']);
    Storage::disk('r2')->assertExists("events/{$response->json('id')}/cover.jpg");
});

it('cria evento com logo e persiste a URL no banco', function () {
    Storage::fake('r2');
    $admin = User::factory()->admin()->create();
    $file = UploadedFile::fake()->image('logo.png', 400, 400)->size(200);

    $response = $this->actingAs($admin)
        ->post('/admin/api/events', [
            'name' => 'Evento com Logo',
            'starts_at' => '2026-07-10 09:00:00',
            'is_online' => false,
            'logo' => $file,
        ])
        ->assertCreated();

    expect($response->json('logo'))->not->toBeNull();
    Storage::disk('r2')->assertExists("events/{$response->json('id')}/logo.png");
});

it('cria evento sem imagens com cover_image e logo nulos', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/events', [
            'name' => 'Evento Sem Imagens',
            'starts_at' => '2026-08-01 09:00:00',
            'is_online' => false,
        ])
        ->assertCreated()
        ->assertJsonPath('cover_image', null)
        ->assertJsonPath('logo', null);
});

it('atualizar cover_image deleta a anterior do R2 e persiste nova URL', function () {
    Storage::fake('r2');
    $admin = User::factory()->admin()->create();
    $oldFile = UploadedFile::fake()->image('antiga.jpg')->size(100);
    $newFile = UploadedFile::fake()->image('nova.png')->size(100);

    $event = $this->actingAs($admin)
        ->post('/admin/api/events', [
            'name' => 'Evento Troca Capa',
            'starts_at' => '2026-09-01 09:00:00',
            'is_online' => false,
            'cover_image' => $oldFile,
        ])
        ->assertCreated()
        ->json();

    $oldPath = "events/{$event['id']}/cover.jpg";
    Storage::disk('r2')->assertExists($oldPath);

    $this->actingAs($admin)
        ->post("/admin/api/events/{$event['id']}", [
            '_method' => 'PUT',
            'name' => $event['name'],
            'starts_at' => $event['starts_at'],
            'is_online' => false,
            'cover_image' => $newFile,
        ])
        ->assertOk();

    Storage::disk('r2')->assertMissing($oldPath);
    Storage::disk('r2')->assertExists("events/{$event['id']}/cover.png");
});

it('retorna 422 ao enviar cover_image com formato inválido', function () {
    $admin = User::factory()->admin()->create();
    $file = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

    $this->actingAs($admin)
        ->withHeaders(['Accept' => 'application/json'])
        ->post('/admin/api/events', [
            'name' => 'Evento Inválido',
            'starts_at' => '2026-06-15 09:00:00',
            'is_online' => false,
            'cover_image' => $file,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cover_image']);
});

it('retorna 422 ao enviar cover_image acima de 5 MB', function () {
    $admin = User::factory()->admin()->create();
    $file = UploadedFile::fake()->image('grande.jpg')->size(5121);

    $this->actingAs($admin)
        ->withHeaders(['Accept' => 'application/json'])
        ->post('/admin/api/events', [
            'name' => 'Evento Grande',
            'starts_at' => '2026-06-15 09:00:00',
            'is_online' => false,
            'cover_image' => $file,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cover_image']);
});
