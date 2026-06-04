# Spec — Módulo CFP (Call for Papers)

**Status:** 🔲 A implementar
**Módulo:** Admin → Eventos → CFP
**Depende de:** `.claude/specs/admin/events-details.md`

---

## 1. Visão geral

Gerenciamento do Call for Papers de um evento. O admin configura o período e as regras de submissão; os palestrantes submetem palestras pelo fluxo público `/cfp` (spec separada). Aqui o admin visualiza e avalia as propostas recebidas.

A página `/admin/events/{id}/cfp` tem duas seções:
1. **Configuração do CFP** — período, guia do palestrante, limite de propostas
2. **Palestras submetidas** — lista com filtros e modal de avaliação

---

## 2. Models

### 2.1 `EventCfp`

**Arquivo:** `app/Models/EventCfp.php`

Um evento tem **no máximo um** registro de CFP.

#### Campos

| Campo | Tipo | Observação |
|-------|------|-----------|
| `id` | `bigIncrements` | PK |
| `event_id` | `foreignId → events.id` | Único — `cascadeOnDelete` |
| `opens_at` | `datetime` | Início do período de submissão |
| `closes_at` | `datetime` | Fim do período (`after:opens_at`) |
| `speaker_guide` | `text` | Nullable — guia para palestrantes (markdown) |
| `max_talks_per_speaker` | `tinyInteger unsigned` | Nullable — null = ilimitado |
| `created_by` | `foreignId → users.id` | `nullOnDelete` |
| `timestamps` | — | `created_at`, `updated_at` |

#### Migration

```php
Schema::create('event_cfp', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
    $table->datetime('opens_at');
    $table->datetime('closes_at');
    $table->text('speaker_guide')->nullable();
    $table->tinyInteger('max_talks_per_speaker')->unsigned()->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

#### Model

```php
protected $fillable = [
    'event_id', 'opens_at', 'closes_at',
    'speaker_guide', 'max_talks_per_speaker', 'created_by',
];

protected function casts(): array
{
    return ['opens_at' => 'datetime', 'closes_at' => 'datetime'];
}

public function event(): BelongsTo
{
    return $this->belongsTo(Event::class);
}

public function talks(): HasMany
{
    return $this->hasMany(Talk::class, 'event_id', 'event_id');
}

public function status(): string
{
    $now = now();
    if ($now->lt($this->opens_at))  return 'aguardando';
    if ($now->lte($this->closes_at)) return 'aberto';
    return 'encerrado';
}
```

---

### 2.2 `Speaker`

**Arquivo:** `app/Models/Speaker.php`

Perfil do palestrante. Criado quando o usuário (role `palestrante`) completa seu perfil no fluxo público de CFP. A tabela é criada nesta spec; o fluxo de criação é implementado na spec do CFP público.

#### Campos

| Campo | Tipo | Observação |
|-------|------|-----------|
| `id` | `bigIncrements` | PK |
| `user_id` | `foreignId → users.id` | Único — `cascadeOnDelete` |
| `bio` | `text` | Nullable |
| `company` | `string(255)` | Nullable |
| `avatar_url` | `string(500)` | Nullable — URL pública no R2 |
| `website` | `string(255)` | Nullable |
| `twitter` | `string(100)` | Nullable — handle sem `@` |
| `github` | `string(100)` | Nullable |
| `linkedin` | `string(255)` | Nullable — URL do perfil |
| `timestamps` | — | `created_at`, `updated_at` |

#### Migration

```php
Schema::create('speakers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
    $table->text('bio')->nullable();
    $table->string('company')->nullable();
    $table->string('avatar_url', 500)->nullable();
    $table->string('website')->nullable();
    $table->string('twitter', 100)->nullable();
    $table->string('github', 100)->nullable();
    $table->string('linkedin')->nullable();
    $table->timestamps();
});
```

#### Model

```php
protected $fillable = [
    'user_id', 'bio', 'company', 'avatar_url',
    'website', 'twitter', 'github', 'linkedin',
];

public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

public function talks(): HasMany
{
    return $this->hasMany(Talk::class);
}
```

---

### 2.3 `Talk`

**Arquivo:** `app/Models/Talk.php`

Proposta de palestra submetida por um palestrante para um evento.

#### Campos

| Campo | Tipo | Observação |
|-------|------|-----------|
| `id` | `bigIncrements` | PK |
| `event_id` | `foreignId → events.id` | `cascadeOnDelete` |
| `speaker_id` | `foreignId → speakers.id` | `cascadeOnDelete` |
| `title` | `string(255)` | Título da palestra |
| `abstract` | `text` | Resumo / descrição |
| `duration` | `enum('25','50')` | Duração em minutos |
| `level` | `enum('iniciante','intermediario','avancado')` | Nível do conteúdo |
| `status` | `enum` | `submetida`, `em_analise`, `aprovada`, `rejeitada`, `cancelada` — default `submetida` |
| `feedback` | `text` | Nullable — comentário do organizador |
| `submitted_at` | `datetime` | Preenchido ao criar |
| `timestamps` | — | `created_at`, `updated_at` |

#### Migration

```php
Schema::create('talks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->foreignId('speaker_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('abstract');
    $table->enum('duration', ['25', '50']);
    $table->enum('level', ['iniciante', 'intermediario', 'avancado']);
    $table->enum('status', ['submetida', 'em_analise', 'aprovada', 'rejeitada', 'cancelada'])
          ->default('submetida');
    $table->text('feedback')->nullable();
    $table->datetime('submitted_at');
    $table->timestamps();
});
```

#### Model

```php
protected $fillable = [
    'event_id', 'speaker_id', 'title', 'abstract',
    'duration', 'level', 'status', 'feedback', 'submitted_at',
];

protected function casts(): array
{
    return ['submitted_at' => 'datetime'];
}

public function event(): BelongsTo   { return $this->belongsTo(Event::class); }
public function speaker(): BelongsTo { return $this->belongsTo(Speaker::class); }
```

---

## 3. Relações a adicionar no Model `Event`

```php
// app/Models/Event.php
public function cfp(): HasOne   { return $this->hasOne(EventCfp::class); }
public function talks(): HasMany { return $this->hasMany(Talk::class); }
```

---

## 4. Regras de negócio

### 4.1 Acesso

| Ação | admin | colaborador |
|------|-------|------------|
| Ver configuração e palestras | ✅ | ✅ |
| Criar / editar CFP | ✅ | ✅ |
| Alterar status de palestra | ✅ | ✅ |

### 4.2 CFP

| Regra | Detalhe |
|-------|---------|
| Unicidade | Um evento tem no máximo um CFP — segundo `POST` retorna `422` |
| `closes_at` | Deve ser posterior a `opens_at` |
| Edição | O CFP pode ser editado a qualquer momento, mesmo com palestras |
| `max_talks_per_speaker` | `null` = sem limite; validado ao submeter (fora do escopo desta spec) |

### 4.3 Transições de status das palestras

```
submetida  ──→ em_analise              (admin ou colaborador)
submetida  ──→ cancelada               (admin ou colaborador)
em_analise ──→ aprovada                (admin ou colaborador)
em_analise ──→ rejeitada  [+ feedback obrigatório]
em_analise ──→ cancelada               (admin ou colaborador)
aprovada   ──→ cancelada               (admin ou colaborador)
rejeitada  ──→ em_analise              (admin ou colaborador) ← reabre para revisão
cancelada  ──→ (estado final)
```

`feedback` é **obrigatório ao rejeitar** e opcional nos demais casos. Transição inválida retorna `422`.

---

## 5. Rotas

```php
// routes/web.php — dentro do grupo auth + EnsureAdminRole

// API — CFP
Route::prefix('api/events/{event}')->name('events.')->group(function () {
    Route::get('/cfp',  [CfpController::class, 'show'])->name('cfp.show');
    Route::post('/cfp', [CfpController::class, 'store'])->name('cfp.store');
    Route::put('/cfp',  [CfpController::class, 'update'])->name('cfp.update');

    Route::get('/talks',                          [TalkController::class, 'index'])->name('talks.index');
    Route::get('/talks/{talk}',                   [TalkController::class, 'show'])->name('talks.show');
    Route::patch('/talks/{talk}/status',          [TalkController::class, 'updateStatus'])->name('talks.updateStatus');
});
```

---

## 6. Controllers

### 6.1 `CfpController`

**Arquivo:** `app/Http/Controllers/Admin/CfpController.php`

```
GET /admin/api/events/{event}/cfp
→ Retorna o CFP do evento (ou null) + contagem de palestras por status:
  {
    "data": {
      "id": 1, "event_id": 5,
      "opens_at": "...", "closes_at": "...",
      "speaker_guide": null, "max_talks_per_speaker": null,
      "status": "aberto",
      "talks_count": { "total": 12, "submetida": 5, "em_analise": 3,
                       "aprovada": 3, "rejeitada": 1, "cancelada": 0 }
    }
  }
  (data: null se não configurado)

POST /admin/api/events/{event}/cfp
→ Valida via StoreCfpRequest
→ Retorna 201 + registro criado
→ 422 se CFP já existe para o evento

PUT /admin/api/events/{event}/cfp
→ Valida via UpdateCfpRequest
→ Retorna 200 + registro atualizado
→ 404 se CFP não existe
```

### 6.2 `TalkController`

**Arquivo:** `app/Http/Controllers/Admin/TalkController.php`

```
GET /admin/api/events/{event}/talks?status=&search=&page=
→ Lista paginada (9/página) — ordenação: submitted_at DESC
→ Filtros: status (exato), search (title LIKE)
→ Cada item inclui speaker com { id, name, email, company, avatar_url }

GET /admin/api/events/{event}/talks/{talk}
→ Retorna palestra completa com speaker completo
→ 404 se a palestra não pertence ao evento

PATCH /admin/api/events/{event}/talks/{talk}/status
Body: { status: string, feedback?: string }
→ Valida transição (seção 4.3)
→ Retorna 200 + { status, feedback }
→ 404 se a palestra não pertence ao evento
```

---

## 7. Requests

### 7.1 `StoreCfpRequest`

**Arquivo:** `app/Http/Requests/Admin/Cfp/StoreCfpRequest.php`

```php
public function rules(): array
{
    return [
        'opens_at'              => ['required', 'date'],
        'closes_at'             => ['required', 'date', 'after:opens_at'],
        'speaker_guide'         => ['nullable', 'string'],
        'max_talks_per_speaker' => ['nullable', 'integer', 'min:1', 'max:10'],
    ];
}

public function messages(): array
{
    return [
        'opens_at.required'  => 'A data de abertura é obrigatória.',
        'closes_at.required' => 'A data de encerramento é obrigatória.',
        'closes_at.after'    => 'O encerramento deve ser posterior à abertura.',
    ];
}
```

### 7.2 `UpdateCfpRequest`

Mesmas regras de `StoreCfpRequest` — arquivo separado em `app/Http/Requests/Admin/Cfp/UpdateCfpRequest.php`.

---

## 8. Services

### 8.1 `CfpService`

**Arquivo:** `app/Services/CfpService.php`

```php
public function getWithStats(Event $event): ?array
// Retorna ['cfp' => EventCfp, 'talks_count' => [...]] ou null

public function create(Event $event, array $data, int $createdBy): EventCfp
// Lança InvalidArgumentException se CFP já existe

public function update(EventCfp $cfp, array $data): EventCfp
```

### 8.2 `TalkService`

**Arquivo:** `app/Services/TalkService.php`

```php
/** @var array<string, list<string>> */
private const TRANSITIONS = [
    'submetida'  => ['em_analise', 'cancelada'],
    'em_analise' => ['aprovada', 'rejeitada', 'cancelada'],
    'aprovada'   => ['cancelada'],
    'rejeitada'  => ['em_analise'],
    'cancelada'  => [],
];

public function list(Event $event, array $filters): LengthAwarePaginator
// eager loads speaker.user; filtra por status e search; pagina 9/página

public function updateStatus(Talk $talk, string $newStatus, ?string $feedback): Talk
// Valida transição; exige feedback se rejeitada; lança InvalidArgumentException
```

---

## 9. Interface Vue

### 9.1 `EventCfp.vue` — página do módulo

**Arquivo:** `resources/js/views/admin/EventCfp.vue`

```
← Voltar para evento

── Configuração do CFP ──────────────────────────────────────────

[Se não configurado]
  "Nenhuma configuração de CFP para este evento."
  [Botão "Configurar agora"]

[Se configurado]
  Período: 15/06/2026 às 09:00 → 31/07/2026 às 23:59
  Status: [Badge Aberto / Aguardando / Encerrado]
  Limite por palestrante: 2 propostas (ou "Sem limite")
  [Botão "Editar"]

  [Se speaker_guide preenchido]
    "Guia do palestrante" ▼  (colapsável)
    [Texto em markdown renderizado]

── Palestras submetidas ─────────────────────────────────────────

[Filtro: Todos os status ▼]  [🔍 Buscar por título]

[Se vazio]
  "Nenhuma palestra submetida ainda."

[Grid de cards — 1/2/3 colunas]
  [Avatar]  Nome do palestrante  [Empresa]
  [Título da palestra]
  [Badge nível]  [Badge duração]  [Badge status]
  [Botão "Avaliar"]
```

### 9.2 `CfpModal.vue` — configurar/editar CFP

**Arquivo:** `resources/js/components/CfpModal.vue`

Campos:
- Data/hora de abertura `*`
- Data/hora de encerramento `*`
- Guia do palestrante (textarea, opcional)
- Limite de propostas por palestrante (number, opcional)

Erros de validação exibidos por campo (padrão dos outros modais do projeto).

### 9.3 `TalkReviewModal.vue` — avaliar palestra

**Arquivo:** `resources/js/components/TalkReviewModal.vue`

```
[Título da palestra]
[Resumo — expandido]
─────────────────────────────────
[Avatar]  [Nome]  [Empresa]
[Bio — colapsável se longa]
[Links: GitHub, Twitter, LinkedIn, Website]
─────────────────────────────────
[Campo: Feedback  (obrigatório se rejeitar)]
─────────────────────────────────
Ações disponíveis conforme status atual:
  submetida  → [Colocar em análise]  [Cancelar]
  em_analise → [Aprovar]  [Rejeitar]  [Cancelar]
  aprovada   → [Cancelar]
  rejeitada  → [Reabrir para análise]
  cancelada  → (sem ações)
```

---

## 10. Arquivos a criar

**Novos:**

| Arquivo | Tipo |
|---------|------|
| `database/migrations/*_create_event_cfp_table.php` | Migration |
| `database/migrations/*_create_speakers_table.php` | Migration |
| `database/migrations/*_create_talks_table.php` | Migration |
| `app/Models/EventCfp.php` | Model |
| `app/Models/Speaker.php` | Model |
| `app/Models/Talk.php` | Model |
| `app/Http/Controllers/Admin/CfpController.php` | Controller |
| `app/Http/Controllers/Admin/TalkController.php` | Controller |
| `app/Http/Requests/Admin/Cfp/StoreCfpRequest.php` | Form Request |
| `app/Http/Requests/Admin/Cfp/UpdateCfpRequest.php` | Form Request |
| `app/Services/CfpService.php` | Service |
| `app/Services/TalkService.php` | Service |
| `database/factories/EventCfpFactory.php` | Factory |
| `database/factories/SpeakerFactory.php` | Factory |
| `database/factories/TalkFactory.php` | Factory |
| `resources/js/views/admin/EventCfp.vue` | View Vue |
| `resources/js/components/CfpModal.vue` | Componente Vue |
| `resources/js/components/TalkReviewModal.vue` | Componente Vue |
| `tests/Feature/Admin/Cfp/CfpTest.php` | Testes |
| `tests/Feature/Admin/Talks/TalkTest.php` | Testes |

**Modificar:**

| Arquivo | O que muda |
|---------|-----------|
| `app/Models/Event.php` | Adicionar relações `cfp()` e `talks()` |
| `routes/web.php` | Adicionar rotas de API do CFP e Talks |

---

## 11. Critérios de aceite

### CFP — configuração
- [ ] `GET /cfp` retorna `null` quando não configurado
- [ ] `GET /cfp` retorna config + contagem de palestras quando configurado
- [ ] Admin e colaborador criam CFP com campos obrigatórios
- [ ] `closes_at` anterior a `opens_at` retorna `422`
- [ ] Segundo `POST` no mesmo evento retorna `422`
- [ ] Admin e colaborador editam CFP existente
- [ ] `PUT` em evento sem CFP retorna `404`

### Palestras — listagem
- [ ] Lista retorna 9 por página com speaker (eager loaded)
- [ ] Filtro por status funciona
- [ ] Busca por título funciona (LIKE, case-insensitive)
- [ ] Palestra de outro evento retorna `404`

### Palestras — transições
- [ ] `submetida → em_analise` funciona
- [ ] `em_analise → aprovada` funciona
- [ ] `em_analise → rejeitada` com feedback funciona
- [ ] `em_analise → rejeitada` sem feedback retorna `422`
- [ ] `rejeitada → em_analise` (reabertura) funciona
- [ ] Transição inválida (ex: `cancelada → aprovada`) retorna `422`
- [ ] Palestra de outro evento retorna `404`

---

## 12. Bateria de testes

### `tests/Feature/Admin/Cfp/CfpTest.php`

```php
<?php

use App\Models\Event;
use App\Models\EventCfp;
use App\Models\User;

// ─── Acesso ───────────────────────────────────────────────────────────────────

it('guest recebe 401 ao acessar CFP', function () {
    $event = Event::factory()->create();
    $this->getJson("/admin/api/events/{$event->id}/cfp")->assertUnauthorized();
});

it('colaborador acessa CFP', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->colaborador()->create())
        ->getJson("/admin/api/events/{$event->id}/cfp")
        ->assertOk();
});

// ─── Show ─────────────────────────────────────────────────────────────────────

it('retorna null quando CFP não está configurado', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/cfp")
        ->assertOk()
        ->assertJson(['data' => null]);
});

it('retorna CFP com contagem de palestras por status', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    EventCfp::factory()->create(['event_id' => $event->id]);

    $this->actingAs($admin)
        ->getJson("/admin/api/events/{$event->id}/cfp")
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'opens_at', 'closes_at', 'status', 'talks_count']]);
});

// ─── Store ────────────────────────────────────────────────────────────────────

it('admin cria CFP para um evento', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/cfp", [
            'opens_at'  => '2026-06-15 09:00:00',
            'closes_at' => '2026-07-31 23:59:59',
        ])
        ->assertCreated()
        ->assertJsonPath('event_id', $event->id);
});

it('retorna 422 ao criar segundo CFP para o mesmo evento', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    EventCfp::factory()->create(['event_id' => $event->id]);

    $this->actingAs($admin)
        ->postJson("/admin/api/events/{$event->id}/cfp", [
            'opens_at'  => '2026-08-01 09:00:00',
            'closes_at' => '2026-08-31 23:59:59',
        ])
        ->assertUnprocessable();
});

it('retorna 422 quando closes_at é anterior a opens_at', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/cfp", [
            'opens_at'  => '2026-07-31 09:00:00',
            'closes_at' => '2026-06-15 09:00:00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['closes_at']);
});

// ─── Update ───────────────────────────────────────────────────────────────────

it('admin edita CFP existente', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    $cfp   = EventCfp::factory()->create(['event_id' => $event->id]);

    $this->actingAs($admin)
        ->putJson("/admin/api/events/{$event->id}/cfp", [
            'opens_at'      => $cfp->opens_at->toDateTimeString(),
            'closes_at'     => $cfp->closes_at->toDateTimeString(),
            'speaker_guide' => '## Guia para palestrantes',
        ])
        ->assertOk()
        ->assertJsonPath('speaker_guide', '## Guia para palestrantes');
});

it('retorna 404 ao editar CFP inexistente', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->putJson("/admin/api/events/{$event->id}/cfp", [
            'opens_at'  => '2026-06-15 09:00:00',
            'closes_at' => '2026-07-31 23:59:59',
        ])
        ->assertNotFound();
});
```

### `tests/Feature/Admin/Talks/TalkTest.php`

```php
<?php

use App\Models\Event;
use App\Models\Speaker;
use App\Models\Talk;
use App\Models\User;

// ─── Listagem ─────────────────────────────────────────────────────────────────

it('lista palestras do evento com dados do palestrante', function () {
    $event = Event::factory()->create();
    Talk::factory()->count(3)->for($event)->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/talks")
        ->assertOk();

    expect($response->json('data'))->toHaveCount(3);
    expect($response->json('data.0'))->toHaveKey('speaker');
});

it('filtro por status retorna apenas palestras do status informado', function () {
    $event = Event::factory()->create();
    Talk::factory()->submetida()->count(2)->for($event)->create();
    Talk::factory()->aprovada()->count(1)->for($event)->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/talks?status=submetida")
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2);
    collect($response->json('data'))
        ->each(fn ($t) => expect($t['status'])->toBe('submetida'));
});

it('retorna 404 ao acessar palestra de outro evento', function () {
    $event       = Event::factory()->create();
    $outroEvento = Event::factory()->create();
    $talk        = Talk::factory()->for($outroEvento)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/talks/{$talk->id}")
        ->assertNotFound();
});

// ─── Transições de status ─────────────────────────────────────────────────────

it('coloca palestra em análise', function () {
    $event = Event::factory()->create();
    $talk  = Talk::factory()->submetida()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/talks/{$talk->id}/status", [
            'status' => 'em_analise',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'em_analise');
});

it('aprova palestra em análise', function () {
    $event = Event::factory()->create();
    $talk  = Talk::factory()->emAnalise()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/talks/{$talk->id}/status", [
            'status' => 'aprovada',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'aprovada');
});

it('rejeita palestra com feedback', function () {
    $event = Event::factory()->create();
    $talk  = Talk::factory()->emAnalise()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/talks/{$talk->id}/status", [
            'status'   => 'rejeitada',
            'feedback' => 'O tema não está alinhado com o público deste evento.',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'rejeitada');
});

it('rejeitar sem feedback retorna 422', function () {
    $event = Event::factory()->create();
    $talk  = Talk::factory()->emAnalise()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/talks/{$talk->id}/status", [
            'status' => 'rejeitada',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['feedback']);
});

it('reabre palestra rejeitada para análise', function () {
    $event = Event::factory()->create();
    $talk  = Talk::factory()->rejeitada()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/talks/{$talk->id}/status", [
            'status' => 'em_analise',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'em_analise');
});

it('transição inválida retorna 422', function () {
    $event = Event::factory()->create();
    $talk  = Talk::factory()->cancelada()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/talks/{$talk->id}/status", [
            'status' => 'aprovada',
        ])
        ->assertUnprocessable();
});

it('retorna 404 ao avaliar palestra de outro evento', function () {
    $event       = Event::factory()->create();
    $outroEvento = Event::factory()->create();
    $talk        = Talk::factory()->for($outroEvento)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/admin/api/events/{$event->id}/talks/{$talk->id}/status", [
            'status' => 'em_analise',
        ])
        ->assertNotFound();
});
```

---

## 13. Factories

### `EventCfpFactory`

```php
// database/factories/EventCfpFactory.php
public function definition(): array
{
    return [
        'event_id'              => Event::factory(),
        'opens_at'              => now()->addDays(7),
        'closes_at'             => now()->addDays(37),
        'speaker_guide'         => null,
        'max_talks_per_speaker' => null,
        'created_by'            => User::factory()->admin(),
    ];
}

public function aberto(): static
{
    return $this->state(['opens_at' => now()->subDay(), 'closes_at' => now()->addDays(30)]);
}

public function encerrado(): static
{
    return $this->state(['opens_at' => now()->subDays(60), 'closes_at' => now()->subDays(30)]);
}
```

### `SpeakerFactory`

```php
// database/factories/SpeakerFactory.php
public function definition(): array
{
    return [
        'user_id'    => User::factory()->palestrante(),
        'bio'        => fake('pt_BR')->paragraph(),
        'company'    => fake('pt_BR')->company(),
        'avatar_url' => null,
        'website'    => null,
        'twitter'    => fake()->userName(),
        'github'     => fake()->userName(),
        'linkedin'   => null,
    ];
}
```

> Requer o estado `palestrante()` no `UserFactory` (adicionar se ainda não existir):
> ```php
> public function palestrante(): static
> {
>     return $this->state(['role' => 'palestrante', 'is_active' => true]);
> }
> ```

### `TalkFactory`

```php
// database/factories/TalkFactory.php
public function definition(): array
{
    return [
        'event_id'    => Event::factory(),
        'speaker_id'  => Speaker::factory(),
        'title'       => fake('pt_BR')->sentence(5),
        'abstract'    => fake('pt_BR')->paragraph(3),
        'duration'    => fake()->randomElement(['25', '50']),
        'level'       => fake()->randomElement(['iniciante', 'intermediario', 'avancado']),
        'status'      => 'submetida',
        'feedback'    => null,
        'submitted_at' => now(),
    ];
}

public function submetida(): static  { return $this->state(['status' => 'submetida']); }
public function emAnalise(): static  { return $this->state(['status' => 'em_analise']); }
public function aprovada(): static   { return $this->state(['status' => 'aprovada']); }
public function rejeitada(): static  { return $this->state(['status' => 'rejeitada', 'feedback' => fake('pt_BR')->sentence()]); }
public function cancelada(): static  { return $this->state(['status' => 'cancelada']); }
```
