# Spec — CRUD de Usuários

**Status:** ✅ Implementado
**Testes:** `tests/Feature/Admin/Users/UserCrudTest.php` — 31 casos, 69 assertions
**Módulo:** Admin → Usuários
**Depende de:** `.claude/specs/admin/auth-spec.md`

---

## 1. Visão geral

Gerenciamento de usuários do painel acessível **somente pela role `admin`**. Colaboradores não têm acesso a este módulo.

Usuários com role `palestrante` são criados pelo fluxo público `/cfp` — não podem ser criados pelo admin. Na listagem aparecem normalmente, mas as ações disponíveis são restritas ao toggle de status.

---

## 2. Regras de negócio

| Regra | Detalhe |
|-------|---------|
| Acesso | Somente `admin` — `colaborador` recebe `403` |
| Criar | Roles permitidas: `admin`, `colaborador`. Role `palestrante` é bloqueada |
| Editar palestrante | Apenas o toggle de status é permitido — nome, email, role e senha ficam bloqueados |
| Auto-edição | Admin não pode editar nem desativar a própria conta |
| Excluir | Não é permitido excluir usuários — apenas desativar via toggle |
| Email | Único na tabela — validado no create e no update |
| Senha no update | Campo opcional — se vazio, mantém a senha atual |
| `created_by` | Preenchido automaticamente com `Auth::id()` ao criar |

---

## 3. Rotas

```php
// routes/web.php — dentro do grupo auth + EnsureAdminRole
Route::prefix('api/users')->name('users.')->middleware('role:admin')->group(function () {
    Route::get('/',              [UserController::class, 'index'])->name('index');
    Route::post('/',             [UserController::class, 'store'])->name('store');
    Route::get('/{user}',        [UserController::class, 'show'])->name('show');
    Route::put('/{user}',        [UserController::class, 'update'])->name('update');
    Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggleStatus');
});

// Rota Vue (recarregar a SPA na URL /admin/users)
Route::get('/users', fn () => view('admin'))->name('users');
Route::get('/users/{any}', fn () => view('admin'))->where('any', '.*');
```

> Não há rota `DELETE` — exclusão de usuário não é permitida.

---

## 4. Controller

**Arquivo:** `app/Http/Controllers/Admin/UserController.php`

### 4.1 `index`

```
GET /admin/api/users?page=1&search=&role=&status=

Retorna JSON paginado:
{
  "data": [ ...users ],
  "meta": { "current_page", "last_page", "total", "per_page": 9 }
}
```

Filtros aceitos via query string:

| Parâmetro | Tipo | Comportamento |
|-----------|------|---------------|
| `search` | string | LIKE em `name` ou `email` |
| `role` | string | Filtro exato: `admin`, `colaborador`, `palestrante` |
| `status` | string | `active` → `is_active = true`, `inactive` → `is_active = false` |
| `page` | int | Paginação — 9 itens por página |

Campos retornados por usuário:

```json
{
  "id": 1,
  "name": "Nome Sobrenome",
  "email": "email@exemplo.com",
  "role": "admin",
  "is_active": true,
  "created_by": null,
  "last_login_at": "2025-06-01T14:30:00Z",
  "created_at": "2025-05-20T10:00:00Z"
}
```

### 4.2 `store`

```
POST /admin/api/users
Body: { name, email, password, password_confirmation, role }

→ valida via StoreUserRequest
→ bloqueia role = palestrante (422)
→ cria usuário com created_by = Auth::id()
→ retorna 201 + usuário criado
```

### 4.3 `show`

```
GET /admin/api/users/{user}
→ retorna usuário pelo ID
→ 404 se não encontrado
```

### 4.4 `update`

```
PUT /admin/api/users/{user}
Body: { name, email, role, password?, password_confirmation? }

→ valida via UpdateUserRequest
→ bloqueia edição da própria conta (403)
→ se usuário é palestrante: bloqueia alteração de name/email/role/password (422)
→ senha: só atualiza se o campo password vier preenchido
→ retorna 200 + usuário atualizado
```

### 4.5 `toggleStatus`

```
PATCH /admin/api/users/{user}/toggle-status

→ bloqueia toggle da própria conta (403)
→ inverte is_active
→ retorna 200 + { is_active: bool }
```

---

## 5. Requests

### 5.1 `StoreUserRequest`

```php
// app/Http/Requests/Admin/Users/StoreUserRequest.php

public function rules(): array
{
    return [
        'name'     => ['required', 'string', 'max:255'],
        'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role'     => ['required', Rule::in(['admin', 'colaborador'])],
    ];
}

public function messages(): array
{
    return [
        'name.required'     => 'O nome é obrigatório.',
        'email.required'    => 'O e-mail é obrigatório.',
        'email.email'       => 'Informe um e-mail válido.',
        'email.unique'      => 'Este e-mail já está em uso.',
        'password.required' => 'A senha é obrigatória.',
        'password.min'      => 'A senha deve ter pelo menos 8 caracteres.',
        'password.confirmed'=> 'A confirmação de senha não confere.',
        'role.required'     => 'A função é obrigatória.',
        'role.in'           => 'Função inválida.',
    ];
}
```

### 5.2 `UpdateUserRequest`

```php
// app/Http/Requests/Admin/Users/UpdateUserRequest.php

public function rules(): array
{
    return [
        'name'     => ['required', 'string', 'max:255'],
        'email'    => ['required', 'email', 'max:255',
                       Rule::unique('users', 'email')->ignore($this->route('user'))],
        'role'     => ['required', Rule::in(['admin', 'colaborador'])],
        'password' => ['nullable', 'string', 'min:8', 'confirmed'],
    ];
}
```

---

## 6. Service

**Arquivo:** `app/Services/UserService.php`

```php
public function list(array $filters): LengthAwarePaginator
public function create(array $data, int $createdBy): User
public function update(User $user, array $data): User
public function toggleStatus(User $user): User
```

Responsabilidades do service:

- `list`: aplica os filtros e retorna paginado (9/página)
- `create`: hash da senha é feito pelo cast `hashed` do model — não fazer no service
- `update`: só inclui `password` no array de update se vier preenchida
- `toggleStatus`: inverte `is_active` e salva

---

## 7. Interface

### 7.1 Listagem (`/admin/users`)

**Cabeçalho da página:**
```
[Título: "Usuários"]   [Botão "Novo usuário" — role:admin, desabilitado para colaborador]
```

**Barra de filtros:**
```
[🔍 Buscar por nome ou e-mail]  [Dropdown: Todas as funções]  [Dropdown: Todos os status]
```

**Grid de cards** — `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4`

Cada card exibe:
```
[Avatar com iniciais — círculo colorido por role]
[Nome]
[E-mail — truncado]
[Badge de role]  [Badge de status]
[Último acesso: "há 2 dias" ou "Nunca"]
[Criado por: nome do criador ou "Seed"]
[────────────────────────────]
[Toggle ativo/inativo]  [Editar]
```

> Botão "Editar" desabilitado para a própria conta do admin logado.
> Para palestrantes: botão "Editar" oculto, apenas o toggle fica visível.

**Cores dos badges de role:**

| Role | Cor |
|------|-----|
| `admin` | `--color-primary` (azul) |
| `colaborador` | `--color-warning` (âmbar) |
| `palestrante` | `--color-success` (verde) |

**Paginação:** abaixo do grid, links de página + total de registros.

### 7.2 Modal de criar/editar

```
[Título: "Novo usuário" / "Editar usuário"]

[Campo: Nome completo *]
[Campo: E-mail *]
[Campo: Função * — select com admin/colaborador (palestrante não aparece)]
[Campo: Senha * (criar) / Senha — opcional (editar)]
[Campo: Confirmar senha]

[Cancelar]  [Salvar]
```

> No modal de edição de palestrante: todos os campos são `disabled`, apenas o modal é aberto para visualização — mas o botão editar nem aparece (ver 7.1).

### 7.3 Arquivos Vue

| Arquivo | Descrição |
|---------|-----------|
| `resources/js/views/admin/Users.vue` | Listagem com filtros e grid |
| `resources/js/components/UserModal.vue` | Modal criar/editar |
| `resources/js/components/ConfirmModal.vue` | Reutilizável — confirmação genérica |

---

## 8. Vue Router

```js
// resources/js/router/admin.js
{ path: '/admin/users', component: () => import('../views/admin/Users.vue') }
```

---

## 9. Arquivos a criar

| Arquivo | Tipo |
|---------|------|
| `app/Http/Controllers/Admin/UserController.php` | Controller |
| `app/Http/Requests/Admin/Users/StoreUserRequest.php` | Form Request |
| `app/Http/Requests/Admin/Users/UpdateUserRequest.php` | Form Request |
| `app/Services/UserService.php` | Service |
| `resources/js/views/admin/Users.vue` | View Vue |
| `resources/js/components/UserModal.vue` | Componente Vue |
| `resources/js/components/ConfirmModal.vue` | Componente Vue (reutilizável) |
| `tests/Feature/Admin/Users/UserCrudTest.php` | Testes |

---

## 10. Critérios de aceite

### Acesso
- [ ] `colaborador` recebe `403` em qualquer rota `/admin/api/users`
- [ ] `palestrante` recebe `403` (bloqueado pelo `EnsureAdminRole`)

### Listagem
- [ ] Retorna 9 usuários por página
- [ ] Filtro `search` filtra por nome e email (case-insensitive)
- [ ] Filtro `role` retorna apenas usuários da role informada
- [ ] Filtro `status=active` retorna apenas `is_active = true`
- [ ] Filtro `status=inactive` retorna apenas `is_active = false`

### Criação
- [ ] Cria usuário com role `admin` ou `colaborador`
- [ ] `created_by` é o ID do admin logado
- [ ] Role `palestrante` retorna `422`
- [ ] Email duplicado retorna `422` com mensagem em português
- [ ] Senha com menos de 8 chars retorna `422`
- [ ] Senha sem confirmação retorna `422`

### Edição
- [ ] Atualiza nome, email e role
- [ ] Senha só é alterada se o campo `password` vier preenchido
- [ ] Admin não pode editar a própria conta (`403`)
- [ ] Editar palestrante (name/email/role/password) retorna `422`
- [ ] Email alterado para um já existente retorna `422`

### Toggle de status
- [ ] Inverte `is_active` corretamente
- [ ] Admin não pode desativar a própria conta (`403`)
- [ ] Toggle em palestrante funciona normalmente

### Segurança
- [ ] `password` nunca aparece no JSON de resposta
- [ ] Rota inexistente retorna `404`

---

## 11. Bateria de testes

**Arquivo:** `tests/Feature/Admin/Users/UserCrudTest.php`

```php
<?php

use App\Models\User;

// ─── Controle de acesso ───────────────────────────────────────────────────────

it('guest é redirecionado para login ao acessar a listagem', function () {
    $this->getJson('/admin/api/users')
        ->assertUnauthorized();
});

it('colaborador recebe 403 na listagem', function () {
    $this->actingAs(User::factory()->colaborador()->create())
        ->getJson('/admin/api/users')
        ->assertForbidden();
});

it('colaborador recebe 403 ao tentar criar usuário', function () {
    $this->actingAs(User::factory()->colaborador()->create())
        ->postJson('/admin/api/users', [])
        ->assertForbidden();
});

it('colaborador recebe 403 ao tentar editar usuário', function () {
    $target = User::factory()->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->putJson("/admin/api/users/{$target->id}", [])
        ->assertForbidden();
});

it('colaborador recebe 403 ao tentar alterar status', function () {
    $target = User::factory()->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->patchJson("/admin/api/users/{$target->id}/toggle-status")
        ->assertForbidden();
});

it('admin acessa a listagem com sucesso', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/admin/api/users')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta']);
});

// ─── Listagem ─────────────────────────────────────────────────────────────────

it('retorna 9 usuários por página', function () {
    User::factory()->count(12)->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->getJson('/admin/api/users')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(9);
    expect($response->json('meta.per_page'))->toBe(9);
    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(12);
});

it('filtro search encontra usuário pelo nome', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['name' => 'Fulano de Tal']);
    User::factory()->create(['name' => 'Outro Nome']);

    $response = $this->actingAs($admin)
        ->getJson('/admin/api/users?search=Fulano')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.name'))->toBe('Fulano de Tal');
});

it('filtro search encontra usuário pelo email', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'busca@exemplo.com']);

    $response = $this->actingAs($admin)
        ->getJson('/admin/api/users?search=busca@exemplo')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.email'))->toBe('busca@exemplo.com');
});

it('filtro role retorna apenas a role informada', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->colaborador()->count(2)->create();
    User::factory()->palestrante()->count(3)->create();

    $response = $this->actingAs($admin)
        ->getJson('/admin/api/users?role=colaborador')
        ->assertOk();

    collect($response->json('data'))
        ->each(fn ($u) => expect($u['role'])->toBe('colaborador'));
});

it('filtro status=active retorna apenas usuários ativos', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->inactive()->count(3)->create();

    $response = $this->actingAs($admin)
        ->getJson('/admin/api/users?status=active')
        ->assertOk();

    collect($response->json('data'))
        ->each(fn ($u) => expect($u['is_active'])->toBeTrue());
});

it('filtro status=inactive retorna apenas usuários inativos', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->inactive()->count(2)->create();

    $response = $this->actingAs($admin)
        ->getJson('/admin/api/users?status=inactive')
        ->assertOk();

    collect($response->json('data'))
        ->each(fn ($u) => expect($u['is_active'])->toBeFalse());
});

it('não expõe senha na listagem', function () {
    User::factory()->count(3)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/admin/api/users')
        ->assertJsonMissing(['password']);
});

// ─── Criação ──────────────────────────────────────────────────────────────────

it('admin cria usuário com role colaborador', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/admin/api/users', [
            'name'                  => 'Novo Colaborador',
            'email'                 => 'novo@exemplo.com',
            'role'                  => 'colaborador',
            'password'              => 'senha@123',
            'password_confirmation' => 'senha@123',
        ])
        ->assertCreated()
        ->assertJsonPath('role', 'colaborador')
        ->assertJsonMissing(['password']);

    $this->assertDatabaseHas('users', ['email' => 'novo@exemplo.com']);
});

it('admin cria usuário com role admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/admin/api/users', [
            'name'                  => 'Novo Admin',
            'email'                 => 'novo.admin@exemplo.com',
            'role'                  => 'admin',
            'password'              => 'senha@123',
            'password_confirmation' => 'senha@123',
        ])
        ->assertCreated()
        ->assertJsonPath('role', 'admin');
});

it('created_by é preenchido com o id do admin logado', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->postJson('/admin/api/users', [
            'name'                  => 'Criado Por',
            'email'                 => 'criadopor@exemplo.com',
            'role'                  => 'colaborador',
            'password'              => 'senha@123',
            'password_confirmation' => 'senha@123',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('users', [
        'email'      => 'criadopor@exemplo.com',
        'created_by' => $admin->id,
    ]);
});

it('bloqueia criação com role palestrante', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/users', [
            'name'                  => 'Palestrante',
            'email'                 => 'palestrante@exemplo.com',
            'role'                  => 'palestrante',
            'password'              => 'senha@123',
            'password_confirmation' => 'senha@123',
        ])
        ->assertUnprocessable();
});

it('retorna 422 ao criar com email já existente', function () {
    User::factory()->create(['email' => 'existente@exemplo.com']);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/users', [
            'name'                  => 'Duplicado',
            'email'                 => 'existente@exemplo.com',
            'role'                  => 'colaborador',
            'password'              => 'senha@123',
            'password_confirmation' => 'senha@123',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('retorna 422 ao criar com senha menor que 8 caracteres', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/users', [
            'name'                  => 'Curto',
            'email'                 => 'curto@exemplo.com',
            'role'                  => 'colaborador',
            'password'              => '1234567',
            'password_confirmation' => '1234567',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('retorna 422 ao criar sem confirmação de senha', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/users', [
            'name'     => 'Sem Confirm',
            'email'    => 'semconfirm@exemplo.com',
            'role'     => 'colaborador',
            'password' => 'senha@123',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

// ─── Edição ───────────────────────────────────────────────────────────────────

it('admin atualiza nome e email de outro usuário', function () {
    $admin  = User::factory()->admin()->create();
    $target = User::factory()->colaborador()->create();

    $this->actingAs($admin)
        ->putJson("/admin/api/users/{$target->id}", [
            'name'  => 'Nome Atualizado',
            'email' => 'atualizado@exemplo.com',
            'role'  => 'colaborador',
        ])
        ->assertOk()
        ->assertJsonPath('name', 'Nome Atualizado')
        ->assertJsonPath('email', 'atualizado@exemplo.com');
});

it('senha não é alterada quando o campo não é enviado', function () {
    $admin  = User::factory()->admin()->create();
    $target = User::factory()->colaborador()->create(['password' => 'original@123']);
    $hashAntes = $target->password;

    $this->actingAs($admin)
        ->putJson("/admin/api/users/{$target->id}", [
            'name'  => $target->name,
            'email' => $target->email,
            'role'  => 'colaborador',
        ])
        ->assertOk();

    expect($target->fresh()->password)->toBe($hashAntes);
});

it('senha é alterada quando o campo é enviado', function () {
    $admin  = User::factory()->admin()->create();
    $target = User::factory()->colaborador()->create(['password' => 'original@123']);

    $this->actingAs($admin)
        ->putJson("/admin/api/users/{$target->id}", [
            'name'                  => $target->name,
            'email'                 => $target->email,
            'role'                  => 'colaborador',
            'password'              => 'nova@senha123',
            'password_confirmation' => 'nova@senha123',
        ])
        ->assertOk();

    expect(\Illuminate\Support\Facades\Hash::check('nova@senha123', $target->fresh()->password))->toBeTrue();
});

it('admin não pode editar a própria conta', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->putJson("/admin/api/users/{$admin->id}", [
            'name'  => 'Eu Mesmo',
            'email' => $admin->email,
            'role'  => 'admin',
        ])
        ->assertForbidden();
});

it('não permite editar dados de palestrante', function () {
    $admin       = User::factory()->admin()->create();
    $palestrante = User::factory()->palestrante()->create();

    $this->actingAs($admin)
        ->putJson("/admin/api/users/{$palestrante->id}", [
            'name'  => 'Nome Novo',
            'email' => 'novo@exemplo.com',
            'role'  => 'colaborador',
        ])
        ->assertUnprocessable();
});

it('retorna 422 ao atualizar email para um já existente', function () {
    $admin  = User::factory()->admin()->create();
    $outro  = User::factory()->create(['email' => 'ocupado@exemplo.com']);
    $target = User::factory()->colaborador()->create();

    $this->actingAs($admin)
        ->putJson("/admin/api/users/{$target->id}", [
            'name'  => $target->name,
            'email' => 'ocupado@exemplo.com',
            'role'  => 'colaborador',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('não gera erro de email duplicado ao manter o email do próprio usuário', function () {
    $admin  = User::factory()->admin()->create();
    $target = User::factory()->colaborador()->create(['email' => 'meu@exemplo.com']);

    $this->actingAs($admin)
        ->putJson("/admin/api/users/{$target->id}", [
            'name'  => 'Nome Atualizado',
            'email' => 'meu@exemplo.com',
            'role'  => 'colaborador',
        ])
        ->assertOk();
});

// ─── Toggle de status ─────────────────────────────────────────────────────────

it('desativa usuário ativo', function () {
    $admin  = User::factory()->admin()->create();
    $target = User::factory()->create(['is_active' => true]);

    $this->actingAs($admin)
        ->patchJson("/admin/api/users/{$target->id}/toggle-status")
        ->assertOk()
        ->assertJsonPath('is_active', false);

    expect($target->fresh()->is_active)->toBeFalse();
});

it('ativa usuário inativo', function () {
    $admin  = User::factory()->admin()->create();
    $target = User::factory()->inactive()->create();

    $this->actingAs($admin)
        ->patchJson("/admin/api/users/{$target->id}/toggle-status")
        ->assertOk()
        ->assertJsonPath('is_active', true);

    expect($target->fresh()->is_active)->toBeTrue();
});

it('admin não pode desativar a própria conta', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patchJson("/admin/api/users/{$admin->id}/toggle-status")
        ->assertForbidden();

    expect($admin->fresh()->is_active)->toBeTrue();
});

it('toggle de status em palestrante funciona normalmente', function () {
    $admin       = User::factory()->admin()->create();
    $palestrante = User::factory()->palestrante()->create(['is_active' => true]);

    $this->actingAs($admin)
        ->patchJson("/admin/api/users/{$palestrante->id}/toggle-status")
        ->assertOk()
        ->assertJsonPath('is_active', false);
});
```
