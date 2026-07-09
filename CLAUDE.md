# CLAUDE.md — PHP com Rapadura Suite

Guia para desenvolvimento neste projeto com Claude Code.

---

## ⚠️ Regra obrigatória antes de qualquer commit

**Antes de commitar, atualize as documentações relacionadas aos arquivos alterados.**

| Se alterou | Atualize |
|-----------|----------|
| Qualquer arquivo de `app/`, `routes/`, `database/` | `CLAUDE.md` (status), `.claude/about.md`, spec correspondente |
| Nova feature ou módulo | Spec em `.claude/specs/`, `README.md`, `CLAUDE.md` (status) |
| Padrão de código ou arquitetura | `.claude/patterns/` e/ou `.claude/skills/` |
| Configuração de ambiente (`.env`, Docker, Vite) | `README.md`, `CLAUDE.md` |
| Testes | `.claude/skills/tests.md` se mudou a convenção |

Checklist rápido pré-commit:
- [ ] Specs das features implementadas marcadas como `✅ Implementado`
- [ ] Status no `CLAUDE.md` reflete o estado atual
- [ ] `README.md` com instruções de setup atualizadas
- [ ] `about.md` descreve o que foi entregue

---

## Contexto do Projeto

Suite de aplicações da comunidade **PHP com Rapadura**, composta por três módulos:

1. **Site institucional** *(implementado)* — Blade + Tailwind CSS v4, single-page com âncoras
2. **Call for Papers (CFP)** *(implementado)* — submissão de propostas de palestras
3. **Gestão de Eventos** *(a implementar)* — painel administrativo para organizadores

Stack: **Laravel 13 + PHP 8.4 | Blade (site) + Vue.js 3 SPA (admin) | Tailwind CSS v4 | MySQL 8.4 | Redis | Laravel Sanctum**

---

## Arquitetura

### Site institucional (Blade)

O site é uma **single-page Blade** com seções âncora. Não usa Vue.js.

```
resources/views/welcome.blade.php   # página principal
resources/css/app.css               # Tailwind v4 + design tokens + CSS customizado
resources/js/app.js                 # Vanilla JS (scroll spy, menu, animações)
public/images/                      # Imagens e SVGs estáticos
```

Rota principal e adicionais:
```
GET /              → view('welcome', compact('events'))  — events = publicado, starts_at DESC
GET /sitemap.xml   → resources/views/sitemap.blade.php
GET /robots.txt    → public/robots.txt (arquivo estático)
GET /{slug}        → EventSitePublicController@show (última rota — 404 se não publicado)
```

> **`@` em Blade (armadilha):** o compilador Blade interpreta `@php`, `@if`, etc. como diretivas. Strings como `@phpcomrapadura` ou `contato@phpcomrapadura.org` podem ser compiladas erroneamente. A solução correta é definir variáveis PHP no topo do template (`@php $contactEmail = '...' @endphp`) e usar `{{ $var }}` — dentro de expressões `{{ }}` o `@` nunca é processado como diretiva. Evitar `@@` como escape genérico: ele só funciona quando o segundo `@` inicia um diretivo reconhecido pelo Blade.

### CFP — Vue.js SPA

```
resources/js/
├── cfp.js                          # Bootstrap da SPA CFP (rotas públicas + área autenticada)
├── CfpApp.vue                      # Componente raiz
├── composables/
│   └── useCfpAuth.js               # Singleton reativo de auth (user, fetchUser, logout)
├── layouts/
│   └── CfpLayout.vue               # Layout com sidebar para área autenticada do palestrante
└── views/cfp/
    ├── Home.vue                    # Lista de eventos com CFP aberto/aguardando (redireciona palestrante logado)
    ├── Login.vue                   # Login de palestrantes (link "Esqueceu a senha?")
    ├── Register.vue                # Cadastro de palestrantes
    ├── ForgotPassword.vue          # Solicitar link de redefinição de senha
    ├── ResetPassword.vue           # Redefinir senha via token (lê ?token=&email= da URL)
    ├── CfpDashboard.vue            # Dashboard do palestrante: boas-vindas, stats, CFP abertos
    ├── CfpMyEvents.vue             # Histórico de propostas agrupadas por evento (com abstract)
    ├── Profile.vue                 # Perfil do palestrante (avatar R2, bio, localização, redes sociais)
    └── SubmitTalk.vue              # Submissão e edição de proposta para um evento
```

Rotas CFP público (sem autenticação):
```
GET  /cfp                        → view('cfp')  [SPA pública]
GET  /cfp/login                  → view('cfp')  [SPA pública]
GET  /cfp/{any}                  → view('cfp')  [SPA pública]
GET  /cfp/api/events             → CfpPublicController@events  [sem auth]
GET  /cfp/api/me                 → CfpAuthController@me  [sem auth — retorna null se não logado]
POST /cfp/login                  → CfpAuthController@login  [sem auth — aceita qualquer role ativo]
POST /cfp/logout                 → CfpAuthController@logout  [sem auth]
POST /cfp/forgot-password        → CfpPasswordResetController@sendLink  [sem auth]
POST /cfp/reset-password         → CfpPasswordResetController@reset  [sem auth]
```

Rotas CFP autenticadas (middleware `speaker`):
```
GET  /cfp/api/my-talks       → TalkSubmissionController@allMyTalks  [speaker]
```

> **Diferença do login admin:** `POST /cfp/login` aceita `palestrante` (e outros roles). `POST /admin/login` rejeita `palestrante` via `hasAdminAccess()`.

> **useCfpAuth — singleton:** `user` e `loaded` vivem em nível de módulo (fora do `export function`), portanto são compartilhados entre todos os componentes da SPA sem re-fetch. Após atualizar o avatar no perfil, deve-se escrever diretamente em `user.value.avatar_url` para a sidebar reagir sem reload.

### Admin — Vue.js SPA

```
resources/js/
├── admin.js                        # Bootstrap da SPA admin
├── App.vue                         # Componente raiz
├── router/admin.js                 # Vue Router do admin
├── composables/
│   ├── useAuth.js                  # Autenticação (user, logout)
│   └── useTheme.js                 # Dark mode (toggle, isDark)
├── layouts/AdminLayout.vue         # Layout com sidebar + slot
├── components/
│   ├── AppSidebar.vue              # Sidebar com nav, toggle de tema e logout
│   ├── ConfirmModal.vue            # Modal de confirmação genérico
│   ├── EventModal.vue              # Modal criar/editar evento (com upload R2)
│   ├── UserModal.vue               # Modal criar/editar usuário
│   └── ChangePasswordModal.vue     # Modal para o usuário logado trocar a própria senha
└── views/
    ├── auth/Login.vue              # Página de login (dark mode nativo)
    └── admin/
        ├── Dashboard.vue           # Dashboard com stats
        ├── Events.vue              # CRUD de eventos
        └── Users.vue               # CRUD de usuários
```

Rotas admin em `routes/web.php`:
```
GET  /admin/login              → view('admin')  [pública]
POST /admin/login              → AdminLoginController@login
POST /admin/logout             → AdminLoginController@logout  [auth]
GET  /admin/api/me             → Auth::user()  [auth]
PUT  /admin/api/account/password → AccountController@updatePassword  [auth]  ← troca da própria senha (exige current_password)
GET  /admin/api/dashboard/stats → DashboardController@stats  [auth]
GET  /admin/api/users          → UserController@index  [auth, role:admin]
POST /admin/api/users          → UserController@store  [auth, role:admin]
...
GET  /admin/api/events         → EventController@index  [auth]
POST /admin/api/events         → EventController@store  [auth]
GET  /admin/api/events/{id}     → EventController@show  [auth]
GET  /admin/api/events/{id}/cfp → EventController@cfp  [auth]  ← stub; substituído pelo CfpController no módulo CFP
PUT  /admin/api/events/{id}    → EventController@update  [auth]
POST /admin/api/events/{id}    → EventController@update  [auth] ← method spoofing
PATCH /admin/api/events/{id}/status      → EventController@updateStatus  [auth]
PATCH /admin/api/events/{id}/toggle-talks → EventController@toggleTalks  [auth, role:admin]
GET  /admin/dashboard        → view('admin')  [auth]
GET  /admin/users            → view('admin')  [auth]
GET  /admin/events           → view('admin')  [auth]
GET  /admin/events/{id}      → view('admin')  [auth]
GET  /admin/events/{id}/cfp  → view('admin')  [auth]
GET  /admin/events/{id}/site → view('admin')  [auth]
GET  /admin/{any}            → view('admin')  [auth]
GET  /admin/api/events/{id}/site                        → EventSiteController@show  [auth]
POST /admin/api/events/{id}/site                        → EventSiteController@store  [auth]
PUT  /admin/api/events/{id}/site                        → EventSiteController@update  [auth]
PATCH /admin/api/events/{id}/site/toggle-published      → EventSiteController@togglePublished  [auth]
GET  /admin/api/events/{id}/site/sponsors               → EventSponsorController@index  [auth]
POST /admin/api/events/{id}/site/sponsors               → EventSponsorController@store  [auth]
PUT  /admin/api/events/{id}/site/sponsors/{sponsor}     → EventSponsorController@update  [auth]
DELETE /admin/api/events/{id}/site/sponsors/{sponsor}   → EventSponsorController@destroy  [auth]
PATCH /admin/api/events/{id}/site/sponsors/reorder      → EventSponsorController@reorder  [auth]
GET  /admin/api/events/{id}/site/schedule               → EventScheduleController@index  [auth]
POST /admin/api/events/{id}/site/schedule               → EventScheduleController@store  [auth]
PUT  /admin/api/events/{id}/site/schedule/{item}        → EventScheduleController@update  [auth]
DELETE /admin/api/events/{id}/site/schedule/{item}      → EventScheduleController@destroy  [auth]
```

> **Importante:** Toda rota Vue acessível diretamente pela URL precisa de rota correspondente em `routes/web.php` retornando `view('admin')`.

### Upload de imagens — Cloudflare R2

Imagens dos eventos (`cover_image`, `logo`) são armazenadas no **Cloudflare R2** via disco `r2` (driver S3-compatible). O banco persiste apenas a URL pública — nunca dados binários.

Estrutura de paths no bucket:
```
events/{event_id}/cover.{ext}    ← imagem de capa (jpg/jpeg/png/webp, máx 5 MB)
events/{event_id}/logo.{ext}     ← logo do evento (jpg/jpeg/png/webp, máx 2 MB — SVG bloqueado por risco de XSS)
```

Para testes: usar `Storage::fake('r2')` — o `EventService::deleteImage()` usa `Storage::disk('r2')->url('')` dinamicamente para extrair o path da URL, funcionando tanto com o disco real quanto com o fake.

Requests com `multipart/form-data` em PUT usam **method spoofing** (`_method: PUT` no body + `POST` no axios). No backend, há uma rota `Route::post` paralela para suportar isso.

> Para testes de validação de arquivo que retornam 422, usar `->withHeaders(['Accept' => 'application/json'])->post(...)` para forçar resposta JSON em vez de redirect.

---

## ⚠️ Tailwind v4 — CSS Variables

**CRÍTICO:** No Tailwind v4, a sintaxe para CSS custom properties mudou.

| Errado ❌ | Correto ✅ |
|-----------|-----------|
| `bg-[--color-bg]` | `bg-(--color-bg)` |
| `text-[--color-text]` | `text-(--color-text)` |
| `border-[--color-border]` | `border-(--color-border)` |
| `hover:text-[--color-primary]` | `hover:text-(--color-primary)` |

A sintaxe `[--color-X]` gera `background-color: --color-bg` (inválido no CSS).
A sintaxe `(--color-X)` gera `background-color: var(--color-bg)` (correto).

---

## Design System

### Fonte

**Lexend** — carregada via Bunny Fonts no `vite.config.js` com pesos 400/500/600/700.

### Tokens de cor (CSS variables — definidos em `app.css`)

```css
:root {
    --color-primary: #025c98;
    --color-primary-hover: #024d80;
    --color-bg: #f5f6f8;
    --color-surface: #ffffff;
    --color-border: #e5e7eb;
    --color-text: #111827;
    --color-text-muted: #6b7280;
    --color-success: #16a34a;
    --color-warning: #f59e0b;
    --color-danger: #dc2626;
}
```

### Regras de UI

- Mobile-first: telas funcionam a partir de 360px
- Radius: 10–12px em cards, 8–10px em inputs/botões
- Alvos clicáveis mínimo de 40px
- Grid de cards: `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4`
- Scroll behavior: `scroll-behavior: smooth` no HTML
- Animações de entrada: `.section-hidden` + `.section-visible` via `IntersectionObserver`

---

## Padrão de CRUD (módulos futuros)

Todo CRUD segue o padrão do módulo de Usuários.

### Arquivos por CRUD

| Arquivo | Localização |
|---------|-------------|
| Controller | `app/Http/Controllers/{Module}Controller.php` |
| StoreRequest | `app/Http/Requests/{Module}/Store{Model}Request.php` |
| UpdateRequest | `app/Http/Requests/{Module}/Update{Model}Request.php` |
| Service | `app/Services/{Model}Service.php` |
| View | `resources/js/views/{section}/{Model}s.vue` |
| Modal | `resources/js/components/{Model}Modal.vue` |

### Interface

- Listagem em grid de cards (nunca tabelas)
- 3 colunas desktop / 2 tablet / 1 mobile
- Paginação: 9 itens por página
- Criar/Editar via modal, Excluir via ConfirmModal
- Toggle para ativar/inativar no card

### Checklist de implementação

- [ ] Model com `$fillable` e `$casts`
- [ ] Migration
- [ ] Controller (index, store, show, update, destroy, toggleStatus)
- [ ] StoreRequest e UpdateRequest com validações em português
- [ ] Service com lógica de negócio
- [ ] Rotas API + rota web para reload
- [ ] View + Modal + ConfirmModal + Toggle
- [ ] Item no menu (`menu.js`) e rota no Vue Router

---

## SEO (site institucional)

O `welcome.blade.php` já inclui:

- `<title>`, `<meta description>`, `<meta keywords>`, `<meta robots>`
- Open Graph completo (7 tags) + Twitter Card `summary_large_image`
- JSON-LD: `Organization` (com fundador, endereço, sameAs) + `WebSite`
- `<link rel="canonical">` e `<link rel="sitemap">`
- `<meta name="theme-color">` e `<link rel="apple-touch-icon">`
- `<link rel="preload">` para logo e favicon
- `<h1 class="sr-only">` para leitores de tela

> **Blade vs JSON-LD:** Propriedades `@context`, `@type` do JSON-LD devem ser escritas como `@@context`, `@@type` para não conflitar com diretivas Blade.

---

## Acessibilidade (site institucional)

- `lang="pt-BR"` no `<html>`
- `<main id="main-content">` envolvendo o conteúdo principal
- Skip link "Pular para o conteúdo principal" (`.sr-only` com focus visível)
- `<div role="status" aria-live="polite">` para anúncios dinâmicos
- `aria-label` em todos os links externos e botões sem texto visível
- `aria-current="true"` nos links de navegação ativos (scroll spy)
- `aria-expanded`, `aria-controls`, `aria-modal` no menu mobile
- Focus trap no menu mobile (Tab/Shift+Tab cicla dentro do dialog)
- `@media (prefers-reduced-motion: reduce)` — desativa todas as animações
- `width` e `height` em todas as `<img>` para evitar CLS

---

## Ambiente Docker

| Serviço | Porta | URL |
|---------|-------|-----|
| Nginx | 8000 | http://localhost:8000 |
| MySQL | 3306 | — |
| PHPMyAdmin | 8080 | http://localhost:8080 |
| Redis | 6379 | — |

```bash
docker compose up -d --build     # subir
docker compose down              # parar
docker compose exec app bash     # acessar container
docker compose exec app php artisan migrate
docker compose exec app php artisan view:clear
docker compose logs -f app       # ver logs
```

Build do frontend (no host, Node.js disponível):
```bash
npm run build    # produção
npm run dev      # desenvolvimento com HMR
```

---

## Qualidade de código (CaptainHook)

Pre-commit hooks gerenciados pelo **CaptainHook** com a seguinte stack:

| Hook | Ferramenta | O que verifica |
|------|-----------|----------------|
| `pre-commit` | PHP Lint | Sintaxe PHP |
| `pre-commit` | **Pint** | Code style PSR-12 (Laravel preset) |
| `pre-commit` | **Larastan** | Análise estática nível 5 |
| `pre-commit` | **Pest** | Testes de feature |
| `pre-push` | **Composer audit** | CVEs em dependências PHP de produção (`--no-dev`) |
| `pre-push` | **NPM audit** | CVEs no bundle de produção (`--omit=dev --audit-level=high`) |
| `pre-push` | **Pest** | Testes + cobertura ≥ 80% |
| `commit-msg` | Beams | Subject 10–50 chars, body ≤ 72 chars/linha, capitalizado |

> **Auditoria de dependências:** os hooks de `pre-push` bloqueiam apenas vulnerabilidades em dependências de **produção** (o que é servido ao usuário/roda no servidor), evitando travar o push com avisos de ferramentas de dev sem correção disponível. Rode `npm audit` (completo) periodicamente para revisar vulnerabilidades de dev também.

```bash
# Rodar manualmente antes de commitar
docker compose exec app ./vendor/bin/pint              # corrige code style
docker compose exec app ./vendor/bin/phpstan analyse   # análise estática
docker compose exec app ./vendor/bin/pest --parallel   # testes

# Auditoria de dependências
docker compose exec app composer audit --no-dev        # CVEs PHP (produção)
npm audit --omit=dev --audit-level=high                # CVEs do bundle (produção)
npm audit                                              # auditoria completa (inclui dev)
```

Configuração: `captainhook.json` e `phpstan.neon` na raiz do projeto.

---

## Testes

```bash
# Rodar todos os testes
docker compose exec app ./vendor/bin/pest

# Com paralelismo
docker compose exec app ./vendor/bin/pest --parallel

# Suite específica
docker compose exec app ./vendor/bin/pest tests/Feature/Admin/

# Com cobertura
docker compose exec app ./vendor/bin/pest --coverage
```

Framework: **Pest v4** com `RefreshDatabase` habilitado globalmente para Feature tests.
Banco de testes: **SQLite in-memory** (configurado em `phpunit.xml`).

### Testes E2E (Playwright)

```bash
# Rodar testes e2e
npx playwright test tests/e2e/

# Arquivo específico
npx playwright test tests/e2e/home.spec.js

# Instalar browsers (necessário na primeira vez)
npx playwright install chromium
```

Testes e2e ficam em `tests/e2e/` e rodam contra `http://localhost:8000` (requer containers no ar).

---

## Status atual

### Site institucional

| Seção | Status |
|-------|--------|
| Loader ("Perainda!") | ✅ Implementado |
| Hero | ✅ Implementado |
| Sobre + Parallax | ✅ Implementado |
| Eventos | ✅ Implementado — grid de cards com eventos publicados |
| Código de Conduta | ✅ Implementado |
| Contato | ✅ Implementado |
| Footer | ✅ Implementado |
| SEO | ✅ 9.5/10 |
| Acessibilidade | ✅ 8.5/10 |
| Mobile Friendly | ✅ 8.5/10 |

### Admin

| Funcionalidade | Status |
|---------------|--------|
| Autenticação (login/logout) | ✅ Implementado |
| Middleware `EnsureAdminRole` | ✅ Implementado |
| Seed do primeiro admin | ✅ Implementado |
| Roles: admin, colaborador, palestrante | ✅ Implementado |
| Testes de autenticação (20 casos) | ✅ Implementado |
| CRUD de usuários (admin/colaborador) | ✅ Implementado |
| Middleware `CheckRole` (role:admin) | ✅ Implementado |
| Testes CRUD de usuários (31 casos) | ✅ Implementado |
| Dashboard com stats expandidas (eventos, CFP, palestras, palestrantes, tarefas, usuários) | ✅ Implementado |
| Dashboard — card "Próximo evento" com countdown e mini-stats | ✅ Implementado |
| Dashboard — feed "Atividade recente" (palestras pendentes + tarefas críticas) | ✅ Implementado |
| Dashboard — painel "Ações rápidas" e "Status do sistema" | ✅ Implementado |
| Dark mode (tokens + toggle + anti-flash) | ✅ Implementado |
| Dark mode na página de login | ✅ Implementado |
| Composables useAuth + useTheme | ✅ Implementado |
| Testes E2E da rota GET / (Playwright) | ✅ Implementado |
| CRUD de eventos (admin + colaborador) | ✅ Implementado |
| Upload de imagens para Cloudflare R2 | ✅ Implementado |
| Transições de status de eventos | ✅ Implementado |
| Toggle CFP (is_accepting_talks) | ✅ Implementado |
| Testes CRUD de eventos (34 casos) | ✅ Implementado |
| Página de detalhe do evento (hub) | ✅ Implementado |
| Botão "Ver detalhes" nos cards de eventos | ✅ Implementado |
| Módulo CFP — models, migrations, services, controllers | ✅ Implementado |
| Módulo CFP — CfpModal.vue + TalkReviewModal.vue + EventCfp.vue | ✅ Implementado |
| Testes CFP + Talks (19 casos) | ✅ Implementado |
| CFP público — página `/cfp` com listagem de eventos | ✅ Implementado |
| CFP público — login de palestrantes (`POST /cfp/login`) | ✅ Implementado |
| CFP público — API `GET /cfp/api/events` (sem auth) | ✅ Implementado |
| Testes CFP público (14 casos) | ✅ Implementado |
| CFP público — submissão de palestras (`/cfp/submit/:eventId`) | ✅ Implementado |
| CFP público — perfil do palestrante (`/cfp/perfil`) | ✅ Implementado |
| CFP público — middleware `EnsureSpeaker` + rotas protegidas | ✅ Implementado |
| CFP público — botão "Palestras enviadas" na home | ✅ Implementado |
| Testes submissão de palestras (25 casos) | ✅ Implementado |
| CFP público — avatar, city e state no perfil do palestrante | ✅ Implementado |
| CFP público — link "Perfil" no header da home para palestrantes | ✅ Implementado |
| CFP — área autenticada imersiva com sidebar (`CfpLayout.vue`) | ✅ Implementado |
| CFP — composable singleton `useCfpAuth` (user, fetchUser, logout) | ✅ Implementado |
| CFP — dashboard do palestrante: boas-vindas, stats, CFP abertos (`/cfp/dashboard`) | ✅ Implementado |
| CFP — histórico de propostas por evento com abstract (`/cfp/eventos`) | ✅ Implementado |
| CFP — API `GET /cfp/api/my-talks` retornando propostas + stats | ✅ Implementado |
| CFP — recuperação de senha (`ForgotPassword.vue` + `ResetPassword.vue`) | ✅ Implementado |
| CFP — `CfpPasswordResetController` + `CfpResetPasswordMail` + template HTML | ✅ Implementado |
| Configuração de e-mail via SMTP2Go (`mail.smtp2go.com:587`, TLS) | ✅ Implementado |
| Site do evento — seção "Sobre o evento" (exibe `events.description`) | ✅ Implementado |
| Site do evento — seção CFP com banner e botão `/cfp` (quando `is_accepting_talks`) | ✅ Implementado |
| Site do evento — configuração admin (`/admin/events/{id}/site`) | ✅ Implementado |
| Site do evento — gerenciamento de patrocinadores com upload R2 | ✅ Implementado |
| Site do evento — 3 layouts públicos (Clássico, Imersivo, Minimalista) | ✅ Implementado |
| Site do evento — página pública `GET /{slug}` | ✅ Implementado |
| Site do evento — card "Site do Evento" no hub de detalhes | ✅ Implementado |
| Testes Site do evento (27 casos) | ✅ Implementado |
| Grade de programação — model `EventScheduleItem` + migration | ✅ Implementado |
| Grade de programação — `EventScheduleController` CRUD | ✅ Implementado |
| Grade de programação — seção na `EventSite.vue` (admin) | ✅ Implementado |
| Grade de programação — exibição com dias nos 3 layouts públicos | ✅ Implementado |
| Grade de programação — separação multi-dia (tabs por data) | ✅ Implementado |
| Testes Grade de programação (11 casos) | ✅ Implementado |
| Site do evento — nav sticky + scroll spy + `aria-current` (3 layouts) | ✅ Implementado |
| Site do evento — skip-to-content link acessível via Tab (3 layouts) | ✅ Implementado |
| Site do evento — back-to-top button com transição (3 layouts) | ✅ Implementado |
| Site do evento — patrocinadores por tier com tamanhos diferenciados (3 layouts) | ✅ Implementado |
| Site do evento — ARIA: `aria-hidden` em SVGs, accordion FAQ com `aria-controls` + `id` | ✅ Implementado |
| Site do evento — seção "Palestrantes" com palestras aprovadas (foto, nome, cidade, estado, redes sociais) nos 3 layouts | ✅ Implementado |
| Despesas — model `EventExpense` + migration | ✅ Implementado |
| Despesas — `EventExpenseController` CRUD (admin + colaborador) | ✅ Implementado |
| Despesas — `EventExpenseService` com upload/delete de comprovante no R2 | ✅ Implementado |
| Despesas — `EventExpenses.vue` com toggle cards/lista e painel de totais | ✅ Implementado |
| Despesas — `ExpenseModal.vue` (criar e editar) | ✅ Implementado |
| Despesas — card "Despesas" no hub do evento com link ativo | ✅ Implementado |
| Testes Despesas (22 casos) | ✅ Implementado |
| Kanban de tarefas — model `EventTask` + migration | ✅ Implementado |
| Kanban de tarefas — model `EventTaskComment` + migration | ✅ Implementado |
| Kanban de tarefas — `EventTaskController` CRUD (admin + colaborador) | ✅ Implementado |
| Kanban de tarefas — `EventTaskCommentController` (qualquer autenticado; editar/excluir somente autor) | ✅ Implementado |
| Kanban de tarefas — `EventTaskService` com drag-and-drop, soft delete e restore | ✅ Implementado |
| Kanban de tarefas — `EventTasks.vue` com board, lixeira e HTML5 DnD | ✅ Implementado |
| Kanban de tarefas — `TaskModal.vue` com abas Detalhes e Comentários | ✅ Implementado |
| Kanban de tarefas — card no hub do evento com barra de progresso | ✅ Implementado |
| Testes Tarefas (28 casos) | ✅ Implementado |
| Testes Comentários de Tarefas (13 casos) | ✅ Implementado |
| Kanban de tarefas — coluna "Impedimento" (5ª coluna para bloqueios burocráticos) | ✅ Implementado |
| Participantes — model `EventParticipant` + migration + índice único `(event_id, registration_order)` | ✅ Implementado |
| Participantes — `EventParticipantController` CRUD (admin) | ✅ Implementado |
| Participantes — `EventParticipantService` com import CSV (encoding, upsert, mb_strtoupper, parseAmount) | ✅ Implementado |
| Participantes — `UploadParticipantsRequest` + `ParticipantUploadModal.vue` | ✅ Implementado |
| Participantes — `EventParticipants.vue` com toggle cards/lista (localStorage) e filtros reativos | ✅ Implementado |
| Participantes — card no hub do evento com stats e link ativo | ✅ Implementado |
| Testes Participantes (27 casos) | ✅ Implementado |
| Sorteio — model `EventLotteryWinner` + migration + índice único `(event_id, participant_id)` | ✅ Implementado |
| Sorteio — `EventLotteryController` (index, draw, reset) | ✅ Implementado |
| Sorteio — `EventLotteryService` com draw, reset, formatWinner, obfuscateEmail | ✅ Implementado |
| Sorteio — `EventLottery.vue` com animação, contagem regressiva, confete e lista de sorteados | ✅ Implementado |
| Sorteio — card no hub do evento com stats e link ativo | ✅ Implementado |
| Testes Sorteio (15 casos) | ✅ Implementado |
| Palestrantes — listagem somente-leitura com filtros (nome, cidade, estado) | ✅ Implementado |
| Palestrantes — toggle cards/lista (localStorage) + paginação | ✅ Implementado |
| Palestrantes — `SpeakerModal.vue` com detalhes, bio, contato e palestras | ✅ Implementado |
| Palestrantes — `SpeakerService` com `withCount` + `orderByRaw` | ✅ Implementado |
| Palestrantes — Sidebar colapsável (68 px colapsado, 260 px expandido) | ✅ Implementado |
| Testes Palestrantes (16 casos) | ✅ Implementado |
| Artes de Divulgação — geração de PNG (Story 1080×1920 / Post 1080×1080) via `intervention/image` | ✅ Implementado |
| Artes de Divulgação — composição com capa, logo, overlay, tema do site e fallback gradiente | ✅ Implementado |
| Artes de Divulgação — card no hub + tela de geração (`EventSocialAssets.vue`) | ✅ Implementado |
| Artes de Divulgação — persistência em `event_social_assets` (1 registro por evento+tipo+formato+assunto, `updateOrCreate`) | ✅ Implementado |
| Artes de Divulgação — arquitetura multi-template (`SocialAssetCanvas` + `Templates/*`, ver `.claude/plans/majestic-greeting-donut.md`) | ✅ Implementado (Fase 1/5) |
| Artes de Divulgação — "Divulgar palestrante" (`SpeakerSpotlightTemplate`, seletor de palestra aprovada) | ✅ Implementado (Fase 2/5) |
| Artes de Divulgação — "Divulgar patrocinador" (`SponsorSpotlightTemplate`, logo com `contain()`, selo de nível) | ✅ Implementado (Fase 3/5) |
| Artes de Divulgação — download real via `Storage::download()` (`GET .../social-assets/{asset}/download`) | ✅ Implementado |
| Artes de Divulgação — "Ingressos esgotando" (`SellingOutTemplate`, mensagem fixa, sem assunto) | ✅ Implementado (Fase 4/5) |
| Artes de Divulgação — "É amanhã" (`TomorrowTemplate`, data/hora, sem bloqueio de proximidade) | ✅ Implementado (Fase 5/5 — módulo completo) |
| Artes de Divulgação — redesign profissional (Lexend estática em 5 pesos, scrim em gradiente, CTA em pílula, avatar circular com anel, cartão arredondado, meta resumida via `EventMeta`) | ✅ Implementado |
| Artes de Divulgação — rodapé de patrocínio em todas as artes (`drawSponsorFooter`, chips brancos com logos, condicional) | ✅ Implementado |
| Artes de Divulgação — fetch de imagens com retry (3×) e cache por geração (logos externos intermitentes) | ✅ Implementado |
| Testes Artes de Divulgação (31 casos) | ✅ Implementado |

### Segurança

| Item | Status |
|------|--------|
| XSS — `eventData` na página pública escapado com `JSON_HEX_*` (impede breakout de `</script>`) | ✅ Implementado |
| IDOR — scoping `event_id` em Sponsors e Schedule (`abort_if`) | ✅ Implementado |
| Upload — logo SVG bloqueado (vetor de XSS) | ✅ Implementado |
| Rate limiting — `throttle:auth` (5/min por IP+e-mail) em login/registro/forgot/reset; limiter `api` (60/min) | ✅ Implementado |
| `TrustProxies` configurável via `TRUSTED_PROXIES` (CDN/LB) | ✅ Implementado |
| Security headers — middleware `SecurityHeaders` (nosniff, X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS em HTTPS, CSP em produção) | ✅ Implementado |
| Seed admin — senha previsível removida; aborta em produção sem `ADMIN_PASSWORD`, gera aleatória em dev | ✅ Implementado |
| Lockout — `UserService::isLastActiveAdmin` impede rebaixar/desativar o último admin ativo | ✅ Implementado |
| Import CSV — limite de 10.000 linhas (anti-DoS) | ✅ Implementado |
| `.env.example` — `SESSION_SECURE_COOKIE`, `TRUSTED_PROXIES` e avisos de produção | ✅ Implementado |
| Testes de segurança (9 casos: XSS, IDOR, SVG, lockout, isolamento) | ✅ Implementado |
| Conta — usuário logado troca a própria senha (`AccountController` + `ChangePasswordModal` na sidebar; exige `current_password`) | ✅ Implementado |
| Testes troca de senha (7 casos) | ✅ Implementado |
