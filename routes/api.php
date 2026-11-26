<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\VerifyEmailController;
use App\Http\Controllers\Api\V1\BlogAuthorController;
use App\Http\Controllers\Api\V1\BlogController;
use App\Http\Controllers\Api\V1\ContactSubmissionController;
use App\Http\Controllers\Api\V1\FaqController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\SuccessStoryController;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ---------------------------------------------------------------
    // Auth (register and login: 5 tries per minute per IP)
    // ---------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
        Route::post('email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:6,1');
        Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
    });

    // ---------------------------------------------------------------
    // Public content (no login)
    // ---------------------------------------------------------------
    Route::apiResource('plans', PlanController::class)->only(['index', 'show'])
        ->parameters(['plans' => 'plan']);
    Route::apiResource('blogs', BlogController::class)->only(['index', 'show'])
        ->parameters(['blogs' => 'blog']);
    Route::apiResource('success-stories', SuccessStoryController::class)->only(['index', 'show'])
        ->parameters(['success-stories' => 'story']);
    Route::apiResource('faqs', FaqController::class)->only(['index', 'show'])
        ->parameters(['faqs' => 'faq']);
    Route::post('contact-submissions', [ContactSubmissionController::class, 'store'])
        ->middleware('throttle:10,1,contact')
        ->name('contact-submissions.store');

    // ---------------------------------------------------------------
    // Private routes: valid token, account not blocked, 60 requests per minute
    // ---------------------------------------------------------------
    Route::middleware(['auth:sanctum', EnsureAccountActive::class, 'throttle:60,1'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // -----------------------------------------------------------
        // Admin only (full control): /api/v1/admin/...
        // -----------------------------------------------------------
        Route::middleware(EnsureRole::class . ':admin')->prefix('admin')->name('admin.')->group(function () {
            Route::get('ping', fn () => response()->json(['message' => 'admin ok']))->name('ping');

            Route::apiResource('plans', PlanController::class)
                ->parameters(['plans' => 'plan']);
            Route::apiResource('blogs', BlogController::class)
                ->parameters(['blogs' => 'blog']);
            Route::apiResource('blog-authors', BlogAuthorController::class)
                ->parameters(['blog-authors' => 'author']);
            Route::apiResource('success-stories', SuccessStoryController::class)
                ->parameters(['success-stories' => 'story']);
            Route::apiResource('faqs', FaqController::class)
                ->parameters(['faqs' => 'faq']);
            Route::apiResource('contact-submissions', ContactSubmissionController::class)
                ->only(['index', 'show', 'update', 'destroy'])
                ->parameters(['contact-submissions' => 'submission']);
        });
    });
});
