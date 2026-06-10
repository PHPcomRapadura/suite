<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', env('ADMIN_EMAIL', 'admin@phpcomrapadura.org'))->exists()) {
            $this->command->info('Admin já existe, seed ignorada.');

            return;
        }

        $password = env('ADMIN_PASSWORD');

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
            'email' => env('ADMIN_EMAIL', 'admin@phpcomrapadura.org'),
            'password' => $password,
            'role' => 'admin',
            'is_active' => true,
            'created_by' => null,
        ]);

        $this->command->info('Admin criado com sucesso.');
    }
}
