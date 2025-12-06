<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\InAppResetLinkController;

Route::get('/', function () {
    return view('attendify');
})->name('landing-page');

Route::get('/dashboard', function () {
    return view('dashboard.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::view('/terms', 'legal.terms_page')->name('terms');
Route::view('/privacy', 'legal.privacy_page')->name('privacy');

Route::middleware(['auth', 'fresh.session','verified'])->group(function () {
    Route::post('me/password/email-link', [InAppResetLinkController::class, 'store'])
        ->name('password.email.inapp');
});

require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
require __DIR__.'/profile.php';
require __DIR__.'/qr.php';
require __DIR__.'/classroom.php';
require __DIR__.'/events.php';
