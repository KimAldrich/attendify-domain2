<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'fresh.session','verified'])
    ->prefix('classroom')
    ->as('classroom.')
    ->group(function () {
        // /classroom (student/admin views)
        Route::get('/', fn () => view('classroom.index'))
            ->middleware('can:view attendance')
            ->name('index');

        // /classroom/manage (faculty/admin control)
        Route::get('/manage', fn () => view('classroom.manage'))
            ->middleware('can:manage attendance')
            ->name('manage');
    });