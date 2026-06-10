<?php

namespace App\Http\Controllers\Cfp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cfp\UpdateAccountRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function update(UpdateAccountRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $data = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        $user->update($data);

        return response()->json([
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    }
}
