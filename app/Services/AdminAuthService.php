<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminAuthService
{
    /**
     * @throws AuthenticationException
     * @throws AccessDeniedHttpException
     */
    public function login(string $email, string $password, bool $remember): User
    {
        if (! Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            throw new AuthenticationException('E-mail ou senha incorretos.');
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            throw new AccessDeniedHttpException('Sua conta está desativada. Entre em contato com um administrador.');
        }

        if (! $user->hasAdminAccess()) {
            Auth::logout();
            throw new AccessDeniedHttpException('Acesso não autorizado.');
        }

        $user->update(['last_login_at' => now()]);

        return $user;
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
