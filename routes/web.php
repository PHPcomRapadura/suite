<?php

use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\CfpController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\EventExpenseController;
use App\Http\Controllers\Admin\EventScheduleController;
use App\Http\Controllers\Admin\EventSiteController;
use App\Http\Controllers\Admin\EventSponsorController;
use App\Http\Controllers\Admin\EventParticipantController;
use App\Http\Controllers\Admin\EventTaskCommentController;
use App\Http\Controllers\Admin\EventTaskController;
use App\Http\Controllers\Admin\TalkController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\CfpPublicController;
use App\Http\Controllers\Cfp\AccountController;
use App\Http\Controllers\Cfp\CfpAuthController;
use App\Http\Controllers\Cfp\CfpPasswordResetController;
use App\Http\Controllers\Cfp\SpeakerProfileController;
use App\Http\Controllers\Cfp\TalkSubmissionController;
use App\Http\Controllers\EventSitePublicController;
use App\Http\Middleware\EnsureAdminRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $events = \App\Models\Event::where('status', 'publicado')
        ->orderBy('starts_at', 'desc')
        ->get(['name', 'slug', 'edition', 'starts_at', 'ends_at',
               'location', 'is_online', 'is_accepting_talks', 'cover_image']);

    return view('welcome', compact('events'));
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
            // Site do evento
            Route::get('/{event}/site',                    [EventSiteController::class, 'show'])->name('site.show');
            Route::post('/{event}/site',                   [EventSiteController::class, 'store'])->name('site.store');
            Route::put('/{event}/site',                    [EventSiteController::class, 'update'])->name('site.update');
            Route::patch('/{event}/site/toggle-published', [EventSiteController::class, 'togglePublished'])->name('site.togglePublished');
            // Patrocinadores
            Route::get('/{event}/site/sponsors',               [EventSponsorController::class, 'index'])->name('site.sponsors.index');
            Route::post('/{event}/site/sponsors',              [EventSponsorController::class, 'store'])->name('site.sponsors.store');
            Route::put('/{event}/site/sponsors/{sponsor}',     [EventSponsorController::class, 'update'])->name('site.sponsors.update');
            Route::delete('/{event}/site/sponsors/{sponsor}',  [EventSponsorController::class, 'destroy'])->name('site.sponsors.destroy');
            Route::patch('/{event}/site/sponsors/reorder',     [EventSponsorController::class, 'reorder'])->name('site.sponsors.reorder');
            // Grade de palestras
            Route::get('/{event}/site/schedule',           [EventScheduleController::class, 'index'])->name('site.schedule.index');
            Route::post('/{event}/site/schedule',          [EventScheduleController::class, 'store'])->name('site.schedule.store');
            Route::put('/{event}/site/schedule/{item}',    [EventScheduleController::class, 'update'])->name('site.schedule.update');
            Route::delete('/{event}/site/schedule/{item}', [EventScheduleController::class, 'destroy'])->name('site.schedule.destroy');
            // CFP
            Route::get('/{event}/cfp',  [CfpController::class, 'show'])->name('cfp.show');
            Route::post('/{event}/cfp', [CfpController::class, 'store'])->name('cfp.store');
            Route::put('/{event}/cfp',  [CfpController::class, 'update'])->name('cfp.update');
            // Despesas
            Route::get('/{event}/expenses',                     [EventExpenseController::class, 'index'])->name('expenses.index');
            Route::post('/{event}/expenses',                    [EventExpenseController::class, 'store'])->name('expenses.store');
            Route::get('/{event}/expenses/{expense}',           [EventExpenseController::class, 'show'])->name('expenses.show');
            Route::put('/{event}/expenses/{expense}',           [EventExpenseController::class, 'update'])->name('expenses.update')->middleware('role:admin');
            Route::post('/{event}/expenses/{expense}',          [EventExpenseController::class, 'update'])->name('expenses.update.post')->middleware('role:admin');
            Route::delete('/{event}/expenses/{expense}',        [EventExpenseController::class, 'destroy'])->name('expenses.destroy')->middleware('role:admin');
            // Tarefas
            Route::prefix('/{event}/tasks')->name('tasks.')->group(function () {
                Route::get('/',                    [EventTaskController::class, 'index'])->name('index');
                Route::post('/',                   [EventTaskController::class, 'store'])->name('store')->middleware('role:admin');
                Route::get('/trash',               [EventTaskController::class, 'trash'])->name('trash')->middleware('role:admin');
                Route::patch('/reorder',           [EventTaskController::class, 'reorder'])->name('reorder');
                Route::get('/{task}',              [EventTaskController::class, 'show'])->name('show');
                Route::put('/{task}',              [EventTaskController::class, 'update'])->name('update')->middleware('role:admin');
                Route::patch('/{task}/status',     [EventTaskController::class, 'updateStatus'])->name('updateStatus');
                Route::delete('/{task}',           [EventTaskController::class, 'destroy'])->name('destroy')->middleware('role:admin');
                Route::patch('/{taskId}/restore',  [EventTaskController::class, 'restore'])->name('restore')->middleware('role:admin');
                // Comentários
                Route::get('/{task}/comments',                       [EventTaskCommentController::class, 'index'])->name('comments.index');
                Route::post('/{task}/comments',                      [EventTaskCommentController::class, 'store'])->name('comments.store');
                Route::put('/{task}/comments/{comment}',             [EventTaskCommentController::class, 'update'])->name('comments.update');
                Route::delete('/{task}/comments/{comment}',          [EventTaskCommentController::class, 'destroy'])->name('comments.destroy');
            });
            // Participantes
            Route::prefix('/{event}/participants')->name('participants.')->group(function () {
                Route::get('/',              [EventParticipantController::class, 'index'])->name('index');
                Route::post('/upload',       [EventParticipantController::class, 'upload'])->name('upload')->middleware('role:admin');
                Route::delete('/',           [EventParticipantController::class, 'clear'])->name('clear')->middleware('role:admin');
                Route::delete('/{participant}', [EventParticipantController::class, 'destroy'])->name('destroy')->middleware('role:admin');
            });
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
        Route::get('/events/{id}/site', fn () => view('admin'))->name('events.site');
        Route::get('/events/{id}/expenses', fn () => view('admin'))->name('events.expenses');
        Route::get('/events/{id}/tasks', fn () => view('admin'))->name('events.tasks');
        Route::get('/events/{id}/participants', fn () => view('admin'))->name('events.participants');
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
    Route::post('/forgot-password', [CfpPasswordResetController::class, 'sendLink'])->name('password.email');
    Route::post('/reset-password',  [CfpPasswordResetController::class, 'reset'])->name('password.reset');

    // Rotas protegidas — apenas palestrantes
    Route::middleware('speaker')->group(function () {
        Route::get('/api/speaker/profile',               [SpeakerProfileController::class, 'show'])->name('api.speaker.show');
        Route::patch('/api/speaker/profile',             [SpeakerProfileController::class, 'update'])->name('api.speaker.update');
        Route::post('/api/speaker/profile',              [SpeakerProfileController::class, 'update'])->name('api.speaker.update.post');
        Route::patch('/api/account',                     [AccountController::class, 'update'])->name('api.account.update');
        Route::get('/api/my-talks',                      [TalkSubmissionController::class, 'allMyTalks'])->name('api.all-my-talks');
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

// Página pública do evento — deve ser a última rota para não interceptar /admin, /cfp, etc.
Route::get('/{slug}', [EventSitePublicController::class, 'show'])->name('event.site');
