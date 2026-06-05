# PHP com Rapadura — Suite

Suite de aplicações de uso da comunidade PHP com Rapadura.

## Módulos

### ✅ Site Institucional
Single-page com as seções: Hero, Sobre, Eventos, Código de Conduta, Contato e Footer.
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

### Gestão de Eventos (sub-módulos pendentes)
- ⬜ Controle de despesas por evento
- ⬜ Controle de tarefas por evento (Kanban)
- ⬜ Fórum com tópicos por evento
- ⬜ Controle de participantes (upload CSV)
- ⬜ Sorteio digital por evento

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

# Primeiro admin (gerado via seed)
# ATENÇÃO: valor vazio faz env() retornar '' em vez de usar o default da seed.
# Sempre defina um valor antes de rodar php artisan db:seed.
ADMIN_EMAIL=admin@phpcomrapadura.org
ADMIN_PASSWORD=mudar@123

# Cloudflare R2 (upload de imagens de eventos, logos de patrocinadores, avatares)
# Obter em: dash.cloudflare.com → R2 → Overview
CLOUDFLARE_R2_ACCESS_KEY_ID=
CLOUDFLARE_R2_SECRET_ACCESS_KEY=
CLOUDFLARE_R2_ACCOUNT_ID=       # ex: abc123def456...
CLOUDFLARE_R2_BUCKET=phpcomrapadura
CLOUDFLARE_R2_URL=               # ex: https://assets.phpcomrapadura.org (CDN público)
```
