# Spec — Submissão de Palestra e Perfil do Palestrante

**Status:** ✅ Implementado
**Módulo:** CFP Público
**Depende de:** `.claude/specs/cfp/home-spec.md`, `.claude/specs/admin/events-cfp.md`

---

## 1. Visão geral

Três adições ao módulo CFP público:

1. **Cards da home** — botão "Palestras enviadas" abaixo do "Submeter palestra", mostrando quantas propostas o palestrante já enviou para aquele evento.
2. **Página de submissão** (`/cfp/submit/{event_id}`) — formulário de proposta de palestra. Ao acessar, se o perfil obrigatório ainda não foi preenchido, redireciona para `/cfp/perfil?redirect=/cfp/submit/{event_id}` antes de prosseguir.
3. **Página de perfil** (`/cfp/perfil`) — palestrante edita seus dados cadastrais (bio, empresa, redes sociais) e dados de login (nome, e-mail, senha).

---

## 2. Alterações na Home (`/cfp`)

### 2.1 Botão "Palestras enviadas"

Abaixo do botão "Submeter palestra" em cada card, adicionar um segundo botão que aparece **apenas para o palestrante autenticado**.

```
[Submeter palestra]
[Palestras enviadas (2)]     ← novo, visível só se logado como palestrante
```

- Texto: `"Palestras enviadas"` (sem contagem) se 0, `"Palestras enviadas (N)"` se N > 0
- Clique leva para `/cfp/submit/{event_id}` — onde a seção "Suas propostas" está anchorada
- Visível mesmo com CFP encerrado (para consulta das propostas)
- A contagem vem de um endpoint `GET /cfp/api/events/{event}/my-talks/count`

### 2.2 Endpoint de contagem

`GET /cfp/api/events/{event}/my-talks/count` — auth `palestrante`

```json
{ "count": 2 }
```

Retorna `count` de talks do palestrante para aquele evento (excluindo `canceladas`).

---

## 3. Guards da página de submissão

| Condição | Comportamento |
|----------|--------------|
| Não autenticado | Redirect para `/cfp/login?redirect=/cfp/submit/{event_id}` |
| Autenticado, não `palestrante` | Exibe aviso: "Use uma conta de palestrante para submeter propostas." |
| Perfil incompleto (bio vazio) | Redirect para `/cfp/perfil?redirect=/cfp/submit/{event_id}` com banner: "Complete seu perfil antes de submeter uma proposta." |
| CFP não configurado | Mensagem de erro; sem formulário |
| CFP `aguardando` | Banner informativo com data de abertura; formulário desabilitado |
| CFP `encerrado` | Banner "Período encerrado"; formulário oculto; lista de propostas visível |
| Limite de propostas atingido | Banner de aviso; formulário de nova proposta oculto |

---

## 4. Página de submissão (`/cfp/submit/{event_id}`)

### 4.1 Cabeçalho

```
← Voltar para o CFP

[cover_image ou placeholder]
Nome do evento · Edição N
📅 Data · 📍 Local
CFP: aberto até 31/07/2026
```

### 4.2 Seção: Guia do palestrante

Renderizado como markdown em bloco colapsável (accordion), visível apenas se `cfp.speaker_guide` não for nulo.

### 4.3 Seção: Suas propostas

Exibida se o palestrante já tem propostas para este evento. Cada card:

```
Arquitetura Hexagonal em PHP
50 min · Intermediário · [Badge: Submetida]
Enviada em 10/06/2026          [Editar]
```

- Badge de status com as mesmas cores do admin
- Botão "Editar" — abre o formulário pré-preenchido com os dados daquela proposta (PUT)
- Só é possível editar propostas com status `submetida` ou `em_analise`; demais status desabilitam o botão

### 4.4 Formulário de nova proposta

**Bloco 1 — Proposta**

| Campo | Tipo | Obrigatoriedade |
|-------|------|----------------|
| Título | `input text` (max 255, contador) | Obrigatório |
| Resumo / Abstract | `textarea` (min 100 / max 2000, contador) | Obrigatório |
| Duração | Radio: `25 min` (lightning) / `50 min` (palestra) | Obrigatório |
| Nível | Radio: `Iniciante` / `Intermediário` / `Avançado` | Obrigatório |

**Botão:** "Enviar proposta"

Após submissão com sucesso:
- Toast/banner: "Proposta enviada com sucesso!"
- Card da nova proposta aparece na seção "Suas propostas"
- Formulário limpa para nova proposta (se dentro do limite)

---

## 5. Página de perfil (`/cfp/perfil`)

### 5.1 Guards

- Não autenticado → redirect `/cfp/login?redirect=/cfp/perfil`
- Não `palestrante` → exibe aviso

### 5.2 Seção: Dados do palestrante

> Visíveis para a comissão avaliadora ao analisar suas propostas.

| Campo | Coluna DB | Tipo | Obrigatoriedade |
|-------|-----------|------|----------------|
| Foto de perfil | `avatar_url` | Upload de imagem (jpg/png/webp, máx 2 MB) | Opcional |
| Bio | `bio` | `textarea` (max 1000, contador) | **Obrigatório** |
| Empresa / Organização | `company` | `input text` (max 255) | Opcional |
| Cidade | `city` | `input text` (max 255) | Opcional |
| UF | `state` | `select` com 27 estados brasileiros (2 chars) | Opcional |
| Telefone | `phone_number` | `input tel` (max 20) | Opcional |
| Site pessoal | `website` | `input url` | Opcional |
| Twitter / X | `twitter` | `input text` com prefixo `@` (max 100) | Opcional |
| GitHub | `github` | `input text` com prefixo `@` (max 100) | Opcional |
| LinkedIn | `linkedin` | `input text` | Opcional |

**Botão:** "Salvar perfil" → `POST /cfp/api/speaker/profile` com `multipart/form-data` e `_method=PATCH`

> **Nota de implementação:** o formulário sempre envia via `FormData + POST + _method: PATCH` para suportar o upload do avatar. A rota `PATCH /cfp/api/speaker/profile` permanece ativa para clientes que não precisam enviar arquivo.

### 5.3 Seção: Dados da conta

| Campo | Tipo | Observação |
|-------|------|-----------|
| Nome | `input text` (max 255) | Pré-preenchido |
| E-mail | `input email` (max 255) | Pré-preenchido; unique |
| Nova senha | `input password` (min 8) | Opcional — só atualiza se preenchido |
| Confirmar nova senha | `input password` | Obrigatório se nova senha preenchida |
| Senha atual | `input password` | Obrigatório para confirmar qualquer alteração |

**Botão:** "Salvar dados da conta" → `PATCH /cfp/api/account`

### 5.4 Banner de perfil incompleto

Se o usuário foi redirecionado de `/cfp/submit/{event_id}` (via `?redirect=`), exibe no topo:

```
⚠️ Complete seu perfil antes de submeter uma proposta.
   A bio é obrigatória para que a comissão possa avaliar sua candidatura.
```

---

## 6. API

### 6.1 `GET /cfp/api/events/{event}` *(novo método em CfpPublicController)*

Pública. Retorna dados do evento + CFP para o cabeçalho da página de submissão.

**Resposta:**

```json
{
  "data": {
    "id": 1,
    "name": "PHP com Rapadura 2026",
    "edition": 3,
    "starts_at": "2026-08-20T09:00:00Z",
    "location": "Fortaleza — CE",
    "is_online": false,
    "cover_image": "https://...",
    "cfp": {
      "opens_at": "2026-06-01T00:00:00Z",
      "closes_at": "2026-07-31T23:59:59Z",
      "speaker_guide": "## Guia...",
      "max_talks_per_speaker": 2,
      "status": "aberto"
    }
  }
}
```

Retorna `404` se evento não existe ou não tem CFP.

---

### 6.2 `GET /cfp/api/speaker/profile` — auth `palestrante`

Retorna perfil do speaker ou `null`.

```json
{
  "data": {
    "bio": "...",
    "company": "Acme",
    "city": "Fortaleza",
    "state": "CE",
    "avatar_url": "https://r2.phpcomrapadura.org/speakers/1/avatar.jpg",
    "phone_number": "+55 85 99999-9999",
    "website": "https://exemplo.com",
    "twitter": "usuario",
    "github": "usuario",
    "linkedin": "usuario"
  }
}
```

---

### 6.3 `POST /cfp/api/speaker/profile` (multipart) ou `PATCH /cfp/api/speaker/profile` (JSON) — auth `palestrante`

Faz upsert do perfil. Retorna 200 com perfil atualizado.

Quando enviado via `POST` com `_method=PATCH` em `multipart/form-data`, aceita o campo `avatar` (imagem, máx 2 MB). O avatar anterior é deletado do R2 antes do novo upload. Caminho no bucket: `speakers/{user_id}/avatar.{ext}`.

---

### 6.4 `PATCH /cfp/api/account` — auth `palestrante`

Atualiza nome, e-mail e/ou senha. Requer senha atual.

**Body:**

```json
{
  "name":                  "João Silva",
  "email":                 "joao@exemplo.com",
  "current_password":      "senha-atual",
  "password":              "nova-senha",
  "password_confirmation": "nova-senha"
}
```

**Erros:**

| Situação | Campo | Mensagem |
|----------|-------|---------|
| Senha atual incorreta | `current_password` | `"Senha atual incorreta."` |
| E-mail já cadastrado | `email` | `"Este e-mail já está em uso."` |
| Nova senha < 8 chars | `password` | `"A nova senha deve ter pelo menos 8 caracteres."` |

---

### 6.5 `GET /cfp/api/events/{event}/my-talks` — auth `palestrante`

Lista propostas do palestrante para o evento.

```json
{
  "data": [
    {
      "id": 7,
      "title": "Arquitetura Hexagonal em PHP",
      "abstract": "...",
      "duration": "50",
      "level": "intermediario",
      "status": "submetida",
      "submitted_at": "2026-06-10T14:22:00Z"
    }
  ]
}
```

---

### 6.6 `GET /cfp/api/events/{event}/my-talks/count` — auth `palestrante`

```json
{ "count": 2 }
```

---

### 6.7 `POST /cfp/api/events/{event}/talks` — auth `palestrante`

Submete nova proposta.

**Body:**

```json
{
  "title":    "Arquitetura Hexagonal em PHP",
  "abstract": "Nesta palestra vamos explorar...",
  "duration": "50",
  "level":    "intermediario"
}
```

**Fluxo:**

1. Valida campos via `StoreTalkRequest`
2. Verifica CFP com status `aberto` → 422 se não
3. Verifica `max_talks_per_speaker` (exclui `canceladas` da contagem) → 422 se atingido
4. Cria `Talk` com `status = submetida`, `submitted_at = now()`
5. Retorna 201 com talk criada

---

### 6.8 `PUT /cfp/api/talks/{talk}` — auth `palestrante`

Edita proposta própria. Só permitido se `status` for `submetida` ou `em_analise`.

**Body:** mesmos campos do POST.

**Validações extras:**
- Talk não pertence ao palestrante logado → 403
- Status inelegível para edição → 422: `"Esta proposta não pode mais ser editada."`

---

## 7. Middleware `EnsureSpeaker`

**Arquivo:** `app/Http/Middleware/EnsureSpeaker.php`

```php
public function handle(Request $request, Closure $next): Response
{
    if (! Auth::check()) {
        return response()->json(['message' => 'Não autenticado.'], 401);
    }

    /** @var User $user */
    $user = Auth::user();

    if (! $user->isSpeaker()) {
        return response()->json(['message' => 'Acesso restrito a palestrantes.'], 403);
    }

    return $next($request);
}
```

Registrar como alias `speaker` no `bootstrap/app.php`.

---

## 8. Rotas

```php
// Dentro do grupo Route::prefix('cfp')

// API pública
Route::get('/api/events/{event}', [CfpPublicController::class, 'show'])->name('api.event');

// API protegida (palestrante)
Route::middleware(['auth', 'speaker'])->group(function () {
    Route::get('/api/speaker/profile',                    [SpeakerProfileController::class, 'show'])->name('api.speaker.show');
    Route::patch('/api/speaker/profile',                  [SpeakerProfileController::class, 'update'])->name('api.speaker.update');
    Route::patch('/api/account',                          [AccountController::class, 'update'])->name('api.account.update');
    Route::get('/api/events/{event}/my-talks',            [TalkSubmissionController::class, 'myTalks'])->name('api.my-talks');
    Route::get('/api/events/{event}/my-talks/count',      [TalkSubmissionController::class, 'myTalksCount'])->name('api.my-talks.count');
    Route::post('/api/events/{event}/talks',              [TalkSubmissionController::class, 'store'])->name('api.talks.store');
    Route::put('/api/talks/{talk}',                       [TalkSubmissionController::class, 'update'])->name('api.talks.update');
});
```

---

## 9. Requests

### 9.1 `StoreTalkRequest`

**Arquivo:** `app/Http/Requests/Cfp/StoreTalkRequest.php`

```php
public function rules(): array
{
    return [
        'title'    => ['required', 'string', 'max:255'],
        'abstract' => ['required', 'string', 'min:100', 'max:2000'],
        'duration' => ['required', 'in:25,50'],
        'level'    => ['required', 'in:iniciante,intermediario,avancado'],
    ];
}

public function messages(): array
{
    return [
        'title.required'    => 'O título é obrigatório.',
        'title.max'         => 'O título deve ter no máximo 255 caracteres.',
        'abstract.required' => 'O resumo é obrigatório.',
        'abstract.min'      => 'O resumo deve ter pelo menos 100 caracteres.',
        'abstract.max'      => 'O resumo deve ter no máximo 2000 caracteres.',
        'duration.required' => 'A duração é obrigatória.',
        'duration.in'       => 'A duração deve ser 25 ou 50 minutos.',
        'level.required'    => 'O nível é obrigatório.',
        'level.in'          => 'Nível inválido.',
    ];
}
```

### 9.2 `UpdateSpeakerProfileRequest`

**Arquivo:** `app/Http/Requests/Cfp/UpdateSpeakerProfileRequest.php`

```php
public function rules(): array
{
    return [
        'bio'          => ['required', 'string', 'max:1000'],
        'company'      => ['nullable', 'string', 'max:255'],
        'phone_number' => ['nullable', 'string', 'max:20'],
        'website'      => ['nullable', 'url', 'max:500'],
        'twitter'      => ['nullable', 'string', 'max:100'],
        'github'       => ['nullable', 'string', 'max:100'],
        'linkedin'     => ['nullable', 'string', 'max:255'],
    ];
}

public function messages(): array
{
    return [
        'bio.required' => 'A bio é obrigatória.',
        'bio.max'      => 'A bio deve ter no máximo 1000 caracteres.',
        'website.url'  => 'Informe uma URL válida para o site.',
    ];
}
```

### 9.3 `UpdateAccountRequest`

**Arquivo:** `app/Http/Requests/Cfp/UpdateAccountRequest.php`

```php
public function rules(): array
{
    return [
        'name'                  => ['required', 'string', 'max:255'],
        'email'                 => ['required', 'email', 'max:255',
                                    Rule::unique('users', 'email')->ignore(Auth::id())],
        'current_password'      => ['required', 'string', 'current_password'],
        'password'              => ['nullable', 'string', 'min:8', 'confirmed'],
    ];
}

public function messages(): array
{
    return [
        'name.required'             => 'O nome é obrigatório.',
        'email.required'            => 'O e-mail é obrigatório.',
        'email.unique'              => 'Este e-mail já está em uso.',
        'current_password.required' => 'A senha atual é obrigatória.',
        'current_password.current_password' => 'Senha atual incorreta.',
        'password.min'              => 'A nova senha deve ter pelo menos 8 caracteres.',
        'password.confirmed'        => 'As senhas não coincidem.',
    ];
}
```

---

## 10. Model `User` — relação `speaker`

Adicionar ao `app/Models/User.php`:

```php
use Illuminate\Database\Eloquent\Relations\HasOne;

public function speaker(): HasOne
{
    return $this->hasOne(Speaker::class);
}
```

---

## 11. Arquivos Vue

| Arquivo | Descrição |
|---------|-----------|
| `resources/js/views/cfp/SubmitTalk.vue` | Página de submissão + lista de propostas |
| `resources/js/views/cfp/Profile.vue` | Página de perfil (dados do palestrante + conta) |

Adicionar ao `cfp.js`:

```js
{ path: '/cfp/submit/:eventId', name: 'cfp.submit',  component: () => import('./views/cfp/SubmitTalk.vue') },
{ path: '/cfp/perfil',          name: 'cfp.profile', component: () => import('./views/cfp/Profile.vue')    },
```

---

## 12. Arquivos a criar/modificar

| Arquivo | Ação |
|---------|------|
| `app/Http/Middleware/EnsureSpeaker.php` | Criar |
| `app/Http/Controllers/Api/CfpPublicController.php` | Modificar — adicionar método `show` |
| `app/Http/Controllers/Cfp/SpeakerProfileController.php` | Criar |
| `app/Http/Controllers/Cfp/AccountController.php` | Criar |
| `app/Http/Controllers/Cfp/TalkSubmissionController.php` | Criar |
| `app/Http/Requests/Cfp/StoreTalkRequest.php` | Criar |
| `app/Http/Requests/Cfp/UpdateSpeakerProfileRequest.php` | Criar |
| `app/Http/Requests/Cfp/UpdateAccountRequest.php` | Criar |
| `app/Models/User.php` | Modificar — adicionar `speaker(): HasOne` |
| `bootstrap/app.php` | Modificar — registrar alias `speaker` |
| `routes/web.php` | Modificar — adicionar rotas protegidas e `GET /cfp/api/events/{event}` |
| `resources/js/views/cfp/SubmitTalk.vue` | Criar |
| `resources/js/views/cfp/Profile.vue` | Criar |
| `resources/js/views/cfp/Home.vue` | Modificar — adicionar botão "Palestras enviadas" e contagem |
| `resources/js/cfp.js` | Modificar — adicionar rotas `cfp.submit` e `cfp.profile` |

---

## 13. Testes

**Arquivo:** `tests/Feature/Cfp/TalkSubmissionTest.php`

```php
<?php

use App\Models\Event;
use App\Models\EventCfp;
use App\Models\Speaker;
use App\Models\Talk;
use App\Models\User;

// ─── GET /cfp/api/events/{event} ──────────────────────────────────────────────

it('retorna dados do evento com CFP', function () {
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);

    $this->getJson("/cfp/api/events/{$event->id}")
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'name', 'cfp' => ['status', 'opens_at', 'closes_at']]]);
});

it('retorna 404 para evento sem CFP', function () {
    $event = Event::factory()->create();
    $this->getJson("/cfp/api/events/{$event->id}")->assertNotFound();
});

// ─── GET /cfp/api/speaker/profile ─────────────────────────────────────────────

it('guest recebe 401 ao acessar perfil', function () {
    $this->getJson('/cfp/api/speaker/profile')->assertUnauthorized();
});

it('admin recebe 403 ao acessar perfil de speaker', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/cfp/api/speaker/profile')->assertForbidden();
});

it('retorna null quando palestrante não tem perfil', function () {
    $this->actingAs(User::factory()->palestrante()->create())
        ->getJson('/cfp/api/speaker/profile')
        ->assertOk()
        ->assertJsonPath('data', null);
});

it('retorna perfil do palestrante', function () {
    $user = User::factory()->palestrante()->create();
    Speaker::factory()->create(['user_id' => $user->id, 'company' => 'Acme']);

    $this->actingAs($user)
        ->getJson('/cfp/api/speaker/profile')
        ->assertOk()
        ->assertJsonPath('data.company', 'Acme');
});

// ─── PATCH /cfp/api/speaker/profile ───────────────────────────────────────────

it('palestrante atualiza perfil com sucesso', function () {
    $user = User::factory()->palestrante()->create();

    $this->actingAs($user)
        ->patchJson('/cfp/api/speaker/profile', [
            'bio'     => str_repeat('a', 50),
            'company' => 'Nova Empresa',
        ])
        ->assertOk()
        ->assertJsonPath('data.company', 'Nova Empresa');

    $this->assertDatabaseHas('speakers', ['user_id' => $user->id, 'company' => 'Nova Empresa']);
});

it('retorna 422 quando bio está vazia no update do perfil', function () {
    $user = User::factory()->palestrante()->create();

    $this->actingAs($user)
        ->patchJson('/cfp/api/speaker/profile', ['bio' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['bio']);
});

// ─── PATCH /cfp/api/account ───────────────────────────────────────────────────

it('palestrante atualiza nome e email', function () {
    $user = User::factory()->palestrante()->create();

    $this->actingAs($user)
        ->patchJson('/cfp/api/account', [
            'name'             => 'Novo Nome',
            'email'            => $user->email,
            'current_password' => 'password',
        ])
        ->assertOk();

    $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Novo Nome']);
});

it('retorna 422 quando senha atual está errada', function () {
    $user = User::factory()->palestrante()->create();

    $this->actingAs($user)
        ->patchJson('/cfp/api/account', [
            'name'             => $user->name,
            'email'            => $user->email,
            'current_password' => 'senha-errada',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);
});

it('retorna 422 quando e-mail já está em uso', function () {
    $other = User::factory()->create(['email' => 'outro@exemplo.com']);
    $user  = User::factory()->palestrante()->create();

    $this->actingAs($user)
        ->patchJson('/cfp/api/account', [
            'name'             => $user->name,
            'email'            => 'outro@exemplo.com',
            'current_password' => 'password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

// ─── POST /cfp/api/events/{event}/talks ───────────────────────────────────────

it('guest recebe 401 ao submeter palestra', function () {
    $event = Event::factory()->create();
    $this->postJson("/cfp/api/events/{$event->id}/talks", [])->assertUnauthorized();
});

it('admin recebe 403 ao submeter palestra', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/cfp/api/events/{$event->id}/talks", [])
        ->assertForbidden();
});

it('palestrante submete palestra com sucesso', function () {
    $user    = User::factory()->palestrante()->create();
    Speaker::factory()->create(['user_id' => $user->id, 'bio' => 'Bio do palestrante']);
    $event   = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);

    $this->actingAs($user)
        ->postJson("/cfp/api/events/{$event->id}/talks", [
            'title'    => 'Arquitetura Hexagonal em PHP',
            'abstract' => str_repeat('a', 100),
            'duration' => '50',
            'level'    => 'intermediario',
        ])
        ->assertCreated()
        ->assertJsonPath('status', 'submetida');

    $this->assertDatabaseHas('talks', ['title' => 'Arquitetura Hexagonal em PHP']);
});

it('retorna 422 quando CFP não está aberto', function () {
    $user  = User::factory()->palestrante()->create();
    Speaker::factory()->create(['user_id' => $user->id, 'bio' => 'Bio']);
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->encerrado()->create(['event_id' => $event->id]);

    $this->actingAs($user)
        ->postJson("/cfp/api/events/{$event->id}/talks", [
            'title'    => 'Palestra',
            'abstract' => str_repeat('a', 100),
            'duration' => '25',
            'level'    => 'iniciante',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'O período de submissão não está aberto.');
});

it('retorna 422 quando limite de propostas foi atingido', function () {
    $user    = User::factory()->palestrante()->create();
    $speaker = Speaker::factory()->create(['user_id' => $user->id, 'bio' => 'Bio']);
    $event   = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id, 'max_talks_per_speaker' => 1]);
    Talk::factory()->submetida()->create(['event_id' => $event->id, 'speaker_id' => $speaker->id]);

    $this->actingAs($user)
        ->postJson("/cfp/api/events/{$event->id}/talks", [
            'title'    => 'Segunda Palestra',
            'abstract' => str_repeat('a', 100),
            'duration' => '25',
            'level'    => 'iniciante',
        ])
        ->assertUnprocessable();
});

it('proposta cancelada não conta no limite', function () {
    $user    = User::factory()->palestrante()->create();
    $speaker = Speaker::factory()->create(['user_id' => $user->id, 'bio' => 'Bio']);
    $event   = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id, 'max_talks_per_speaker' => 1]);
    Talk::factory()->cancelada()->create(['event_id' => $event->id, 'speaker_id' => $speaker->id]);

    $this->actingAs($user)
        ->postJson("/cfp/api/events/{$event->id}/talks", [
            'title'    => 'Nova Palestra',
            'abstract' => str_repeat('a', 100),
            'duration' => '25',
            'level'    => 'iniciante',
        ])
        ->assertCreated();
});

it('retorna 422 quando resumo tem menos de 100 caracteres', function () {
    $user  = User::factory()->palestrante()->create();
    Speaker::factory()->create(['user_id' => $user->id, 'bio' => 'Bio']);
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);

    $this->actingAs($user)
        ->postJson("/cfp/api/events/{$event->id}/talks", [
            'title'    => 'Palestra',
            'abstract' => 'Curto demais.',
            'duration' => '25',
            'level'    => 'iniciante',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['abstract']);
});

// ─── PUT /cfp/api/talks/{talk} ────────────────────────────────────────────────

it('palestrante edita própria proposta submetida', function () {
    $user    = User::factory()->palestrante()->create();
    $speaker = Speaker::factory()->create(['user_id' => $user->id]);
    $event   = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);
    $talk    = Talk::factory()->submetida()->create(['event_id' => $event->id, 'speaker_id' => $speaker->id]);

    $this->actingAs($user)
        ->putJson("/cfp/api/talks/{$talk->id}", [
            'title'    => 'Título Atualizado',
            'abstract' => str_repeat('a', 100),
            'duration' => '25',
            'level'    => 'iniciante',
        ])
        ->assertOk()
        ->assertJsonPath('title', 'Título Atualizado');
});

it('palestrante não pode editar proposta de outro', function () {
    $user    = User::factory()->palestrante()->create();
    $other   = Speaker::factory()->create();
    $event   = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);
    $talk    = Talk::factory()->submetida()->create(['event_id' => $event->id, 'speaker_id' => $other->id]);

    $this->actingAs($user)
        ->putJson("/cfp/api/talks/{$talk->id}", [
            'title'    => 'Tentativa',
            'abstract' => str_repeat('a', 100),
            'duration' => '25',
            'level'    => 'iniciante',
        ])
        ->assertForbidden();
});

it('não permite editar proposta aprovada', function () {
    $user    = User::factory()->palestrante()->create();
    $speaker = Speaker::factory()->create(['user_id' => $user->id]);
    $event   = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);
    $talk    = Talk::factory()->aprovada()->create(['event_id' => $event->id, 'speaker_id' => $speaker->id]);

    $this->actingAs($user)
        ->putJson("/cfp/api/talks/{$talk->id}", [
            'title'    => 'Tentativa',
            'abstract' => str_repeat('a', 100),
            'duration' => '25',
            'level'    => 'iniciante',
        ])
        ->assertUnprocessable();
});

// ─── GET /cfp/api/events/{event}/my-talks ─────────────────────────────────────

it('retorna propostas do palestrante para o evento', function () {
    $user    = User::factory()->palestrante()->create();
    $speaker = Speaker::factory()->create(['user_id' => $user->id]);
    $event   = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);
    Talk::factory()->count(2)->submetida()->create(['event_id' => $event->id, 'speaker_id' => $speaker->id]);

    $response = $this->actingAs($user)
        ->getJson("/cfp/api/events/{$event->id}/my-talks")
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2);
});

it('não retorna propostas de outros palestrantes', function () {
    $user  = User::factory()->palestrante()->create();
    $other = Speaker::factory()->create();
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);
    Talk::factory()->submetida()->create(['event_id' => $event->id, 'speaker_id' => $other->id]);

    $this->actingAs($user)
        ->getJson("/cfp/api/events/{$event->id}/my-talks")
        ->assertOk()
        ->assertJsonPath('data', []);
});
```

---

## 14. Critérios de aceite

### Página de submissão
- [ ] Usuário não autenticado é redirecionado para `/cfp/login?redirect=...`
- [ ] Palestrante sem bio é redirecionado para `/cfp/perfil?redirect=...`
- [ ] CFP encerrado oculta formulário e exibe mensagem
- [ ] CFP aguardando desabilita formulário e mostra data de abertura
- [ ] Propostas existentes aparecem na seção "Suas propostas" com status e botão Editar
- [ ] Botão Editar desabilitado para propostas aprovadas/rejeitadas/canceladas
- [ ] Formulário limpa após submissão e nova proposta aparece na lista
- [ ] Limite atingido oculta formulário e exibe banner

### Perfil do palestrante
- [ ] Dados pré-preenchidos se perfil já existe
- [ ] Bio obrigatória — `422` sem bio
- [ ] Salvar perfil cria `Speaker` se não existia
- [ ] URL inválida em `website` retorna `422`

### Dados da conta
- [ ] Nome e e-mail atualizados com senha atual correta
- [ ] Senha atual incorreta retorna `422` com mensagem em português
- [ ] E-mail duplicado retorna `422`
- [ ] Nova senha opcional — só atualiza se preenchida

### Home — botão "Palestras enviadas"
- [ ] Botão visível apenas para palestrante autenticado
- [ ] Contagem correta de propostas (excluindo canceladas)
- [ ] Clique navega para `/cfp/submit/{event_id}`
