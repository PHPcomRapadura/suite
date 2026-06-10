# Spec — Site Público do Evento

**Status:** ✅ Implementado
**Módulo:** Admin → Eventos → Site do Evento + Página Pública
**Depende de:** `.claude/specs/admin/events-details.md`

---

## 1. Visão geral

Cada evento publicado pode ter uma página pública acessível via:

```
GET https://phpcomrapadura.org/{slug}
```

A página exibe informações do evento para o público: hero, patrocinadores, FAQ, código de conduta e CTA de ingressos. O organizador configura o visual (layout, cores, fonte) e o conteúdo (FAQ, código de conduta, link de ingressos) pelo painel admin, numa nova aba do hub de detalhes do evento.

O módulo tem duas partes:

1. **Admin** — configuração do site e gerenciamento de patrocinadores (`/admin/events/{id}/site`)
2. **Público** — Vue SPA servida pela rota `/{slug}`

---

## 2. Database

### 2.1 Tabela `event_site_configs`

Relação **1:1** com `events` (`event_id` único).

| Coluna | Tipo | Default | Observação |
|--------|------|---------|-----------|
| `id` | `bigIncrements` | — | PK |
| `event_id` | `foreignId → events` | — | Único, `cascadeOnDelete` |
| `is_published` | `boolean` | `false` | Controla se a página pública está acessível |
| `layout` | `tinyInteger unsigned` | `1` | 1, 2 ou 3 (ver seção 6) |
| `primary_color` | `string(7)` | `#025c98` | Hex — cor principal do evento |
| `secondary_color` | `string(7)` | `#f59e0b` | Hex — cor de destaque |
| `font` | `string(50)` | `lexend` | `lexend`, `inter`, `poppins`, `space_grotesk` |
| `hero_tagline` | `string(255)` | null | Slogan ou chamada do evento |
| `ticket_url` | `string(500)` | null | Link externo para compra de ingressos |
| `code_of_conduct` | `longText` | null | Markdown |
| `faq` | `json` | null | Array de `{question, answer}` — ver 2.3 |
| `created_by` | `foreignId → users` | null | `nullOnDelete` |
| `timestamps` | — | — | — |

### 2.2 Tabela `event_sponsors`

Relação **1:N** com `events`.

| Coluna | Tipo | Default | Observação |
|--------|------|---------|-----------|
| `id` | `bigIncrements` | — | PK |
| `event_id` | `foreignId → events` | — | `cascadeOnDelete` |
| `name` | `string(255)` | — | Nome do patrocinador |
| `logo_url` | `string(500)` | null | URL pública no R2 |
| `website_url` | `string(500)` | null | Link externo |
| `level` | `string(30)` | — | `rapadura_tradicional`, `rapadura_com_coco`, `rapadura_com_castanha` |
| `sort_order` | `tinyInteger unsigned` | `0` | Ordenação dentro do nível |
| `timestamps` | — | — | — |

**Hierarquia de níveis** (exibição do maior para o menor):

| Nível (DB) | Rótulo exibido | Posição |
|------------|---------------|---------|
| `rapadura_com_castanha` | Rapadura com Castanha | Destaque — topo |
| `rapadura_com_coco` | Rapadura com Coco | Intermediário |
| `rapadura_tradicional` | Rapadura Tradicional | Base |

### 2.3 Tabela `event_schedule_items`

Relação **1:N** com `events`.

| Coluna | Tipo | Default | Observação |
|--------|------|---------|-----------|
| `id` | `bigIncrements` | — | PK |
| `event_id` | `foreignId → events` | — | `cascadeOnDelete` |
| `talk_id` | `foreignId → talks` | null | `nullOnDelete` — vínculo opcional com palestra aprovada |
| `title` | `string(255)` | null | Título do item (ou herdado de `talk.title`) |
| `speaker_name` | `string(255)` | null | Palestrante (ou herdado de `talk.speaker.user.name`) |
| `starts_at` | `dateTime` | — | Horário local do evento (sem timezone — app_timezone=UTC) |
| `duration` | `smallInteger unsigned` | `50` | Duração em minutos |
| `room` | `string(100)` | null | Sala / trilha |
| `type` | `enum` | `palestra` | `palestra`, `intervalo`, `abertura`, `encerramento`, `outro` |
| `sort_order` | `tinyInteger unsigned` | `0` | Ordenação dentro do mesmo horário |
| `created_by` | `foreignId → users` | null | `nullOnDelete` |
| `timestamps` | — | — | — |

> **Timezone:** `starts_at` é armazenado como horário local do evento (sem conversão). No frontend, usar sempre `timeZone: 'UTC'` em `toLocaleTimeString` / `toLocaleDateString` para exibir o valor como armazenado.

### 2.4 Estrutura do campo `faq` (JSON)

```json
[
  { "question": "Como me inscrever?", "answer": "Acesse o link de ingressos acima." },
  { "question": "O evento é presencial?", "answer": "Sim, com transmissão online simultânea." }
]
```

---

## 3. Models

### 3.1 `EventSiteConfig`

**Arquivo:** `app/Models/EventSiteConfig.php`

```php
protected $fillable = [
    'event_id', 'is_published', 'layout',
    'primary_color', 'secondary_color', 'font',
    'hero_tagline', 'ticket_url',
    'code_of_conduct', 'faq', 'created_by',
];

protected function casts(): array
{
    return [
        'is_published' => 'boolean',
        'faq'          => 'array',
    ];
}

public function event(): BelongsTo
{
    return $this->belongsTo(Event::class);
}
```

### 3.2 `EventSponsor`

**Arquivo:** `app/Models/EventSponsor.php`

```php
protected $fillable = [
    'event_id', 'name', 'logo_url', 'website_url', 'level', 'sort_order',
];

public function event(): BelongsTo
{
    return $this->belongsTo(Event::class);
}
```

### 3.3 `EventScheduleItem`

**Arquivo:** `app/Models/EventScheduleItem.php`

```php
/**
 * @property \Illuminate\Support\Carbon $starts_at
 */
protected $fillable = [
    'event_id', 'talk_id', 'title', 'speaker_name',
    'starts_at', 'duration', 'room', 'type', 'sort_order', 'created_by',
];

protected function casts(): array
{
    return ['starts_at' => 'datetime'];
}

public function event(): BelongsTo
{
    return $this->belongsTo(Event::class);
}

public function talk(): BelongsTo
{
    return $this->belongsTo(Talk::class);
}
```

> **PHPStan:** O `@property \Illuminate\Support\Carbon $starts_at` é necessário para que o PHPStan (nível 5) reconheça `$item->starts_at->toIso8601String()` sem erro. Sem o docblock, ele infere `mixed` e rejeita chamadas de método.

### 3.4 Relações no `Event`

```php
public function site(): HasOne
{
    return $this->hasOne(EventSiteConfig::class);
}

public function sponsors(): HasMany
{
    return $this->hasMany(EventSponsor::class)->orderBy('sort_order');
}

public function schedule(): HasMany
{
    return $this->hasMany(EventScheduleItem::class)->orderBy('starts_at')->orderBy('sort_order');
}
```

---

## 4. Admin — Configuração do site

### 4.1 Acesso

Nova entrada no hub de detalhes do evento (`/admin/events/{id}`):

```
┌──────────────────────────────────────┐
│  🌐  Site do Evento                   │
│  Página pública do evento            │
│  [Publicado ●] ou [Não publicado ○]  │
│  [Configurar →]                      │
└──────────────────────────────────────┘
```

O card exibe o estado de publicação. O botão "Configurar →" navega para `/admin/events/{id}/site`.

### 4.2 Página de configuração (`/admin/events/{id}/site`)

Divide-se em duas abas ou seções:

#### Seção A — Aparência e Conteúdo

| Campo | Tipo | Observação |
|-------|------|-----------|
| Layout | Radio 1/2/3 com preview em miniatura | Ver seção 6 |
| Cor primária | `input[type=color]` + hex text | |
| Cor secundária | `input[type=color]` + hex text | |
| Fonte | Select: Lexend / Inter / Poppins / Space Grotesk | |
| Tagline / Chamada | `input text` max 255 | Subtítulo exibido no hero |
| Link de ingressos | `input url` | Abre em nova aba |

**Botão:** "Salvar aparência"

#### Seção B — Conteúdo textual

| Campo | Tipo | Observação |
|-------|------|-----------|
| Código de conduta | `textarea` (Markdown, com preview) | |
| FAQ | Editor de pares pergunta/resposta | Botão "+ Adicionar pergunta"; reordenável |

**Botão:** "Salvar conteúdo"

#### Seção C — Publicação

```
[ ] Publicar página do evento
URL pública: https://phpcomrapadura.org/{slug}  [Copiar]
```

Toggle que aciona `PATCH /admin/api/events/{event}/site/toggle-published`.
Quando publicado, exibe o link com botão copiar e link externo.

### 4.3 Gerenciamento de patrocinadores

Subseção na mesma página, abaixo das seções A/B/C.

```
Patrocinadores
──────────────────────────────────────────────

  Rapadura com Castanha
  ┌────────────────────────┐   [+ Adicionar]
  │ [Logo]  Nome           │
  │ website.com   [Editar] [Remover] [↕]  │
  └────────────────────────┘

  Rapadura com Coco
  ...

  Rapadura Tradicional
  ...
```

- Patrocinadores agrupados por nível
- Botão "+ Adicionar" por nível abre modal com: nome, logo (upload → R2), URL do site, nível
- Botão "Editar" abre o mesmo modal pré-preenchido
- Botão "Remover" abre `ConfirmModal`
- Setas ↑↓ ou drag para reordenar dentro do nível (atualiza `sort_order`)

**Path de upload no R2:**

```
events/{event_id}/sponsors/{sponsor_id}/logo.{ext}
```

---

## 5. API

### 5.1 Rotas admin

Dentro do grupo `middleware(['auth', EnsureAdminRole::class])`:

```php
// Site config
Route::get('/{event}/site',                        [EventSiteController::class, 'show'])
Route::post('/{event}/site',                       [EventSiteController::class, 'store'])
Route::put('/{event}/site',                        [EventSiteController::class, 'update'])
Route::patch('/{event}/site/toggle-published',     [EventSiteController::class, 'togglePublished'])

// Patrocinadores
Route::get('/{event}/site/sponsors',               [EventSponsorController::class, 'index'])
Route::post('/{event}/site/sponsors',              [EventSponsorController::class, 'store'])
Route::put('/{event}/site/sponsors/{sponsor}',     [EventSponsorController::class, 'update'])
Route::delete('/{event}/site/sponsors/{sponsor}',  [EventSponsorController::class, 'destroy'])
Route::patch('/{event}/site/sponsors/reorder',     [EventSponsorController::class, 'reorder'])

// Grade de programação
Route::get('/{event}/site/schedule',               [EventScheduleController::class, 'index'])
Route::post('/{event}/site/schedule',              [EventScheduleController::class, 'store'])
Route::put('/{event}/site/schedule/{item}',        [EventScheduleController::class, 'update'])
Route::delete('/{event}/site/schedule/{item}',     [EventScheduleController::class, 'destroy'])
```

### 5.2 Rota pública

```php
// Última rota em routes/web.php — após todas as outras
Route::get('/{slug}', [EventSitePublicController::class, 'show'])->name('event.site');
```

> **Atenção:** esta rota deve ser registrada após `/admin`, `/cfp`, `/sitemap.xml` e `/robots.txt` para não interceptar essas rotas. O controller valida se o evento existe e se `is_published = true` antes de retornar a view; caso contrário, retorna 404.

### 5.3 Controladores

| Controlador | Arquivo |
|-------------|---------|
| `EventSiteController` | `app/Http/Controllers/Admin/EventSiteController.php` |
| `EventSponsorController` | `app/Http/Controllers/Admin/EventSponsorController.php` |
| `EventScheduleController` | `app/Http/Controllers/Admin/EventScheduleController.php` |
| `EventSitePublicController` | `app/Http/Controllers/EventSitePublicController.php` |

### 5.4 Resposta `GET /admin/api/events/{event}/site`

```json
{
  "data": {
    "is_published": true,
    "layout": 1,
    "primary_color": "#025c98",
    "secondary_color": "#f59e0b",
    "font": "lexend",
    "hero_tagline": "A maior comunidade PHP do Nordeste",
    "ticket_url": "https://sympla.com.br/...",
    "code_of_conduct": "## Código de Conduta\n...",
    "faq": [
      { "question": "Como me inscrever?", "answer": "..." }
    ]
  }
}
```

### 5.5 Resposta `GET /admin/api/events/{event}/site/sponsors`

```json
{
  "data": [
    {
      "id": 1,
      "name": "Empresa X",
      "logo_url": "https://r2.phpcomrapadura.org/events/1/sponsors/1/logo.png",
      "website_url": "https://empresax.com.br",
      "level": "rapadura_com_castanha",
      "sort_order": 0
    }
  ]
}
```

### 5.6 Resposta pública `GET /{slug}`

O `EventSitePublicController` monta os dados do evento e retorna a Blade view que injeta o JSON no DOM para o Vue:

```php
return view('event-site', [
    'eventData' => json_encode([
        'event'    => [...],   // nome, slug, datas, local, cover_image, is_online, description, is_accepting_talks
        'site'     => [...],   // config de aparência e conteúdo (layout, cores, fonte, faq, etc.)
        'sponsors' => [...],   // agrupados por nível: { rapadura_com_castanha: [...], ... }
        'schedule' => [...],   // agrupados por data (YYYY-MM-DD): { '2026-06-10': [{...}], ... }
        'speakers' => [...],   // palestrantes com talks aprovadas: [{ id, name, avatar_url, city, state, twitter, github, linkedin, website }]
    ]),
]);
```

**Formato do campo `schedule`:** chave = data em `YYYY-MM-DD`, valor = array de items ordenados por `starts_at`.

```json
{
  "2026-06-10": [
    {
      "title": "Abertura",
      "speaker_name": null,
      "starts_at": "2026-06-10T09:00:00.000000Z",
      "duration": 30,
      "room": "Auditório",
      "type": "abertura"
    }
  ]
}
```

> **Timezone:** `starts_at` é serializado como ISO 8601 com `+00:00`. No frontend, usar `timeZone: 'UTC'` em `toLocaleTimeString` para exibir o horário como armazenado (sem conversão pelo browser).

---

## 6. Três layouts

O layout é selecionado em `event_site_configs.layout` (1, 2 ou 3). Todos compartilham as mesmas seções de conteúdo, mas diferem na estrutura visual e uso das cores configuradas.

### Recursos comuns de UX/Acessibilidade (todos os layouts)

Implementados como melhoria pós-avaliação:

| Recurso | Descrição |
|---------|-----------|
| **Skip-to-content link** | Primeiro elemento focável (`href="#conteudo"`); `sr-only` por padrão, visível ao Tab com `background: primary_color` |
| **Nav sticky / header sticky** | Aparece ao scrollar (L1/L2: slide-down via `IntersectionObserver` no hero; L3: header sempre fixo); contém nome do evento, links condicionais por seção disponível, botão "Ingressos" em `secondary_color` |
| **Scroll spy** | `onScroll` percorre `main section[id]` e aplica `bg-white/15` + `aria-current="true"` no link da última seção cujo `getBoundingClientRect().top` passou o offset do nav (80px L1/L2, 56px L3) |
| **Back-to-top** | Botão circular fixo `bottom-6 right-6`, aparece após 400px de scroll, com `Transition` scale+opacity e `aria-label="Voltar ao topo"` |
| **IDs de seção** | Todas as seções têm `id` (`sobre`, `cfp`, `palestrantes`, `patrocinadores`, `programacao`, `faq`, `conduta`) e `scroll-mt-16` / `scroll-mt-14` para compensar o nav sticky |
| **Patrocinadores por tier** | Todos os layouts diferenciam o tamanho dos cards por nível (Castanha > Coco > Tradicional) |
| **`onUnmounted` cleanup** | Listener de scroll removido em todos os layouts |
| **`aria-hidden`** | SVGs decorativos marcados em todos os layouts |
| **`aria-controls` + `id` no FAQ** | Accordion com ARIA correto em todos os layouts |

---

### Layout 1 — Clássico

```
┌─────────────────────────────────────────────┐
│  Nav sticky (aparece após hero)             │
│  Nome · Sobre · CFP · Patrocinadores · FAQ  │
│                              [Ingressos]    │
├─────────────────────────────────────────────┤
│  [cover_image — fullwidth, min-h 480px]     │
│  overlay gradiente + logo + nome + tagline  │
│  Data · Local                               │
│  [Comprar ingresso]                         │
│  ↓ (scroll indicator)                       │
├─────────────────────────────────────────────┤
│  Sobre o evento (description)               │
├─────────────────────────────────────────────┤
│  CFP — card centralizado com ícone sólido   │
├─────────────────────────────────────────────┤
│  Palestrantes — grid 2→4 cols, card com     │
│  avatar/iniciais, nome, cidade, redes sociais│
├─────────────────────────────────────────────┤
│  Patrocinadores — por tier, tamanhos distintos │
├─────────────────────────────────────────────┤
│  Programação — grade multi-dia              │
├─────────────────────────────────────────────┤
│  FAQ — accordion                            │
├─────────────────────────────────────────────┤
│  Código de conduta — texto corrido          │
└─────────────────────────────────────────────┘
                              [↑ back-to-top]
```

- Cover image como banner superior com overlay gradiente `from-black/50 via-black/60 to-black/80`; fallback para `primary_color` sólido quando ausente
- `<main id="conteudo">` envolvendo o corpo de conteúdo
- CFP: card centralizado com ícone de microfone em fundo sólido `primary_color`, título em `primary_color`, botão com sombra
- Patrocinadores: Castanha `w-44 h-28`, Coco `w-36 h-20`, Tradicional `w-28 h-16`; logos em grayscale com `hover:grayscale-0`
- FAQ com `aria-controls` + `id` no painel de resposta

### Layout 2 — Imersivo

```
┌─────────────────────────────────────────────┐
│  Nav sticky (aparece após hero)             │
│  Nome · Sobre · CFP · Patrocinadores · FAQ  │
│                              [Ingressos]    │
├─────────────────────────────────────────────┤
│  Hero fullscreen (min-h-screen)             │
│  fundo: primary_color                       │
│  logo + nome 5xl/7xl + tagline              │
│  [Comprar ingresso] (secondary_color)       │
│  ↓ (scroll indicator)                       │
├─────────────────────────────────────────────┤
│  Faixa — color-mix(primary 85%, black)      │
│  Data · Local                               │
├─────────────────────────────────────────────┤
│  Sobre o evento (description)               │
├─────────────────────────────────────────────┤
│  CFP — card rounded-3xl centralizado        │
├─────────────────────────────────────────────┤
│  Palestrantes — grid 2→3 cols, cards com    │
│  ring colorido, avatar, nome, redes sociais │
├─────────────────────────────────────────────┤
│  Patrocinadores — cards brancos por tier    │
├─────────────────────────────────────────────┤
│  Programação — grade multi-dia              │
├─────────────────────────────────────────────┤
│  FAQ — accordion com borda primary          │
├─────────────────────────────────────────────┤
│  Código de conduta — card bg-gray           │
└─────────────────────────────────────────────┘
                              [↑ back-to-top]
```

- Sem cover image — `primary_color` como fundo de tela inteira
- `<main id="conteudo">` envolvendo o corpo de conteúdo
- Back-to-top usa `secondary_color` para coerência com o CTA de ingressos
- Patrocinadores: Castanha `w-52 h-32`, Coco `w-40 h-24`, Tradicional `w-32 h-20`; cards brancos com sombra no hover

### Layout 3 — Minimalista

```
┌─────────────────────────────────────────────┐
│  Header sticky permanente (primary_color)   │
│  Logo · Nome · Sobre · CFP · Patrocinadores │
│  Programação · FAQ         [Ingressos]      │
├─────────────────────────────────────────────┤
│  <main> — max-w-2xl centralizado            │
│  H1 · Tagline · Data · Local · [Ingressos]  │
├─────────────────────────────────────────────┤
│  Sobre o evento (description)               │
├─────────────────────────────────────────────┤
│  CFP — card horizontal com ícone e botão    │
├─────────────────────────────────────────────┤
│  Palestrantes — grid 2 cols, estilo lista   │
│  avatar 40px, nome, cidade, redes sociais   │
├─────────────────────────────────────────────┤
│  Patrocinadores — por tier com labels       │
│  Castanha > Coco > Tradicional              │
├─────────────────────────────────────────────┤
│  Programação — lista minimalista com dots   │
├─────────────────────────────────────────────┤
│  FAQ — lista colapsável com dividers        │
├─────────────────────────────────────────────┤
│  Código de conduta — texto inline           │
└─────────────────────────────────────────────┘
                              [↑ back-to-top]
```

- Header `sticky top-0 z-50` sempre visível; links de nav `hidden md:flex`
- Sem cover image — tipografia e conteúdo como identidade principal
- CFP: card horizontal `flex-col sm:flex-row` com ícone em `primary_color`, texto e botão "Enviar proposta"
- Patrocinadores: Castanha `w-36 h-20`, Coco `w-28 h-16`, Tradicional `w-24 h-14`; todos com `grayscale hover:grayscale-0`
- FAQ: hover no botão aplica `color: primary_color` via inline style

---

## 7. Frontend

### 7.1 Blade view + entry point Vue

**`resources/views/event-site.blade.php`** — mesmo padrão do `cfp.blade.php`:
- Anti-flash dark mode
- Loader "Perainda!"
- `@vite(['resources/css/app.css', 'resources/js/event-site.js'])`
- `<div id="event-site-app"></div>`
- JSON injetado em `<script>` tag para o Vue consumir sem fetch adicional

**`resources/js/event-site.js`** — entry point simples (sem router):

```js
import { createApp } from 'vue'
import EventSiteApp from './EventSiteApp.vue'

const rawData = JSON.parse(document.getElementById('event-site-data').textContent)
createApp(EventSiteApp, { data: rawData }).mount('#event-site-app')
```

**`vite.config.js`** — adicionar `'resources/js/event-site.js'` ao `input`.

### 7.2 Estrutura de arquivos Vue

```
resources/js/
├── event-site.js                        # entry point
├── EventSiteApp.vue                     # componente raiz — seleciona o layout
└── views/event-site/
    ├── Layout1.vue                      # Layout Clássico
    ├── Layout2.vue                      # Layout Imersivo
    └── Layout3.vue                      # Layout Minimalista
```

`EventSiteApp.vue` resolve o layout via `defineAsyncComponent` e controla o loader "Perainda!" via `Suspense @resolve`:

```vue
<script setup>
import { defineAsyncComponent } from 'vue'

const props = defineProps({ data: { type: Object, required: true } })

const layouts = {
    1: defineAsyncComponent(() => import('./views/event-site/Layout1.vue')),
    2: defineAsyncComponent(() => import('./views/event-site/Layout2.vue')),
    3: defineAsyncComponent(() => import('./views/event-site/Layout3.vue')),
}
const Layout = layouts[props.data.site?.layout] ?? layouts[1]

function onLayoutReady() {
    const loader = document.getElementById('page-loader')
    if (!loader) return
    loader.classList.add('hidden')
    setTimeout(() => loader.remove(), 400)
}
</script>

<template>
    <Suspense @resolve="onLayoutReady">
        <component
            :is="Layout"
            :event="data.event"
            :site="data.site"
            :sponsors="data.sponsors"
            :schedule="data.schedule"
            :speakers="data.speakers ?? []"
        />
    </Suspense>
</template>
```

> **Por que `Suspense @resolve`?** O `defineAsyncComponent` faz o componente carregar de forma lazy. O evento `@resolve` dispara **após** o componente ter sido montado no DOM — momento certo para esconder o loader. Remover o loader no `event-site.js` (antes do `mount`) resultaria em flash de tela em branco.

**`resources/js/event-site.js`** — não remove o loader (isso é responsabilidade do `EventSiteApp.vue`):

```js
import { createApp } from 'vue'
import EventSiteApp from './EventSiteApp.vue'

const rawData = JSON.parse(document.getElementById('event-site-data').textContent)
createApp(EventSiteApp, { data: rawData }).mount('#event-site-app')
```

### 7.3 Vue admin — `EventSite.vue`

**Arquivo:** `resources/js/views/admin/EventSite.vue`

- Busca `GET /admin/api/events/:id/site`, `GET /admin/api/events/:id/site/sponsors` e `GET /admin/api/events/:id/site/schedule` ao montar
- Exibe formulário de aparência (cores com color picker, layout com radio + preview, fonte com select)
- Exibe editor de FAQ (array dinâmico de pares pergunta/resposta com botões adicionar/remover/reordenar)
- Exibe textarea de código de conduta
- Exibe lista de patrocinadores agrupada por nível com modal de criação/edição e ConfirmModal para remoção
- Toggle de publicação com URL pública quando publicado
- Seção de grade de programação: listagem de itens agrupados por data, modal de criação/edição, ConfirmModal para remoção
  - Modal pré-preenche data/hora de `starts_at` com `timeZone: 'UTC'` (evitar conversão do browser)
  - Campo "Palestra vinculada": select de palestras aprovadas do CFP (preenche título, palestrante, duração)
  - Campo `type`: select com opções `palestra`, `intervalo`, `abertura`, `encerramento`, `outro`

**Timezone no admin:** ao editar um item, extrair data e hora de `starts_at` usando:
```js
const dt = new Date(item.starts_at)
return {
    date: dt.toLocaleDateString('fr-CA', { timeZone: 'UTC' }),           // YYYY-MM-DD
    time: dt.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: 'UTC' }),
}
```

---

## 8. Requests

### 8.1 `StoreEventSiteRequest` / `UpdateEventSiteRequest`

```php
'is_published'    => ['boolean'],
'layout'          => ['required', 'integer', 'in:1,2,3'],
'primary_color'   => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
'secondary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
'font'            => ['required', 'in:lexend,inter,poppins,space_grotesk'],
'hero_tagline'    => ['nullable', 'string', 'max:255'],
'ticket_url'      => ['nullable', 'url', 'max:500'],
'code_of_conduct' => ['nullable', 'string'],
'faq'             => ['nullable', 'array'],
'faq.*.question'  => ['required', 'string', 'max:500'],
'faq.*.answer'    => ['required', 'string'],
```

### 8.2 `StoreEventSponsorRequest` / `UpdateEventSponsorRequest`

```php
'name'        => ['required', 'string', 'max:255'],
'logo'        => ['nullable', 'image', 'max:2048'],
'website_url' => ['nullable', 'url', 'max:500'],
'level'       => ['required', 'in:rapadura_tradicional,rapadura_com_coco,rapadura_com_castanha'],
'sort_order'  => ['nullable', 'integer', 'min:0'],
```

### 8.3 `StoreEventScheduleItemRequest` / `UpdateEventScheduleItemRequest`

```php
'talk_id'      => ['nullable', 'exists:talks,id'],
'title'        => ['nullable', 'string', 'max:255'],
'speaker_name' => ['nullable', 'string', 'max:255'],
'starts_at'    => ['required', 'date'],
'duration'     => ['nullable', 'integer', 'min:1', 'max:480'],
'room'         => ['nullable', 'string', 'max:100'],
'type'         => ['required', 'in:palestra,intervalo,abertura,encerramento,outro'],
'sort_order'   => ['nullable', 'integer', 'min:0'],
```

---

## 9. Arquivos a criar / modificar

**Criar:**

| Arquivo | Tipo |
|---------|------|
| `database/migrations/..._create_event_site_configs_table.php` | Migration |
| `database/migrations/..._create_event_sponsors_table.php` | Migration |
| `database/migrations/..._create_event_schedule_items_table.php` | Migration |
| `app/Models/EventSiteConfig.php` | Model |
| `app/Models/EventSponsor.php` | Model |
| `app/Models/EventScheduleItem.php` | Model |
| `app/Http/Controllers/Admin/EventSiteController.php` | Controller |
| `app/Http/Controllers/Admin/EventSponsorController.php` | Controller |
| `app/Http/Controllers/Admin/EventScheduleController.php` | Controller |
| `app/Http/Controllers/EventSitePublicController.php` | Controller |
| `app/Http/Requests/Admin/StoreEventSiteRequest.php` | Request |
| `app/Http/Requests/Admin/UpdateEventSiteRequest.php` | Request |
| `app/Http/Requests/Admin/StoreEventSponsorRequest.php` | Request |
| `app/Http/Requests/Admin/UpdateEventSponsorRequest.php` | Request |
| `app/Http/Requests/Admin/StoreEventScheduleItemRequest.php` | Request |
| `app/Http/Requests/Admin/UpdateEventScheduleItemRequest.php` | Request |
| `resources/views/event-site.blade.php` | Blade view |
| `resources/js/event-site.js` | Entry point Vue |
| `resources/js/EventSiteApp.vue` | Componente raiz |
| `resources/js/views/event-site/Layout1.vue` | Layout Clássico |
| `resources/js/views/event-site/Layout2.vue` | Layout Imersivo |
| `resources/js/views/event-site/Layout3.vue` | Layout Minimalista |
| `resources/js/views/admin/EventSite.vue` | View admin |
| `tests/Feature/Admin/EventSite/EventScheduleTest.php` | Testes da grade |

**Modificar:**

| Arquivo | O que muda |
|---------|-----------|
| `app/Models/Event.php` | Adicionar relações `site()`, `sponsors()` e `schedule()` |
| `routes/web.php` | Novas rotas admin; rota pública `/{slug}` no final |
| `vite.config.js` | Adicionar `event-site.js` ao `input` |
| `resources/js/router/admin.js` | Rota `events/:id/site` → `EventSite.vue` |
| `resources/js/views/admin/EventDetail.vue` | Adicionar card "Site do Evento" no hub |

---

## 10. Testes

### 10.1 Site config + patrocinadores

**Arquivo:** `tests/Feature/Admin/EventSiteTest.php`

```
// Configuração do site
- guest recebe 401
- palestrante recebe 403
- retorna null quando site não configurado
- admin cria configuração do site
- admin atualiza configuração do site
- colaborador pode atualizar configuração
- toggle published ativa e desativa a página
- retorna 422 com cor inválida (não é hex)
- retorna 422 com layout inválido (fora de 1-3)
- retorna 422 com URL de ingresso inválida
- FAQ é salvo e retornado corretamente como array
- código de conduta é salvo e retornado

// Patrocinadores
- lista patrocinadores agrupados por evento
- admin adiciona patrocinador sem logo
- admin adiciona patrocinador com upload de logo (Storage::fake('r2'))
- admin atualiza patrocinador
- admin remove patrocinador
- reordenar atualiza sort_order
- retorna 422 com nível inválido

// Página pública
- retorna 404 para slug inexistente
- retorna 404 para evento com site não publicado
- retorna 200 para evento publicado com dados do evento, site e patrocinadores
- dados incluem patrocinadores agrupados por nível na ordem correta
- dados incluem description e is_accepting_talks do evento
```

### 10.2 Grade de programação

**Arquivo:** `tests/Feature/Admin/EventSite/EventScheduleTest.php`

```
- guest recebe 401 ao acessar grade
- palestrante recebe 403 ao acessar grade
- index retorna lista vazia quando não há itens
- index retorna itens ordenados por starts_at
- admin cria item de grade com dados válidos
- colaborador pode criar item de grade
- criação retorna 422 sem starts_at
- criação retorna 422 com type inválido
- admin atualiza item de grade
- admin remove item de grade
- guest recebe 401 ao tentar remover
```

---

## 11. Critérios de aceite

### Admin
- [ ] Card "Site do Evento" aparece no hub de detalhes do evento
- [ ] Card exibe estado de publicação (publicado / não publicado)
- [ ] Formulário de aparência salva layout, cores e fonte
- [ ] Preview do layout mostra miniatura representativa de cada opção
- [ ] Color picker e campo hex funcionam em sincronia
- [ ] FAQ permite adicionar, editar e remover perguntas
- [ ] Editor de código de conduta aceita e salva Markdown
- [ ] Toggle de publicação ativa/desativa a página pública
- [ ] Quando publicado, exibe URL com botão copiar
- [ ] Patrocinadores listados agrupados por nível em ordem
- [ ] Upload de logo vai para o R2 com o path correto
- [ ] Remoção de patrocinador deleta logo do R2
- [ ] Reordenação dentro do nível persiste no banco

### Página pública
- [ ] `/{slug}` retorna 200 para evento publicado
- [ ] `/{slug}` retorna 404 para evento não publicado ou inexistente
- [ ] Layout selecionado é renderizado corretamente
- [ ] Cores configuradas são aplicadas como CSS custom properties
- [ ] Fonte configurada é carregada e aplicada
- [ ] Seção "Sobre o evento" aparece quando `description` está preenchido e é ocultada quando nulo
- [ ] Seção CFP aparece quando `is_accepting_talks = true` e é ocultada quando false
- [ ] Botão "Enviar proposta" da seção CFP aponta para `/cfp`
- [ ] Seção "Palestrantes" aparece quando há ao menos um palestrante com palestra aprovada
- [ ] Seção "Palestrantes" é omitida quando não há palestras aprovadas
- [ ] Cada card exibe: avatar (ou círculo de iniciais com `primary_color`), nome, cidade/estado, ícones de redes sociais condicionais (Twitter/X, GitHub, LinkedIn, website)
- [ ] Palestrante com múltiplas palestras aprovadas aparece apenas uma vez (deduplicação por `speaker.id`)
- [ ] Patrocinadores exibidos na hierarquia correta (castanha > coco > tradicional)
- [ ] Link de ingressos abre em nova aba
- [ ] FAQ funciona como accordion
- [ ] Página não quebra quando campos opcionais são nulos (tagline, description, FAQ, código de conduta)
- [ ] Loader "Perainda!" aparece antes do conteúdo
- [ ] Dark mode respeita tokens `--color-*`
- [ ] Layout responsivo: funciona em mobile, tablet e desktop
