<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Via config(), não env(): com a config em cache (produção), env() retorna null.
        // ADMIN_EMAIL vazio no .env cai no padrão.
        $email = config('app.admin.email') ?: 'admin@phpcomrapadura.org';
        $password = config('app.admin.password');

        if (User::where('email', $email)->exists()) {
            $this->command->info('Admin já existe, seed ignorada.');

            return;
        }

        // Nunca usar senha fixa previsível. Sem ADMIN_PASSWORD definido:
        // em produção, aborta; fora dela, gera uma senha aleatória e a exibe uma única vez.
        if (empty($password)) {
            if (app()->environment('production')) {
                $this->command->error('ADMIN_PASSWORD não definido. Defina-o no .env antes de rodar a seed em produção.');

                return;
            }

            $password = Str::password(16);
            $this->command->warn("ADMIN_PASSWORD não definido — senha gerada: {$password}");
            $this->command->warn('Anote agora: ela não será exibida novamente.');
        }

        User::create([
            'name' => 'Administrador',
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
            'is_active' => true,
            'created_by' => null,
        ]);

        $this->command->info('Admin criado com sucesso.');
    }
}
