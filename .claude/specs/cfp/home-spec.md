# Spec — Página Pública do CFP

**Status:** ✅ Implementado
**Módulo:** CFP Público
**Depende de:** `.claude/specs/admin/events-cfp.md`

---

## 1. Visão geral

Página pública acessível em `/cfp` que lista os eventos com CFP configurado (status `aguardando` ou `aberto`). Um visitante pode ver os eventos disponíveis e clicar em "Submeter palestra" para iniciar o fluxo de submissão.

O clique no botão redireciona para `/cfp/login` se o usuário não estiver autenticado como `palestrante`. Após login (ou registro), o palestrante é levado diretamente à tela de submissão do evento escolhido.

Esta página é uma **Vue.js SPA separada** do admin — tem seu próprio entry point `cfp.js` e é servida pela Blade view `cfp.blade.php`. Ela não compartilha o `AdminLayout` nem o router do admin.

---

## 2. Rota pública

```
GET /cfp              → view('cfp')   [pública]
GET /cfp/login        → view('cfp')   [pública]
GET /cfp/{any}        → view('cfp')   [pública, where any: .*]
```

Todas as rotas acima retornam a mesma Blade view e deixam o Vue Router resolver a navegação interna.

---

## 3. API pública

### 3.1 `GET /api/cfp/events`

Sem autenticação. Retorna eventos com CFP `aberto` ou `aguardando`, ordenados por `opens_at ASC`.

**Resposta:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "PHP com Rapadura 2026",
      "slug": "php-com-rapadura-2026",
      "edition": 3,
      "starts_at": "2026-08-20T09:00:00Z",
      "ends_at": "2026-08-20T18:00:00Z",
      "location": "Centro de Convenções, Fortaleza — CE",
      "is_online": false,
      "cover_image": "https://assets.phpcomrapadura.org/events/1/cover.jpg",
      "cfp": {
        "opens_at": "2026-06-01T00:00:00Z",
        "closes_at": "2026-07-31T23:59:59Z",
        "speaker_guide": "## Guia do Palestrante\n...",
        "max_talks_per_speaker": 2,
        "status": "aberto"
      }
    }
  ]
}
```

**Filtro de elegibilidade:**
- Inclui apenas eventos com CFP configurado
- Status do CFP: `aberto` (entre `opens_at` e `closes_at`) ou `aguardando` (`opens_at` ainda no futuro)
- Exclui eventos `encerrados` ou `cancelados`

### 3.2 Controlador

**Arquivo:** `app/Http/Controllers/Api/CfpPublicController.php`

```php
public function events(): JsonResponse
{
    $events = Event::query()
        ->whereHas('cfp')
        ->whereIn('status', ['rascunho', 'publicado'])
        ->with('cfp')
        ->get()
        ->filter(fn (Event $event) => in_array($event->cfp->status(), ['aguardando', 'aberto']))
        ->sortBy(fn (Event $event) => $event->cfp->opens_at)
        ->values();

    return response()->json([
        'data' => $events->map(fn (Event $event) => [
            'id'          => $event->id,
            'name'        => $event->name,
            'slug'        => $event->slug,
            'edition'     => $event->edition,
            'starts_at'   => $event->starts_at,
            'ends_at'     => $event->ends_at,
            'location'    => $event->location,
            'is_online'   => $event->is_online,
            'cover_image' => $event->cover_image,
            'cfp'         => [
                'opens_at'              => $event->cfp->opens_at,
                'closes_at'             => $event->cfp->closes_at,
                'speaker_guide'         => $event->cfp->speaker_guide,
                'max_talks_per_speaker' => $event->cfp->max_talks_per_speaker,
                'status'                => $event->cfp->status(),
            ],
        ]),
    ]);
}
```

**Rota:**

```php
// routes/web.php — fora de qualquer grupo de autenticação
Route::get('/api/cfp/events', [CfpPublicController::class, 'events'])->name('cfp.public.events');
```

---

## 4. Frontend

### 4.1 Entry point e Blade view

**Novo arquivo:** `resources/js/cfp.js`

```js
import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './CfpApp.vue'
import routes from './router/cfp.js'

const router = createRouter({
    history: createWebHistory(),
    routes,
})

createApp(App).use(router).mount('#cfp-app')
```

**Novo arquivo:** `resources/views/cfp.blade.php`

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call for Papers — PHP com Rapadura</title>
    @vite(['resources/css/app.css', 'resources/js/cfp.js'])
</head>
<body class="bg-(--color-bg) text-(--color-text)">
    <div id="cfp-app"></div>
</body>
</html>
```

**`vite.config.js`** — adicionar `cfp.js` ao array de `input`:

```js
input: [
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/js/admin.js',
    'resources/js/cfp.js',   // ← adicionar
],
```

### 4.2 Estrutura de arquivos Vue

```
resources/js/
├── cfp.js                          # entry point
├── CfpApp.vue                      # componente raiz (sem layout, só <RouterView>)
├── router/cfp.js                   # Vue Router do módulo CFP público
└── views/cfp/
    ├── Home.vue                    # lista de eventos com CFP aberto/aguardando
    └── Login.vue                   # página de login/registro para palestrantes
```

### 4.3 Vue Router (`resources/js/router/cfp.js`)

```js
const routes = [
    {
        path: '/cfp',
        name: 'cfp.home',
        component: () => import('@/views/cfp/Home.vue'),
    },
    {
        path: '/cfp/login',
        name: 'cfp.login',
        component: () => import('@/views/cfp/Login.vue'),
    },
]
```

---

## 5. Interface

### 5.1 Página inicial (`/cfp`) — `Home.vue`

**Cabeçalho da página:**

```
Logo PHP com Rapadura              [Entrar]           (guest)
Logo PHP com Rapadura     [Perfil] [Sair]             (palestrante logado)
Logo PHP com Rapadura     Nome do usuário · [Sair]    (admin/colaborador logado)
─────────────────────────────────────────────────────────────
Call for Papers
Submeta sua proposta de palestra para os próximos eventos.
```

O link **"Perfil"** aparece apenas para usuários com role `palestrante` e navega para `/cfp/perfil`.

**Estado de carregamento:** skeleton de 3 cards durante o fetch.

**Estado vazio:** mensagem "Nenhum evento com CFP aberto no momento."

**Grid de cards:** `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6`

### 5.2 Card de evento

```
┌──────────────────────────────────────────┐
│  [cover_image — 16:9, object-cover]      │
│  ou placeholder com gradiente azul       │
├──────────────────────────────────────────┤
│  [Badge CFP status]                      │
│  Nome do evento (font-semibold, lg)      │
│  Edição 3 · 20 ago 2026                  │
│  📍 Fortaleza — CE  (ou 🌐 Online)       │
│                                          │
│  Submissões abertas até 31/07/2026       │
│  Ou: Submissões abrem em 01/06/2026      │
│                                          │
│  [max_talks_per_speaker: "Máx. 2 propostas por palestrante"]
│  (se null, não exibe)                    │
│                                          │
│  ─────────────────────────────────────── │
│  [Botão: "Submeter palestra"]            │
└──────────────────────────────────────────┘
```

### 5.3 Badge de status do CFP

| Status | Rótulo | Estilo |
|--------|--------|--------|
| `aguardando` | "Em breve" | `bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400` |
| `aberto` | "Aberto" | `bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400` |

### 5.4 Botão "Submeter palestra"

| Estado | Comportamento |
|--------|--------------|
| CFP `aguardando` | Desabilitado (`disabled`, cursor-not-allowed, opacidade reduzida) |
| CFP `aberto` + guest | Redireciona para `/cfp/login?redirect=/cfp/submit/{event_id}` |
| CFP `aberto` + `palestrante` logado | Redireciona para `/cfp/submit/{event_id}` (spec futura) |
| CFP `aberto` + `admin`/`colaborador` logado | Exibe tooltip: "Use sua conta de palestrante para submeter propostas" |

### 5.5 Página de login (`/cfp/login`) — `Login.vue`

Formulário simples de autenticação:

```
E-mail
Senha
[Entrar]

Não tem conta? [Criar conta como palestrante]
```

- Usa a mesma rota `POST /admin/login` do admin (Sanctum session-based)
- Após login bem-sucedido, redireciona para o `?redirect` da query string ou para `/cfp`
- Se o usuário logado não for `palestrante`, exibe aviso: "Sua conta não tem permissão para submeter palestras."
- O registro de palestrante é spec futura; o link "Criar conta" pode ser placeholder por ora

---

## 6. Regras de negócio

| Regra | Detalhe |
|-------|---------|
| Visibilidade | Apenas eventos com CFP `aberto` ou `aguardando` aparecem na listagem |
| Eventos encerrados/cancelados | Não aparecem, mesmo que o CFP ainda esteja aberto |
| Botão desabilitado | Status `aguardando` — ainda não abriu para submissões |
| Autenticação | Apenas `palestrante` pode submeter; `admin`/`colaborador` veem tooltip explicativo |
| Submissão | Redireciona para `/cfp/submit/{event_id}` — fluxo detalhado em spec separada |

---

## 7. Rotas (resumo)

```php
// routes/web.php — fora de qualquer grupo de autenticação admin

// API pública
Route::get('/api/cfp/events', [CfpPublicController::class, 'events'])->name('cfp.public.events');

// SPA pública — retorna cfp.blade.php
Route::get('/cfp',        fn () => view('cfp'))->name('cfp.home');
Route::get('/cfp/{any}',  fn () => view('cfp'))->where('any', '.*');
```

---

## 8. Arquivos a criar/modificar

| Arquivo | Tipo | Ação |
|---------|------|------|
| `app/Http/Controllers/Api/CfpPublicController.php` | Controller | Criar |
| `resources/views/cfp.blade.php` | Blade view | Criar |
| `resources/js/cfp.js` | Entry point Vue | Criar |
| `resources/js/CfpApp.vue` | Componente raiz | Criar |
| `resources/js/router/cfp.js` | Vue Router | Criar |
| `resources/js/views/cfp/Home.vue` | View | Criar |
| `resources/js/views/cfp/Login.vue` | View | Criar |
| `vite.config.js` | Build config | Modificar — adicionar `cfp.js` ao input |
| `routes/web.php` | Rotas | Modificar — adicionar rotas públicas |

---

## 9. Testes

**Arquivo:** `tests/Feature/Cfp/CfpPublicTest.php`

```php
<?php

use App\Models\Event;
use App\Models\EventCfp;

it('retorna lista vazia quando não há CFPs abertos', function () {
    $this->getJson('/api/cfp/events')
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('retorna eventos com CFP aberto', function () {
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);

    $response = $this->getJson('/api/cfp/events')->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.id'))->toBe($event->id);
    expect($response->json('data.0.cfp.status'))->toBe('aberto');
});

it('retorna eventos com CFP aguardando', function () {
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->create([
        'event_id'  => $event->id,
        'opens_at'  => now()->addDays(10),
        'closes_at' => now()->addDays(40),
    ]);

    $response = $this->getJson('/api/cfp/events')->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.cfp.status'))->toBe('aguardando');
});

it('não retorna eventos com CFP encerrado', function () {
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->encerrado()->create(['event_id' => $event->id]);

    $this->getJson('/api/cfp/events')
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('não retorna eventos cancelados mesmo com CFP aberto', function () {
    $event = Event::factory()->cancelado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);

    $this->getJson('/api/cfp/events')
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('retorna os campos esperados no card do evento', function () {
    $event = Event::factory()->publicado()->create();
    EventCfp::factory()->aberto()->create(['event_id' => $event->id]);

    $data = $this->getJson('/api/cfp/events')->assertOk()->json('data.0');

    expect($data)->toHaveKeys([
        'id', 'name', 'slug', 'edition',
        'starts_at', 'ends_at', 'location', 'is_online', 'cover_image',
        'cfp',
    ]);
    expect($data['cfp'])->toHaveKeys([
        'opens_at', 'closes_at', 'speaker_guide', 'max_talks_per_speaker', 'status',
    ]);
});

it('endpoint público não requer autenticação', function () {
    $this->getJson('/api/cfp/events')->assertOk();
});
```

---

## 10. Critérios de aceite

### API
- [ ] `GET /api/cfp/events` retorna 200 sem autenticação
- [ ] Apenas eventos com CFP `aberto` ou `aguardando` aparecem
- [ ] Eventos `cancelados` ou `encerrados` não aparecem
- [ ] Resposta inclui todos os campos do card (id, name, slug, edition, starts_at, ends_at, location, is_online, cover_image, cfp)
- [ ] `cfp.status` reflete corretamente `aberto` ou `aguardando`

### Frontend
- [ ] Página `/cfp` renderiza lista de cards
- [ ] Card exibe: imagem de capa (ou placeholder), badge de status, nome, edição, data, local, período de submissão
- [ ] Badge "Aberto" em verde e "Em breve" em amarelo
- [ ] Botão "Submeter palestra" desabilitado quando CFP `aguardando`
- [ ] Botão redireciona para `/cfp/login?redirect=...` quando usuário não autenticado
- [ ] Estado vazio exibe mensagem quando não há CFPs disponíveis
- [ ] Página `/cfp/login` tem formulário de e-mail e senha funcional
- [ ] Após login como `palestrante`, redireciona para o destino original
- [ ] Dark mode funciona corretamente (usa tokens `--color-*` do design system)
- [ ] Layout responsivo: 1 coluna mobile → 2 tablet → 3 desktop
- [ ] Link "Perfil" visível no header apenas para `palestrante` autenticado
- [ ] Link "Perfil" navega para `/cfp/perfil`
