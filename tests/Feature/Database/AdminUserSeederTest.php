<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

// A seed lê config('app.admin'), não env(): em produção a config fica em cache
// e env() retorna null fora dos arquivos de config.

it('cria o admin com as credenciais de config(app.admin)', function () {
    config(['app.admin.email' => 'admin@exemplo.org', 'app.admin.password' => 'segredo-forte-123']);

    $this->seed(AdminUserSeeder::class);

    $user = User::where('email', 'admin@exemplo.org')->firstOrFail();
    expect($user->role)->toBe('admin')
        ->and((bool) $user->is_active)->toBeTrue()
        ->and(Hash::check('segredo-forte-123', $user->password))->toBeTrue();
});

it('usa o e-mail padrão quando ADMIN_EMAIL está vazio', function () {
    config(['app.admin.email' => '', 'app.admin.password' => 'segredo-forte-123']);

    $this->seed(AdminUserSeeder::class);

    expect(User::where('email', 'admin@phpcomrapadura.org')->exists())->toBeTrue();
});

it('não cria o admin em produção sem ADMIN_PASSWORD', function () {
    config(['app.admin.email' => 'admin@exemplo.org', 'app.admin.password' => null]);
    $this->app['env'] = 'production';

    $this->artisan('db:seed', ['--class' => AdminUserSeeder::class, '--force' => true]);

    expect(User::count())->toBe(0);
});

it('não duplica o admin quando ele já existe', function () {
    config(['app.admin.email' => 'admin@exemplo.org', 'app.admin.password' => 'segredo-forte-123']);

    $this->seed(AdminUserSeeder::class);
    $this->seed(AdminUserSeeder::class);

    expect(User::where('email', 'admin@exemplo.org')->count())->toBe(1);
});
