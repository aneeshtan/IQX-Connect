<?php

use App\Http\Controllers\AdminGoogleOAuthController;
use App\Http\Controllers\SourceWebhookController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('home');

Route::get('product', function () {
    return auth()->check()
        ? view('documentation')
        : view('product');
})->name('product');

Route::view('presentation', 'presentation')
    ->name('presentation');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::post('source-webhooks/{source}', [SourceWebhookController::class, 'ingest'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('source-webhooks.ingest');

Route::view('admin', 'admin')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('admin');

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin/google')->name('admin.google.')->group(function () {
    Route::get('connect/{company}', [AdminGoogleOAuthController::class, 'redirect'])->name('redirect');
    Route::get('callback', [AdminGoogleOAuthController::class, 'callback'])->name('callback');
    Route::delete('disconnect/{company}', [AdminGoogleOAuthController::class, 'disconnect'])->name('disconnect');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::redirect('documentation', 'product')
        ->middleware('verified');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
