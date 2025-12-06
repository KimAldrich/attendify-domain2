<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Events\EventPublicController;
use App\Http\Controllers\Events\EventManageController;
use App\Http\Controllers\Events\EventProgramController;
use App\Http\Controllers\Events\EventPeopleController;
use App\Http\Controllers\Events\EventGalleryController;
use App\Http\Controllers\Events\EventCertificateController;
use App\Http\Controllers\Events\EventAnalyticsController;
use App\Http\Controllers\Events\EventRegistrationController;
use App\Http\Controllers\Events\EventEvaluationController;
use App\Livewire\Attendance\ScanStation;
use App\Http\Controllers\Events\EventSpecialGuestController;
use App\Http\Controllers\Events\EventPreviewController;


Route::prefix('events')
    ->as('events.')
    ->group(function () {

        // PUBLIC
        Route::get('/', [EventPublicController::class, 'index'])
            ->name('index');

        // AUTH ATTENDEE
        Route::middleware(['auth', 'fresh.session', 'verified'])
            ->group(function () {

                Route::get('/my', [EventPublicController::class, 'myEvents'])
                    ->name('my');

                Route::delete('/{event:slug}/registration', [EventPublicController::class, 'cancelRegistration'])
                    ->name('registration.cancel');

                Route::get('/{event:slug}/evaluation', [EventEvaluationController::class, 'show'])
                    ->name('evaluation.show');

                Route::post('/{event:slug}/evaluation', [EventEvaluationController::class, 'submit'])
                    ->name('evaluation.submit');
            });

        /*
        |----------------------------------------------------------------------
        | Manage Events routes
        |----------------------------------------------------------------------
        */

        Route::prefix('manage')
            ->as('manage.')
            ->middleware([
                'auth', 'fresh.session', 'verified',
                'can:access-manage-events',
            ])
            ->group(function () {

                Route::get( '/{event}/preview', [EventPreviewController::class, 'show'])
                    ->name('preview');

                Route::get('/', [EventManageController::class, 'index'])
                    ->name('index');

                Route::post('/', [EventManageController::class, 'store'])
                    ->name('store');

                Route::get('/{event}', [EventManageController::class, 'redirectToDetails'])
                    ->name('show');

                Route::post('/{event}/publish', [EventManageController::class, 'publish'])
                    ->name('publish');

                Route::get('/{event}/details', [EventManageController::class, 'details'])
                    ->name('details');
                Route::put('/{event}/details', [EventManageController::class, 'updateDetails'])
                    ->name('details.update');

                Route::delete('/{event}', [EventManageController::class, 'destroy'])
                    ->name('destroy');

                Route::post('/{event}/cancel', [EventManageController::class, 'cancel'])
                    ->name('cancel');

                Route::post('/{event}/archive', [EventManageController::class, 'archive'])
                    ->name('archive');

                // PROGRAM
                Route::get('/{event}/program', [EventProgramController::class, 'show'])->name('program');

                Route::post('/{event}/program/days', [EventProgramController::class, 'storeDay'])->name('program.days.store');
                Route::put('/{event}/program/days/{day}', [EventProgramController::class, 'updateDay'])->name('program.days.update');
                Route::delete('/{event}/program/days/{day}', [EventProgramController::class, 'destroyDay'])->name('program.days.destroy');

                Route::post('/{event}/program/tracks', [EventProgramController::class, 'storeTrack'])->name('program.tracks.store');
                Route::put('/{event}/program/tracks/{track}', [EventProgramController::class, 'updateTrack'])->name('program.tracks.update');
                Route::delete('/{event}/program/tracks/{track}', [EventProgramController::class, 'destroyTrack'])->name('program.tracks.destroy');

                Route::post('/{event}/program/activities', [EventProgramController::class, 'storeActivity'])->name('program.activities.store');
                Route::put('/{event}/program/activities/{activity}', [EventProgramController::class, 'updateActivity'])->name('program.activities.update');
                Route::delete('/{event}/program/activities/{activity}', [EventProgramController::class, 'destroyActivity'])->name('program.activities.destroy');
                Route::post(
                    '/events/manage/{event}/program/activities',
                    [EventProgramController::class, 'storeActivity']
                )->name('program.activities.store');
                Route::post('/events/manage/{event}/program/activities/{activity}/duplicate', [
                    EventProgramController::class,
                    'duplicateActivity',
                ])->name('program.activities.duplicate');
                Route::get('{event}/program/export-pdf', [EventProgramController::class, 'exportProgramPdf'])
                    ->name('program.export-pdf');
                Route::get('/events/manage/{event}/program/csv', 
                    [EventProgramController::class, 'exportProgramCsv']
                )->name('program.csv');
                Route::post('/{event}/program/days/{day}/transfer-activities', [EventProgramController::class, 'transferDayActivities'])
                    ->name('program.days.transfer-activities');

                // REGISTRATION
                Route::get('/{event}/registration', [EventRegistrationController::class, 'index'])->name('registration');
                Route::post('/{event}/registration/{registration}/approve', [EventRegistrationController::class, 'approve'])->name('registration.approve');
                Route::post('/{event}/registration/{registration}/reject', [EventRegistrationController::class, 'reject'])->name('registration.reject');
                Route::post('/{event}/registration/{registration}/waitlist', [EventRegistrationController::class, 'waitlist'])->name('registration.waitlist');
                Route::put('/{event}/registration/settings', [EventRegistrationController::class, 'updateSettings'])->name('registration.settings.update');

                // SPECIAL GUESTS
                Route::get('/{event}/guests', [EventSpecialGuestController::class, 'index'])
                    ->name('guests');

                Route::post('/{event}/guests', [EventSpecialGuestController::class, 'store'])
                    ->name('guests.store');

                Route::delete('/{event}/guests/{guest}', [EventSpecialGuestController::class, 'destroy'])
                    ->name('guests.destroy');

                Route::put('/{event}/special-guests/{guest}', [EventSpecialGuestController::class, 'update'])
                    ->name('guests.update');

                // PEOPLE
                Route::get('/{event}/people', [EventPeopleController::class, 'index'])->name('people');
                Route::post('/{event}/people/roles', [EventPeopleController::class, 'storeRole'])->name('people.roles.store');
                Route::delete('/{event}/people/roles/{role}', [EventPeopleController::class, 'destroyRole'])->name('people.roles.destroy');

                // GALLERY
                Route::get('/{event}/gallery', [EventGalleryController::class, 'index'])->name('gallery');
                Route::post('/{event}/gallery', [EventGalleryController::class, 'store'])->name('gallery.store');
                Route::put('/{event}/gallery/{photo}', [EventGalleryController::class, 'update'])->name('gallery.update');
                Route::delete('/{event}/gallery/{photo}', [EventGalleryController::class, 'destroy'])->name('gallery.destroy');
                Route::post('/{event}/gallery/rename-album', [EventGalleryController::class, 'renameAlbum'])
                    ->name('gallery.rename-album');
                Route::delete('/{event}/gallery/bulk-delete', [EventGalleryController::class, 'bulkDestroy'])
                    ->name('gallery.bulk-destroy');
                Route::post('/{event}/gallery/bulk-transfer', [EventGalleryController::class, 'bulkTransfer'])
                    ->name('gallery.bulk-transfer');
                    
                // CERTIFICATES
                Route::get('/{event}/certificates', [EventCertificateController::class, 'index'])->name('certificates');
                Route::put('/{event}/certificates/settings', [EventCertificateController::class, 'updateSettings'])->name('certificates.settings.update');
                Route::post('/{event}/certificates/generate', [EventCertificateController::class, 'generateForEvent'])->name('certificates.generate');
                Route::post('/{event}/certificates/{certificate}/reissue', [EventCertificateController::class, 'reissue'])->name('certificates.reissue');

                // ANALYTICS
                Route::get('/{event}/analytics', [EventAnalyticsController::class, 'show'])->name('analytics');
                Route::post('/{event}/analytics/recalculate', [EventAnalyticsController::class, 'recalculate'])->name('analytics.recalculate');
                Route::post('/{event}/analytics/regenerate-ai', [EventAnalyticsController::class, 'regenerateAiSummary'])->name('analytics.regenerate-ai');

                    });

        /*
        |----------------------------------------------------------------------
        | QR scanner
        |----------------------------------------------------------------------
        */

        Route::get('/scanner', ScanStation::class)
            ->middleware([
                'auth', 'fresh.session', 'verified',
                'can:access-event-scanner',
            ])
            ->name('scanner');

        /*
        |----------------------------------------------------------------------
        | Slug routes
        |----------------------------------------------------------------------
        */

        Route::get('/{event:slug}', [EventPublicController::class, 'show'])
            ->name('show');

        Route::post('/{event:slug}/register', [EventPublicController::class, 'register'])
            ->name('register');
    });

