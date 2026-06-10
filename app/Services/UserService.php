<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->when(
                $filters['search'] ?? null,
                fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
            )
            ->when($filters['role'] ?? null, fn ($q, $r) => $q->where('role', $r))
            ->when(
                $filters['status'] ?? null,
                fn ($q, $s) => $q->where('is_active', $s === 'active')
            )
            ->orderBy('name')
            ->paginate(9);
    }

    public function create(array $data, int $createdBy): User
    {
        return User::create([...$data, 'created_by' => $createdBy]);
    }

    public function update(User $user, array $data): User
    {
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        return $user->fresh();
    }

    public function toggleStatus(User $user): User
    {
        $user->update(['is_active' => ! $user->is_active]);

        return $user->fresh();
    }

    /**
     * Indica se o usuário é o único admin ativo restante — usado para impedir
     * que o sistema fique sem nenhum administrador (lockout).
     */
    public function isLastActiveAdmin(User $user): bool
    {
        return $user->role === 'admin'
            && $user->is_active
            && User::where('role', 'admin')->where('is_active', true)->count() <= 1;
    }
}
