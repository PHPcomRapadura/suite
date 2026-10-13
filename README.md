# PHP com Rapadura — Suite

Suite de aplicações de uso da comunidade PHP com Rapadura.

## Módulos

### ✅ Site Institucional
Single-page com as seções: Hero, Sobre, Eventos, Código de Conduta, Contato e Footer, mais a página `/blog` com o feed dos posts do WordPress legado (REST API, cache de 1 h e fallback para a última cópia boa).
Inclui loader "Perainda!", scroll spy, animações de entrada via IntersectionObserver e SEO completo.

### ✅ Admin — Autenticação
Área restrita em `/admin` com login, logout, controle de roles (`admin`, `colaborador`) e proteção de rotas.

### ✅ Admin — CRUD de Usuários
Gerenciamento de usuários (admin e colaborador) com listagem em cards, modal criar/editar e toggle de status.

### ✅ Admin — CRUD de Eventos
Gerenciamento de eventos com status (`rascunho → publicado → encerrado | cancelado`), upload de imagem de capa e logo para o **Cloudflare R2**, e controle de CFP (`is_accepting_talks`).

### ✅ Call for Papers (CFP)
Sistema completo de submissão de propostas por palestrantes — Vue SPA pública em `/cfp` com autenticação própria, perfil do palestrante (avatar, bio, redes sociais), submissão/edição de palestras por evento, e revisão pelo admin.

### ✅ Site Público do Evento
Cada evento publicado ganha uma página em `/{slug}` com três layouts (Clássico, Imersivo, Minimalista), patrocinadores por nível de cota, grade de programação multi-dia, FAQ, código de conduta e link de ingressos. Inclui loader "Perainda!" e animações de entrada entre seções.

### ✅ Despesas por Evento
Registro e acompanhamento de gastos com 9 categorias, comprovante de pagamento (PDF/imagem via R2), painel de totais com barra de progresso pago/pendente, toggle cards/lista e filtros reativos por categoria, status e data.

### ✅ Kanban de Tarefas por Evento
Quadro Kanban com 5 colunas (A Fazer, Em Andamento, Em Revisão, Impedimento, Concluída), drag-and-drop via HTML5, soft delete com lixeira e restore, controle de acesso por role (somente admin cria/edita/exclui) e comentários por tarefa com contador no card.

### ✅ Controle de Participantes por Evento
Importação via CSV exportado do Sympla (upsert idempotente por ordem de inscrição), visualização em cards ou lista com filtros (busca, tipo de ingresso, estado de pagamento, check-in), painel de stats (total, aprovados, barra de progresso de check-in) e exclusão individual ou total pelo admin.

### ✅ Sorteio Digital por Evento
Sorteio entre participantes aprovados com animação de contagem regressiva e confete, e-mail ofuscado na exibição pública, lista de sorteados (sem repetição por evento) e reset pelo admin.

### ✅ Palestrantes (Admin)
Listagem somente-leitura de todos os palestrantes cadastrados via CFP, com filtros por nome, cidade e estado, toggle cards/lista e modal de detalhes (bio, contato, redes sociais e histórico de palestras).

### ✅ Artes de Divulgação por Evento
Geração de imagens PNG prontas para Instagram (Story 1080×1920 e Post 1080×1080) a partir dos dados do evento, com 5 tipos de arte: chamada para o evento, divulgação de palestrante (avatar circular), divulgação de patrocinador, "ingressos esgotando" e "é amanhã". Composição via `intervention/image` (GD) com a fonte Lexend, capa do evento como fundo, cores do site do evento, rodapé com logos dos patrocinadores e download direto pelo painel. As artes ficam salvas no R2 e podem ser baixadas a qualquer momento.

### Gestão de Eventos (sub-módulos pendentes)
- ⬜ Fórum com tópicos por evento

---

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Backend | Laravel 13 / PHP 8.4 |
| Autenticação | Laravel Sanctum (sessão + cookie) |
| Frontend site | Blade + Tailwind CSS v4 |
| Frontend admin | Vue.js 3 SPA + Vue Router |
| Frontend CFP | Vue.js 3 SPA |
| Frontend evento | Vue.js 3 SPA (entry `event-site.js`) |
| Build | Vite |
| Banco de dados | MySQL 8.4 |
| Cache / Filas / Sessão | Redis |
| Armazenamento de mídia | Cloudflare R2 (S3-compatible) |
| Geração de imagens | intervention/image v3 (GD) |
| E-mail transacional | SMTP2Go |
| Admin BD | PHPMyAdmin |

---

## Qualidade de código

| Ferramenta | Função |
|-----------|--------|
| **Pint** | Code style PSR-12 |
| **Larastan** | Análise estática nível 5 |
| **Pest** | Testes automatizados |
| **Playwright** | Testes E2E |
| **CaptainHook** | Pre-commit hooks (lint + style + análise + testes) |

---

## Ambiente Docker

| Serviço | Porta | URL |
|---------|-------|-----|
| Nginx | 8000 | http://localhost:8000 |
| MySQL | 3306 | — |
| PHPMyAdmin | 8080 | http://localhost:8080 |
| Redis | 6379 | — |

### Subir o ambiente

```bash
docker compose up -d --build
```

### Primeiro acesso

```bash
# Rodar migrations e seed (cria o admin inicial)
docker compose exec app php artisan migrate --seed

# Acessar o painel admin
# http://localhost:8000/admin/login
# E-mail e senha definidos em ADMIN_EMAIL e ADMIN_PASSWORD no .env
```

### Comandos úteis

```bash
docker compose exec app bash                        # shell no container
docker compose exec app php artisan migrate         # migrations
docker compose exec app php artisan db:seed         # seeds
docker compose exec app php artisan view:clear      # limpar cache de views
docker compose exec app ./vendor/bin/pint           # corrigir code style
docker compose exec app ./vendor/bin/phpstan analyse # análise estática
docker compose exec app ./vendor/bin/pest --parallel # rodar testes
npx playwright test tests/e2e/                       # testes e2e (requer containers)
docker compose down                                  # parar containers
```

---

## Instalação local (sem Docker)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
```

---

## Variáveis de ambiente relevantes

```dotenv
# Banco de dados (Docker: host = nome do serviço)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=phpcomrapadura
DB_USERNAME=laravel
DB_PASSWORD=secret

# Redis (Docker: host = nome do serviço)
REDIS_HOST=redis
REDIS_PORT=6379
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# Primeiro admin (gerado via seed; lido de config('app.admin'), funciona com a config em cache)
# ADMIN_EMAIL vazio usa admin@phpcomrapadura.org. ADMIN_PASSWORD é obrigatório em produção.
ADMIN_EMAIL=admin@phpcomrapadura.org
ADMIN_PASSWORD=mudar@123

# Cloudflare R2 (upload de imagens de eventos, logos de patrocinadores,
# avatares e artes de divulgação)
# Obter em: dash.cloudflare.com → R2 → Overview
CLOUDFLARE_R2_ACCESS_KEY_ID=
CLOUDFLARE_R2_SECRET_ACCESS_KEY=
CLOUDFLARE_R2_ACCOUNT_ID=       # ex: abc123def456...
CLOUDFLARE_R2_BUCKET=phpcomrapadura
CLOUDFLARE_R2_URL=               # ex: https://assets.phpcomrapadura.org (CDN público)

# Blog — /blog lista os posts do WordPress legado pela REST API
# (vazio usa https://legacy.phpcomrapadura.org; cache por página em segundos)
BLOG_URL=
BLOG_CACHE_TTL=3600

# E-mail transacional via SMTP2Go (recuperação de senha do CFP)
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=mail.smtp2go.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="contato@phpcomrapadura.org"
```

---

## Deploy (CI/CD)

Todo push em `main` passa pelo workflow `.github/workflows/ci.yaml`:

1. **quality** — Pint, Larastan, Pest, caches do Laravel (`config`, `event`, `view`, `route`) e as migrations em MariaDB 11.4 (o banco de produção). Pull requests rodam só este job.
2. **build** — `composer install --no-dev`, `npm run build` e um tarball da release (artifact `suite-<sha>`, guardado 30 dias).
3. **deploy-production** — envia o tarball assinado (HMAC) para o receptor no servidor da comunidade. Lá um script extrai a release, roda as migrations pendentes (com dump antes), troca a release atomicamente e verifica `/up` e `/`. O job espera o resultado e mostra o log do servidor; se algo falhar, a release anterior volta sozinha e o job fica vermelho.
4. **deploy-staging** — o deploy já existente para o Tevun (`phpcomrapadura.dev.br`).

Produção: https://phpcomrapadura.org (o WordPress antigo continua em https://legacy.phpcomrapadura.org até ser desligado; `novo.` e `www.` redirecionam para o apex).

**Rollback:** `git revert` em `main` (passa pelo mesmo pipeline) ou re-executar o job `deploy-production` do último run bom.

Os segredos (`DEPLOY_HOOK_SECRET`, `DEPLOY_URL`) ficam no environment `production` do GitHub, restrito à branch `main`. Um push em `main` chega em produção em cerca de 6 minutos — não faça merge com o CI vermelho.
