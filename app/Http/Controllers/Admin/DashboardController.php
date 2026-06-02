<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'users_total' => User::count(),
            'users_admin' => User::where('role', 'admin')->count(),
            'users_inactive' => User::where('is_active', false)->count(),
        ]);
    }
}
