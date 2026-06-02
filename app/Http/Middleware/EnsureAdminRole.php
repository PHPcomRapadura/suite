<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED)
                : redirect()->route('admin.login');
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();

            return $request->expectsJson()
                ? response()->json(['message' => 'Sua conta está desativada.'], Response::HTTP_FORBIDDEN)
                : redirect()->route('admin.login')->withErrors(['email' => 'Sua conta foi desativada.']);
        }

        if (! $user->hasAdminAccess()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Acesso não autorizado.'], Response::HTTP_FORBIDDEN)
                : redirect()->route('admin.login');
        }

        return $next($request);
    }
}
