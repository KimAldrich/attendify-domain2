<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminUserController as AdminUserController;
use App\Http\Controllers\Admin\SystemManagementController;
use App\Http\Controllers\Admin\RoleApplicationsController;
use App\Http\Controllers\Admin\StudentManagementController;

Route::middleware(['auth', 'fresh.session','verified','can:manage users'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])
            ->name('users.index');
    });

Route::middleware(['auth', 'fresh.session','verified','can:manage system'])
    ->prefix('admin')->as('admin.')
    ->group(function () {
        Route::get('/system-management', [SystemManagementController::class, 'index'])
            ->name('system-management');
    });

Route::middleware(['auth', 'fresh.session', 'verified', 'can:review role applications'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('/role-applications', [RoleApplicationsController::class, 'index'])
            ->name('role-applications.index');

        Route::post('/role-applications/{roleRequest}/approve', [RoleApplicationsController::class, 'approve'])
            ->name('role-applications.approve');

        Route::post('/role-applications/{roleRequest}/reject', [RoleApplicationsController::class, 'reject'])
            ->name('role-applications.reject');
    });

Route::middleware(['auth', 'fresh.session', 'verified', 'can:manage users'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])
            ->name('users.index');

        // NEW: Student management page
        Route::get('/students', [StudentManagementController::class, 'index'])
            ->name('students.manage');
    });