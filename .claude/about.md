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
- **Seção "Palestrantes"**: grid de palestrantes com palestras aprovadas no evento — avatar (ou iniciais), nome, cidade/estado, ícones de redes sociais (Twitter/X, GitHub, LinkedIn, website); aparece apenas quando há pelo menos um palestrante aprovado; deduplicação por `speaker.id` caso o mesmo palestrante tenha várias palestras aprovadas
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

**Despesas por evento:**
- `EventExpense` model com enum de 9 categorias, `decimal(10,2)`, `is_paid`, `receipt_url` (R2), `created_by`/`updated_by`
- CRUD via `EventExpenseController` + `EventExpenseService`; admin tem acesso total, colaborador só cria
- Comprovante opcional (JPG/PNG/WebP/PDF, 5 MB) armazenado no R2 em `events/{id}/expenses/{id}/receipt.{ext}`
- `EventExpenses.vue`: painel de totais (total/pago/pendente + barra de progresso), toggle cards/lista (persistido em `localStorage`), filtros reativos por categoria/status/data
- `ExpenseModal.vue`: máscara de moeda pt-BR sem biblioteca externa — `type="text" inputmode="numeric"`, lógica de centavos (`centsToDisplay`), `form.amount` armazena valor numérico
- 22 testes em `tests/Feature/Admin/Events/EventExpensesTest.php`

### ✅ Kanban de Tarefas — implementado

Quadro Kanban vinculado a um evento (`/admin/events/{id}/tasks`):

- **5 colunas**: A Fazer, Em Andamento, Em Revisão, Impedimento, Concluída — "Impedimento" sinaliza tarefa parada por bloqueio burocrático (cabeçalho vermelho)
- **Drag-and-drop** via HTML5 API (sem biblioteca extra) com atualização otimista de estado
- **Soft delete** com aba "Lixeira" (admin) e botão "Restaurar"
- **Controle de acesso**: somente admin cria/edita/exclui; colaborador visualiza e move cards
- **`assigned_to`** validado para admin/colaborador (não palestrante)
- **`is_overdue`**: calculado no backend com prazo passado + status ≠ concluida
- **Comentários** em cada tarefa: qualquer usuário autenticado pode comentar; editar/excluir somente o autor (soft delete)
- **Contador de comentários** no card: ícone de balão + `N comentário(s)` abaixo do responsável, visível quando `> 0`; atualizado otimisticamente via evento `comment-changed` sem refetch do board
- **`TaskModal.vue`**: abas Detalhes e Comentários; campos desabilitados para colaborador
- **Card no hub** do evento com barra de progresso (concluídas/total) e alerta de atrasadas
- **41 testes de feature** (28 de tarefas + 13 de comentários)

### ✅ Controle de Participantes — implementado

Importação e visualização de participantes de um evento (`/admin/events/{id}/participants`):

**Importação:**
- Upload de CSV exportado do Sympla via `ParticipantUploadModal.vue`
- Detecção automática de encoding (UTF-8 / Latin-1 / Windows-1252)
- Upsert idempotente por `(event_id, registration_order)` — re-uploads atualizam sem duplicar
- `first_name` e `last_name` gravados em maiúsculas via `mb_strtoupper($value, 'UTF-8')`
- `email` gravado em minúsculas; `amount` parseado de `"R$ 1.500,00"` → `1500.00`
- Erros por linha registrados e retornados sem interromper o import
- Somente `admin` pode fazer upload, excluir individual ou limpar todos

**Interface:**
- Toggle cards/lista persistido em `localStorage` (`participants_view_mode`)
- Modo cards: grid 3/2/1 colunas; Modo lista: tabela com scroll horizontal mobile
- Painel de stats: total, aprovados, barra de progresso de check-in
- Filtros reativos: busca (nome/email), tipo de ingresso, estado de pagamento, check-in
- Paginação: 50 por página

**Infraestrutura:**
- `EventParticipant` model com índice único `(event_id, registration_order)`
- `EventParticipantService` com `import()`, `list()`, `summary()`, `delete()`, `clear()`
- `EventParticipantController` — 4 actions (index, upload, destroy, clear)
- `UploadParticipantsRequest` com validação `mimes:csv,txt`
- Card "Participantes" no hub do evento com stats e link ativo
- 27 testes de feature

### ✅ Sorteio Digital — implementado

Sorteio de participantes vinculado ao evento (`/admin/events/{id}/lottery`):

- **Pool**: apenas participantes com `checked_in = true`
- **Unicidade**: cada participante só pode ser sorteado uma vez por evento (`UNIQUE(event_id, participant_id)`)
- **Animação**: overlay fullscreen com favicon girando (`spin-full 1.2s`), contagem regressiva 3→2→1, chamada API em paralelo com a animação
- **Revelação**: nome + e-mail ofuscado (`wi*****@gmail.com`), chuva de confete via `canvas-confetti`
- **Auto-close**: revelação fecha automaticamente após 4 s; botão "Continuar" para fechar manualmente
- **Lista de sorteados**: posição, nome completo, e-mail ofuscado (sem tipo de ingresso)
- **Reset**: `ConfirmModal` com contagem dinâmica dos já sorteados; somente `admin`
- **Controle de acesso**: `draw` e `reset` exigem `role:admin`; `index` aberto para colaborador
- **Card no hub**: exibe sorteados e disponíveis; link para gerenciar
- 15 testes de feature

### ✅ Palestrantes — implementado

Listagem somente-leitura de palestrantes cadastrados (`/admin/speakers`):

- **Filtros**: busca por nome/e-mail (debounce 300 ms), cidade (debounce 300 ms), estado (dropdown 27 UFs)
- **Toggle cards/lista** persistido em `localStorage` (`speakers_view_mode`)
- **Cards**: avatar (ou iniciais coloridas), nome, e-mail, empresa, localização, contagem de palestras submetidas/aprovadas, badge Ativo/Inativo
- **Lista**: tabela responsiva com avatar+nome, localização, telefone, palestras (submetidas + apr.), status
- **Modal de detalhes** (`SpeakerModal.vue`): bio, contato (tel + site + redes sociais), último acesso, lista de palestras com badges de status, nível e duração
- **Controle de acesso**: `admin` e `colaborador` têm acesso; `palestrante` recebe 403
- **Paginação**: 12 por página com ellipsis
- **`SpeakerService`**: `list()` com `withCount` para contagem de palestras (total e aprovadas), ordenação por `orderByRaw` para preservar subqueries; `detail()` com `load(['user', 'talks.event'])`
- 16 testes de feature

### ✅ Artes de Divulgação (MVP) — implementado

Geração de artes para Instagram Stories e posts de feed a partir dos dados do evento (`/admin/events/{id}/social-assets`):

- **Formatos**: Story (1080×1920) e Post (1080×1080), gerados como PNG via `intervention/image` (driver GD)
- **Fundo**: capa do evento (`cover_image`) redimensionada com `cover()`; fallback para gradiente diagonal (cor primária → secundária do site, ou tokens padrão do sistema) quando não há capa ou o download falha
- **Overlay** escuro semi-transparente para legibilidade do texto sobre a imagem
- **Logo**: sobreposto no canto superior esquerdo quando `logo` está definido (com suporte a transparência PNG)
- **Texto**: nome do evento (título, quebra automática), tagline do site (se configurada), data + local, descrição curta e CTA "Garanta sua vaga" — renderizados com a fonte Lexend (`resources/fonts/Lexend-Variable.ttf`, variável, licença OFL) via GD/FreeType
- **Origem das imagens**: `EventSocialAssetService` resolve `cover_image`/`logo` primeiro como path no disco `r2` (mesmo padrão de `EventService::deleteImage()`); se a URL não pertencer ao R2, tenta download HTTP; se tudo falhar, aplica fallback silenciosamente
- **Armazenamento**: PNG salvo em `events/{event_id}/social/{type}-{format}.png` no disco `r2` (mesmo disco usado por capa/logo/patrocinadores — nunca o disco `public`, que exigiria `storage:link` e não é usado em nenhum outro lugar do projeto)
- **Persistência**: tabela `event_social_assets` (model `EventSocialAsset`) guarda a última arte gerada por evento+**tipo**+formato+**assunto** (`unique(event_id, type, format, subject_key)`); gerar de novo faz `updateOrCreate` — sobrescreve o registro e o arquivo no R2 em vez de acumular histórico. `subject_key` existe porque `NULL` não é único em índices SQL — vale `'event'` quando o tipo não tem um assunto específico (ex: `announcement`) ou `"talk:{id}"`/`"sponsor:{id}"` para tipos futuros com assunto (palestrante/patrocinador)
- **Fluxo**: `GET /admin/api/events/{event}/social-assets` (dados do evento + lista de artes já geradas) + `POST .../social-assets/generate` (`format: story|post`, `type: announcement|...`, default `announcement`) → `EventSocialAssetController`
- **Frontend**: `EventSocialAssets.vue` com seletor de **tipo de arte** (cards), seletor de formato, preview e download; ao carregar a tela, mostra a arte já gerada por tipo+formato (+ assunto) sem precisar clicar em "Gerar" de novo; card "Artes de Divulgação" no hub do evento
- **Arquitetura multi-template** (`.claude/plans/majestic-greeting-donut.md`): os primitivos de desenho (fundo, overlay, logo, bloco de texto medido) vivem em `App\Services\SocialAssets\SocialAssetCanvas`, desacoplados de `Event`. Cada tipo de arte é uma classe pequena em `App\Services\SocialAssets\Templates\` implementando `SocialAssetTemplate::compose()`. `EventSocialAssetService` é um orquestrador: monta fundo/overlay/logo, resolve o template pelo `type` e delega o conteúdo.
  - `AnnouncementTemplate` (Fase 1) — a chamada genérica do evento.
  - `SpeakerSpotlightTemplate` (Fase 2) — divulga uma palestra aprovada específica: avatar do palestrante (foto com moldura na cor secundária do site, ou círculo com iniciais quando não há `avatar_url`, mesmo padrão de fallback do `SpeakerModal.vue`), nome, empresa/cidade-UF, título da palestra, evento+data, CTA "Inscreva-se". Exige `talk_id` no payload; `EventSocialAssetController` valida que a talk pertence ao evento (404) e está `aprovada` (422) antes de gerar.
  - **Armadilha de `valign` corrigida em `SocialAssetCanvas::drawTextBlock()`**: o valign padrão do `intervention/image` é `bottom` (o `y` passado é a base do texto, que cresce para cima), o oposto do que o método promete (`y` = topo do bloco, empilha para baixo). Sem forçar `setValignment('top')` ali, um bloco de fonte pequena seguido de um bloco com fonte bem maior colide visualmente — foi exatamente o bug pego ao testar `SpeakerSpotlightTemplate` com um título de palestra longo. Corrigido uma vez no helper compartilhado; nenhum template precisa se preocupar com isso.
- **Origem do avatar do palestrante**: mesma resolução R2-primeiro-depois-HTTP do `SocialAssetCanvas::fetchImage()` usada para capa/logo do evento — reaproveitada, não duplicada.
- Fases seguintes (patrocinador, ingressos esgotando, é amanhã) descritas em `.claude/plans/majestic-greeting-donut.md`.
- 15 testes de feature no total: 9 do `AnnouncementTemplate` (story, post, formato inválido, tipo inválido, evento inexistente, fallback sem capa/logo, nome com caracteres especiais, regenerar não duplica registro, `show` retorna artes já geradas) + 6 do `SpeakerSpotlightTemplate` (gerar com sucesso, `talk_id` obrigatório, talk não aprovada rejeitada, talk de outro evento rejeitada, fallback sem avatar, regenerar não duplica)

### Eventos — sub-módulos pendentes

- ⬜ Fórum com tópicos por evento
