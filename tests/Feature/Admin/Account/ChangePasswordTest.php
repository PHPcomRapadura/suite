<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('guest recebe 401 ao tentar alterar a senha', function () {
    $this->putJson('/admin/api/account/password', [])->assertUnauthorized();
});

it('admin altera a própria senha com sucesso', function () {
    $admin = User::factory()->admin()->create(['password' => Hash::make('senha-atual-123')]);

    $this->actingAs($admin)
        ->putJson('/admin/api/account/password', [
            'current_password' => 'senha-atual-123',
            'password' => 'nova-senha-456',
            'password_confirmation' => 'nova-senha-456',
        ])
        ->assertOk()
        ->assertJson(['message' => 'Senha alterada com sucesso.']);

    expect(Hash::check('nova-senha-456', $admin->fresh()->password))->toBeTrue();
});

it('colaborador também pode alterar a própria senha', function () {
    $colab = User::factory()->colaborador()->create(['password' => Hash::make('senha-atual-123')]);

    $this->actingAs($colab)
        ->putJson('/admin/api/account/password', [
            'current_password' => 'senha-atual-123',
            'password' => 'nova-senha-456',
            'password_confirmation' => 'nova-senha-456',
        ])
        ->assertOk();
});

it('rejeita quando a senha atual está incorreta', function () {
    $admin = User::factory()->admin()->create(['password' => Hash::make('senha-atual-123')]);

    $this->actingAs($admin)
        ->putJson('/admin/api/account/password', [
            'current_password' => 'senha-errada',
            'password' => 'nova-senha-456',
            'password_confirmation' => 'nova-senha-456',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('current_password');

    expect(Hash::check('senha-atual-123', $admin->fresh()->password))->toBeTrue();
});

it('rejeita nova senha com menos de 8 caracteres', function () {
    $admin = User::factory()->admin()->create(['password' => Hash::make('senha-atual-123')]);

    $this->actingAs($admin)
        ->putJson('/admin/api/account/password', [
            'current_password' => 'senha-atual-123',
            'password' => 'curta',
            'password_confirmation' => 'curta',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

it('rejeita quando a confirmação não coincide', function () {
    $admin = User::factory()->admin()->create(['password' => Hash::make('senha-atual-123')]);

    $this->actingAs($admin)
        ->putJson('/admin/api/account/password', [
            'current_password' => 'senha-atual-123',
            'password' => 'nova-senha-456',
            'password_confirmation' => 'outra-coisa-789',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

it('rejeita quando a nova senha é igual à atual', function () {
    $admin = User::factory()->admin()->create(['password' => Hash::make('senha-atual-123')]);

    $this->actingAs($admin)
        ->putJson('/admin/api/account/password', [
            'current_password' => 'senha-atual-123',
            'password' => 'senha-atual-123',
            'password_confirmation' => 'senha-atual-123',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');
});
