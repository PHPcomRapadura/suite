<?php

use App\Models\Event;
use App\Models\EventSiteConfig;
use App\Models\EventSponsor;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ─── Site config — Acesso ─────────────────────────────────────────────────────

it('guest recebe 401 ao acessar site config', function () {
    $event = Event::factory()->create();
    $this->getJson("/admin/api/events/{$event->id}/site")->assertUnauthorized();
});

it('palestrante recebe 403 ao acessar site config', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->palestrante()->create())
        ->getJson("/admin/api/events/{$event->id}/site")
        ->assertForbidden();
});

// ─── Site config — Show ───────────────────────────────────────────────────────

it('retorna null quando site não está configurado', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/site")
        ->assertOk()
        ->assertJson(['data' => null]);
});

// ─── Site config — Store ──────────────────────────────────────────────────────

it('admin cria configuração do site', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/site", [
            'layout'          => 2,
            'primary_color'   => '#ff0000',
            'secondary_color' => '#00ff00',
            'font'            => 'inter',
            'hero_tagline'    => 'O melhor evento PHP',
        ])
        ->assertCreated()
        ->assertJsonPath('data.layout', 2)
        ->assertJsonPath('data.font', 'inter');
});

it('colaborador pode criar configuração do site', function () {
    $colab = User::factory()->colaborador()->create();
    $event = Event::factory()->create();

    $this->actingAs($colab)
        ->postJson("/admin/api/events/{$event->id}/site", [
            'layout'          => 1,
            'primary_color'   => '#025c98',
            'secondary_color' => '#f59e0b',
            'font'            => 'lexend',
        ])
        ->assertCreated();
});

it('retorna 422 ao criar segundo site para o mesmo evento', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    EventSiteConfig::factory()->create(['event_id' => $event->id]);

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/site", [
            'layout'          => 1,
            'primary_color'   => '#025c98',
            'secondary_color' => '#f59e0b',
            'font'            => 'lexend',
        ])
        ->assertUnprocessable();
});

it('retorna 422 com cor primária inválida', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/site", [
            'layout'          => 1,
            'primary_color'   => 'nao-e-hex',
            'secondary_color' => '#f59e0b',
            'font'            => 'lexend',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['primary_color']);
});

it('retorna 422 com layout inválido', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/site", [
            'layout'          => 9,
            'primary_color'   => '#025c98',
            'secondary_color' => '#f59e0b',
            'font'            => 'lexend',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['layout']);
});

it('retorna 422 com URL de ingresso inválida', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/site", [
            'layout'          => 1,
            'primary_color'   => '#025c98',
            'secondary_color' => '#f59e0b',
            'font'            => 'lexend',
            'ticket_url'      => 'nao-e-url',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ticket_url']);
});

// ─── Site config — Update ─────────────────────────────────────────────────────

it('admin atualiza configuração do site', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    EventSiteConfig::factory()->create(['event_id' => $event->id]);

    $this->actingAs($admin)
        ->putJson("/admin/api/events/{$event->id}/site", [
            'layout'          => 3,
            'primary_color'   => '#111111',
            'secondary_color' => '#222222',
            'font'            => 'poppins',
            'code_of_conduct' => '## Código de Conduta',
        ])
        ->assertOk()
        ->assertJsonPath('data.layout', 3)
        ->assertJsonPath('data.font', 'poppins')
        ->assertJsonPath('data.code_of_conduct', '## Código de Conduta');
});

it('FAQ é salvo e retornado corretamente como array', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    EventSiteConfig::factory()->create(['event_id' => $event->id]);

    $faq = [
        ['question' => 'Como me inscrever?', 'answer' => 'Clique no botão de ingressos.'],
        ['question' => 'O evento é gratuito?', 'answer' => 'Sim!'],
    ];

    $this->actingAs($admin)
        ->putJson("/admin/api/events/{$event->id}/site", [
            'layout'          => 1,
            'primary_color'   => '#025c98',
            'secondary_color' => '#f59e0b',
            'font'            => 'lexend',
            'faq'             => $faq,
        ])
        ->assertOk()
        ->assertJsonCount(2, 'data.faq')
        ->assertJsonPath('data.faq.0.question', 'Como me inscrever?');
});

it('retorna 404 ao atualizar site inexistente', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->putJson("/admin/api/events/{$event->id}/site", [
            'layout'          => 1,
            'primary_color'   => '#025c98',
            'secondary_color' => '#f59e0b',
            'font'            => 'lexend',
        ])
        ->assertNotFound();
});

// ─── Site config — Toggle published ──────────────────────────────────────────

it('toggle published ativa a página', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    EventSiteConfig::factory()->create(['event_id' => $event->id, 'is_published' => false]);

    $this->actingAs($admin)
        ->patchJson("/admin/api/events/{$event->id}/site/toggle-published")
        ->assertOk()
        ->assertJsonPath('data.is_published', true);
});

it('toggle published desativa a página', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    EventSiteConfig::factory()->create(['event_id' => $event->id, 'is_published' => true]);

    $this->actingAs($admin)
        ->patchJson("/admin/api/events/{$event->id}/site/toggle-published")
        ->assertOk()
        ->assertJsonPath('data.is_published', false);
});

it('toggle published retorna 404 se site não configurado', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/site/toggle-published")
        ->assertNotFound();
});

// ─── Patrocinadores — Index ───────────────────────────────────────────────────

it('lista patrocinadores de um evento', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    EventSponsor::factory()->count(3)->create(['event_id' => $event->id]);

    $this->actingAs($admin)
        ->getJson("/admin/api/events/{$event->id}/site/sponsors")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

// ─── Patrocinadores — Store ───────────────────────────────────────────────────

it('admin adiciona patrocinador sem logo', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/site/sponsors", [
            'name'  => 'Empresa X',
            'level' => 'rapadura_com_castanha',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Empresa X')
        ->assertJsonPath('data.level', 'rapadura_com_castanha');
});

it('admin adiciona patrocinador com upload de logo', function () {
    Storage::fake('r2');

    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    $logo  = UploadedFile::fake()->image('logo.png');

    $this->actingAs($admin)
        ->post(
            "/admin/api/events/{$event->id}/site/sponsors",
            ['name' => 'Empresa Y', 'level' => 'rapadura_com_coco', 'logo' => $logo],
            ['Accept' => 'application/json']
        )
        ->assertCreated()
        ->assertJsonPath('data.name', 'Empresa Y');

    $sponsor = EventSponsor::where('name', 'Empresa Y')->first();
    expect($sponsor->logo_url)->not->toBeNull();
});

it('retorna 422 com nível inválido ao criar patrocinador', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/site/sponsors", [
            'name'  => 'Empresa Z',
            'level' => 'nivel_invalido',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['level']);
});

// ─── Patrocinadores — Update ──────────────────────────────────────────────────

it('admin atualiza patrocinador', function () {
    $admin   = User::factory()->admin()->create();
    $event   = Event::factory()->create();
    $sponsor = EventSponsor::factory()->create(['event_id' => $event->id, 'name' => 'Antigo Nome']);

    $this->actingAs($admin)
        ->putJson("/admin/api/events/{$event->id}/site/sponsors/{$sponsor->id}", [
            'name'  => 'Novo Nome',
            'level' => 'rapadura_com_coco',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Novo Nome');
});

// ─── Patrocinadores — Destroy ─────────────────────────────────────────────────

it('admin remove patrocinador', function () {
    $admin   = User::factory()->admin()->create();
    $event   = Event::factory()->create();
    $sponsor = EventSponsor::factory()->create(['event_id' => $event->id]);

    $this->actingAs($admin)
        ->deleteJson("/admin/api/events/{$event->id}/site/sponsors/{$sponsor->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('event_sponsors', ['id' => $sponsor->id]);
});

// ─── Patrocinadores — Reorder ─────────────────────────────────────────────────

it('reordenar atualiza sort_order dos patrocinadores', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    $s1    = EventSponsor::factory()->create(['event_id' => $event->id, 'sort_order' => 0]);
    $s2    = EventSponsor::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);

    $this->actingAs($admin)
        ->patchJson("/admin/api/events/{$event->id}/site/sponsors/reorder", [
            'items' => [
                ['id' => $s1->id, 'sort_order' => 1],
                ['id' => $s2->id, 'sort_order' => 0],
            ],
        ])
        ->assertOk();

    $this->assertDatabaseHas('event_sponsors', ['id' => $s1->id, 'sort_order' => 1]);
    $this->assertDatabaseHas('event_sponsors', ['id' => $s2->id, 'sort_order' => 0]);
});

// ─── Página pública ───────────────────────────────────────────────────────────

it('retorna 404 para slug inexistente', function () {
    $this->get('/slug-que-nao-existe')->assertNotFound();
});

it('retorna 404 para evento com site não publicado', function () {
    $event = Event::factory()->create(['slug' => 'phpcomrapadura-2026']);
    EventSiteConfig::factory()->create(['event_id' => $event->id, 'is_published' => false]);

    $this->get('/phpcomrapadura-2026')->assertNotFound();
});

it('retorna 200 para evento publicado', function () {
    $event = Event::factory()->create(['slug' => 'phpcomrapadura-2026-pub']);
    EventSiteConfig::factory()->published()->create(['event_id' => $event->id]);

    $this->withoutVite()->get('/phpcomrapadura-2026-pub')->assertOk();
});

it('página pública inclui dados do evento, site e patrocinadores', function () {
    $event = Event::factory()->create(['slug' => 'phpcomrapadura-test', 'name' => 'PHP com Rapadura Test']);
    EventSiteConfig::factory()->published()->create(['event_id' => $event->id]);
    EventSponsor::factory()->create(['event_id' => $event->id, 'level' => 'rapadura_com_castanha', 'name' => 'Castanha Corp']);
    EventSponsor::factory()->create(['event_id' => $event->id, 'level' => 'rapadura_tradicional', 'name' => 'Trad Corp']);

    $response = $this->withoutVite()->get('/phpcomrapadura-test')->assertOk();

    $response->assertSee('PHP com Rapadura Test');
});

it('retorna 404 para evento sem site configurado', function () {
    $event = Event::factory()->create(['slug' => 'sem-site-2026']);

    $this->get('/sem-site-2026')->assertNotFound();
});
