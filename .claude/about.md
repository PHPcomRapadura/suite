Suite de aplicações de uso da comunidade PHP com Rapadura

## Módulos

### ✅ Site institucional (implementado — `resources/views/welcome.blade.php`)
Single-page Blade + Tailwind CSS v4 com as seções:
- **Hero** — logo centralizada, subtitle, scroll indicator, animação de entrada
- **Sobre** — texto da comunidade + parallax com foto da comunidade
- **Eventos** — placeholder "Em breve" (seção completa a implementar)
- **Código de Conduta** — compromisso + regras comportamentais atualizadas em 02/06/2026
- **Contato** — email com botão copiar + 6 redes sociais (Telegram, Instagram, Twitter/X, Facebook, GitHub, Flickr)
- **Footer** — fundo azul com marca d'água ilustração nordestina, logo branca, nav e copyright

Recursos transversais do site:
- Loader "Perainda!" com favicon girando
- Header fixo com scroll spy e menu mobile (drawer com focus trap)
- Back-to-top button contextual
- Transições de entrada (fade+slide) via IntersectionObserver
- SEO completo (OG, Twitter Card, JSON-LD, sitemap, robots.txt)
- Acessibilidade: skip link, aria-live, prefers-reduced-motion, aria-current

### ✅ Admin — Autenticação (implementado)

Área restrita em `/admin` com Vue.js 3 SPA + Laravel Sanctum:
- Login/logout com sessão + cookie HttpOnly
- Roles: `admin`, `colaborador`, `palestrante`
- Middleware `EnsureAdminRole` protege todas as rotas `/admin/*`
- Seed do primeiro admin via `ADMIN_EMAIL` / `ADMIN_PASSWORD` no `.env`
- 20 testes de feature cobrindo todos os cenários de autenticação
- Página de login com dark mode correto (`bg-(--color-surface)` em card e inputs)

> **Atenção com `ADMIN_PASSWORD` no `.env`:** se a chave existir com valor vazio (`ADMIN_PASSWORD=`), o PHP retorna `''` e o default do `env()` não é usado. Sempre preencha o valor antes de rodar a seed em um novo ambiente.

### ✅ Admin — CRUD de Eventos (implementado)

Gerenciamento de eventos da comunidade, acessível por `admin` e `colaborador`:

- **Listagem** em grid de cards com filtros: busca por nome/slug, status e ano
- **Criar/editar** via modal com todos os campos: nome, slug (auto-gerado do nome), edição, descrição, data início/fim, toggle online/presencial, local, capacidade
- **Imagens** (cover_image e logo) com upload para **Cloudflare R2** via disco `r2` (driver S3-compatible); preview local antes de salvar; remoção individual por card
- **Status** com máquina de estados: `rascunho → publicado → encerrado | cancelado` — somente admin publica e cancela; colaborador pode encerrar
- **Toggle CFP** (`is_accepting_talks`) — somente admin; ativo apenas para eventos publicados
- **Eventos cancelados/encerrados** não são editáveis (422)
- 34 testes de feature cobrindo acesso, listagem com filtros, criação, edição, transições de status, toggle CFP e uploads R2

Padrão de implementação adotado:
- `EventService::uploadImage()` → `Storage::disk('r2')->putFileAs()` + URL pública
- `EventService::deleteImage()` → extrai path via `Storage::disk('r2')->url('')` dinamicamente (funciona com disco real e com `Storage::fake('r2')`)
- `EventController::update()` aceita tanto `PUT` quanto `POST` com `_method: PUT` (method spoofing para `FormData`)

### ✅ Testes E2E (Playwright)

Testes de ponta a ponta em `tests/e2e/`:
- `home.spec.js` — cobre `GET /`: status 200, `<main>` visível, nav principal presente
- Requer containers no ar (`docker compose up -d`) e `npx playwright install chromium`

### ✅ Call for Papers (CFP) — implementado

Vue.js SPA pública em `/cfp`, separada do admin, com autenticação própria via `POST /cfp/login`:

- **Home** (`/cfp`) — grid de cards com eventos cujo CFP está `aberto` ou `aguardando`; badge de status; botão "Submeter palestra"; botão "Palestras enviadas (N)" para palestrantes logados; link "Perfil" no header exclusivo para palestrantes
- **Login** (`/cfp/login`) — aceita qualquer role ativo (ao contrário do `/admin/login` que bloqueia `palestrante`); link para registro
- **Registro** (`/cfp/register`) — cria conta com `role=palestrante`, faz login imediato
- **Submissão** (`/cfp/submit/:eventId`) — guards: redireciona para login se não autenticado, para `/cfp/perfil` se bio vazia; exibe guia do palestrante (accordion), lista de propostas enviadas com botão Editar, formulário de nova proposta (título, resumo min 100 chars, duração 25/50 min, nível)
- **Perfil** (`/cfp/perfil`) — duas seções: dados do palestrante (avatar com upload para R2, bio obrigatória, empresa, city, state/UF, telefone, site, Twitter, GitHub, LinkedIn) e dados da conta (nome, e-mail, senha)
- **Middleware `EnsureSpeaker`** — 401 se não autenticado, 403 se não `palestrante`; registrado como alias `speaker`
- **39 testes de feature** cobrindo todos os endpoints (CFP público + submissão + perfil)

Campos da tabela `speakers`: `bio`, `company`, `city`, `state`, `avatar_url`, `phone_number`, `website`, `twitter`, `github`, `linkedin`. Avatar armazenado no R2 em `speakers/{user_id}/avatar.{ext}`.

### Eventos (Sistema gerenciado de eventos) — em andamento

O módulo base de controle de eventos está implementado. Os sub-módulos a implementar são:
- ✅ Controle de eventos (CRUD + status + imagens R2 + CFP toggle)
- ✅ Controle de submissão de palestras por evento (CFP)
- ⬜ Controle de despesas por evento
- ⬜ Controle de tarefas por evento (Kanban)
- ⬜ Fórum com tópicos específicos relacionados a um evento
- ⬜ Controle de participantes com upload de arquivo CSV
- ⬜ Sorteio digital de acordo com os participantes de um evento
