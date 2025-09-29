<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    // API Token management routes
    Route::get('settings/api-tokens', [ApiTokenController::class, 'index'])
        ->name('settings.api-tokens');
    Route::post('settings/api-tokens', [ApiTokenController::class, 'store'])
        ->name('settings.api-tokens.store');
    Route::put('settings/api-tokens/{apiToken}', [ApiTokenController::class, 'update'])
        ->name('settings.api-tokens.update');
    Route::delete('settings/api-tokens/{apiToken}', [ApiTokenController::class, 'destroy'])
        ->name('settings.api-tokens.destroy');
    Route::post('settings/api-tokens/{apiToken}/regenerate', [ApiTokenController::class, 'regenerate'])
        ->name('settings.api-tokens.regenerate');
});
