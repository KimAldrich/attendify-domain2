<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

Route::middleware(['auth', 'fresh.session','verified'])->group(function () {
    // My profile (no param)
    Route::get('/my-profile', function () {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    })->name('profile.me');

    // Someone else’s profile (slug)
    Route::get('/profile/{user:slug}', function (User $user) {
        return view('profile.index', compact('user'));
    })->name('profile.show');
});