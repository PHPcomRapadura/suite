<?php

use App\Models\Event;
use App\Models\EventExpense;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ─── Acesso ───────────────────────────────────────────────────────────────────

it('guest recebe 401 na listagem de despesas', function () {
    $event = Event::factory()->create();
    $this->getJson("/admin/api/events/{$event->id}/expenses")->assertUnauthorized();
});

it('colaborador lista despesas de um evento', function () {
    $event = Event::factory()->create();
    EventExpense::factory()->count(3)->for($event)->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->getJson("/admin/api/events/{$event->id}/expenses")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('admin lista despesas de um evento', function () {
    $event = Event::factory()->create();
    EventExpense::factory()->count(5)->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/expenses")
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

// ─── Listagem com filtros ─────────────────────────────────────────────────────

it('filtro por categoria retorna apenas despesas da categoria', function () {
    $event = Event::factory()->create();
    EventExpense::factory()->count(3)->for($event)->create(['category' => 'alimentacao']);
    EventExpense::factory()->count(2)->for($event)->create(['category' => 'transporte']);

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/expenses?category=alimentacao")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filtro por is_paid retorna apenas despesas pagas', function () {
    $event = Event::factory()->create();
    EventExpense::factory()->count(2)->for($event)->paid()->create();
    EventExpense::factory()->count(3)->for($event)->pending()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/expenses?is_paid=true")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filtro por date_from retorna despesas a partir da data', function () {
    $event = Event::factory()->create();
    EventExpense::factory()->for($event)->create(['date' => '2026-01-10']);
    EventExpense::factory()->for($event)->create(['date' => '2026-03-15']);
    EventExpense::factory()->for($event)->create(['date' => '2026-05-20']);

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/expenses?date_from=2026-03-01")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('listagem retorna summary com totais corretos', function () {
    $event = Event::factory()->create();
    EventExpense::factory()->for($event)->paid()->create(['amount' => 500.00]);
    EventExpense::factory()->for($event)->paid()->create(['amount' => 300.00]);
    EventExpense::factory()->for($event)->pending()->create(['amount' => 200.00]);

    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/expenses")
        ->assertOk();

    expect((float) $response->json('summary.total'))->toBe(1000.0);
    expect((float) $response->json('summary.paid'))->toBe(800.0);
    expect((float) $response->json('summary.pending'))->toBe(200.0);
});

it('listagem retorna category_label junto com cada despesa', function () {
    $event   = Event::factory()->create();
    EventExpense::factory()->for($event)->create(['category' => 'transporte']);

    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/expenses")
        ->assertOk();

    expect($response->json('data.0.category_label'))->toBe('Transporte');
});

// ─── Criação ─────────────────────────────────────────────────────────────────

it('admin cria despesa sem comprovante', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/expenses", [
            'category'    => 'alimentacao',
            'description' => 'Coffee break dia 1',
            'amount'      => 350.00,
            'date'        => '2026-06-01',
            'is_paid'     => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.category', 'alimentacao')
        ->assertJsonPath('data.amount', '350.00');

    expect(EventExpense::where('event_id', $event->id)->count())->toBe(1);
});

it('colaborador cria despesa', function () {
    $colaborador = User::factory()->colaborador()->create();
    $event       = Event::factory()->create();

    $this->actingAs($colaborador)
        ->postJson("/admin/api/events/{$event->id}/expenses", [
            'category'    => 'transporte',
            'description' => 'Passagens aéreas',
            'amount'      => 1200.00,
            'date'        => '2026-05-20',
            'is_paid'     => false,
        ])
        ->assertCreated();
});

it('admin cria despesa com comprovante', function () {
    Storage::fake('r2');
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/api/events/{$event->id}/expenses", [
            'category'    => 'equipamentos',
            'description' => 'Projetor alugado',
            'amount'      => 800.00,
            'date'        => '2026-06-01',
            'is_paid'     => true,
            'receipt'     => UploadedFile::fake()->create('nota.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])
        ->assertCreated();

    $expense = EventExpense::where('event_id', $event->id)->first();
    expect($expense->receipt_url)->not->toBeNull();
});

// ─── Validação na criação ─────────────────────────────────────────────────────

it('criar com amount zero retorna 422', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/expenses", [
            'category'    => 'outros',
            'description' => 'Teste',
            'amount'      => 0,
            'date'        => '2026-06-01',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

it('criar com date futura retorna 422', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/expenses", [
            'category'    => 'outros',
            'description' => 'Teste',
            'amount'      => 100,
            'date'        => now()->addDays(5)->format('Y-m-d'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['date']);
});

it('criar com category invalida retorna 422', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/expenses", [
            'category'    => 'invalida',
            'description' => 'Teste',
            'amount'      => 100,
            'date'        => '2026-06-01',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category']);
});

// ─── Edição ───────────────────────────────────────────────────────────────────

it('admin edita despesa', function () {
    $admin   = User::factory()->admin()->create();
    $event   = Event::factory()->create();
    $expense = EventExpense::factory()->for($event)->create(['description' => 'Descrição original', 'amount' => 100]);

    $this->actingAs($admin)
        ->putJson("/admin/api/events/{$event->id}/expenses/{$expense->id}", [
            'category'    => $expense->category,
            'description' => 'Descrição atualizada',
            'amount'      => 250.00,
            'date'        => $expense->date->format('Y-m-d'),
            'is_paid'     => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.description', 'Descrição atualizada')
        ->assertJsonPath('data.amount', '250.00');
});

it('colaborador tenta editar despesa e recebe 403', function () {
    $event   = Event::factory()->create();
    $expense = EventExpense::factory()->for($event)->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->putJson("/admin/api/events/{$event->id}/expenses/{$expense->id}", [
            'category'    => $expense->category,
            'description' => 'Tentativa',
            'amount'      => 999,
            'date'        => $expense->date->format('Y-m-d'),
        ])
        ->assertForbidden();
});

it('admin edita despesa substituindo comprovante', function () {
    Storage::fake('r2');
    $admin   = User::factory()->admin()->create();
    $event   = Event::factory()->create();
    $expense = EventExpense::factory()->for($event)->withReceipt()->create();

    $this->actingAs($admin)
        ->post("/admin/api/events/{$event->id}/expenses/{$expense->id}", [
            '_method'     => 'PUT',
            'category'    => $expense->category,
            'description' => $expense->description,
            'amount'      => $expense->amount,
            'date'        => $expense->date->format('Y-m-d'),
            'is_paid'     => $expense->is_paid ? '1' : '0',
            'receipt'     => UploadedFile::fake()->create('novo.pdf', 50, 'application/pdf'),
        ], ['Accept' => 'application/json'])
        ->assertOk();

    $updated = $expense->fresh();
    expect($updated->receipt_url)->not->toBeNull();
    expect($updated->receipt_url)->not->toBe($expense->receipt_url);
});

// ─── Exclusão ─────────────────────────────────────────────────────────────────

it('admin exclui despesa sem comprovante', function () {
    $admin   = User::factory()->admin()->create();
    $event   = Event::factory()->create();
    $expense = EventExpense::factory()->for($event)->create();

    $this->actingAs($admin)
        ->deleteJson("/admin/api/events/{$event->id}/expenses/{$expense->id}")
        ->assertNoContent();

    expect(EventExpense::find($expense->id))->toBeNull();
});

it('admin exclui despesa com comprovante e remove arquivo do R2', function () {
    Storage::fake('r2');
    $admin   = User::factory()->admin()->create();
    $event   = Event::factory()->create();
    $expense = EventExpense::factory()->for($event)->withReceipt()->create();

    $this->actingAs($admin)
        ->deleteJson("/admin/api/events/{$event->id}/expenses/{$expense->id}")
        ->assertNoContent();

    expect(EventExpense::find($expense->id))->toBeNull();
});

it('colaborador tenta excluir despesa e recebe 403', function () {
    $event   = Event::factory()->create();
    $expense = EventExpense::factory()->for($event)->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->deleteJson("/admin/api/events/{$event->id}/expenses/{$expense->id}")
        ->assertForbidden();
});

// ─── Isolamento ───────────────────────────────────────────────────────────────

it('despesa de outro evento retorna 404', function () {
    $admin   = User::factory()->admin()->create();
    $event1  = Event::factory()->create();
    $event2  = Event::factory()->create();
    $expense = EventExpense::factory()->for($event2)->create();

    $this->actingAs($admin)
        ->getJson("/admin/api/events/{$event1->id}/expenses/{$expense->id}")
        ->assertNotFound();
});

it('usuario nao autenticado recebe 401', function () {
    $event   = Event::factory()->create();
    $expense = EventExpense::factory()->for($event)->create();

    $this->deleteJson("/admin/api/events/{$event->id}/expenses/{$expense->id}")
        ->assertUnauthorized();
});
