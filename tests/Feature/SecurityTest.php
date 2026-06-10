<?php

use App\Models\Event;
use App\Models\EventScheduleItem;
use App\Models\EventSiteConfig;
use App\Models\EventSponsor;
use App\Models\Speaker;
use App\Models\Talk;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Testing\File;

// ─── XSS: escape no JSON embutido na página pública do evento ──────────────────

it('escapa </script> nos dados do evento para impedir XSS armazenado', function () {
    $event = Event::factory()->create([
        'status' => 'publicado',
        'description' => 'inofensivo</script><script>alert(1)</script>',
    ]);
    EventSiteConfig::factory()->published()->create(['event_id' => $event->id]);

    $response = $this->get("/{$event->slug}")->assertOk();

    // O payload não pode aparecer como tags <script> reais no HTML.
    expect($response->getContent())->not->toContain('</script><script>alert(1)</script>');
    // E a sequência de fechamento deve estar escapada (< ou <\/).
    expect($response->getContent())->toContain('<');
});

// ─── IDOR: recursos filhos devem pertencer ao evento do path ──────────────────

it('retorna 404 ao editar sponsor de outro evento', function () {
    $admin = User::factory()->admin()->create();
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create();
    $sponsor = EventSponsor::factory()->create(['event_id' => $eventB->id, 'name' => 'Original']);

    $this->actingAs($admin)
        ->putJson("/admin/api/events/{$eventA->id}/site/sponsors/{$sponsor->id}", [
            'name' => 'Hackeado',
            'level' => 'rapadura_tradicional',
        ])
        ->assertNotFound();

    expect($sponsor->fresh()->name)->toBe('Original');
});

it('retorna 404 ao excluir sponsor de outro evento', function () {
    $admin = User::factory()->admin()->create();
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create();
    $sponsor = EventSponsor::factory()->create(['event_id' => $eventB->id]);

    $this->actingAs($admin)
        ->deleteJson("/admin/api/events/{$eventA->id}/site/sponsors/{$sponsor->id}")
        ->assertNotFound();

    expect(EventSponsor::find($sponsor->id))->not->toBeNull();
});

it('retorna 404 ao editar item de grade de outro evento', function () {
    $admin = User::factory()->admin()->create();
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create();
    $item = EventScheduleItem::factory()->create(['event_id' => $eventB->id]);

    $this->actingAs($admin)
        ->putJson("/admin/api/events/{$eventA->id}/site/schedule/{$item->id}", [
            'title' => 'Invadido',
            'starts_at' => now()->toIso8601String(),
            'duration' => 50,
            'type' => 'palestra',
        ])
        ->assertNotFound();
});

it('retorna 404 ao excluir item de grade de outro evento', function () {
    $admin = User::factory()->admin()->create();
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create();
    $item = EventScheduleItem::factory()->create(['event_id' => $eventB->id]);

    $this->actingAs($admin)
        ->deleteJson("/admin/api/events/{$eventA->id}/site/schedule/{$item->id}")
        ->assertNotFound();

    expect(EventScheduleItem::find($item->id))->not->toBeNull();
});

// ─── Lockout: regra de "último admin ativo" (defesa em profundidade) ──────────

it('identifica o único admin ativo do sistema', function () {
    $service = app(UserService::class);
    $admin = User::factory()->admin()->create();

    expect($service->isLastActiveAdmin($admin))->toBeTrue();

    // Com um segundo admin ativo, deixa de ser o último.
    User::factory()->admin()->create();
    expect($service->isLastActiveAdmin($admin))->toBeFalse();
});

it('não considera admin inativo como o último admin ativo', function () {
    $service = app(UserService::class);
    $inactive = User::factory()->admin()->create(['is_active' => false]);

    expect($service->isLastActiveAdmin($inactive))->toBeFalse();
});

// ─── Upload: logo não pode ser SVG (vetor de XSS) ─────────────────────────────

it('rejeita logo em formato SVG', function () {
    $admin = User::factory()->admin()->create();

    $svg = File::create('logo.svg', 1);

    $this->actingAs($admin)
        ->withHeaders(['Accept' => 'application/json'])
        ->postJson('/admin/api/events', [
            'name' => 'Evento Teste',
            'starts_at' => now()->addMonth()->toDateString(),
            'logo' => $svg,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('logo');
});

// ─── Isolamento de palestrantes (regressão) ───────────────────────────────────

it('palestrante não vê palestras de outro palestrante', function () {
    $event = Event::factory()->create();

    $userA = User::factory()->palestrante()->create();
    $speakerA = Speaker::factory()->create(['user_id' => $userA->id]);
    Talk::factory()->create(['event_id' => $event->id, 'speaker_id' => $speakerA->id]);

    $userB = User::factory()->palestrante()->create();
    Speaker::factory()->create(['user_id' => $userB->id]);

    $response = $this->actingAs($userB)
        ->getJson('/cfp/api/my-talks')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(0);
});
