<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Define os rate limiters da aplicação.
     */
    private function configureRateLimiting(): void
    {
        // Rotas de autenticação (login, registro, recuperação de senha):
        // limita brute force e abuso de envio de e-mail por IP + e-mail informado.
        RateLimiter::for('auth', function (Request $request) {
            $email = (string) $request->input('email');

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
            ];
        });

        // Limite genérico para as APIs autenticadas.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by(
            optional($request->user())->id ?: $request->ip()
        ));
    }
}
