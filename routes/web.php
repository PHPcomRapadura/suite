<?php

use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\EnsureAdminRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin
Route::prefix('admin')->name('admin.')->group(function () {

    // Rotas públicas
    Route::get('/login', [AdminLoginController::class, 'show'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login']);

    // Rotas protegidas
    Route::middleware(['auth', EnsureAdminRole::class])->group(function () {
        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

        // API — utilitários
        Route::get('/api/me', fn () => response()->json(Auth::user()))->name('me');
        Route::get('/api/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

        // API — CRUD de usuários (somente admin)
        Route::prefix('api/users')->name('users.')->middleware('role:admin')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}', [UserController::class, 'show'])->name('show');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggleStatus');
        });

        // SPA Vue — rotas de página
        Route::get('/dashboard', fn () => view('admin'))->name('dashboard');
        Route::get('/users', fn () => view('admin'))->name('users');
        Route::get('/{any}', fn () => view('admin'))->where('any', '[a-zA-Z0-9/_-]+');
    });
});

Route::get('/sitemap.xml', function () {
    $content = view('sitemap');

    return response($content, 200)->header('Content-Type', 'application/xml');
});

Route::get('/robots.txt', function () {
    return response(view('robots'), 200)->header('Content-Type', 'text/plain');
});
