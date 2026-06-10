<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\StoreUserRequest;
use App\Http\Requests\Admin\Users\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->userService->list(
            $request->only(['search', 'role', 'status'])
        );

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create(
            $request->validated(),
            Auth::id(),
        );

        return response()->json($user, Response::HTTP_CREATED);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        if ($user->id === Auth::id()) {
            return response()->json(
                ['message' => 'Você não pode editar a própria conta.'],
                Response::HTTP_FORBIDDEN
            );
        }

        if ($user->isSpeaker()) {
            return response()->json(
                ['message' => 'Dados de palestrantes não podem ser editados.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($request->validated()['role'] !== 'admin' && $this->userService->isLastActiveAdmin($user)) {
            return response()->json(
                ['message' => 'Não é possível rebaixar o único administrador ativo do sistema.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $updated = $this->userService->update($user, $request->validated());

        return response()->json($updated);
    }

    public function toggleStatus(User $user): JsonResponse
    {
        if ($user->id === Auth::id()) {
            return response()->json(
                ['message' => 'Você não pode alterar o status da própria conta.'],
                Response::HTTP_FORBIDDEN
            );
        }

        if ($this->userService->isLastActiveAdmin($user)) {
            return response()->json(
                ['message' => 'Não é possível desativar o único administrador ativo do sistema.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $updated = $this->userService->toggleStatus($user);

        return response()->json(['is_active' => $updated->is_active]);
    }
}
