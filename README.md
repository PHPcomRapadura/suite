# PHP com Rapadura — Suite

Suite de aplicações de uso da comunidade PHP com Rapadura.

## Módulos

### Site Institucional
Site da comunidade com as seções: Hero, About, Events, Code of Conduct e Footer.

### Call for Papers (CFP)
Sistema de submissão de propostas de palestras. Acesse `/cfp` para ver os eventos abertos. O palestrante se cadastra ou faz login, escolhe um evento e preenche o formulário de submissão. As palestras ficam com status **Enviada** e podem ser aprovadas ou rejeitadas pela organização com feedback.

### Gestão de Eventos (admin)
Acesso restrito a administradores com controle de permissões. Módulos:

- Controle de eventos
- Controle de submissão de palestras por evento
- Controle de despesas por evento
- Controle de tarefas por evento (Kanban)
- Fórum com tópicos por evento
- Controle de participantes (upload CSV)
- Sorteio digital por evento

---

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Backend | Laravel 13 / PHP 8.4 |
| Frontend | Vue.js + Tailwind CSS v4 + Vite |
| Banco de dados | MySQL 8.4 |
| Cache / Filas / Sessão | Redis |
| Admin BD | PHPMyAdmin |

---

## Ambiente Docker

| Serviço | Porta | URL |
|---------|-------|-----|
| App (PHP-FPM) | — | via nginx |
| Nginx | 8000 | http://localhost:8000 |
| MySQL | 3306 | — |
| PHPMyAdmin | 8080 | http://localhost:8080 |
| Redis | 6379 | — |

### Subir o ambiente

```bash
docker compose up -d --build
```

### Comandos úteis

```bash
# Acessar o container da aplicação
docker compose exec app bash

# Rodar migrations
docker compose exec app php artisan migrate

# Gerar chave da aplicação
docker compose exec app php artisan key:generate

# Limpar cache
docker compose exec app php artisan cache:clear

# Parar todos os containers
docker compose down
```

---

## Instalação local (sem Docker)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev
```

---

## Variáveis de ambiente relevantes

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=phpcomrapadura
DB_USERNAME=laravel
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```
