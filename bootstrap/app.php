<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureSpeaker;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->alias([
            'role' => CheckRole::class,
            'speaker' => EnsureSpeaker::class,
        ]);

        // Adiciona headers de segurança (CSP, X-Frame-Options, HSTS, nosniff) a todas as respostas.
        $middleware->append(SecurityHeaders::class);

        // Confia nos proxies/CDN definidos em TRUSTED_PROXIES (ex.: ranges do Cloudflare ou
        // do load balancer). Vazio/ausente = não confia em nenhum proxy (usa REMOTE_ADDR real),
        // que é o correto para o setup nginx→PHP-FPM direto. Defina em produção atrás de um CDN.
        $trustedProxies = env('TRUSTED_PROXIES');
        if (! empty($trustedProxies)) {
            $middleware->trustProxies(
                at: $trustedProxies === '*' ? '*' : explode(',', $trustedProxies),
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO,
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
