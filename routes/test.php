<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/test-flash', function () {
    return redirect()->route('settings.api-tokens')
        ->with('success', 'Test flash message')
        ->with('api_token', 'test-token-12345');
});
