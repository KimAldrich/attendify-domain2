<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Attendance\ScanStation;

Route::middleware(['auth', 'fresh.session','verified'])
    ->prefix('events')
    ->as('events.')
    ->group(function () {
        // /events  (list/browse)
        Route::get('/', fn () => view('events.index'))
            ->middleware('can:view events')
            ->name('index');

        // /events/manage  (CRUD etc.)
        Route::get('/manage', fn () => view('events.eventDashboardModerator'))
            ->middleware('can:manage events')
            ->name('manage');

        // /events/scanner  (QR scanner)
        Route::get('/scanner', ScanStation::class)
            ->middleware('can:open scanner')
            ->name('scanner');

        Route::view('drafts', 'events.drafts')
            ->middleware('can:manage events')->name('drafts');
        Route::view('create', 'events.create')
            ->middleware('can:manage events')->name('create');
        Route::get('design/{token}', fn($token)=>view('events.design',['token'=>$token]))
            ->middleware('can:manage events')->name('design');
        Route::get('view/{id}', fn($id)=>view('events.view',['id'=>$id]))
            ->middleware('can:manage events')->name('view');

    });

