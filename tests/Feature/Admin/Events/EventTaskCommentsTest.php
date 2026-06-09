<?php

use App\Models\Event;
use App\Models\EventTask;
use App\Models\EventTaskComment;
use App\Models\User;

// ─── Listagem ─────────────────────────────────────────────────────────────────

it('admin lista comentários de uma tarefa', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();
    EventTaskComment::factory()->count(3)->for($task, 'task')->create();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/tasks/{$task->id}/comments")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('colaborador lista comentários de uma tarefa', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();
    EventTaskComment::factory()->count(2)->for($task, 'task')->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->getJson("/admin/api/events/{$event->id}/tasks/{$task->id}/comments")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('comentários retornam campo is_mine correto', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();
    $author = User::factory()->admin()->create();
    $other = User::factory()->colaborador()->create();

    EventTaskComment::factory()->for($task, 'task')->create(['user_id' => $author->id]);
    EventTaskComment::factory()->for($task, 'task')->create(['user_id' => $other->id]);

    $response = $this->actingAs($author)
        ->getJson("/admin/api/events/{$event->id}/tasks/{$task->id}/comments")
        ->assertOk();

    $comments = $response->json('data');
    $mine = collect($comments)->where('user_id', $author->id)->first();
    $theirs = collect($comments)->where('user_id', $other->id)->first();

    expect($mine['is_mine'])->toBeTrue();
    expect($theirs['is_mine'])->toBeFalse();
});

// ─── Criar ────────────────────────────────────────────────────────────────────

it('admin cria comentário', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/tasks/{$task->id}/comments", ['body' => 'Comentário de teste'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Comentário de teste');

    $this->assertDatabaseHas('event_task_comments', ['event_task_id' => $task->id, 'body' => 'Comentário de teste']);
});

it('colaborador cria comentário', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->postJson("/admin/api/events/{$event->id}/tasks/{$task->id}/comments", ['body' => 'Boa ideia!'])
        ->assertCreated();
});

it('criar comentário vazio retorna 422', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/tasks/{$task->id}/comments", ['body' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['body']);
});

it('criar comentário com mais de 2000 chars retorna 422', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/tasks/{$task->id}/comments", ['body' => str_repeat('a', 2001)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['body']);
});

// ─── Editar ───────────────────────────────────────────────────────────────────

it('autor edita comentário próprio', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();
    $author = User::factory()->admin()->create();
    $comment = EventTaskComment::factory()->for($task, 'task')->create(['user_id' => $author->id, 'body' => 'Original']);

    $this->actingAs($author)
        ->putJson("/admin/api/events/{$event->id}/tasks/{$task->id}/comments/{$comment->id}", ['body' => 'Editado'])
        ->assertOk()
        ->assertJsonPath('data.body', 'Editado');

    $this->assertDatabaseHas('event_task_comments', ['id' => $comment->id, 'body' => 'Editado']);
});

it('usuário tenta editar comentário alheio e recebe 403', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();
    $author = User::factory()->admin()->create();
    $other = User::factory()->colaborador()->create();
    $comment = EventTaskComment::factory()->for($task, 'task')->create(['user_id' => $author->id]);

    $this->actingAs($other)
        ->putJson("/admin/api/events/{$event->id}/tasks/{$task->id}/comments/{$comment->id}", ['body' => 'Tentativa'])
        ->assertForbidden();
});

// ─── Excluir ──────────────────────────────────────────────────────────────────

it('autor faz soft delete de comentário próprio', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();
    $author = User::factory()->admin()->create();
    $comment = EventTaskComment::factory()->for($task, 'task')->create(['user_id' => $author->id]);

    $this->actingAs($author)
        ->deleteJson("/admin/api/events/{$event->id}/tasks/{$task->id}/comments/{$comment->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('event_task_comments', ['id' => $comment->id]);
});

it('usuário tenta excluir comentário alheio e recebe 403', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();
    $author = User::factory()->admin()->create();
    $other = User::factory()->colaborador()->create();
    $comment = EventTaskComment::factory()->for($task, 'task')->create(['user_id' => $author->id]);

    $this->actingAs($other)
        ->deleteJson("/admin/api/events/{$event->id}/tasks/{$task->id}/comments/{$comment->id}")
        ->assertForbidden();
});

it('comentário excluído não aparece na listagem', function () {
    $event = Event::factory()->create();
    $task = EventTask::factory()->for($event)->create();
    $author = User::factory()->admin()->create();
    $comment = EventTaskComment::factory()->for($task, 'task')->create(['user_id' => $author->id]);
    $comment->delete();

    $response = $this->actingAs($author)
        ->getJson("/admin/api/events/{$event->id}/tasks/{$task->id}/comments")
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->not->toContain($comment->id);
});

// ─── Isolamento ───────────────────────────────────────────────────────────────

it('comentário de task de outro evento retorna 404', function () {
    $event1 = Event::factory()->create();
    $event2 = Event::factory()->create();
    $task = EventTask::factory()->for($event1)->create();
    $author = User::factory()->admin()->create();
    $comment = EventTaskComment::factory()->for($task, 'task')->create(['user_id' => $author->id]);

    $this->actingAs($author)
        ->deleteJson("/admin/api/events/{$event2->id}/tasks/{$task->id}/comments/{$comment->id}")
        ->assertNotFound();
});
