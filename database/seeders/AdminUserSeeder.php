<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', env('ADMIN_EMAIL', 'admin@phpcomrapadura.org'))->exists()) {
            $this->command->info('Admin já existe, seed ignorada.');

            return;
        }

        User::create([
            'name' => 'Administrador',
            'email' => env('ADMIN_EMAIL', 'admin@phpcomrapadura.org'),
            'password' => env('ADMIN_PASSWORD', 'mudar@123'),
            'role' => 'admin',
            'is_active' => true,
            'created_by' => null,
        ]);

        $this->command->info('Admin criado com sucesso.');
    }
}
