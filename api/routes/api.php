<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\MediaController;
use App\Http\Controllers\Api\Admin\PostController as AdminPostController;
use App\Http\Controllers\Api\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Api\Admin\RevisionController;
use App\Http\Controllers\Api\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Api\Admin\TagController as AdminTagController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Public\CategoryController as PublicCategoryController;
use App\Http\Controllers\Api\Public\ContactController as PublicContactController;
use App\Http\Controllers\Api\Public\PostController as PublicPostController;
use App\Http\Controllers\Api\Public\ProjectController as PublicProjectController;
use App\Http\Controllers\Api\Public\SearchController;
use App\Http\Controllers\Api\Public\SettingController as PublicSettingController;
use App\Http\Controllers\Api\Public\TagController as PublicTagController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ─── Public Authentication (Rate-limited: 5 req/min) ──────────────────────
    Route::post('auth/login', [LoginController::class, 'login'])
        ->middleware('throttle:login');

    // ─── Public Read Endpoints (Rate-limited: 60 req/min) ─────────────────────
    Route::middleware('throttle:public-api')->group(function () {
        Route::get('posts', [PublicPostController::class, 'index']);
        Route::get('posts/{slug}', [PublicPostController::class, 'show']);

        Route::get('categories', [PublicCategoryController::class, 'index']);
        Route::get('categories/{slug}', [PublicCategoryController::class, 'show']);

        Route::get('tags', [PublicTagController::class, 'index']);
        Route::get('tags/{slug}', [PublicTagController::class, 'show']);

        Route::get('projects', [PublicProjectController::class, 'index']);
        Route::get('projects/{slug}', [PublicProjectController::class, 'show']);

        Route::get('settings', [PublicSettingController::class, 'index']);
    });

    // ─── Read Beacons (Rate-limited: 20 req/min per IP) ───────────────────────
    Route::middleware('throttle:views')->group(function () {
        Route::post('posts/{slug}/view', [PublicPostController::class, 'view']);
        Route::post('projects/{slug}/view', [PublicProjectController::class, 'view']);
    });

    // ─── Live Search (Rate-limited: 30 req/min) ───────────────────────────────
    Route::get('search', [SearchController::class, 'index'])
        ->middleware('throttle:search');

    // ─── Contact Submission (Rate-limited: 3 req/5min + Bot Protection) ───────
    Route::post('contact', [PublicContactController::class, 'store'])
        ->middleware('throttle:contact');

    // ─── Admin Endpoints (Authenticated + Rate-limited: 120 req/min) ──────────
    Route::middleware(['auth:sanctum', 'throttle:admin-api'])->prefix('admin')->group(function () {
        // Auth session
        Route::post('auth/logout', [LoginController::class, 'logout']);
        Route::get('auth/me', [LoginController::class, 'me']);

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::post('dashboard/flush-views', [DashboardController::class, 'flushViews']);

        // Posts & Actions
        Route::post('posts/{post}/publish', [AdminPostController::class, 'publish']);
        Route::post('posts/{post}/unpublish', [AdminPostController::class, 'unpublish']);
        Route::post('posts/{post}/schedule', [AdminPostController::class, 'schedule']);
        Route::get('posts/{post}/revisions', [RevisionController::class, 'index']);
        Route::get('posts/{post}/revisions/{revision}', [RevisionController::class, 'show']);
        Route::post('posts/{post}/revisions/{revision}/restore', [RevisionController::class, 'restore']);
        Route::apiResource('posts', AdminPostController::class);

        // Taxonomies
        Route::apiResource('categories', AdminCategoryController::class);
        Route::apiResource('tags', AdminTagController::class);

        // Media
        Route::apiResource('media', MediaController::class)
            ->parameters(['media' => 'media'])
            ->only(['index', 'store', 'destroy']);

        // Projects
        Route::post('projects/reorder', [AdminProjectController::class, 'reorder']);
        Route::apiResource('projects', AdminProjectController::class);

        // Messages
        Route::apiResource('messages', AdminContactMessageController::class)
            ->only(['index', 'show', 'update', 'destroy']);

        // Settings
        Route::get('settings', [AdminSettingController::class, 'index']);
        Route::put('settings', [AdminSettingController::class, 'update']);
    });
});
