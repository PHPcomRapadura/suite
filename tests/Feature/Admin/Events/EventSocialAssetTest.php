<?php

use App\Models\Event;
use App\Models\EventSocialAsset;
use App\Models\Talk;
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
        ->assertJsonPath('data.type', 'announcement')
        ->assertJsonPath('data.format', 'story')
        ->assertJsonPath('data.asset_url', fn (string $url) => str_contains($url, '.png'));

    Storage::disk('r2')->assertExists("events/{$event->id}/social/announcement-story.png");

    $this->assertDatabaseHas('event_social_assets', [
        'event_id' => $event->id,
        'type' => 'announcement',
        'format' => 'story',
        'subject_key' => 'event',
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

    $response = $this->actingAs($admin)
        ->getJson("/admin/api/events/{$event->id}/social-assets")
        ->assertOk();

    $assets = $response->json('data.assets');

    expect($assets)->toHaveCount(1)
        ->and($assets[0]['type'])->toBe('announcement')
        ->and($assets[0]['format'])->toBe('story');
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

    Storage::disk('r2')->assertExists("events/{$event->id}/social/announcement-post.png");
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

it('retorna 422 para tipo de arte inválido', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
            'format' => 'story',
            'type' => 'invalido',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
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

    Storage::disk('r2')->assertExists("events/{$event->id}/social/announcement-story.png");
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

    Storage::disk('r2')->assertExists("events/{$event->id}/social/announcement-post.png");
});

it('admin gera arte de palestrante para uma talk aprovada', function () {
    Storage::fake('r2');

    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    $talk = Talk::factory()->aprovada()->for($event)->create(['title' => 'TDD na prática']);

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
            'format' => 'story',
            'type' => 'speaker',
            'talk_id' => $talk->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.type', 'speaker')
        ->assertJsonPath('data.talk_id', $talk->id);

    Storage::disk('r2')->assertExists("events/{$event->id}/social/speaker-story-talk{$talk->id}.png");

    $this->assertDatabaseHas('event_social_assets', [
        'event_id' => $event->id,
        'type' => 'speaker',
        'talk_id' => $talk->id,
        'subject_key' => "talk:{$talk->id}",
    ]);
});

it('exige talk_id ao gerar arte de palestrante', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
            'format' => 'story',
            'type' => 'speaker',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['talk_id']);
});

it('rejeita gerar arte de palestrante para talk não aprovada', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    $talk = Talk::factory()->submetida()->for($event)->create();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
            'format' => 'story',
            'type' => 'speaker',
            'talk_id' => $talk->id,
        ])
        ->assertUnprocessable();
});

it('rejeita gerar arte de palestrante para talk de outro evento', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    $outroEvento = Event::factory()->create();
    $talk = Talk::factory()->aprovada()->for($outroEvento)->create();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
            'format' => 'story',
            'type' => 'speaker',
            'talk_id' => $talk->id,
        ])
        ->assertNotFound();
});

it('gera arte de palestrante sem avatar usando fallback de iniciais', function () {
    Storage::fake('r2');

    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    $talk = Talk::factory()->aprovada()->for($event)->create();
    expect($talk->speaker->avatar_url)->toBeNull();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
            'format' => 'post',
            'type' => 'speaker',
            'talk_id' => $talk->id,
        ])
        ->assertOk();

    Storage::disk('r2')->assertExists("events/{$event->id}/social/speaker-post-talk{$talk->id}.png");
});

it('gerar arte de palestrante novamente para a mesma talk não duplica registro', function () {
    Storage::fake('r2');

    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    $talk = Talk::factory()->aprovada()->for($event)->create();

    $payload = ['format' => 'story', 'type' => 'speaker', 'talk_id' => $talk->id];

    $this->actingAs($admin)->postJson("/admin/api/events/{$event->id}/social-assets/generate", $payload)->assertOk();
    $this->actingAs($admin)->postJson("/admin/api/events/{$event->id}/social-assets/generate", $payload)->assertOk();

    expect(EventSocialAsset::where('event_id', $event->id)->where('type', 'speaker')->count())->toBe(1);
});

it('baixa a arte gerada forçando download em vez de abrir a imagem', function () {
    Storage::fake('r2');

    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create(['slug' => 'evento-teste']);

    $generateResponse = $this->actingAs($admin)->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
        'format' => 'story',
    ])->assertOk();

    $assetId = $generateResponse->json('data.id');

    $response = $this->actingAs($admin)->get("/admin/api/events/{$event->id}/social-assets/{$assetId}/download");

    $response->assertOk();
    $response->assertHeader('content-disposition', 'attachment; filename=evento-teste-announcement-story.png');
});

it('retorna 404 ao baixar arte que pertence a outro evento', function () {
    Storage::fake('r2');

    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    $outroEvento = Event::factory()->create();

    $generateResponse = $this->actingAs($admin)->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
        'format' => 'story',
    ])->assertOk();

    $assetId = $generateResponse->json('data.id');

    $this->actingAs($admin)
        ->get("/admin/api/events/{$outroEvento->id}/social-assets/{$assetId}/download")
        ->assertNotFound();
});
