<?php

use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\CfpController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\TalkController;
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

        // API — CRUD de eventos (admin + colaborador)
        Route::prefix('api/events')->name('events.')->group(function () {
            Route::get('/', [EventController::class, 'index'])->name('index');
            Route::post('/', [EventController::class, 'store'])->name('store');
            // CFP
            Route::get('/{event}/cfp',  [CfpController::class, 'show'])->name('cfp.show');
            Route::post('/{event}/cfp', [CfpController::class, 'store'])->name('cfp.store');
            Route::put('/{event}/cfp',  [CfpController::class, 'update'])->name('cfp.update');
            // Talks
            Route::get('/{event}/talks',                 [TalkController::class, 'index'])->name('talks.index');
            Route::get('/{event}/talks/{talk}',          [TalkController::class, 'show'])->name('talks.show');
            Route::patch('/{event}/talks/{talk}/status', [TalkController::class, 'updateStatus'])->name('talks.updateStatus');
            // Eventos
            Route::get('/{event}', [EventController::class, 'show'])->name('show');
            Route::put('/{event}', [EventController::class, 'update'])->name('update');
            Route::post('/{event}', [EventController::class, 'update'])->name('update.post');
            Route::patch('/{event}/status', [EventController::class, 'updateStatus'])->name('updateStatus');
            Route::patch('/{event}/toggle-talks', [EventController::class, 'toggleTalks'])->name('toggleTalks')->middleware('role:admin');
        });

        // SPA Vue — rotas de página
        Route::get('/dashboard', fn () => view('admin'))->name('dashboard');
        Route::get('/users', fn () => view('admin'))->name('users');
        Route::get('/events', fn () => view('admin'))->name('events');
        Route::get('/events/{id}', fn () => view('admin'))->name('events.show');
        Route::get('/events/{id}/cfp', fn () => view('admin'))->name('events.cfp');
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
