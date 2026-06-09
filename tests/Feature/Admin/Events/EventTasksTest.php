<?php

use App\Models\Event;
use App\Models\EventTask;
use App\Models\User;

// ─── Acesso ───────────────────────────────────────────────────────────────────

it('guest recebe 401 no board', function () {
    $event = Event::factory()->create();
    $this->getJson("/admin/api/events/{$event->id}/tasks")->assertUnauthorized();
});

it('admin visualiza board agrupado por status', function () {
    $event = Event::factory()->create();
    EventTask::factory()->for($event)->aFazer()->create();
    EventTask::factory()->for($event)->emAndamento()->create();
    EventTask::factory()->for($event)->concluida()->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/tasks")
        ->assertOk();

    expect($response->json('data'))->toHaveKeys(['a_fazer', 'em_andamento', 'em_revisao', 'concluida']);
    expect($response->json('data.a_fazer'))->toHaveCount(1);
    expect($response->json('data.em_andamento'))->toHaveCount(1);
    expect($response->json('data.concluida'))->toHaveCount(1);
});

it('colaborador visualiza board', function () {
    $event = Event::factory()->create();
    EventTask::factory()->count(2)->for($event)->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->getJson("/admin/api/events/{$event->id}/tasks")
        ->assertOk();
});

it('board retorna summary com total, concluídas e atrasadas', function () {
    $event = Event::factory()->create();
    EventTask::factory()->for($event)->aFazer()->create();
    EventTask::factory()->for($event)->concluida()->create();
    EventTask::factory()->for($event)->overdue()->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/tasks")
        ->assertOk();

    expect($response->json('summary.total'))->toBe(3);
    expect($response->json('summary.concluida'))->toBe(1);
    expect($response->json('summary.overdue'))->toBe(1);
});

it('board retorna assignees somente com admin e colaborador', function () {
    $admin = User::factory()->admin()->create();
    $colaborador = User::factory()->colaborador()->create();
    $palestrante = User::factory()->create(['role' => 'palestrante', 'is_active' => true]);

    $event = Event::factory()->create();

    $response = $this->actingAs($admin)
        ->getJson("/admin/api/events/{$event->id}/tasks")
        ->assertOk();

    $assigneeIds = collect($response->json('assignees'))->pluck('id')->all();
    expect($assigneeIds)->toContain($admin->id);
    expect($assigneeIds)->toContain($colaborador->id);
    expect($assigneeIds)->not->toContain($palestrante->id);
});

// ─── Criar ────────────────────────────────────────────────────────────────────

it('admin cria tarefa com status a_fazer por padrão', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/tasks", ['title' => 'Nova tarefa'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'a_fazer');

    $this->assertDatabaseHas('event_tasks', ['event_id' => $event->id, 'title' => 'Nova tarefa', 'status' => 'a_fazer']);
});

it('admin cria tarefa com status específico', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/tasks", ['title' => 'Tarefa', 'status' => 'em_andamento'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'em_andamento');
});

it('colaborador tenta criar tarefa e recebe 403', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->postJson("/admin/api/events/{$event->id}/tasks", ['title' => 'Tarefa'])
        ->assertForbidden();
});

it('criar sem título retorna 422', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/tasks", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

it('criar com assigned_to de palestrante retorna 422', function () {
    $event = Event::factory()->create();
    $palestrante = User::factory()->create(['role' => 'palestrante']);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/tasks", [
            'title' => 'Tarefa',
            'assigned_to' => $palestrante->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['assigned_to']);
});

it('sort_order recebe MAX + 1 da coluna ao criar', function () {
    $event = Event::factory()->create();
    EventTask::factory()->for($event)->aFazer()->create(['sort_order' => 0]);
    EventTask::factory()->for($event)->aFazer()->create(['sort_order' => 1]);

    $response = $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/tasks", ['title' => 'Terceira tarefa', 'status' => 'a_fazer'])
        ->assertCreated();

    expect($response->json('data.sort_order'))->toBe(2);
});

// ─── Editar ───────────────────────────────────────────────────────────────────

it('admin edita tarefa', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create(['title' => 'Título original']);

    $this->actingAs(User::factory()->admin()->create())
        ->putJson("/admin/api/events/{$event->id}/tasks/{$task->id}", ['title' => 'Título atualizado'])
        ->assertOk()
        ->assertJsonPath('data.title', 'Título atualizado');

    $this->assertDatabaseHas('event_tasks', ['id' => $task->id, 'title' => 'Título atualizado']);
});

it('colaborador tenta editar e recebe 403', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->putJson("/admin/api/events/{$event->id}/tasks/{$task->id}", ['title' => 'Novo título'])
        ->assertForbidden();
});

// ─── Mover ────────────────────────────────────────────────────────────────────

it('admin move tarefa para outra coluna via updateStatus', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->aFazer()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/tasks/{$task->id}/status", ['status' => 'em_andamento'])
        ->assertOk()
        ->assertJsonPath('status', 'em_andamento');

    $this->assertDatabaseHas('event_tasks', ['id' => $task->id, 'status' => 'em_andamento']);
});

it('colaborador move tarefa para outra coluna via updateStatus', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->aFazer()->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->patchJson("/admin/api/events/{$event->id}/tasks/{$task->id}/status", ['status' => 'em_revisao'])
        ->assertOk();
});

it('sort_order na nova coluna é MAX + 1 após mover', function () {
    $event = Event::factory()->create();
    EventTask::factory()->for($event)->emAndamento()->create(['sort_order' => 0]);
    EventTask::factory()->for($event)->emAndamento()->create(['sort_order' => 1]);
    $task = EventTask::factory()->for($event)->aFazer()->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/tasks/{$task->id}/status", ['status' => 'em_andamento'])
        ->assertOk();

    expect($response->json('sort_order'))->toBe(2);
});

// ─── Reordenar ────────────────────────────────────────────────────────────────

it('reorder atualiza sort_order dos cards (admin)', function () {
    $event = Event::factory()->create();
    $t1 = EventTask::factory()->for($event)->aFazer()->create(['sort_order' => 0]);
    $t2 = EventTask::factory()->for($event)->aFazer()->create(['sort_order' => 1]);

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/tasks/reorder", [
            'items' => [
                ['id' => $t1->id, 'sort_order' => 1],
                ['id' => $t2->id, 'sort_order' => 0],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('ok', true);

    $this->assertDatabaseHas('event_tasks', ['id' => $t1->id, 'sort_order' => 1]);
    $this->assertDatabaseHas('event_tasks', ['id' => $t2->id, 'sort_order' => 0]);
});

it('reorder atualiza sort_order dos cards (colaborador)', function () {
    $event = Event::factory()->create();
    $t1 = EventTask::factory()->for($event)->aFazer()->create(['sort_order' => 0]);
    $t2 = EventTask::factory()->for($event)->aFazer()->create(['sort_order' => 1]);

    $this->actingAs(User::factory()->colaborador()->create())
        ->patchJson("/admin/api/events/{$event->id}/tasks/reorder", [
            'items' => [
                ['id' => $t1->id, 'sort_order' => 1],
                ['id' => $t2->id, 'sort_order' => 0],
            ],
        ])
        ->assertOk();
});

// ─── Soft Delete ──────────────────────────────────────────────────────────────

it('admin faz soft delete da tarefa', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->deleteJson("/admin/api/events/{$event->id}/tasks/{$task->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('event_tasks', ['id' => $task->id]);
});

it('colaborador tenta excluir e recebe 403', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->deleteJson("/admin/api/events/{$event->id}/tasks/{$task->id}")
        ->assertForbidden();
});

it('tarefa excluída não aparece no board', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->aFazer()->create();
    $task->delete();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/tasks")
        ->assertOk();

    $ids = collect($response->json('data.a_fazer'))->pluck('id')->all();
    expect($ids)->not->toContain($task->id);
});

it('tarefa excluída aparece na lixeira', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create(['title' => 'Tarefa excluída']);
    $task->delete();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/tasks/trash")
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($task->id);
});

it('admin restaura tarefa da lixeira', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();
    $task->delete();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/tasks/{$task->id}/restore")
        ->assertOk();

    $this->assertDatabaseHas('event_tasks', ['id' => $task->id, 'deleted_at' => null]);
});

it('tarefa restaurada reaparece no board', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->aFazer()->create();
    $task->delete();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/tasks/{$task->id}/restore")
        ->assertOk();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/tasks")
        ->assertOk();

    $ids = collect($response->json('data.a_fazer'))->pluck('id')->all();
    expect($ids)->toContain($task->id);
});

it('colaborador não acessa lixeira e recebe 403', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->getJson("/admin/api/events/{$event->id}/tasks/trash")
        ->assertForbidden();
});

// ─── Isolamento ───────────────────────────────────────────────────────────────

it('tarefa de outro evento retorna 404', function () {
    $event1 = Event::factory()->create();
    $event2 = Event::factory()->create();
    $task = EventTask::factory()->for($event1)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event2->id}/tasks/{$task->id}")
        ->assertNotFound();
});

// ─── is_overdue ───────────────────────────────────────────────────────────────

it('is_overdue é true quando prazo passou e status não é concluida', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->overdue()->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/tasks")
        ->assertOk();

    $found = collect($response->json('data.a_fazer'))->firstWhere('id', $task->id);
    expect($found['is_overdue'])->toBeTrue();
});

it('is_overdue é false quando tarefa está concluída mesmo com prazo passado', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->concluida()->create([
        'due_date' => now()->subDays(5)->format('Y-m-d'),
    ]);

    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/tasks")
        ->assertOk();

    $found = collect($response->json('data.concluida'))->firstWhere('id', $task->id);
    expect($found['is_overdue'])->toBeFalse();
});
