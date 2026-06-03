# Spec — CRUD de Eventos

**Status:** ✅ Implementado
**Módulo:** Admin → Eventos
**Depende de:** `.claude/specs/admin/auth-spec.md`, `.claude/specs/admin/dashboard-spec.md`

---

## 1. Visão geral

Gerenciamento de eventos da comunidade PHP com Rapadura. Acessível por `admin` e `colaborador`. Cada evento é a entidade central do sistema — palestras, despesas, tarefas, participantes e sorteios são subentidades vinculadas a um evento.

O campo `slug` é incluído desde já para suportar URLs públicas futuras do tipo:
```
https://phpcomrapadura.org/encontro-de-devs-2026
```
A página pública do evento **não faz parte desta spec** — apenas o atributo é criado e persistido.

As imagens `cover_image` e `logo` são armazenadas no **Cloudflare R2** via disco `r2` do Laravel (driver S3-compatible). O banco persiste apenas a URL pública retornada pelo R2 — nunca dados binários.

---

## 2. Model `Event`

### 2.1 Campos

| Campo | Tipo | Observação |
|-------|------|-----------|
| `id` | `bigIncrements` | PK |
| `name` | `string(255)` | Nome completo do evento |
| `slug` | `string(255)` | Único — auto-gerado do nome, editável |
| `edition` | `unsignedSmallInteger` | Nullable — número da edição (1, 2, 3…) |
| `description` | `text` | Nullable — descrição/chamada do evento |
| `starts_at` | `datetime` | Data/hora de início |
| `ends_at` | `datetime` | Nullable — data/hora de encerramento |
| `location` | `string(255)` | Nullable — endereço físico ou nome do local |
| `is_online` | `boolean` | Default `false` — se true, exibe "Online" |
| `status` | `enum` | `rascunho`, `publicado`, `encerrado`, `cancelado` — default `rascunho` |
| `is_accepting_talks` | `boolean` | Default `false` — ativa o CFP para este evento |
| `max_attendees` | `unsignedInteger` | Nullable — capacidade máxima |
| `cover_image` | `string(500)` | Nullable — URL pública da imagem de capa (armazenada no R2) |
| `logo` | `string(500)` | Nullable — URL pública do logo do evento (armazenado no R2) |
| `created_by` | `foreignId → users.id` | Quem criou o evento (nullOnDelete) |
| `timestamps` | — | `created_at`, `updated_at` |

### 2.2 Migration

```php
Schema::create('events', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->unsignedSmallInteger('edition')->nullable();
    $table->text('description')->nullable();
    $table->datetime('starts_at');
    $table->datetime('ends_at')->nullable();
    $table->string('location')->nullable();
    $table->boolean('is_online')->default(false);
    $table->enum('status', ['rascunho', 'publicado', 'encerrado', 'cancelado'])->default('rascunho');
    $table->boolean('is_accepting_talks')->default(false);
    $table->unsignedInteger('max_attendees')->nullable();
    $table->string('cover_image', 500)->nullable();
    $table->string('logo', 500)->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

### 2.3 Model

**Arquivo:** `app/Models/Event.php`

```php
protected $fillable = [
    'name', 'slug', 'edition', 'description',
    'starts_at', 'ends_at', 'location', 'is_online',
    'status', 'is_accepting_talks', 'max_attendees',
    'cover_image', 'logo', 'created_by',
];

protected function casts(): array
{
    return [
        'starts_at'          => 'datetime',
        'ends_at'            => 'datetime',
        'is_online'          => 'boolean',
        'is_accepting_talks' => 'boolean',
    ];
}

public function creator(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}

public function isPublished(): bool
{
    return $this->status === 'publicado';
}

public function isCancelled(): bool
{
    return $this->status === 'cancelado';
}
```

### 2.4 Cloudflare R2 — disco `r2`

**Configuração em `config/filesystems.php`:**

```php
'r2' => [
    'driver'                  => 's3',
    'key'                     => env('CLOUDFLARE_R2_ACCESS_KEY_ID'),
    'secret'                  => env('CLOUDFLARE_R2_SECRET_ACCESS_KEY'),
    'region'                  => 'auto',
    'bucket'                  => env('CLOUDFLARE_R2_BUCKET'),
    'url'                     => env('CLOUDFLARE_R2_URL'),
    'endpoint'                => 'https://' . env('CLOUDFLARE_R2_ACCOUNT_ID') . '.r2.cloudflarestorage.com',
    'use_path_style_endpoint' => true,
    'throw'                   => true,
    'visibility'              => 'public',
],
```

**Variáveis de ambiente necessárias (`.env`):**

```dotenv
CLOUDFLARE_R2_ACCESS_KEY_ID=
CLOUDFLARE_R2_SECRET_ACCESS_KEY=
CLOUDFLARE_R2_ACCOUNT_ID=       # ex: abc123def456...  (dash.cloudflare.com → R2 → Overview)
CLOUDFLARE_R2_BUCKET=phpcomrapadura
CLOUDFLARE_R2_URL=               # ex: https://assets.phpcomrapadura.org
```

**Estrutura de paths no bucket:**

```
events/{event_id}/cover.{ext}    ← imagem de capa
events/{event_id}/logo.{ext}     ← logo do evento
```

Ao substituir uma imagem, o arquivo anterior é deletado do R2 antes de fazer o upload do novo.

**Pacote necessário:** `league/flysystem-aws-s3-v3` (já suportado pelo Laravel via driver `s3`).

### 2.5 Geração automática do slug

O slug é gerado a partir do `name` usando `Str::slug()`. Se o slug gerado já existir, um sufixo numérico é adicionado: `encontro-de-devs-2026`, `encontro-de-devs-2026-2`, etc.

A geração ocorre no `EventService::create()` — o frontend pode sugerir/editar o slug antes de salvar, mas nunca fica em branco.

---

## 3. Regras de negócio

| Regra | Detalhe |
|-------|---------|
| Acesso | `admin` e `colaborador` podem criar e editar |
| Publicar / Cancelar | Somente `admin` pode alterar status para `publicado` ou `cancelado` |
| Encerrar | Qualquer um dos dois pode marcar como `encerrado` |
| Excluir | Não é permitido excluir eventos — apenas cancelar |
| `is_accepting_talks` | Somente `admin` pode ativar/desativar |
| `ends_at` | Se informado, deve ser posterior a `starts_at` |
| Slug | Único na tabela; gerado automaticamente, mas editável |
| `cover_image` | Upload de arquivo para R2; formatos aceitos: `jpg`, `jpeg`, `png`, `webp`; máx. 5 MB |
| `logo` | Upload de arquivo para R2; formatos aceitos: `jpg`, `jpeg`, `png`, `webp`, `svg`; máx. 2 MB |
| Substituição de imagem | Ao enviar nova imagem, o arquivo anterior é deletado do R2 antes do upload |
| `created_by` | Preenchido com `Auth::id()` ao criar |

### 3.1 Transições de status permitidas

```
rascunho ──→ publicado   (somente admin)
rascunho ──→ cancelado   (somente admin)
publicado ──→ encerrado  (admin ou colaborador)
publicado ──→ cancelado  (somente admin)
encerrado ──→ (final — sem transições)
cancelado ──→ (final — sem transições)
```

---

## 4. Rotas

```php
// routes/web.php — dentro do grupo auth + EnsureAdminRole
Route::prefix('api/events')->name('events.')->group(function () {
    Route::get('/',                          [EventController::class, 'index'])->name('index');
    Route::post('/',                         [EventController::class, 'store'])->name('store');
    Route::get('/{event}',                   [EventController::class, 'show'])->name('show');
    Route::put('/{event}',                   [EventController::class, 'update'])->name('update');
    Route::patch('/{event}/status',          [EventController::class, 'updateStatus'])->name('updateStatus');
    Route::patch('/{event}/toggle-talks',    [EventController::class, 'toggleTalks'])->name('toggleTalks')->middleware('role:admin');
});

// Rota Vue (recarregar a SPA na URL /admin/events)
Route::get('/events',      fn () => view('admin'))->name('events');
Route::get('/events/{any}', fn () => view('admin'))->where('any', '.*');
```

> Não há rota `DELETE` — eventos não são removidos.

---

## 5. Controller

**Arquivo:** `app/Http/Controllers/Admin/EventController.php`

### 5.1 `index`

```
GET /admin/api/events?page=1&search=&status=&year=

Retorna JSON paginado:
{
  "data": [ ...events ],
  "meta": { "current_page", "last_page", "total", "per_page": 9 }
}
```

Filtros aceitos via query string:

| Parâmetro | Tipo | Comportamento |
|-----------|------|---------------|
| `search` | string | LIKE em `name` ou `slug` |
| `status` | string | Filtro exato pelo valor do enum |
| `year` | int | Filtra por ano de `starts_at` |
| `page` | int | Paginação — 9 itens por página |

Ordenação padrão: `starts_at DESC` (mais recentes primeiro).

Campos retornados por evento:

```json
{
  "id": 1,
  "name": "Encontro de Devs 2026",
  "slug": "encontro-de-devs-2026",
  "edition": 3,
  "description": "...",
  "starts_at": "2026-06-15T09:00:00Z",
  "ends_at": "2026-06-15T18:00:00Z",
  "location": "Centro de Convenções, Fortaleza — CE",
  "is_online": false,
  "status": "publicado",
  "is_accepting_talks": true,
  "max_attendees": 300,
  "created_by": 1,
  "created_at": "2026-05-01T10:00:00Z"
}
```

### 5.2 `store`

```
POST /admin/api/events
Content-Type: multipart/form-data
Body: { name, edition?, description?, starts_at, ends_at?, location?, is_online, max_attendees?, slug?,
        cover_image? (arquivo), logo? (arquivo) }

→ valida via StoreEventRequest
→ gera slug a partir do name (se não informado)
→ garante unicidade do slug
→ faz upload das imagens para R2 (se fornecidas) e armazena as URLs
→ cria evento com status = rascunho e created_by = Auth::id()
→ retorna 201 + evento criado
```

### 5.3 `show`

```
GET /admin/api/events/{event}
→ retorna evento pelo ID
→ 404 se não encontrado
```

### 5.4 `update`

```
PUT /admin/api/events/{event}
Content-Type: multipart/form-data
Body: { name, slug, edition?, description?, starts_at, ends_at?, location?, is_online, max_attendees?,
        cover_image? (arquivo), logo? (arquivo) }

→ valida via UpdateEventRequest
→ se name mudou e slug não foi informado, regenera o slug
→ garante unicidade do slug ignorando o próprio registro
→ se nova cover_image enviada: deleta a anterior do R2, faz upload da nova
→ se novo logo enviado: deleta o anterior do R2, faz upload do novo
→ retorna 200 + evento atualizado

Restrições:
→ eventos cancelados ou encerrados não podem ser editados (422)
```

### 5.5 `updateStatus`

```
PATCH /admin/api/events/{event}/status
Body: { status: 'publicado' | 'encerrado' | 'cancelado' }

→ valida a transição conforme tabela de regras (seção 3.1)
→ publicado/cancelado: somente admin (403 para colaborador)
→ encerrado: admin ou colaborador
→ retorna 200 + { status: 'novo_status' }
```

### 5.6 `toggleTalks`

```
PATCH /admin/api/events/{event}/toggle-talks
→ middleware role:admin — colaborador recebe 403
→ somente para eventos com status publicado (422 se rascunho/encerrado/cancelado)
→ inverte is_accepting_talks
→ retorna 200 + { is_accepting_talks: bool }
```

---

## 6. Requests

### 6.1 `StoreEventRequest`

**Arquivo:** `app/Http/Requests/Admin/Events/StoreEventRequest.php`

```php
public function rules(): array
{
    return [
        'name'          => ['required', 'string', 'max:255'],
        'slug'          => ['nullable', 'string', 'max:255', 'unique:events,slug', 'regex:/^[a-z0-9-]+$/'],
        'edition'       => ['nullable', 'integer', 'min:1', 'max:999'],
        'description'   => ['nullable', 'string'],
        'starts_at'     => ['required', 'date'],
        'ends_at'       => ['nullable', 'date', 'after:starts_at'],
        'location'      => ['nullable', 'string', 'max:255'],
        'is_online'     => ['boolean'],
        'max_attendees' => ['nullable', 'integer', 'min:1'],
        'cover_image'   => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        'logo'          => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
    ];
}

public function messages(): array
{
    return [
        'name.required'        => 'O nome do evento é obrigatório.',
        'slug.unique'          => 'Este slug já está em uso.',
        'slug.regex'           => 'O slug deve conter apenas letras minúsculas, números e hífens.',
        'starts_at.required'   => 'A data de início é obrigatória.',
        'starts_at.date'       => 'Data de início inválida.',
        'ends_at.after'        => 'A data de encerramento deve ser posterior ao início.',
        'max_attendees.min'    => 'A capacidade deve ser pelo menos 1.',
        'cover_image.mimes'    => 'A imagem de capa deve ser jpg, jpeg, png ou webp.',
        'cover_image.max'      => 'A imagem de capa deve ter no máximo 5 MB.',
        'logo.mimes'           => 'O logo deve ser jpg, jpeg, png, webp ou svg.',
        'logo.max'             => 'O logo deve ter no máximo 2 MB.',
    ];
}
```

### 6.2 `UpdateEventRequest`

**Arquivo:** `app/Http/Requests/Admin/Events/UpdateEventRequest.php`

```php
public function rules(): array
{
    return [
        'name'          => ['required', 'string', 'max:255'],
        'slug'          => ['nullable', 'string', 'max:255',
                            Rule::unique('events', 'slug')->ignore($this->route('event')),
                            'regex:/^[a-z0-9-]+$/'],
        'edition'       => ['nullable', 'integer', 'min:1', 'max:999'],
        'description'   => ['nullable', 'string'],
        'starts_at'     => ['required', 'date'],
        'ends_at'       => ['nullable', 'date', 'after:starts_at'],
        'location'      => ['nullable', 'string', 'max:255'],
        'is_online'     => ['boolean'],
        'max_attendees' => ['nullable', 'integer', 'min:1'],
        'cover_image'   => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        'logo'          => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
    ];
}
```

---

## 7. Service

**Arquivo:** `app/Services/EventService.php`

```php
public function list(array $filters): LengthAwarePaginator
public function create(array $data, ?UploadedFile $coverImage, ?UploadedFile $logo, int $createdBy): Event
public function update(Event $event, array $data, ?UploadedFile $coverImage, ?UploadedFile $logo): Event
public function updateStatus(Event $event, string $newStatus, User $actor): Event
public function toggleTalks(Event $event): Event
public function generateSlug(string $name, ?int $ignoreId = null): string
public function uploadImage(UploadedFile $file, string $path): string
public function deleteImage(?string $url): void
```

Responsabilidades:

- `list`: aplica filtros, ordena por `starts_at DESC`, retorna 9/página
- `create`: gera slug se não informado, preenche `created_by`, status inicial = `rascunho`, faz upload das imagens se fornecidas
- `update`: regenera slug se name mudou e slug não foi enviado; bloqueia edição de `cancelado`/`encerrado`; substitui imagens se novas foram enviadas (deletando as anteriores do R2)
- `updateStatus`: valida a transição; verifica permissão do ator (admin x colaborador); lança `AccessDeniedHttpException` ou `InvalidArgumentException` conforme o caso
- `generateSlug`: `Str::slug($name)` → verifica unicidade → adiciona sufixo `-2`, `-3`… se necessário
- `uploadImage`: faz upload para o disco `r2` no path informado com visibilidade pública; retorna a URL pública
- `deleteImage`: extrai o path da URL e deleta o arquivo do R2; ignora silenciosamente se URL for `null`

**Path de upload:**

```php
// Capa: events/{event_id}/cover.{ext}
// Logo: events/{event_id}/logo.{ext}
$path = "events/{$event->id}/cover.{$file->extension()}";
Storage::disk('r2')->putFileAs('', $file, $path, 'public');
$url = Storage::disk('r2')->url($path);
```

---

## 8. Interface

### 8.1 Item de menu na sidebar

```js
// resources/js/components/AppSidebar.vue — adicionar ao menu
{ icon: 'calendar', label: 'Eventos', route: 'admin.events', roles: ['admin', 'colaborador'] }
```

Adicionar ao `router/admin.js`:
```js
{ path: 'events', name: 'admin.events', component: () => import('@/views/admin/Events.vue') }
```

### 8.2 Listagem (`/admin/events`)

**Cabeçalho:**
```
[Título: "Eventos"]   [Botão "Novo evento"]
```

**Barra de filtros:**
```
[🔍 Buscar por nome ou slug]  [Dropdown: Todos os status]  [Dropdown: Ano]
```

**Grid de cards** — `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4`

Cada card exibe:
```
[Badge de status]                           [Ações: ▾]
[Nome do evento]
[Nº edição — se houver]
[📅 Data início — Data fim ou "Em aberto"]
[📍 Local ou "🌐 Online"]
[👥 Capacidade: 300 / sem limite]
─────────────────────────────────
[Toggle: Aceitando palestras]   (visível apenas se publicado; editável só por admin)
```

O menu de ações (▾) exibe:
- Editar (admin + colaborador, bloqueado se cancelado/encerrado)
- Alterar status → submenu com transições válidas (ver seção 3.1)

### 8.3 Cores dos badges de status

| Status | Cor |
|--------|-----|
| `rascunho` | cinza — `bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400` |
| `publicado` | verde — `--color-success` |
| `encerrado` | azul — `--color-primary` |
| `cancelado` | vermelho — `--color-danger` |

### 8.4 Modal de criar/editar

```
[Título: "Novo evento" / "Editar evento"]

[Campo: Nome do evento *]
[Campo: Slug — preenchido automaticamente, editável]
[Campo: Edição — número inteiro opcional]
[Campo: Descrição — textarea opcional]

[Campo: Data/hora de início *]
[Campo: Data/hora de encerramento — opcional]

[Toggle: Evento online]
[Campo: Local — desabilitado se online]

[Campo: Capacidade máxima — opcional]

[Upload: Imagem de capa — jpg/jpeg/png/webp, máx. 5 MB]
  → preview da imagem atual (se existir) com botão "Remover"
  → ao selecionar novo arquivo, exibe preview local antes de salvar

[Upload: Logo do evento — jpg/jpeg/png/webp/svg, máx. 2 MB]
  → preview da imagem atual (se existir) com botão "Remover"
  → ao selecionar novo arquivo, exibe preview local antes de salvar

[Cancelar]  [Salvar]
```

> Campos `starts_at` e `ends_at` usam `<input type="datetime-local">`.
> Slug é atualizado automaticamente ao digitar o nome (via `watch`), mas pode ser editado manualmente.
> O formulário deve ser enviado como `multipart/form-data` quando houver arquivos: usar `FormData` no axios (`headers: { 'Content-Type': 'multipart/form-data' }`).
> Para edição via PUT com FormData, adicionar `_method: 'PUT'` no body e usar `POST` no axios (Laravel method spoofing).

### 8.5 Arquivos Vue

| Arquivo | Descrição |
|---------|-----------|
| `resources/js/views/admin/Events.vue` | Listagem com filtros e grid |
| `resources/js/components/EventModal.vue` | Modal criar/editar |

---

## 9. Vue Router

```js
// resources/js/router/admin.js — dentro dos children do AdminLayout
{ path: 'events', name: 'admin.events', component: () => import('@/views/admin/Events.vue') }
```

---

## 10. Arquivos a criar

| Arquivo | Tipo |
|---------|------|
| `database/migrations/*_create_events_table.php` | Migration |
| `app/Models/Event.php` | Model |
| `app/Http/Controllers/Admin/EventController.php` | Controller |
| `app/Http/Requests/Admin/Events/StoreEventRequest.php` | Form Request |
| `app/Http/Requests/Admin/Events/UpdateEventRequest.php` | Form Request |
| `app/Services/EventService.php` | Service |
| `resources/js/views/admin/Events.vue` | View Vue |
| `resources/js/components/EventModal.vue` | Componente Vue |
| `tests/Feature/Admin/Events/EventCrudTest.php` | Testes |

---

## 11. Critérios de aceite

### Acesso
- [ ] Guest recebe `401` em qualquer rota `/admin/api/events`
- [ ] `colaborador` acessa listagem, cria e edita eventos
- [ ] `colaborador` recebe `403` ao tentar publicar ou cancelar
- [ ] `colaborador` recebe `403` ao chamar `/toggle-talks`

### Listagem
- [ ] Retorna 9 eventos por página, ordenados por `starts_at DESC`
- [ ] Filtro `search` filtra por nome e slug
- [ ] Filtro `status` retorna apenas eventos do status informado
- [ ] Filtro `year` retorna apenas eventos cujo `starts_at` é do ano indicado

### Criação
- [ ] Cria evento com status `rascunho`
- [ ] Slug é gerado automaticamente a partir do nome
- [ ] Slug informado manualmente é persistido
- [ ] Slug duplicado retorna `422`
- [ ] `ends_at` anterior a `starts_at` retorna `422`
- [ ] `created_by` é preenchido com o ID do usuário logado

### Edição
- [ ] Atualiza os campos editáveis
- [ ] Slug é regenerado se o nome mudou e o slug não foi enviado
- [ ] Slug alterado para um já existente retorna `422`
- [ ] Editar evento `cancelado` retorna `422`
- [ ] Editar evento `encerrado` retorna `422`

### Transições de status
- [ ] `rascunho → publicado` funciona para admin
- [ ] `rascunho → publicado` retorna `403` para colaborador
- [ ] `publicado → encerrado` funciona para admin e colaborador
- [ ] `publicado → cancelado` funciona para admin
- [ ] `publicado → cancelado` retorna `403` para colaborador
- [ ] Transição inválida (ex: `encerrado → publicado`) retorna `422`

### Toggle de palestras
- [ ] Admin pode ativar/desativar `is_accepting_talks` em evento publicado
- [ ] `colaborador` recebe `403`
- [ ] Ativar em evento não publicado retorna `422`

### Imagens (R2)
- [ ] Criar evento com `cover_image` faz upload para R2 e persiste a URL
- [ ] Criar evento com `logo` faz upload para R2 e persiste a URL
- [ ] Criar evento sem imagens persiste `null` nos campos
- [ ] Atualizar evento com nova `cover_image` deleta a anterior do R2 e persiste nova URL
- [ ] Atualizar evento com novo `logo` deleta o anterior do R2 e persiste nova URL
- [ ] Arquivo com formato inválido (ex: `.pdf`) retorna `422`
- [ ] Arquivo acima do limite (capa > 5 MB, logo > 2 MB) retorna `422`

---

## 12. Bateria de testes

**Arquivo:** `tests/Feature/Admin/Events/EventCrudTest.php`

```php
<?php

use App\Models\Event;
use App\Models\User;

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
    Event::factory()->create(['name' => 'Encontro de Devs 2026']);
    Event::factory()->create(['name' => 'Outro Evento']);

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
    Event::factory()->create(['starts_at' => '2026-06-15 09:00:00']);
    Event::factory()->create(['starts_at' => '2025-03-10 09:00:00']);

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
            'name'      => 'PHP com Rapadura 2026',
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
            'name'      => 'Encontro de Devs 2026',
            'starts_at' => '2026-06-15 09:00:00',
            'is_online' => false,
        ])
        ->assertCreated()
        ->assertJsonPath('slug', 'encontro-de-devs-2026');
});

it('slug informado manualmente é persistido', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/events', [
            'name'      => 'Evento Teste',
            'slug'      => 'meu-slug-customizado',
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
            'name'      => 'Novo Evento',
            'slug'      => 'slug-existente',
            'starts_at' => '2026-06-15 09:00:00',
            'is_online' => false,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});

it('retorna 422 quando ends_at é anterior a starts_at', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/events', [
            'name'      => 'Evento Inválido',
            'starts_at' => '2026-06-15 09:00:00',
            'ends_at'   => '2026-06-14 09:00:00',
            'is_online' => false,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ends_at']);
});

it('colaborador pode criar evento', function () {
    $this->actingAs(User::factory()->colaborador()->create())
        ->postJson('/admin/api/events', [
            'name'      => 'Evento do Colaborador',
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
            'name'      => 'Nome Atualizado',
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
            'name'      => 'Nome Completamente Diferente',
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
            'name'      => $event->name,
            'slug'      => 'meu-evento',
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
            'name'      => 'Tentativa',
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
            'name'      => 'Tentativa',
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
    $file  = UploadedFile::fake()->image('capa.jpg', 1280, 720)->size(500);

    $response = $this->actingAs($admin)
        ->post('/admin/api/events', [
            'name'        => 'Evento com Capa',
            'starts_at'   => '2026-06-15 09:00:00',
            'is_online'   => false,
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
    $file  = UploadedFile::fake()->image('logo.png', 400, 400)->size(200);

    $response = $this->actingAs($admin)
        ->post('/admin/api/events', [
            'name'      => 'Evento com Logo',
            'starts_at' => '2026-07-10 09:00:00',
            'is_online' => false,
            'logo'      => $file,
        ])
        ->assertCreated();

    expect($response->json('logo'))->not->toBeNull();
    Storage::disk('r2')->assertExists("events/{$response->json('id')}/logo.png");
});

it('cria evento sem imagens com cover_image e logo nulos', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/events', [
            'name'      => 'Evento Sem Imagens',
            'starts_at' => '2026-08-01 09:00:00',
            'is_online' => false,
        ])
        ->assertCreated()
        ->assertJsonPath('cover_image', null)
        ->assertJsonPath('logo', null);
});

it('atualizar cover_image deleta a anterior do R2 e persiste nova URL', function () {
    Storage::fake('r2');
    $admin    = User::factory()->admin()->create();
    $oldFile  = UploadedFile::fake()->image('antiga.jpg')->size(100);
    $newFile  = UploadedFile::fake()->image('nova.jpg')->size(100);

    $event = $this->actingAs($admin)
        ->post('/admin/api/events', [
            'name'        => 'Evento Troca Capa',
            'starts_at'   => '2026-09-01 09:00:00',
            'is_online'   => false,
            'cover_image' => $oldFile,
        ])
        ->assertCreated()
        ->json();

    $oldPath = "events/{$event['id']}/cover.jpg";
    Storage::disk('r2')->assertExists($oldPath);

    $this->actingAs($admin)
        ->post("/admin/api/events/{$event['id']}", [
            '_method'     => 'PUT',
            'name'        => $event['name'],
            'starts_at'   => $event['starts_at'],
            'is_online'   => false,
            'cover_image' => $newFile,
        ])
        ->assertOk();

    Storage::disk('r2')->assertMissing($oldPath);
    Storage::disk('r2')->assertExists("events/{$event['id']}/cover.jpg");
});

it('retorna 422 ao enviar cover_image com formato inválido', function () {
    $admin = User::factory()->admin()->create();
    $file  = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

    $this->actingAs($admin)
        ->post('/admin/api/events', [
            'name'        => 'Evento Inválido',
            'starts_at'   => '2026-06-15 09:00:00',
            'is_online'   => false,
            'cover_image' => $file,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cover_image']);
});

it('retorna 422 ao enviar cover_image acima de 5 MB', function () {
    $admin = User::factory()->admin()->create();
    $file  = UploadedFile::fake()->image('grande.jpg')->size(5121);

    $this->actingAs($admin)
        ->post('/admin/api/events', [
            'name'        => 'Evento Grande',
            'starts_at'   => '2026-06-15 09:00:00',
            'is_online'   => false,
            'cover_image' => $file,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cover_image']);
});
```

---

## 13. Factory

**Arquivo:** `database/factories/EventFactory.php`

```php
public function definition(): array
{
    return [
        'name'               => fake('pt_BR')->sentence(3),
        'slug'               => fn (array $attrs) => Str::slug($attrs['name']),
        'edition'            => fake()->numberBetween(1, 10),
        'description'        => fake('pt_BR')->paragraph(),
        'starts_at'          => fake()->dateTimeBetween('+1 month', '+6 months'),
        'ends_at'            => fn (array $attrs) => (clone $attrs['starts_at'])->modify('+8 hours'),
        'location'           => fake('pt_BR')->city() . ' — CE',
        'is_online'          => false,
        'status'             => 'rascunho',
        'is_accepting_talks' => false,
        'max_attendees'      => fake()->randomElement([null, 100, 200, 300, 500]),
        'cover_image'        => null,
        'logo'               => null,
        'created_by'         => User::factory(),
    ];
}

public function rascunho(): static
{
    return $this->state(['status' => 'rascunho']);
}

public function publicado(): static
{
    return $this->state(['status' => 'publicado']);
}

public function encerrado(): static
{
    return $this->state(['status' => 'encerrado']);
}

public function cancelado(): static
{
    return $this->state(['status' => 'cancelado']);
}

public function online(): static
{
    return $this->state(['is_online' => true, 'location' => null]);
}
```
