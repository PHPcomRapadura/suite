<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('admin gera arte de story para o evento', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create([
        'name' => 'PHP com Rapadura',
        'location' => 'Fortaleza, CE',
        'description' => 'Evento de PHP e comunidade',
        'cover_image' => 'https://example.com/cover.jpg',
        'logo' => 'https://example.com/logo.png',
    ]);

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/social-assets/generate", [
            'format' => 'story',
        ])
        ->assertOk()
        ->assertJsonPath('data.format', 'story')
        ->assertJsonPath('data.asset_url', fn (string $url) => str_contains($url, '.svg'));

    Storage::disk('public')->assertExists("events/{$event->id}/social/story.svg");
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
