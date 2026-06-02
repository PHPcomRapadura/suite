<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Services\AdminAuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminLoginController extends Controller
{
    public function __construct(
        private AdminAuthService $authService
    ) {}

    public function show(): View
    {
        return view('admin');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->login(
                $request->email,
                $request->password,
                $request->boolean('remember'),
            );
        } catch (AuthenticationException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'user' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role],
            'redirect' => route('admin.dashboard'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }
}
