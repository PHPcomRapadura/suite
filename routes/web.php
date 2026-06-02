<?php

use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Middleware\EnsureAdminRole;
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
        Route::get('/dashboard', fn () => view('admin'))->name('dashboard');
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
