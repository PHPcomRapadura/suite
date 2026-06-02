<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

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
            'name' => 'Novo Colaborador',
            'email' => 'novo@exemplo.com',
            'role' => 'colaborador',
            'password' => 'senha@123',
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
            'name' => 'Novo Admin',
            'email' => 'novo.admin@exemplo.com',
            'role' => 'admin',
            'password' => 'senha@123',
            'password_confirmation' => 'senha@123',
        ])
        ->assertCreated()
        ->assertJsonPath('role', 'admin');
});

it('created_by é preenchido com o id do admin logado', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/admin/api/users', [
            'name' => 'Criado Por',
            'email' => 'criadopor@exemplo.com',
            'role' => 'colaborador',
            'password' => 'senha@123',
            'password_confirmation' => 'senha@123',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('users', [
        'email' => 'criadopor@exemplo.com',
        'created_by' => $admin->id,
    ]);
});

it('bloqueia criação com role palestrante', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/users', [
            'name' => 'Palestrante',
            'email' => 'palestrante@exemplo.com',
            'role' => 'palestrante',
            'password' => 'senha@123',
            'password_confirmation' => 'senha@123',
        ])
        ->assertUnprocessable();
});

it('retorna 422 ao criar com email já existente', function () {
    User::factory()->create(['email' => 'existente@exemplo.com']);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/users', [
            'name' => 'Duplicado',
            'email' => 'existente@exemplo.com',
            'role' => 'colaborador',
            'password' => 'senha@123',
            'password_confirmation' => 'senha@123',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('retorna 422 ao criar com senha menor que 8 caracteres', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/users', [
            'name' => 'Curto',
            'email' => 'curto@exemplo.com',
            'role' => 'colaborador',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('retorna 422 ao criar sem confirmação de senha', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/admin/api/users', [
            'name' => 'Sem Confirm',
            'email' => 'semconfirm@exemplo.com',
            'role' => 'colaborador',
            'password' => 'senha@123',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

// ─── Edição ───────────────────────────────────────────────────────────────────

it('admin atualiza nome e email de outro usuário', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->colaborador()->create();

    $this->actingAs($admin)
        ->putJson("/admin/api/users/{$target->id}", [
            'name' => 'Nome Atualizado',
            'email' => 'atualizado@exemplo.com',
            'role' => 'colaborador',
        ])
        ->assertOk()
        ->assertJsonPath('name', 'Nome Atualizado')
        ->assertJsonPath('email', 'atualizado@exemplo.com');
});

it('senha não é alterada quando o campo não é enviado', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->colaborador()->create(['password' => 'original@123']);
    $hashAntes = $target->password;

    $this->actingAs($admin)
        ->putJson("/admin/api/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'colaborador',
        ])
        ->assertOk();

    expect($target->fresh()->password)->toBe($hashAntes);
});

it('senha é alterada quando o campo é enviado', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->colaborador()->create(['password' => 'original@123']);

    $this->actingAs($admin)
        ->putJson("/admin/api/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'colaborador',
            'password' => 'nova@senha123',
            'password_confirmation' => 'nova@senha123',
        ])
        ->assertOk();

    expect(Hash::check('nova@senha123', $target->fresh()->password))->toBeTrue();
});

it('admin não pode editar a própria conta', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->putJson("/admin/api/users/{$admin->id}", [
            'name' => 'Eu Mesmo',
            'email' => $admin->email,
            'role' => 'admin',
        ])
        ->assertForbidden();
});

it('não permite editar dados de palestrante', function () {
    $admin = User::factory()->admin()->create();
    $palestrante = User::factory()->palestrante()->create();

    $this->actingAs($admin)
        ->putJson("/admin/api/users/{$palestrante->id}", [
            'name' => 'Nome Novo',
            'email' => 'novo@exemplo.com',
            'role' => 'colaborador',
        ])
        ->assertUnprocessable();
});

it('retorna 422 ao atualizar email para um já existente', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'ocupado@exemplo.com']);
    $target = User::factory()->colaborador()->create();

    $this->actingAs($admin)
        ->putJson("/admin/api/users/{$target->id}", [
            'name' => $target->name,
            'email' => 'ocupado@exemplo.com',
            'role' => 'colaborador',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('não gera erro de email duplicado ao manter o email do próprio usuário', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->colaborador()->create(['email' => 'meu@exemplo.com']);

    $this->actingAs($admin)
        ->putJson("/admin/api/users/{$target->id}", [
            'name' => 'Nome Atualizado',
            'email' => 'meu@exemplo.com',
            'role' => 'colaborador',
        ])
        ->assertOk();
});

// ─── Toggle de status ─────────────────────────────────────────────────────────

it('desativa usuário ativo', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create(['is_active' => true]);

    $this->actingAs($admin)
        ->patchJson("/admin/api/users/{$target->id}/toggle-status")
        ->assertOk()
        ->assertJsonPath('is_active', false);

    expect($target->fresh()->is_active)->toBeFalse();
});

it('ativa usuário inativo', function () {
    $admin = User::factory()->admin()->create();
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
    $admin = User::factory()->admin()->create();
    $palestrante = User::factory()->palestrante()->create(['is_active' => true]);

    $this->actingAs($admin)
        ->patchJson("/admin/api/users/{$palestrante->id}/toggle-status")
        ->assertOk()
        ->assertJsonPath('is_active', false);
});
