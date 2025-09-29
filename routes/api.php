<?php

use App\Http\Controllers\PostAPIController;
use App\Http\Controllers\CategoryAPIController;
use App\Http\Controllers\ProjectAPIController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes (no authentication required)
Route::post('/auth/login', [PostAPIController::class, 'login']);
Route::post('/auth/logout', [PostAPIController::class, 'logout'])->middleware('auth:sanctum');

// Protected routes (require authentication)
Route::prefix('api')->middleware('api.token')->group(function () {
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
    
    // Category API routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryAPIController::class, 'index']);
        Route::get('/{id}', [CategoryAPIController::class, 'show']);
        Route::post('/', [CategoryAPIController::class, 'store']);
        Route::put('/{id}', [CategoryAPIController::class, 'update']);
        Route::delete('/{id}', [CategoryAPIController::class, 'destroy']);
    });
    
    // Project API routes
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectAPIController::class, 'index']);
        Route::get('/{id}', [ProjectAPIController::class, 'show']);
        Route::post('/', [ProjectAPIController::class, 'store']);
        Route::put('/{id}', [ProjectAPIController::class, 'update']);
        Route::delete('/{id}', [ProjectAPIController::class, 'destroy']);
        Route::post('/{id}/make-default', [ProjectAPIController::class, 'makeDefault']);
        Route::post('/switch', [ProjectAPIController::class, 'switch']);
    });
});
