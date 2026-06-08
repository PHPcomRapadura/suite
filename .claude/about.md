Suite de aplicações de uso da comunidade PHP com Rapadura

## Módulos

### ✅ Site institucional (implementado — `resources/views/welcome.blade.php`)
Single-page Blade + Tailwind CSS v4 com as seções:
- **Hero** — logo centralizada, subtitle, scroll indicator, animação de entrada
- **Sobre** — texto da comunidade + parallax com foto da comunidade
- **Eventos** — grid responsivo (1/2/3 cols) de cards com eventos `status = publicado`, ordenados por `starts_at DESC`; badge "CFP Aberto" condicional, data formatada pt-BR, local truncado, botão "Ver evento →" para `/{slug}`; estado vazio quando não há eventos publicados; animação escalonada por card
- **Código de Conduta** — compromisso + regras comportamentais
- **Contato** — email com botão copiar + 6 redes sociais
- **Footer** — fundo azul com marca d'água ilustração nordestina, logo branca, nav e copyright

Recursos transversais do site:
- Loader "Perainda!" com favicon girando (`.page-loader` / `.page-loader.hidden`)
- Header fixo com scroll spy e menu mobile (drawer com focus trap)
- Back-to-top button contextual
- Transições de entrada (fade+slide `translateY(40px)`) via IntersectionObserver (`.section-hidden` + `.section-visible`)
- SEO completo (OG, Twitter Card, JSON-LD, sitemap, robots.txt)
- Acessibilidade: skip link, aria-live, prefers-reduced-motion, aria-current

### ✅ Admin — Autenticação (implementado)

Área restrita em `/admin` com Vue.js 3 SPA + Laravel Sanctum:
- Login/logout com sessão + cookie HttpOnly
- Roles: `admin`, `colaborador`, `palestrante`
- Middleware `EnsureAdminRole` protege todas as rotas `/admin/*`
- Seed do primeiro admin via `ADMIN_EMAIL` / `ADMIN_PASSWORD` no `.env`
- 20 testes de feature cobrindo todos os cenários de autenticação
- Página de login com dark mode correto

> **Atenção com `ADMIN_PASSWORD` no `.env`:** se a chave existir com valor vazio (`ADMIN_PASSWORD=`), o PHP retorna `''` e o default do `env()` não é usado. Sempre preencha o valor antes de rodar a seed em um novo ambiente.

### ✅ Admin — CRUD de Eventos (implementado)

Gerenciamento de eventos da comunidade, acessível por `admin` e `colaborador`:

- **Listagem** em grid de cards com filtros: busca por nome/slug, status e ano
- **Criar/editar** via modal com todos os campos
- **Imagens** (cover_image e logo) com upload para **Cloudflare R2**
- **Status** com máquina de estados: `rascunho → publicado → encerrado | cancelado`
- **Toggle CFP** (`is_accepting_talks`) — somente admin
- 34 testes de feature

### ✅ Testes E2E (Playwright)

Testes em `tests/e2e/`:
- `home.spec.js` — cobre `GET /`: status 200, `<main>` visível, nav principal presente

### ✅ Call for Papers (CFP) — implementado

Vue.js SPA em `/cfp`, separada do admin, com autenticação própria e área imersiva do palestrante:

**Área pública (sem autenticação):**
- **Home** (`/cfp`) — grid de cards com eventos cujo CFP está aberto/aguardando; redireciona palestrante logado direto para o dashboard
- **Login/Registro** — aceita qualquer role ativo (ao contrário do `/admin/login`)

**Área autenticada — `CfpLayout.vue` (sidebar fixa, mobile-friendly):**
- **Dashboard** (`/cfp/dashboard`) — boas-vindas com avatar, 4 cards de stats (total, aprovadas, aguardando, recusadas), grid de eventos com CFP aberto e botão "Submeter palestra"
- **Meus Eventos** (`/cfp/eventos`) — histórico de propostas agrupadas por evento, com título, abstract (truncado em 2 linhas), status, nível, duração e data de submissão
- **Perfil** (`/cfp/perfil`) — avatar (R2), bio, empresa, city, state, redes sociais + dados da conta
- **Submissão** (`/cfp/submit/:eventId`) — listagem de propostas do evento, formulário, edição

**Recuperação de senha:**
- **`ForgotPassword.vue`** (`/cfp/forgot-password`) — formulário de solicitação; sempre exibe confirmação (evita enumeração de e-mails)
- **`ResetPassword.vue`** (`/cfp/reset-password?token=&email=`) — lê token/email da query string; detecta link inválido/ausente e redireciona para solicitar novo
- **`CfpPasswordResetController`** — `POST /cfp/forgot-password` (gera token via `Password::broker()`, envia Mailable customizado) e `POST /cfp/reset-password` (valida token, atualiza senha)
- **`CfpResetPasswordMail`** — Mailable com template HTML com branding PHP com Rapadura; link expira em 60 min
- **SMTP2Go** — `mail.smtp2go.com:587` (TLS); credenciais em `MAIL_USERNAME` / `MAIL_PASSWORD` no `.env`

**Infra:**
- **`useCfpAuth.js`** — composable singleton com `user`, `fetchUser`, `logout`; refs vivem em módulo para evitar re-fetch entre componentes
- **`GET /cfp/api/my-talks`** — retorna todas as propostas do palestrante com `abstract` + stats + evento vinculado
- **Middleware `EnsureSpeaker`** — 401/403 por role
- **39 testes de feature**

### ✅ Site Público do Evento — implementado

Cada evento publicado tem página pública `GET /{slug}`:

**Admin** (`/admin/events/{id}/site`):
- Aparência: layout, cores primária/secundária, fonte, tagline, link de ingressos
- Conteúdo: código de conduta, FAQ (editor dinâmico com pares pergunta/resposta)
- Publicação: toggle `is_published` com URL pública e botão copiar
- Patrocinadores: CRUD com upload de logo para R2, agrupados por nível (3 tiers)
- Grade de programação: CRUD de itens (título, palestrante, data/hora, duração, sala, tipo), vínculo opcional com palestras aprovadas do CFP

**Três layouts públicos:**
- **Layout 1 — Clássico**: hero com imagem de capa e overlay gradiente, seções abaixo
- **Layout 2 — Imersivo**: hero fullscreen em `primary_color`, conteúdo rico, patrocinadores com logos dimensionados por tier
- **Layout 3 — Minimalista**: header compacto, tipografia centrada, sem imagem hero

**Recursos comuns aos três layouts:**
- Loader "Perainda!" via `Suspense @resolve` em `EventSiteApp.vue` (esconde quando o layout carrega)
- Animações de entrada entre seções via `IntersectionObserver` (`.section-hidden` + `.section-visible`)
- `prefers-reduced-motion` respeitado (remove classes sem animar)
- Dark mode suportado (loader, seções, código de conduta)
- Ícones SVG inline substituindo emojis (📅 → calendário, 📍 → pin)
- Fuso horário: `formatTime` usa `timeZone: 'UTC'` — horários armazenados como horário local (app timezone = UTC)
- **Seção "Sobre o evento"**: exibe `events.description`; aparece apenas quando preenchido
- **Seção CFP**: banner com botão `/cfp`; aparece apenas quando `is_accepting_talks = true`
- **Nav sticky**: L1/L2 — nav oculta por padrão, aparece via `IntersectionObserver` no `#hero` quando ele sai da viewport; L3 — header sempre sticky no topo
- **Scroll spy**: `onScroll` + `getBoundingClientRect().top <= offset` atualiza `activeSection`; link ativo recebe destaque visual e `aria-current="true"`
- **Skip-to-content link**: `sr-only` por padrão, visível ao receber foco via Tab; aponta para `#conteudo` (`<main id="conteudo">`)
- **Back-to-top button**: fixo no canto inferior direito, aparece após 400px de scroll, com `<Transition>` suave
- **IDs de seção + `scroll-mt`**: todas as seções têm `id` (`sobre`, `cfp`, `patrocinadores`, `programacao`, `faq`, `conduta`) e `scroll-mt-16` para compensar nav fixa
- **Patrocinadores por tier**: todos os layouts diferenciam tamanho de card por nível — `rapadura_com_castanha` (master) > `rapadura_com_coco` (ouro) > `rapadura_tradicional` (prata)
- **`onUnmounted` cleanup**: scroll listener removido ao desmontar o componente
- **`aria-hidden="true"`** em todos os SVGs decorativos; `aria-controls` + `id` no padrão ARIA accordion do FAQ

**Grade de programação multi-dia:**
- Itens agrupados por data (`starts_at.toDateString()`) e ordenados por horário
- Quando mais de 1 dia: tabs clicáveis por data; seleção de dia ativo
- Badges por tipo: `palestra`, `intervalo`, `abertura`, `encerramento`, `outro`
- Vínculo opcional com palestra aprovada do CFP (auto-preenche título, palestrante, duração)

**Página pública:**
- Vue SPA servida por `event-site.blade.php`; dados injetados via `<script id="event-site-data">` sem fetch extra
- `EventSiteApp.vue` seleciona layout dinamicamente via `defineAsyncComponent`
- Última rota em `routes/web.php` (não intercepta `/admin`, `/cfp`, etc.)
- 404 se evento não existe ou `is_published = false`

**Testes:** 27 testes de site config + patrocinadores + página pública; 11 testes de grade de programação

### Eventos — sub-módulos pendentes

- ⬜ Controle de despesas por evento
- ⬜ Controle de tarefas por evento (Kanban)
- ⬜ Fórum com tópicos por evento
- ⬜ Controle de participantes (upload CSV)
- ⬜ Sorteio digital por evento
