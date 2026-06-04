<?php

use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\CfpController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\TalkController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\CfpPublicController;
use App\Http\Controllers\Cfp\AccountController;
use App\Http\Controllers\Cfp\CfpAuthController;
use App\Http\Controllers\Cfp\SpeakerProfileController;
use App\Http\Controllers\Cfp\TalkSubmissionController;
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

// CFP Público
Route::prefix('cfp')->name('cfp.')->group(function () {
    Route::get('/api/events', [CfpPublicController::class, 'events'])->name('api.events');
    Route::get('/api/events/{event}', [CfpPublicController::class, 'show'])->name('api.event');
    Route::get('/api/me', [CfpAuthController::class, 'me'])->name('api.me');
    Route::post('/login', [CfpAuthController::class, 'login'])->name('login');
    Route::post('/register', [CfpAuthController::class, 'register'])->name('register');
    Route::post('/logout', [CfpAuthController::class, 'logout'])->name('logout');

    // Rotas protegidas — apenas palestrantes
    Route::middleware('speaker')->group(function () {
        Route::get('/api/speaker/profile',               [SpeakerProfileController::class, 'show'])->name('api.speaker.show');
        Route::patch('/api/speaker/profile',             [SpeakerProfileController::class, 'update'])->name('api.speaker.update');
        Route::post('/api/speaker/profile',              [SpeakerProfileController::class, 'update'])->name('api.speaker.update.post');
        Route::patch('/api/account',                     [AccountController::class, 'update'])->name('api.account.update');
        Route::get('/api/events/{event}/my-talks',       [TalkSubmissionController::class, 'myTalks'])->name('api.my-talks');
        Route::get('/api/events/{event}/my-talks/count', [TalkSubmissionController::class, 'myTalksCount'])->name('api.my-talks.count');
        Route::post('/api/events/{event}/talks',         [TalkSubmissionController::class, 'store'])->name('api.talks.store');
        Route::put('/api/talks/{talk}',                  [TalkSubmissionController::class, 'update'])->name('api.talks.update');
    });

    // SPA Vue
    Route::get('/', fn () => view('cfp'))->name('home');
    Route::get('/{any}', fn () => view('cfp'))->where('any', '.*');
});

Route::get('/sitemap.xml', function () {
    $content = view('sitemap');

    return response($content, 200)->header('Content-Type', 'application/xml');
});

Route::get('/robots.txt', function () {
    return response(view('robots'), 200)->header('Content-Type', 'text/plain');
});
