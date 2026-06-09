# Spec — Kanban de Tarefas do Evento

**Status:** ✅ Implementado
**Módulo:** Admin → Eventos → Tarefas
**Depende de:** `.claude/specs/admin/events-details.md`
**Arquivo de referência de padrões:** `.claude/patterns/front-patterns.md`

---

## 1. Visão geral

Quadro Kanban vinculado a um evento para organizar as tarefas da equipe organizadora. Acessado pelo hub do evento em `/admin/events/{id}/tasks`.

As tarefas avançam pelas colunas arrastando os cards (drag-and-drop). Somente `admin` pode criar, editar e excluir; `colaborador` pode visualizar e mover cards entre colunas. A exclusão usa **soft delete** — as tarefas excluídas são acessíveis na aba "Lixeira" e podem ser restauradas.

| Ação | `admin` | `colaborador` |
|------|---------|---------------|
| Visualizar quadro | ✅ | ✅ |
| Criar tarefa | ✅ | ❌ |
| Editar tarefa | ✅ | ❌ |
| Mover entre colunas (drag ou dropdown) | ✅ | ✅ |
| Excluir tarefa (soft delete) | ✅ | ❌ |
| Restaurar tarefa excluída | ✅ | ❌ |
| Ver lixeira | ✅ | ❌ |
| Comentar numa tarefa | ✅ | ✅ |
| Editar comentário próprio | ✅ | ✅ |
| Excluir comentário próprio (soft delete) | ✅ | ✅ |
| Editar/excluir comentário alheio | ❌ | ❌ |

---

## 2. Model `EventTask`

**Arquivo:** `app/Models/EventTask.php`

### 2.1 Campos

| Campo | Tipo | Observação |
|-------|------|-----------|
| `id` | `bigIncrements` | PK |
| `event_id` | `foreignId → events.id` | `cascadeOnDelete` |
| `title` | `string(255)` | Título curto e objetivo da tarefa |
| `description` | `text` | Nullable — detalhes da tarefa |
| `status` | `enum` | Ver seção 2.2 — define a coluna do Kanban |
| `priority` | `enum` | `baixa`, `media`, `alta` — default `media` |
| `assigned_to` | `foreignId → users.id` | Nullable, `nullOnDelete` — responsável |
| `due_date` | `date` | Nullable — prazo de conclusão |
| `sort_order` | `unsignedInteger` | Posição dentro da coluna (0 = topo); default `0` |
| `created_by` | `foreignId → users.id` | Nullable, `nullOnDelete` |
| `deleted_at` | `timestamp` | Nullable — soft delete |
| `timestamps` | — | `created_at`, `updated_at` |

### 2.2 Enum `status` — colunas do Kanban

| Valor | Rótulo | Cor do cabeçalho |
|-------|--------|-----------------|
| `a_fazer` | A Fazer | `--color-text-muted` (cinza) |
| `em_andamento` | Em Andamento | `#2563eb` (azul) |
| `em_revisao` | Em Revisão | `#d97706` (âmbar) |
| `impedimento` | Impedimento | `#dc2626` (vermelho) — tarefa parada por bloqueio burocrático |
| `concluida` | Concluída | `--color-success` (verde) |

### 2.3 Enum `priority`

| Valor | Rótulo | Cor do badge |
|-------|--------|-------------|
| `baixa` | Baixa | `#6b7280` (cinza) |
| `media` | Média | `#2563eb` (azul) |
| `alta` | Alta | `#dc2626` (vermelho) |

### 2.4 Migration

```php
Schema::create('event_tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('description')->nullable();
    $table->enum('status', ['a_fazer', 'em_andamento', 'em_revisao', 'impedimento', 'concluida'])->default('a_fazer');
    $table->enum('priority', ['baixa', 'media', 'alta'])->default('media');
    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
    $table->date('due_date')->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->softDeletes();
    $table->timestamps();
});
```

### 2.5 Model

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class EventTask extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id', 'title', 'description', 'status', 'priority',
        'assigned_to', 'due_date', 'sort_order', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && $this->status !== 'concluida';
    }
}
```

### 2.6 Relacionamento no model `Event`

```php
public function tasks(): HasMany
{
    return $this->hasMany(EventTask::class)->orderBy('sort_order');
}
```

### 2.7 Relacionamento no model `EventTask`

```php
public function comments(): HasMany
{
    return $this->hasMany(EventTaskComment::class)->orderBy('created_at', 'asc');
}
```

---

## 2b. Model `EventTaskComment`

**Arquivo:** `app/Models/EventTaskComment.php`

### Campos

| Campo | Tipo | Observação |
|-------|------|-----------|
| `id` | `bigIncrements` | PK |
| `event_task_id` | `foreignId → event_tasks.id` | `cascadeOnDelete` |
| `user_id` | `foreignId → users.id` | `nullOnDelete` — autor |
| `body` | `text` | Conteúdo do comentário |
| `deleted_at` | `timestamp` | Nullable — soft delete |
| `timestamps` | — | `created_at`, `updated_at` |

### Migration

```php
Schema::create('event_task_comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_task_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->text('body');
    $table->softDeletes();
    $table->timestamps();
});
```

### Model

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class EventTaskComment extends Model
{
    use SoftDeletes;

    protected $fillable = ['event_task_id', 'user_id', 'body'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(EventTask::class, 'event_task_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

---

## 3. Regras de negócio

| Regra | Detalhe |
|-------|---------|
| Criar / Editar / Excluir tarefa | Somente `admin` — colaborador recebe `403` |
| Mover coluna | Admin e colaborador podem alterar `status` |
| `assigned_to` | Deve ser usuário com role `admin` ou `colaborador` (não palestrante) |
| `sort_order` ao criar | `MAX(sort_order) + 1` da coluna destino |
| `sort_order` ao mover | Tarefa vai para o final da nova coluna (`MAX + 1`) |
| Soft delete (tarefa) | `DELETE` marca `deleted_at`; tarefa sai do board mas é restaurável |
| Restore | Tarefa restaurada volta para sua coluna original com `sort_order` no final |
| `created_by` | Preenchido com `Auth::id()` ao criar |
| Comentar | Qualquer usuário autenticado no admin (admin ou colaborador) |
| Editar comentário | Somente o autor (`user_id === Auth::id()`) — outro usuário recebe `403` |
| Excluir comentário | Somente o autor (`user_id === Auth::id()`) — soft delete; retorna `204` |
| `user_id` no comentário | Preenchido com `Auth::id()` ao criar; não alterável depois |

---

## 4. Rotas

```php
// routes/web.php — dentro do grupo auth + EnsureAdminRole
Route::prefix('api/events/{event}/tasks')->name('tasks.')->group(function () {
    Route::get('/',                [EventTaskController::class, 'index'])->name('index');
    Route::post('/',               [EventTaskController::class, 'store'])->name('store')->middleware('role:admin');
    Route::get('/trash',           [EventTaskController::class, 'trash'])->name('trash')->middleware('role:admin');
    Route::patch('/reorder',       [EventTaskController::class, 'reorder'])->name('reorder');
    Route::get('/{task}',          [EventTaskController::class, 'show'])->name('show');
    Route::put('/{task}',          [EventTaskController::class, 'update'])->name('update')->middleware('role:admin');
    Route::patch('/{task}/status', [EventTaskController::class, 'updateStatus'])->name('updateStatus');
    Route::delete('/{task}',       [EventTaskController::class, 'destroy'])->name('destroy')->middleware('role:admin');
    Route::patch('/{task}/restore',[EventTaskController::class, 'restore'])->name('restore')->middleware('role:admin');

    // Comentários
    Route::get('/{task}/comments',          [EventTaskCommentController::class, 'index'])->name('comments.index');
    Route::post('/{task}/comments',         [EventTaskCommentController::class, 'store'])->name('comments.store');
    Route::put('/{task}/comments/{comment}',[EventTaskCommentController::class, 'update'])->name('comments.update');
    Route::delete('/{task}/comments/{comment}',[EventTaskCommentController::class, 'destroy'])->name('comments.destroy');
});

// Rota Vue
Route::get('/events/{id}/tasks', fn () => view('admin'))->name('events.tasks');
```

> As rotas `/trash` e `/reorder` devem vir **antes** de `/{task}` para o Laravel não interpretá-las como IDs.

---

## 5. Controller

**Arquivo:** `app/Http/Controllers/Admin/EventTaskController.php`

### 5.1 `index`

```
GET /admin/api/events/{event}/tasks

Retorna JSON:
{
  "data": {
    "a_fazer":      [ ...tasks ],
    "em_andamento": [ ...tasks ],
    "em_revisao":   [ ...tasks ],
    "impedimento":  [ ...tasks ],
    "concluida":    [ ...tasks ]
  },
  "summary": {
    "total":     12,
    "concluida": 5,
    "overdue":   2
  },
  "assignees": [ { "id": 1, "name": "Nome" }, ... ]
}
```

Tasks sem `deleted_at`, ordenadas por `sort_order ASC` dentro de cada coluna. O campo `assignees` retorna todos os usuários com role `admin` ou `colaborador` — usado para popular o `<select>` no modal.

Campos retornados por tarefa:

```json
{
  "id": 1,
  "title": "Confirmar local do evento",
  "description": null,
  "status": "em_andamento",
  "priority": "alta",
  "priority_label": "Alta",
  "due_date": "2026-06-10",
  "is_overdue": false,
  "sort_order": 0,
  "assigned_to": 3,
  "assignee": { "id": 3, "name": "Maria Costa" },
  "comments_count": 2,
  "created_by": 1,
  "created_at": "2026-06-01T10:00:00Z",
  "updated_at": "2026-06-01T10:00:00Z"
}
```

### 5.2 `store` — somente `admin`

```
POST /admin/api/events/{event}/tasks
Body: { title, description?, status?, priority?, assigned_to?, due_date? }

→ valida via StoreTaskRequest
→ status default: 'a_fazer'
→ sort_order = MAX(sort_order) + 1 na coluna destino
→ created_by = Auth::id()
→ 201 com a tarefa criada
```

### 5.3 `show`

```
GET /admin/api/events/{event}/tasks/{task}
→ 200 com a tarefa completa
→ 404 se não pertencer ao evento
```

### 5.4 `update` — somente `admin`

```
PUT /admin/api/events/{event}/tasks/{task}
Body: { title, description?, priority?, assigned_to?, due_date? }

→ valida via UpdateTaskRequest (sem status — use updateStatus para mover)
→ 200 com a tarefa atualizada
→ 404 se não pertencer ao evento
```

### 5.5 `updateStatus`

```
PATCH /admin/api/events/{event}/tasks/{task}/status
Body: { status }

→ valida status in ['a_fazer', 'em_andamento', 'em_revisao', 'concluida']
→ sort_order = MAX(sort_order) + 1 na nova coluna
→ 200 com { id, status, sort_order }
```

Acessível por admin e colaborador (sem middleware `role:admin`).

### 5.6 `reorder`

```
PATCH /admin/api/events/{event}/tasks/reorder
Body: { items: [ { id, sort_order }, ... ] }

→ atualiza sort_order de cada item (verifica que pertence ao evento)
→ 200 com { ok: true }
```

Chamado após soltar um card, transmitindo a nova ordem dos IDs dentro da coluna. Acessível por admin e colaborador.

### 5.7 `destroy` — somente `admin`

```
DELETE /admin/api/events/{event}/tasks/{task}
→ soft delete (marca deleted_at)
→ 204 No Content
→ 404 se não pertencer ao evento
```

### 5.8 `trash` — somente `admin`

```
GET /admin/api/events/{event}/tasks/trash

Retorna JSON:
{
  "data": [ ...tarefas com deleted_at, ordenadas por deleted_at DESC ]
}
```

### 5.9 `restore` — somente `admin`

```
PATCH /admin/api/events/{event}/tasks/{task}/restore

→ remove deleted_at
→ sort_order = MAX + 1 na coluna original
→ 200 com a tarefa restaurada
```

---

## 5b. Controller de comentários

**Arquivo:** `app/Http/Controllers/Admin/EventTaskCommentController.php`

### `index`

```
GET /admin/api/events/{event}/tasks/{task}/comments

→ verifica que task pertence ao event (404 se não)
→ retorna comentários sem deleted_at, ordenados por created_at ASC
→ 200 com array de comentários

Campos por comentário:
{
  "id": 1,
  "body": "Já confirmei com o espaço.",
  "user_id": 2,
  "author": { "id": 2, "name": "Maria Costa" },
  "is_mine": true,          ← user_id === Auth::id()
  "created_at": "2026-06-08T14:00:00Z",
  "updated_at": "2026-06-08T14:05:00Z"
}
```

### `store`

```
POST /admin/api/events/{event}/tasks/{task}/comments
Body: { body }

→ verifica que task pertence ao event (404 se não)
→ valida body: required|string|max:2000
→ cria comentário com user_id = Auth::id()
→ 201 com o comentário criado (mesmo formato de index)
```

### `update`

```
PUT /admin/api/events/{event}/tasks/{task}/comments/{comment}
Body: { body }

→ verifica que task pertence ao event (404 se não)
→ verifica que comment pertence à task (404 se não)
→ verifica que comment.user_id === Auth::id() (403 se não)
→ valida body: required|string|max:2000
→ 200 com o comentário atualizado
```

### `destroy`

```
DELETE /admin/api/events/{event}/tasks/{task}/comments/{comment}

→ verifica que task pertence ao event (404 se não)
→ verifica que comment pertence à task (404 se não)
→ verifica que comment.user_id === Auth::id() (403 se não)
→ soft delete
→ 204 No Content
```

---

## 6. Form Requests

### 6.1 `StoreTaskRequest`

**Arquivo:** `app/Http/Requests/Admin/Tasks/StoreTaskRequest.php`

```php
public function rules(): array
{
    return [
        'title'       => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string', 'max:5000'],
        'status'      => ['nullable', Rule::in(['a_fazer', 'em_andamento', 'em_revisao', 'impedimento', 'concluida'])],
        'priority'    => ['nullable', Rule::in(['baixa', 'media', 'alta'])],
        'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->whereIn('role', ['admin', 'colaborador'])],
        'due_date'    => ['nullable', 'date'],
    ];
}

public function messages(): array
{
    return [
        'title.required'      => 'Informe um título para a tarefa.',
        'title.max'           => 'O título deve ter no máximo 255 caracteres.',
        'description.max'     => 'A descrição deve ter no máximo 5.000 caracteres.',
        'status.in'           => 'Status inválido.',
        'priority.in'         => 'Prioridade inválida.',
        'assigned_to.exists'  => 'Responsável inválido.',
        'due_date.date'       => 'Data de prazo inválida.',
    ];
}
```

### 6.2 `UpdateTaskRequest`

**Arquivo:** `app/Http/Requests/Admin/Tasks/UpdateTaskRequest.php`

Mesmas regras de `StoreTaskRequest`, sem o campo `status` (movimentação é feita via `updateStatus`).

---

## 7. Service

**Arquivo:** `app/Services/EventTaskService.php`

```php
public function board(Event $event): array;
// retorna: data (tarefas agrupadas), summary (total/concluida/overdue), assignees

public function create(Event $event, array $data, int $createdBy): EventTask;

public function update(EventTask $task, array $data): EventTask;

public function updateStatus(EventTask $task, string $newStatus): EventTask;
// sort_order = nextSortOrder($task->event_id, $newStatus)

public function reorder(Event $event, array $items): void;
// $items = [ ['id' => x, 'sort_order' => y], ... ]

public function delete(EventTask $task): void;  // soft delete

public function restore(EventTask $task): EventTask;
// sort_order = nextSortOrder($task->event_id, $task->status)

public function trash(Event $event): array;
// retorna tarefas com deleted_at, ordenadas por deleted_at DESC

public function nextSortOrder(int $eventId, string $status): int;
// MAX(sort_order) + 1 ignorando soft-deleted
```

---

## 8. Layout da página

**Rota Vue:** `/admin/events/{id}/tasks`
**Componente:** `resources/js/views/admin/EventTasks.vue`

```
← Voltar para o evento: [Nome do evento]

┌──────────────────────────────────────────────────────────────────┐
│  ✅ Tarefas — Nome do Evento                                     │
│  12 tarefas · 5 concluídas · ⚠ 2 atrasadas  [+ Nova tarefa]    │
└──────────────────────────────────────────────────────────────────┘

[Board] [Lixeira]  ← abas (somente admin vê a aba Lixeira)

┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│ A Fazer  3  │  │EmAndamento 4│  │ Em Revisão 2│  │Impedimento 1│  │ Concluída 3 │
│─────────────│  │─────────────│  │─────────────│  │─────────────│  │─────────────│
│ ┌─────────┐ │  │ ┌─────────┐ │  │ ┌─────────┐ │  │ ┌─────────┐ │  │ ┌─────────┐ │
│ │● Alta   │ │  │ │● Média  │ │  │ │● Média  │ │  │ │● Alta   │ │  │ │● Baixa  │ │
│ │Confirmar│ │  │ │Criar    │ │  │ │Revisar  │ │  │ │Aprovar  │ │  │ │Enviar   │ │
│ │local    │ │  │ │banner   │ │  │ │contrato │ │  │ │orçamento│ │  │ │e-mails  │ │
│ │📅 10/06 │ │  │ │👤 Maria │ │  │ │📅⚠05/06 │ │  │          │ │  │          │ │
│ └─────────┘ │  │ └─────────┘ │  │ └─────────┘ │  │ └─────────┘ │  │ └─────────┘ │
│             │  │             │  │             │  │             │  │             │
│[+ Adicionar]│  │[+ Adicionar]│  │[+ Adicionar]│  │[+ Adicionar]│  │[+ Adicionar]│
└─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘
```

- Botão `[+ Nova tarefa]` e botões `[+ Adicionar]` visíveis apenas para `admin`
- **Cinco colunas** lado a lado com scroll horizontal em mobile (`overflow-x: auto`)
- Cards arrastáveis por admin e colaborador
- Coluna "Impedimento" tem cabeçalho em vermelho (`text-red-600`) para indicar bloqueio

---

## 9. Colunas do Kanban

Cada coluna é um contêiner drop-zone:

| Propriedade | Detalhe |
|-------------|---------|
| Largura mínima | `min-w-64` (256px) — scroll horizontal em telas pequenas |
| Altura mínima | `min-h-32` — permite soltar em coluna vazia |
| Fundo | `--color-bg` |
| Borda | `1px solid --color-border`, border-radius `12px` |
| Cabeçalho | Nome da coluna + badge contador; cor do texto conforme seção 2.2 |
| Estado vazio | "Nenhuma tarefa aqui", 13px, `--color-text-muted`, `opacity: 0.6`, centralizado |
| Drop highlight | `border-color: --color-primary`, `background: oklch(from var(--color-primary) l c h / 0.05)` |

---

## 10. Card de tarefa

```
┌─────────────────────────────┐
│ ● Alta                      │  ← badge prioridade
│                             │
│ Confirmar local do evento   │  ← título (2 linhas max)
│                             │
│ 📅 10/06/2026               │  ← prazo (se preenchido)
│ 👤 Maria Costa              │  ← responsável (se atribuído)
└─────────────────────────────┘
```

- Fundo: `--color-surface`
- Borda: `1px solid --color-border`, border-radius `10px`, padding `12px`
- Cursor: `grab` (quando admin ou colaborador) — arrastável
- Hover: `box-shadow: 0 2px 8px rgba(0,0,0,0.08)`
- Arrastando: `opacity: 0.5`, cursor `grabbing`
- Click no card abre `TaskModal` em modo edição (somente admin pode salvar)

### 10.1 Badge de prioridade

| Prioridade | Cor de fundo | Cor do texto |
|------------|-------------|--------------|
| `alta` | `#fee2e2` | `#dc2626` |
| `media` | `#dbeafe` | `#2563eb` |
| `baixa` | `#f3f4f6` | `#6b7280` |

- `● Rótulo` — font-size `11px`, peso `600`, padding `2px 8px`, border-radius `6px`

### 10.2 Data de prazo

- Exibida se `due_date` não for nulo
- Formato `DD/MM/YYYY`
- Se `is_overdue = true`: texto em `--color-danger` + ícone `⚠`

### 10.3 Responsável

- Exibido se `assignee` não for nulo
- Ícone de pessoa + nome, 12px, `--color-text-muted`

### 10.4 Contador de comentários

- Exibido **abaixo do responsável** se `comments_count > 0`
- Ícone de balão de diálogo + texto `N comentário(s)`, 12px, `--color-text-muted`
- Atualizado **otimisticamente**: `TaskModal` emite `comment-changed` com `delta: +1` ao enviar e `delta: -1` ao excluir; `EventTasks` ajusta o count no board sem refazer fetch
- `comments_count` é calculado via `withCount('comments')` na query do `board()` (exclui soft-deleted automaticamente)

---

## 11. Drag-and-drop

Implementado com a **HTML5 Drag and Drop API** (sem biblioteca extra):

| Evento | Elemento | Ação |
|--------|----------|------|
| `dragstart` | Card | Guarda `taskId` e `fromStatus` em `dataTransfer` |
| `dragover` | Coluna + outros cards | `e.preventDefault()`; adiciona classe de highlight |
| `dragleave` | Coluna | Remove highlight |
| `drop` | Coluna | Calcula novo status + nova posição; atualiza estado local e chama API |
| `dragend` | Card | Limpa highlight de todas as colunas |

**Lógica ao soltar:**

- Soltar sobre **coluna vazia** ou **abaixo de todos os cards**: nova posição = final da coluna
- Soltar sobre **um card**: inserir acima dele (detectado via `getBoundingClientRect` no `dragover` dos cards)

**Chamadas à API após drop:**

1. Se mudou de coluna → `PATCH /{task}/status { status }`
2. Reordenação dos IDs na coluna destino → `PATCH /reorder { items }`

O estado local é atualizado **otimisticamente** antes da resposta da API. Em caso de erro, reverte para o estado anterior e exibe toast de erro.

---

## 12. Aba Lixeira

Visível somente para `admin`, como segunda aba na barra acima do board:

```
[Board ●]  [Lixeira (3)]
```

Exibe lista simples (não Kanban) das tarefas excluídas:

```
┌──────────────────────────────────────────────────────┐
│ Confirmar local do evento   [Em Andamento] [Restaurar]│
│ Excluída em: 05/06/2026                              │
├──────────────────────────────────────────────────────┤
│ Criar banner do evento      [A Fazer]      [Restaurar]│
│ Excluída em: 03/06/2026                              │
└──────────────────────────────────────────────────────┘
```

- Badge da coluna original do item
- Botão "Restaurar" chama `PATCH /{task}/restore`; tarefa volta para a coluna original
- Estado vazio: "Nenhuma tarefa excluída"

---

## 13. Modal de criação / edição

**Componente:** `resources/js/components/TaskModal.vue`

O modal tem duas abas: **Detalhes** (formulário) e **Comentários** (visível somente ao editar uma tarefa existente).

```
┌──────────────────────────────────────────────────────┐
│  Nova tarefa / Editar tarefa                  [✕]   │
├──────────────────────────────────────────────────────┤
│  [Detalhes]  [Comentários (3)]                       │
├──────────────────────────────────────────────────────┤
│                                                      │
│  Título *                                            │
│  [________________________________________________]  │
│                                                      │
│  Descrição (opcional)                                │
│  [                                              ]    │
│  [                                              ]    │
│                                                      │
│  Coluna *              Prioridade *                  │
│  [A Fazer        ▼]   [Média         ▼]             │
│                                                      │
│  Responsável (opcional)   Prazo (opcional)           │
│  [Selecione          ▼]   [dd/mm/aaaa    ]           │
│                                                      │
│               [Cancelar]  [Salvar]                   │
└──────────────────────────────────────────────────────┘
```

- Campos desabilitados em modo leitura se usuário for colaborador (apenas visualiza, não pode salvar)
- Campo "Coluna": pré-preenchido com o status da coluna onde o `[+ Adicionar]` foi clicado, ou `a_fazer` padrão
- Campo "Responsável": `<select>` populado com `assignees` retornado pelo `index`
- Modal: `max-width: 520px`, centralizado
- Erros de validação exibidos sob cada campo em `--color-danger`, 12px
- Aba "Comentários" só aparece ao editar tarefa existente (não na criação)
- Contador de comentários na aba usa o total de comentários carregados

### 13.1 Aba Comentários

```
┌──────────────────────────────────────────────────────┐
│  [Detalhes]  [Comentários (3)]                       │
├──────────────────────────────────────────────────────┤
│                                                      │
│  ┌────────────────────────────────────────────────┐  │
│  │ 👤 Maria Costa                    08/06 14:00  │  │
│  │ Já confirmei o local com a equipe.             │  │
│  └────────────────────────────────────────────────┘  │
│  ┌────────────────────────────────────────────────┐  │
│  │ 👤 Você                           08/06 15:30  │  │
│  │ Ótimo! Vou atualizar a tarefa.        [✎] [✕]  │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  Adicionar comentário                                │
│  [                                              ]    │
│  [Enviar]                                            │
└──────────────────────────────────────────────────────┘
```

- Comentários carregados via `GET /{task}/comments` ao abrir a aba
- Exibidos em ordem cronológica (mais antigo primeiro)
- Cada comentário mostra: nome do autor, data/hora (`DD/MM HH:mm`), body
- Botões `[✎]` (editar) e `[✕]` (excluir) visíveis somente se `is_mine = true`
- Ao clicar em `[✎]`: o body do comentário vira um `<textarea>` inline com botões "Salvar" / "Cancelar"
- Ao clicar em `[✕]`: abre `ConfirmModal` com mensagem "Excluir comentário?" e botão "Excluir" em `--color-danger`; após confirmar, chama `DELETE` e remove o comentário da lista local
- Campo de novo comentário: `<textarea rows="2">`, `max-length="2000"`
- Botão "Enviar" desabilitado se o campo estiver vazio; exibe spinner durante o POST
- Após enviar: campo limpa, novo comentário aparece no final da lista com `is_mine = true`

---

## 14. Modal de confirmação de exclusão

Reutiliza `ConfirmModal.vue`:

```
Título:    "Excluir tarefa"
Mensagem:  "A tarefa "[título]" será movida para a lixeira. Você poderá restaurá-la depois."
Botão:     "Mover para lixeira" (--color-danger)
```

Acessível apenas via `admin`.

---

## 15. Card no hub do evento (`EventDetail.vue`)

O card exibe um resumo buscado no `Promise.allSettled` do hub:

```
┌──────────────────────────────────────┐
│ ✅  Tarefas                           │
│                                      │
│ 5 de 12 concluídas                   │
│ [████████░░░░░░░] 42%                │
│ ⚠ 2 atrasadas                        │
│                                      │
│ Gerenciar →                          │
└──────────────────────────────────────┘
```

- Barra de progresso: `concluida / total * 100%`, cor `--color-success`
- "⚠ N atrasadas" em `--color-danger`, somente se `summary.overdue > 0`
- Se `total = 0`: "Nenhuma tarefa registrada"
- Botão "Gerenciar →" navega para `admin.events.tasks`

Chamada adicionada ao `fetchData()` de `EventDetail.vue`:

```js
const tasksRes = await axios.get(`/admin/api/events/${route.params.id}/tasks`)
// usar tasksRes.value.data.summary
```

---

## 16. Estado vazio (board sem tarefas)

Exibido no lugar do board quando todas as colunas estão vazias:

```
┌───────────────────────────────────────────┐
│                                           │
│      [ícone checklist 48px]               │
│                                           │
│   "Nenhuma tarefa ainda"                  │
│   "Clique em '+ Nova tarefa' para         │
│    começar a organizar o evento."         │
│                                           │
│   [+ Nova tarefa]   ← somente admin       │
└───────────────────────────────────────────┘
```

---

## 17. Arquivos a criar / modificar

| Arquivo | Ação |
|---------|------|
| `database/migrations/..._create_event_tasks_table.php` | Criar |
| `database/migrations/..._create_event_task_comments_table.php` | Criar |
| `app/Models/EventTask.php` | Criar (com `SoftDeletes`) |
| `app/Models/EventTaskComment.php` | Criar (com `SoftDeletes`) |
| `app/Models/Event.php` | Adicionar `tasks()` HasMany |
| `app/Http/Controllers/Admin/EventTaskController.php` | Criar |
| `app/Http/Controllers/Admin/EventTaskCommentController.php` | Criar |
| `app/Http/Requests/Admin/Tasks/StoreTaskRequest.php` | Criar |
| `app/Http/Requests/Admin/Tasks/UpdateTaskRequest.php` | Criar |
| `app/Services/EventTaskService.php` | Criar |
| `routes/web.php` | Adicionar rotas de tasks + comentários + rota Vue |
| `resources/js/views/admin/EventTasks.vue` | Criar |
| `resources/js/components/TaskModal.vue` | Criar (com aba de comentários) |
| `resources/js/router/admin.js` | Adicionar rota `events/:id/tasks` |
| `resources/js/views/admin/EventDetail.vue` | Atualizar card Tarefas + `fetchData` |
| `.claude/specs/admin/events-details.md` | Atualizar card "Tarefas" para "✅ Link ativo" |
| `CLAUDE.md` | Atualizar status |
| `.claude/about.md` | Atualizar sub-módulos do evento |

---

## 18. Vue Router

```js
// resources/js/router/admin.js — adicionar aos children do AdminLayout
{
    path: 'events/:id/tasks',
    name: 'admin.events.tasks',
    component: () => import('@/views/admin/EventTasks.vue'),
},
```

---

## 19. Testes

**Arquivo:** `tests/Feature/Admin/Events/EventTasksTest.php`

| # | Cenário |
|---|---------|
| 1 | Guest recebe 401 no board |
| 2 | Admin visualiza board agrupado por status |
| 3 | Colaborador visualiza board |
| 4 | Board retorna summary com total, concluídas e atrasadas |
| 5 | Board retorna `assignees` somente com admin e colaborador (não palestrante) |
| 6 | Admin cria tarefa com status `a_fazer` (default) |
| 7 | Admin cria tarefa com status específico |
| 8 | Colaborador tenta criar tarefa → 403 |
| 9 | Criar sem título retorna 422 |
| 10 | Criar com `assigned_to` de palestrante retorna 422 |
| 11 | `sort_order` recebe MAX + 1 da coluna ao criar |
| 12 | Admin edita tarefa |
| 13 | Colaborador tenta editar → 403 |
| 14 | Admin move tarefa para outra coluna via `updateStatus` |
| 15 | Colaborador move tarefa para outra coluna via `updateStatus` |
| 16 | `sort_order` na nova coluna é MAX + 1 após mover |
| 17 | `reorder` atualiza sort_order dos cards (admin) |
| 18 | `reorder` atualiza sort_order dos cards (colaborador) |
| 19 | Admin faz soft delete da tarefa |
| 20 | Colaborador tenta excluir → 403 |
| 21 | Tarefa excluída não aparece no board |
| 22 | Tarefa excluída aparece na lixeira |
| 23 | Admin restaura tarefa da lixeira |
| 24 | Tarefa restaurada reaparece no board |
| 25 | Colaborador não acessa lixeira → 403 |
| 26 | Tarefa de outro evento retorna 404 |
| 27 | `is_overdue = true` quando prazo passado e status ≠ concluida |
| 28 | `is_overdue = false` quando concluída mesmo com prazo passado |

**Arquivo:** `tests/Feature/Admin/Events/EventTaskCommentsTest.php`

| # | Cenário |
|---|---------|
| 29 | Admin lista comentários de uma tarefa |
| 30 | Colaborador lista comentários de uma tarefa |
| 31 | Comentários são retornados com campo `is_mine` correto |
| 32 | Admin cria comentário |
| 33 | Colaborador cria comentário |
| 34 | Criar comentário vazio retorna 422 |
| 35 | Criar comentário com mais de 2000 chars retorna 422 |
| 36 | Autor edita comentário próprio |
| 37 | Usuário tenta editar comentário alheio → 403 |
| 38 | Autor faz soft delete de comentário próprio → 204 |
| 39 | Usuário tenta excluir comentário alheio → 403 |
| 40 | Comentário excluído não aparece na listagem |
| 41 | Comentário de task de outro evento retorna 404 |

---

## 20. Critérios de aceite

- [ ] Board exibe 4 colunas com tarefas agrupadas por status
- [ ] Scroll horizontal em telas < 768px sem overflow na página
- [ ] Drag-and-drop move card para outra coluna e persiste via API
- [ ] Drag-and-drop reordena cards dentro da mesma coluna e persiste via API
- [ ] Estado do board atualiza otimisticamente (sem esperar resposta da API)
- [ ] Badge de prioridade com cor correta por nível
- [ ] Data de prazo em vermelho + ícone ⚠ quando atrasada
- [ ] Somente `admin` vê botão `[+ Nova tarefa]` e `[+ Adicionar]` por coluna
- [ ] Colaborador pode arrastar cards entre colunas
- [ ] Colaborador recebe 403 ao tentar criar, editar ou excluir via API
- [ ] Aba "Lixeira" visível somente para admin
- [ ] Tarefa excluída via soft delete some do board e aparece na lixeira
- [ ] Restaurar traz tarefa de volta ao board na coluna original
- [ ] Card no hub mostra total/concluídas + barra de progresso + alerta de atrasadas
- [ ] Estado vazio do board com botão `[+ Nova tarefa]` (admin) ou sem botão (colaborador)
- [ ] Estado vazio por coluna ("Nenhuma tarefa aqui") quando a coluna está vazia
- [ ] Aba "Comentários" aparece no modal somente ao editar uma tarefa existente
- [ ] Comentários são listados em ordem cronológica (mais antigo primeiro)
- [ ] Botões editar/excluir visíveis somente no comentário próprio (`is_mine`)
- [ ] Edição de comentário exibe textarea inline com "Salvar" / "Cancelar"
- [ ] Exclusão de comentário abre ConfirmModal e remove da lista após confirmação
- [ ] Campo de novo comentário: botão "Enviar" desabilitado enquanto vazio
- [ ] Novo comentário aparece ao final da lista após envio bem-sucedido
- [ ] Usuário sem autoria recebe 403 ao tentar editar/excluir via API
