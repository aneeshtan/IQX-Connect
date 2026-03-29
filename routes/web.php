<?php

use App\Http\Controllers\AdminGoogleOAuthController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('home');

Route::view('product', 'product')
    ->name('product');

Route::view('presentation', 'presentation')
    ->name('presentation');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

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

    Route::view('documentation', 'documentation')
        ->middleware('verified')
        ->name('documentation');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
