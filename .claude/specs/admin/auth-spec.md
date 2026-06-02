# Spec — Autenticação do Admin

**Status:** ✅ Implementado
**Testes:** `tests/Feature/Admin/Auth/LoginTest.php` — 20 casos, 54 assertions
**Módulo:** Admin
**Arquivo de referência:** `.claude/patterns/front-patterns.md`, `.claude/skills/backend.md`, `.claude/skills/security.md`

---

## 1. Visão geral

Área restrita acessível apenas por usuários com role `admin` ou `colaborador`. Não existe registro público — cada usuário é criado por um administrador logado. O primeiro admin é provisionado via seed.

```
/admin/login          → página de login
/admin/dashboard      → painel principal (após login)
/admin/*              → qualquer rota admin exige autenticação + role válida
```

---

## 2. Autenticação

### 2.1 Mecanismo

- **Laravel Sanctum** com autenticação via **sessão + cookie** (SPA browser-based)
- Não usar tokens Bearer — Sanctum SPA auth com `sanctum/csrf-cookie` e cookies `HttpOnly`
- Session driver: `redis` (conforme `.env`)

### 2.2 Fluxo de login

```
1. GET  /admin/login          → exibe o formulário (blade view carregando a SPA Vue)
2. GET  /sanctum/csrf-cookie  → obtém o CSRF token (chamado pelo frontend antes do login)
3. POST /admin/login          → autentica e inicia a sessão
4. GET  /admin/dashboard      → redireciona após login bem-sucedido
```

### 2.3 Fluxo de logout

```
1. POST /admin/logout         → encerra a sessão e invalida o cookie
2. Redireciona para /admin/login
```

---

## 3. Roles e permissões

### 3.1 Roles disponíveis

| Role | Acesso | Pode criar usuários |
|------|--------|---------------------|
| `admin` | Total — todos os módulos do painel | ✅ Sim |
| `colaborador` | Restrito — apenas módulos atribuídos | ❌ Não |
| `palestrante` | Apenas o módulo CFP (submissão de palestras) | ❌ Não (auto-cadastro via CFP) |

> **Nota:** A role `palestrante` é reservada para uso futuro pelo módulo CFP. Palestrantes se registram **publicamente** pelo fluxo `/cfp` — não são criados por admins. Incluída no enum desde já para evitar migrations futuras.

### 3.2 Regras de acesso

- Rotas `/admin/*` protegidas por middleware `auth` + `role:admin,colaborador`
- Role `palestrante` **não tem acesso** ao painel `/admin` — apenas ao fluxo `/cfp`
- Usuários sem role válida recebem `403 Forbidden`
- Usuários não autenticados são redirecionados para `/admin/login`
- Somente `admin` pode criar, editar ou desativar outros usuários

---

## 4. Model `User`

### 4.1 Campos

| Campo | Tipo | Observação |
|-------|------|-----------|
| `id` | `bigIncrements` | PK |
| `name` | `string(255)` | Nome completo |
| `email` | `string(255)` | Único |
| `password` | `string(255)` | Hash bcrypt |
| `role` | `enum('admin','colaborador','palestrante')` | Default: `colaborador` |
| `is_active` | `boolean` | Default: `true` |
| `created_by` | `foreignId → users.id` | Quem criou (nullable — null = seed) |
| `last_login_at` | `timestamp` | Nullable — atualizado a cada login |
| `email_verified_at` | `timestamp` | Nullable |
| `remember_token` | `string(100)` | Nullable |
| `timestamps` | — | `created_at`, `updated_at` |

### 4.2 Migration

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->enum('role', ['admin', 'colaborador', 'palestrante'])->default('colaborador');
    $table->boolean('is_active')->default(true);
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('last_login_at')->nullable();
    $table->timestamp('email_verified_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
});
```

### 4.3 Model

```php
protected $fillable = ['name', 'email', 'password', 'role', 'is_active', 'created_by'];

protected $hidden = ['password', 'remember_token'];

protected $casts = [
    'is_active'         => 'boolean',
    'last_login_at'     => 'datetime',
    'email_verified_at' => 'datetime',
    'password'          => 'hashed',
];

public function creator(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}

public function isAdmin(): bool
{
    return $this->role === 'admin';
}

public function isSpeaker(): bool
{
    return $this->role === 'palestrante';
}

public function hasAdminAccess(): bool
{
    return in_array($this->role, ['admin', 'colaborador']);
}
```

---

## 5. Seed do primeiro admin

Arquivo: `database/seeders/AdminUserSeeder.php`

```php
User::create([
    'name'       => 'Administrador',
    'email'      => env('ADMIN_EMAIL', 'admin@phpcomrapadura.org'),
    'password'   => env('ADMIN_PASSWORD', 'mudar@123'),
    'role'       => 'admin',
    'is_active'  => true,
    'created_by' => null,
]);
```

- Credenciais via `.env` (`ADMIN_EMAIL`, `ADMIN_PASSWORD`) — nunca hardcodar em produção
- Adicionar ao `DatabaseSeeder`: `$this->call(AdminUserSeeder::class)`
- Executar: `php artisan db:seed --class=AdminUserSeeder`

Adicionar ao `.env.example`:
```dotenv
ADMIN_EMAIL=admin@phpcomrapadura.org
ADMIN_PASSWORD=
```

---

## 6. Controller de autenticação

### 6.1 `AdminLoginController`

```
POST /admin/login
  → valida email + password
  → verifica is_active
  → verifica role (admin ou colaborador)
  → atualiza last_login_at
  → inicia sessão
  → retorna JSON { user, redirect }

POST /admin/logout
  → invalida sessão
  → retorna 204
```

### 6.2 Validação do login

```php
'email'    => ['required', 'email'],
'password' => ['required', 'string'],
```

### 6.3 Verificações após credenciais corretas

1. `is_active === false` → retornar erro `403`: "Sua conta está desativada. Entre em contato com um administrador."
2. `role` não é `admin` nem `colaborador` → retornar erro `403`: "Acesso não autorizado."
3. Caso válido → `Auth::login($user)` + atualizar `last_login_at`

---

## 7. Middleware

### 7.1 `EnsureAdminRole`

```php
// app/Http/Middleware/EnsureAdminRole.php
if (!auth()->check() || !auth()->user()->hasAdminAccess()) {
    return $request->expectsJson()
        ? response()->json(['message' => 'Acesso não autorizado.'], 403)
        : redirect()->route('admin.login');
}

if (!auth()->user()->is_active) {
    Auth::logout();
    return redirect()->route('admin.login')
                     ->withErrors(['email' => 'Sua conta foi desativada.']);
}
```

### 7.2 Registrar no grupo de rotas

```php
// routes/web.php
Route::prefix('admin')->name('admin.')->group(function () {
    // Rotas públicas
    Route::get('/login', [AdminLoginController::class, 'show'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login']);

    // Rotas protegidas
    Route::middleware(['auth', EnsureAdminRole::class])->group(function () {
        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');
        Route::get('/{any}', fn () => view('admin'))->where('any', '.*')->name('dashboard');
    });
});
```

---

## 8. Página de login (UI)

### 8.1 Layout

- Fundo: `--color-bg` (`#f5f6f8`)
- Card centralizado vertical e horizontalmente: `max-width: 420px`
- Fundo do card: `--color-surface` (branco), `border-radius: 12px`, sombra suave

### 8.2 Conteúdo do card

```
[Logo PHPcomRapadura_color.svg — 160px — centralizada]
[Título: "Área restrita" — Lexend 22px, 700]
[Subtítulo: "Acesso exclusivo para membros da organização." — muted]

[Campo: E-mail]
[Campo: Senha com toggle de visibilidade]
[Checkbox: Lembrar-me]
[Botão: "Entrar" — primary, full width, com loading state]

[Mensagem de erro inline abaixo do botão]
```

### 8.3 Comportamento

- Botão desabilitado enquanto a requisição está em andamento
- Erro de credenciais: mensagem inline "E-mail ou senha incorretos."
- Erro de conta inativa: mensagem inline com orientação
- Enter no campo de senha submete o formulário
- Após login bem-sucedido: redirecionar para `/admin/dashboard`

### 8.4 Segurança da UI

- Campos `autocomplete="email"` e `autocomplete="current-password"`
- `<form>` com método POST + CSRF token do Sanctum
- Não exibir se a conta existe ou não em mensagens de erro (usar sempre mensagem genérica para credenciais inválidas)

---

## 9. Senha

- Mínimo 8 caracteres
- Primeiro admin: senha provisória via `.env`, **forçar troca** no primeiro login *(fase futura)*
- Hash: `bcrypt` com `BCRYPT_ROUNDS=12`

---

## 10. Arquivos a criar

| Arquivo | Tipo |
|---------|------|
| `database/migrations/*_create_users_table.php` | Migration |
| `database/seeders/AdminUserSeeder.php` | Seeder |
| `app/Models/User.php` | Model (atualizar) |
| `app/Http/Controllers/Admin/AdminLoginController.php` | Controller |
| `app/Http/Middleware/EnsureAdminRole.php` | Middleware |
| `resources/views/admin.blade.php` | View raiz da SPA admin |
| `resources/js/views/auth/Login.vue` | Página de login Vue |

---

## 11. Critérios de aceite

- [ ] `GET /admin/login` exibe o formulário de login
- [ ] Login com credenciais corretas inicia sessão e redireciona para `/admin/dashboard`
- [ ] Login com credenciais incorretas retorna mensagem de erro genérica
- [ ] Conta inativa exibe mensagem específica e não permite login
- [ ] `POST /admin/logout` encerra a sessão e redireciona para `/admin/login`
- [ ] Qualquer rota `/admin/*` (exceto login) redireciona para login se não autenticado
- [ ] Usuário sem role `admin`/`colaborador` recebe 403
- [ ] `last_login_at` atualizado a cada login bem-sucedido
- [ ] Seed cria o primeiro admin via `php artisan db:seed --class=AdminUserSeeder`
- [ ] `created_by` é `null` para usuários criados via seed
- [ ] Senha não aparece em nenhuma resposta JSON
- [ ] Rate limiting de 5 tentativas/minuto na rota de login
