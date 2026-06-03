# PHP com Rapadura — Suite

Suite de aplicações de uso da comunidade PHP com Rapadura.

## Módulos

### ✅ Site Institucional
Single-page com as seções: Hero, Sobre, Eventos, Código de Conduta, Contato e Footer.

### ✅ Admin — Autenticação
Área restrita em `/admin` com login, logout, controle de roles e proteção de rotas.

### Call for Papers (CFP) — em breve
Sistema de submissão de propostas de palestras por palestrantes.

### Gestão de Eventos — em breve
Painel administrativo com controle de eventos, tarefas, despesas, participantes e sorteio.

---

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Backend | Laravel 13 / PHP 8.4 |
| Autenticação | Laravel Sanctum (sessão + cookie) |
| Frontend site | Blade + Tailwind CSS v4 |
| Frontend admin | Vue.js 3 SPA + Vue Router |
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
# DB_PASSWORD padrão do docker-compose é "secret" quando deixado vazio no .env
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
```
