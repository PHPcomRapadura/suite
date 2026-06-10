# Spec — Dashboard & Layout do Admin

**Status:** ✅ Implementado
**Módulo:** Admin — estrutura de layout + dashboard
**Depende de:** `auth-spec.md`, `user-crud-spec.md`, todos os módulos de evento, CFP, palestrantes

---

## 1. Visão geral

Layout permanente do painel administrativo: **sidebar lateral esquerda** colapsável com navegação, informações do usuário logado, alternância de tema e logout. Todas as rotas autenticadas usam esse layout via Vue Router nested routes.

O **Dashboard** é a tela inicial (`/admin/dashboard`) e funciona como painel de controle: exibe métricas de todo o sistema, destaca o próximo evento publicado, lista atividade recente (palestras aguardando + tarefas críticas) e oferece ações rápidas.

---

## 2. Estrutura visual do layout

```
┌──────────────────────────────────────────────────────────────────────┐
│  Sidebar (260px expandida | 68px colapsada)  │  Área de conteúdo     │
│  ───────────────────────────────────────────  │                       │
│  [Logo PHP com Rapadura / favicon]            │  <RouterView />        │
│  [← colapsar / → expandir]                   │                       │
│                                              │                       │
│  ● Dashboard                                 │                       │
│  ○ Eventos                                   │                       │
│  ○ Palestrantes                              │                       │
│  ○ Usuários (só admin)                       │                       │
│                                              │                       │
│  ─── (flex-grow) ────────────────────────── │                       │
│                                              │                       │
│  [Avatar] Nome Sobrenome                     │                       │
│           Administrador                      │                       │
│                                              │                       │
│  ☀ / 🌙  Alternar tema                      │                       │
│  ↪ Sair                                     │                       │
└──────────────────────────────────────────────────────────────────────┘
```

Em **mobile** (< `lg`): sidebar se torna drawer deslizante com overlay, acionado por botão hambúrguer no topo do conteúdo.

---

## 3. Arquitetura de componentes

```
App.vue
  └── RouterView
        ├── Login.vue                  ← rota guest (sem layout)
        └── AdminLayout.vue            ← rota pai autenticada
              ├── AppSidebar.vue       ← sidebar colapsável
              └── RouterView           ← conteúdo da rota filha
                    ├── Dashboard.vue
                    ├── Events.vue
                    ├── Speakers.vue
                    └── Users.vue
```

### 3.1 Nested routes (`resources/js/router/admin.js`)

```js
{
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { auth: true },
    children: [
        { path: 'dashboard',   name: 'admin.dashboard',  component: () => import('@/views/admin/Dashboard.vue') },
        { path: 'events',      name: 'admin.events',     component: () => import('@/views/admin/Events.vue') },
        { path: 'events/:id',  name: 'admin.events.show', component: () => import('@/views/admin/EventDetail.vue') },
        // ... sub-rotas de evento
        { path: 'speakers',    name: 'admin.speakers',   component: () => import('@/views/admin/Speakers.vue') },
        { path: 'users',       name: 'admin.users',      component: () => import('@/views/admin/Users.vue') },
        { path: '',            redirect: { name: 'admin.dashboard' } },
    ],
}
```

---

## 4. Usuário logado (`useAuth`)

**Arquivo:** `resources/js/composables/useAuth.js`

Estado global reativo no nível do módulo (singleton). `AdminLayout.vue` chama `fetchUser()` no `onMounted`.

```
GET /admin/api/me  →  middleware: auth + EnsureAdminRole  →  Auth::user()
```

---

## 5. Tema light/dark (`useTheme`)

**Arquivo:** `resources/js/composables/useTheme.js`

- Persiste em `localStorage` sob a chave `'admin-theme'`
- Aplica a classe `dark` no `<html>`
- **Anti-flash:** script inline no `<head>` do `admin.blade.php` aplica o tema antes do primeiro render

### Tokens CSS — dark mode (`resources/css/app.css`)

```css
html.dark {
    --color-primary:       #3b82f6;
    --color-primary-hover: #2563eb;
    --color-bg:            #0f172a;
    --color-surface:       #1e293b;
    --color-border:        #334155;
    --color-text:          #f1f5f9;
    --color-text-muted:    #94a3b8;
}
```

### Tokens CSS — sidebar (independente do tema)

```css
:root {
    --color-sidebar-bg:          #0f172a;
    --color-sidebar-border:      #1e293b;
    --color-sidebar-text:        #94a3b8;
    --color-sidebar-text-active: #f1f5f9;
    --color-sidebar-hover:       rgba(255, 255, 255, 0.06);
    --color-sidebar-active:      rgba(255, 255, 255, 0.10);
    --color-sidebar-logo-bg:     #020617;
}
```

---

## 6. Sidebar — `AppSidebar.vue`

**Arquivo:** `resources/js/components/AppSidebar.vue`

### 6.1 Colapso

- Estado `collapsed` persistido em `localStorage` sob a chave `'sidebar_collapsed'`
- Expandida: `w-[260px]` — exibe logo `phpcomrapadura_branca.svg?v=2` (155px wide) + rótulos dos itens
- Colapsada: `w-[68px]` — exibe `favicon.png` (36×36px) + somente ícones com `title` tooltip
- Transição suave: `transition-[width] duration-200 ease-in-out overflow-hidden`
- Botão colapsar: chevron esquerdo (`ml-auto` alinhado à direita), visível apenas em desktop (`hidden lg:flex`)
- Botão expandir: chevron direito, visível quando colapsado
- Botão fechar (mobile): `lg:hidden`, emite evento `@close` para `AdminLayout`

### 6.2 Itens de navegação

| Ícone SVG | Rótulo | Rota nomeada | Roles |
|-----------|--------|--------------|-------|
| grid 2×2 | Dashboard | `admin.dashboard` | admin, colaborador |
| calendar | Eventos | `admin.events` | admin, colaborador |
| mic | Palestrantes | `admin.speakers` | admin, colaborador |
| users | Usuários | `admin.users` | admin |

Filtro aplicado via `computed` usando `user.value.role`.

### 6.3 Estilo dos itens

- Colapsado: `justify-center px-0 py-2.5`, sem rótulo
- Expandido: `gap-3 px-3 py-2.5`, com rótulo truncado
- Ativo: `bg-(--color-sidebar-active) text-(--color-sidebar-text-active) font-medium`
- Inativo: `text-(--color-sidebar-text) hover:bg-(--color-sidebar-hover) hover:text-(--color-sidebar-text-active)`
- Altura mínima: `min-h-[40px]`

### 6.4 Avatar e rodapé

- Círculo `w-9 h-9`, fundo `--color-primary`, iniciais do nome (primeiras letras do 1º e 2º token)
- Quando colapsado: apenas avatar centralizado, sem texto
- Toggle de tema: mesmo estilo visual dos itens de menu
- Logout: `hover:text-red-400 hover:bg-red-500/10`

---

## 7. Layout — `AdminLayout.vue`

**Arquivo:** `resources/js/layouts/AdminLayout.vue`

```
div.flex.h-screen.overflow-hidden.bg-(--color-bg)
  ├── AppSidebar (hidden lg:flex — desktop, recebe largura reativa via collapsed)
  ├── div.fixed.inset-0.z-20 (overlay mobile — só quando sidebarOpen)
  ├── AppSidebar (fixed.inset-y-0.left-0.z-30 lg:hidden — mobile drawer)
  └── div.flex-1.flex.flex-col.min-w-0.overflow-auto
        ├── header (lg:hidden — topbar mobile com hambúrguer + logo)
        └── main.flex-1
              └── RouterView
```

### Transição drawer mobile

```css
.sidebar-slide-enter-active,
.sidebar-slide-leave-active { transition: transform 0.2s ease; }
.sidebar-slide-enter-from,
.sidebar-slide-leave-to    { transform: translateX(-100%); }
```

---

## 8. Dashboard — `Dashboard.vue`

**Arquivo:** `resources/js/views/admin/Dashboard.vue`

### 8.1 Layout geral

```
div.p-5.lg:p-8.max-w-7xl.mx-auto.flex.flex-col.gap-7
  ├── Saudação + data
  ├── Grid de 5 stat cards
  └── Grid 3 colunas (lg)
        ├── Coluna principal (lg:col-span-2)
        │     ├── Card "Próximo evento"
        │     └── Card "Atividade recente"
        └── Coluna lateral (lg:col-span-1)
              ├── Card "Ações rápidas"
              └── Card "Status do sistema"
```

### 8.2 Saudação

- `"Bom dia"` / `"Boa tarde"` / `"Boa noite"` por faixa de hora
- Exibe o primeiro token do nome do usuário logado
- Data formatada `pt-BR` com `weekday: 'long'`, atualiza via `setInterval(60000)`

### 8.3 Stat cards (5 cards)

Grid: `grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3`

| Card | Dado | Ícone | Cor | Clicável |
|------|------|-------|-----|----------|
| Eventos publicados | `events_published` | calendar | blue-500 | → `admin.events` |
| Palestras aguardando | `talks_pending` | mic | violet-500 | — |
| Palestrantes | `speakers_total` | users | teal-500 | → `admin.speakers` |
| Tarefas urgentes | `tasks_urgent` | alert-circle | red-500 (se > 0) | — |
| Usuários | `users_total` | shield | amber-500 | → `admin.users` |

Cada card: ícone em caixa colorida (fundo suave), número `text-2xl font-bold`, label, subtítulo contextual.
Cards clicáveis: `hover:border-(--color-primary)/40 hover:shadow-sm transition`.
Skeleton: 5 blocos `animate-pulse` enquanto `loading === true`.

### 8.4 Card "Próximo evento"

Fonte: `GET /admin/api/dashboard/next-event`

- Exibe o primeiro evento com `status = 'publicado'` e `starts_at >= now()`, ordenado por data
- Header: nome do evento, badges "CFP aberto" (verde) e "Online" (azul) quando aplicável
- Linha de metadados: data formatada + countdown `"em X dias"` / `"Amanhã"` / `"Hoje"`, localização
- Mini-stats row (3 cards `bg-(--color-bg)`): Inscritos / Check-in / Palestras pend.
- Botão "Gerenciar" → `admin.events.show` com o id do evento
- Estado vazio: ícone + texto + botão "Criar evento" → `admin.events`
- Skeleton durante carregamento

### 8.5 Card "Atividade recente"

Fonte: `GET /admin/api/dashboard/activity`

Lista unificada de até 8 itens (palestras aguardando + tarefas críticas), ordenados por data descendente.

**Palestras** (`type: 'talk'`):
- Ícone mic em círculo violeta
- Título da palestra + badge de status (Submetida / Em análise)
- Subtítulo: `"Nome do Palestrante · Nome do Evento · há Xh"`
- Clique navega para `admin.events.show` do evento

**Tarefas** (`type: 'task'`):
- Ícone alert em círculo vermelho (em atraso) ou laranja (impedimento)
- Título + badge `"Em atraso"` (vermelho) ou `"Impedimento"` (laranja)
- Subtítulo: `"Nome do Evento · há Xd"`
- Clique navega para `admin.events.show` do evento

Estado vazio: `"Nenhuma atividade pendente. Tudo em dia!"`

### 8.6 Card "Ações rápidas"

Botões com ícone colorido em caixa + título + subtítulo contextual:

| Ação | Destino | Subtítulo |
|------|---------|-----------|
| Criar evento | `admin.events` | "Novo evento na plataforma" |
| Ver palestrantes | `admin.speakers` | "X palestras aguardando" |
| Participantes *(se nextEvent)* | `admin.events.participants` | nome do próximo evento |
| Sorteio *(se nextEvent)* | `admin.events.lottery` | nome do próximo evento |
| Gerenciar usuários | `admin.users` | "X usuário(s) cadastrado(s)" |

### 8.7 Card "Status do sistema"

Três linhas label/valor:
- Usuários ativos: `(users_total - users_inactive) / users_total`
- CFPs abertos: `events_cfp_open`
- Tarefas críticas: `tasks_urgent` — texto vermelho se > 0

---

## 9. Endpoints da API

Todos dentro do grupo `middleware(['auth', EnsureAdminRole::class])` em `routes/web.php`.

### `GET /admin/api/dashboard/stats`

Controller: `DashboardController@stats`

```json
{
    "events_published": 3,
    "events_cfp_open":  1,
    "talks_pending":    12,
    "speakers_total":   47,
    "tasks_urgent":     2,
    "users_total":      8,
    "users_inactive":   1
}
```

`tasks_urgent` = `EventTask` com `status = 'impedimento'` OU `due_date < today AND status != 'concluida'`.

### `GET /admin/api/dashboard/next-event`

Controller: `DashboardController@nextEvent`

```json
{
    "id": 1,
    "name": "PHP com Rapadura 2026",
    "starts_at": "2026-08-15T00:00:00+00:00",
    "ends_at": null,
    "location": "Fortaleza, CE",
    "is_online": false,
    "is_accepting_talks": true,
    "participants_count": 120,
    "participants_checkedin": 47,
    "talks_pending": 8
}
```

Retorna `null` se não houver evento futuro publicado. Usa `withCount` para as contagens (sem `join` — evita sobreescrita dos subqueries de contagem).

### `GET /admin/api/dashboard/activity`

Controller: `DashboardController@activity`

Array de até 8 itens mesclando talks e tasks, ordenados por `at` desc:

```json
[
    {
        "type": "talk",
        "id": 5,
        "title": "Testes em PHP 8.4",
        "speaker_name": "Alice Silva",
        "event_name": "PHP com Rapadura 2026",
        "event_id": 1,
        "status": "submetida",
        "at": "2026-06-09T14:30:00+00:00"
    },
    {
        "type": "task",
        "id": 12,
        "title": "Contratar buffet",
        "event_name": "PHP com Rapadura 2026",
        "event_id": 1,
        "status": "impedimento",
        "is_overdue": false,
        "at": "2026-06-09T10:00:00+00:00"
    }
]
```

---

## 10. Padrões de implementação relevantes

### withCount sem join

`withCount` usa `addSelect` internamente. Chamar `->select('tabela.*')` após `withCount` **sobrescreve** as subqueries e zera as contagens. Usar `->orderByRaw('...')` em vez de `->join()->select()` quando a ordenação envolve outra tabela.

### Tipo das datas no PHPStan

`$model->starts_at` retorna `Carbon` (via cast), mas o PHPStan nível 5 não infere isso automaticamente sem `@property` no modelo. Anotar com `/** @var \Carbon\Carbon $startsAt */` antes de usar métodos Carbon.

### Propriedades dinâmicas de withCount no PHPStan

`$event->participants_checkedin_count` não existe como propriedade tipada. Usar `(int) $event->getAttribute('participants_checkedin_count')` para evitar erro de propriedade indefinida.

### Helpers privados no controller

Para `map()` com callbacks complexos que causam `unresolvable type` no PHPStan, extrair a lógica para métodos privados tipados (`private function formatTalkActivity(Talk $t): array`).

---

## 11. Arquivos

| Arquivo | Estado |
|---------|--------|
| `resources/js/layouts/AdminLayout.vue` | ✅ Implementado |
| `resources/js/components/AppSidebar.vue` | ✅ Implementado (com colapso) |
| `resources/js/composables/useAuth.js` | ✅ Implementado |
| `resources/js/composables/useTheme.js` | ✅ Implementado |
| `resources/js/views/admin/Dashboard.vue` | ✅ Implementado (dashboard completo) |
| `app/Http/Controllers/Admin/DashboardController.php` | ✅ Implementado (3 endpoints) |
| `resources/js/router/admin.js` | ✅ Implementado |
| `resources/css/app.css` | ✅ Implementado |
| `resources/views/admin.blade.php` | ✅ Implementado (anti-flash) |
| `routes/web.php` | ✅ Implementado |

---

## 12. Critérios de aceite

### Layout e sidebar
- [x] Sidebar fixa 260px em ≥ lg; drawer em < lg
- [x] Colapso para 68px persiste em `localStorage`
- [x] Logo branca 155px quando expandida; favicon 36px quando colapsada
- [x] Itens de nav mostram tooltip (`title`) quando colapsados
- [x] Item "Usuários" oculto para `colaborador`
- [x] Item ativo destacado via `route.name`
- [x] Transição de colapso suave (`transition-[width] duration-200`)
- [x] Toggle de tema alterna light/dark com persistência
- [x] Script anti-flash no `<head>` evita FOUC
- [x] Sidebar mantém visual escuro em ambos os temas

### Dashboard — stat cards
- [x] 5 cards com contagens reais do banco
- [x] Card "Tarefas urgentes" em vermelho quando > 0
- [x] Cards clicáveis navegam para a tela correspondente
- [x] Skeleton visível durante `loading === true`

### Dashboard — próximo evento
- [x] Exibe o evento publicado com `starts_at` futuro mais próximo
- [x] Countdown: "Hoje" / "Amanhã" / "em X dias"
- [x] Mini-stats: inscritos, check-ins, palestras pendentes
- [x] Estado vazio com botão "Criar evento"

### Dashboard — atividade recente
- [x] Palestras `submetida`/`em_analise` com badge de status
- [x] Tarefas em atraso (badge vermelho) e impedimento (badge laranja)
- [x] Máx 8 itens, ordenados por data descendente
- [x] Estado vazio: "Tudo em dia!"
- [x] Clique navega para o evento correspondente

### Dashboard — ações rápidas
- [x] Links para criação de evento, palestrantes, usuários
- [x] Links contextuais para participantes e sorteio quando há próximo evento
