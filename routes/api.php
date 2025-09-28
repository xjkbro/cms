<?php

use App\Http\Controllers\PostAPIController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes (no authentication required)
Route::post('/auth/login', [PostAPIController::class, 'login']);
Route::post('/auth/logout', [PostAPIController::class, 'logout'])->middleware('auth:sanctum');

// Protected routes (require authentication)
Route::prefix('api')->middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    // Post API routes
    Route::prefix('posts')->group(function () {
        Route::get('/', [PostAPIController::class, 'index']);
        Route::get('/{id}', [PostAPIController::class, 'show']);
        Route::post('/', [PostAPIController::class, 'store']);
        Route::put('/{id}', [PostAPIController::class, 'update']);
        Route::delete('/{id}', [PostAPIController::class, 'destroy']);
    });
});
