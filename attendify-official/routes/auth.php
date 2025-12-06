<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\FirebasePasswordResetController;
use App\Http\Controllers\Auth\FirebaseResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\IdTokenLoginController;
use App\Http\Controllers\Settings\AccountSecurityController;
use App\Http\Controllers\NotificationsController;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [FirebaseResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [FirebaseResetLinkController::class, 'store'])
        ->name('password.email');

    // Render reset form using ?oobCode=&email=
    Route::get('reset-password', [FirebasePasswordResetController::class, 'create'])
        ->name('password.reset');

    // Apply new password (updates Firebase)
    Route::post('reset-password', [FirebasePasswordResetController::class, 'store'])
        ->name('password.update.firebase');
});

// Email verification (guest signed link)
Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, 'guest'])
    ->middleware(['signed','throttle:6,1'])
    ->name('verification.verify');

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');

    Route::get('/settings/security', [AccountSecurityController::class, 'index'])->name('settings.security');

    Route::get('/notifications', [NotificationsController::class, 'index'])
        ->name('notifications.index');

    Route::post('/notifications/delete-read', [NotificationsController::class, 'deleteRead'])
        ->name('notifications.delete-read');

    Route::get('/notifications/{notification}/open', [NotificationsController::class, 'open'])
        ->name('notifications.open');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::post('/login/idtoken', [IdTokenLoginController::class, 'store'])
    ->middleware(['guest', 'throttle:20,1']) // protect from spam
    ->name('login.idtoken');

Route::post('/settings/security/password/linked',
    [AccountSecurityController::class, 'linked']
)->middleware(['auth','verified','throttle:20,1'])->name('settings.security.password.linked');
