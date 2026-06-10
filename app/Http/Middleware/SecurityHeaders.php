<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;

        // Sempre seguros (não quebram nada):
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HSTS apenas em conexões HTTPS (ignorado pelo browser em HTTP).
        if ($request->secure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // CSP apenas em produção: em dev o Vite serve assets de localhost:5173 via
        // WebSocket/HMR, o que uma CSP estrita bloquearia. 'unsafe-inline' é necessário
        // porque os templates Blade usam <script> inline (toggle de tema) e o Vue/Tailwind
        // injetam estilos inline; ainda assim travamos object-src/base-uri/frame-ancestors.
        if (app()->environment('production') && ! $response->headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline'",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
                "font-src 'self' https://fonts.bunny.net data:",
                "img-src 'self' data: https:",
                "connect-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "frame-ancestors 'self'",
                'upgrade-insecure-requests',
            ]));
        }

        return $response;
    }
}
