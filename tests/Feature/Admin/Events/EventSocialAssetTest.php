<?php

use App\Models\Event;
use App\Models\EventSocialAsset;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('admin gera arte de story para o evento', function () {
    Storage::fake('r2');

    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create([
        'name' => 'PHP com Rapadura',
        'location' => 'Fortaleza, CE',
        'description' => 'Evento de PHP e comunidade',
        'cover_image' => null,
        'logo' => null,
    ]);

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
            'format' => 'story',
        ])
        ->assertOk()
        ->assertJsonPath('data.format', 'story')
        ->assertJsonPath('data.asset_url', fn (string $url) => str_contains($url, '.png'));

    Storage::disk('r2')->assertExists("events/{$event->id}/social/story.png");

    $this->assertDatabaseHas('event_social_assets', [
        'event_id' => $event->id,
        'format' => 'story',
    ]);
});

it('gerar novamente atualiza o registro existente em vez de duplicar', function () {
    Storage::fake('r2');

    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
        'format' => 'story',
    ])->assertOk();

    $this->actingAs($admin)->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
        'format' => 'story',
    ])->assertOk();

    expect(EventSocialAsset::where('event_id', $event->id)->where('format', 'story')->count())->toBe(1);
});

it('retorna as artes já geradas ao carregar a tela', function () {
    Storage::fake('r2');

    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
        'format' => 'story',
    ])->assertOk();

    $this->actingAs($admin)
        ->getJson("/admin/api/events/{$event->id}/social-assets")
        ->assertOk()
        ->assertJsonPath('data.assets.story.format', 'story')
        ->assertJsonPath('data.assets.post', null);
});

it('admin gera arte de post para o evento', function () {
    Storage::fake('r2');

    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
            'format' => 'post',
        ])
        ->assertOk()
        ->assertJsonPath('data.format', 'post');

    Storage::disk('r2')->assertExists("events/{$event->id}/social/post.png");
});

it('retorna 422 para formato inválido', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
            'format' => 'invalid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['format']);
});

it('retorna 404 quando o evento não existe', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/admin/api/events/999999/social-assets/generate', [
            'format' => 'story',
        ])
        ->assertNotFound();
});

it('gera a arte com fallback quando o evento não tem capa nem logo', function () {
    Storage::fake('r2');

    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create([
        'cover_image' => null,
        'logo' => null,
        'description' => null,
    ]);

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
            'format' => 'story',
        ])
        ->assertOk();

    Storage::disk('r2')->assertExists("events/{$event->id}/social/story.png");
});

it('gera a arte com nome contendo caracteres especiais sem quebrar', function () {
    Storage::fake('r2');

    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create([
        'name' => 'Dev & Ops <Rapadura> "Tour"',
    ]);

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
            'format' => 'post',
        ])
        ->assertOk();

    Storage::disk('r2')->assertExists("events/{$event->id}/social/post.png");
});
