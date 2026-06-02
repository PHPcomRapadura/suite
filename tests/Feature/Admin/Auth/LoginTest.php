<?php

use App\Models\User;

// ─── Página de login ─────────────────────────────────────────────────────────

it('exibe a página de login', function () {
    $this->get(route('admin.login'))
        ->assertOk()
        ->assertViewIs('admin');
});

it('redireciona para login ao acessar área protegida sem autenticação', function () {
    $this->get('/admin/dashboard')
        ->assertRedirect(route('admin.login'));
});

// ─── Validação de formulário ──────────────────────────────────────────────────

it('retorna erro de validação sem email', function () {
    $response = $this->postJson('/admin/login', ['password' => 'qualquer']);
    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});

it('retorna erro de validação sem senha', function () {
    $response = $this->postJson('/admin/login', ['email' => 'admin@test.com']);
    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['password']);
});

it('retorna erro de validação com email inválido', function () {
    $response = $this->postJson('/admin/login', ['email' => 'nao-e-email', 'password' => '123']);
    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});

// ─── Credenciais ──────────────────────────────────────────────────────────────

it('retorna 401 com credenciais incorretas', function () {
    User::factory()->create(['email' => 'admin@test.com', 'password' => 'correta@123']);

    $this->postJson(route('admin.login'), [
        'email' => 'admin@test.com',
        'password' => 'errada@123',
    ])->assertUnauthorized()
        ->assertJsonPath('message', 'E-mail ou senha incorretos.');
});

it('retorna 401 com email inexistente', function () {
    $this->postJson(route('admin.login'), [
        'email' => 'naoexiste@test.com',
        'password' => 'qualquer',
    ])->assertUnauthorized()
        ->assertJsonPath('message', 'E-mail ou senha incorretos.');
});

// ─── Conta inativa ────────────────────────────────────────────────────────────

it('retorna 403 quando a conta está inativa', function () {
    User::factory()->create([
        'email' => 'inativo@test.com',
        'password' => 'senha@123',
        'role' => 'admin',
        'is_active' => false,
    ]);

    $this->postJson(route('admin.login'), [
        'email' => 'inativo@test.com',
        'password' => 'senha@123',
    ])->assertForbidden()
        ->assertJsonPath('message', 'Sua conta está desativada. Entre em contato com um administrador.');
});

it('não mantém sessão após tentativa com conta inativa', function () {
    User::factory()->create([
        'email' => 'inativo@test.com',
        'password' => 'senha@123',
        'role' => 'admin',
        'is_active' => false,
    ]);

    $this->postJson(route('admin.login'), [
        'email' => 'inativo@test.com',
        'password' => 'senha@123',
    ]);

    $this->assertGuest();
});

// ─── Role não autorizada ──────────────────────────────────────────────────────

it('retorna 403 para usuário com role palestrante', function () {
    User::factory()->create([
        'email' => 'palestrante@test.com',
        'password' => 'senha@123',
        'role' => 'palestrante',
    ]);

    $this->postJson(route('admin.login'), [
        'email' => 'palestrante@test.com',
        'password' => 'senha@123',
    ])->assertForbidden()
        ->assertJsonPath('message', 'Acesso não autorizado.');
});

it('não mantém sessão após tentativa com role inválida', function () {
    User::factory()->create([
        'email' => 'palestrante@test.com',
        'password' => 'senha@123',
        'role' => 'palestrante',
    ]);

    $this->postJson(route('admin.login'), [
        'email' => 'palestrante@test.com',
        'password' => 'senha@123',
    ]);

    $this->assertGuest();
});

// ─── Login bem-sucedido ───────────────────────────────────────────────────────

it('faz login com role admin', function () {
    $user = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => 'senha@123',
        'role' => 'admin',
    ]);

    $this->postJson(route('admin.login'), [
        'email' => 'admin@test.com',
        'password' => 'senha@123',
    ])->assertOk()
        ->assertJsonStructure(['message', 'user' => ['id', 'name', 'role'], 'redirect'])
        ->assertJsonPath('user.role', 'admin');

    $this->assertAuthenticatedAs($user);
});

it('faz login com role colaborador', function () {
    $user = User::factory()->create([
        'email' => 'colab@test.com',
        'password' => 'senha@123',
        'role' => 'colaborador',
    ]);

    $this->postJson(route('admin.login'), [
        'email' => 'colab@test.com',
        'password' => 'senha@123',
    ])->assertOk()
        ->assertJsonPath('user.role', 'colaborador');

    $this->assertAuthenticatedAs($user);
});

it('atualiza last_login_at após login bem-sucedido', function () {
    $user = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => 'senha@123',
        'role' => 'admin',
        'last_login_at' => null,
    ]);

    $this->postJson(route('admin.login'), [
        'email' => 'admin@test.com',
        'password' => 'senha@123',
    ])->assertOk();

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('não expõe a senha na resposta do login', function () {
    User::factory()->create([
        'email' => 'admin@test.com',
        'password' => 'senha@123',
        'role' => 'admin',
    ]);

    $this->postJson(route('admin.login'), [
        'email' => 'admin@test.com',
        'password' => 'senha@123',
    ])->assertJsonMissing(['password']);
});

// ─── Logout ───────────────────────────────────────────────────────────────────

it('faz logout e encerra a sessão', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->postJson(route('admin.logout'))
        ->assertOk()
        ->assertJsonPath('message', 'Logout realizado com sucesso.');

    $this->assertGuest();
});

it('logout sem autenticação redireciona para login', function () {
    $this->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));
});

// ─── Proteção de rotas ────────────────────────────────────────────────────────

it('redireciona palestrante que tenta acessar o admin', function () {
    $user = User::factory()->create(['role' => 'palestrante']);

    $this->actingAs($user)
        ->get('/admin/dashboard')
        ->assertRedirect(route('admin.login'));
});

it('redireciona usuário inativo que tenta acessar o admin', function () {
    $user = User::factory()->create(['role' => 'admin', 'is_active' => false]);

    $this->actingAs($user)
        ->get('/admin/dashboard')
        ->assertRedirect(route('admin.login'));
});

it('admin acessa o dashboard com sucesso', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->get('/admin/dashboard')
        ->assertOk();
});
