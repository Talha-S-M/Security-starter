<?php

use Illuminate\Support\Facades\Route;
use Pitbphp\Security\Http\Controllers\Admin\AccessRequestController;
use Pitbphp\Security\Http\Controllers\Admin\DashboardController;
use Pitbphp\Security\Http\Controllers\Admin\PermissionController;
use Pitbphp\Security\Http\Controllers\Admin\RoleController;
use Pitbphp\Security\Http\Controllers\Admin\SecurityLogController;
use Pitbphp\Security\Http\Controllers\Admin\UserManagementController;
use Pitbphp\Security\Support\SecurityRoutes;

Route::prefix(SecurityRoutes::adminPath())
    ->name(SecurityRoutes::adminName('partials.'))
    ->middleware(['web', 'auth'])
    ->group(function () {
        Route::get('summary', [DashboardController::class, 'summary'])
            ->middleware('permission:audit-logs.view|users.view|roles.view|permissions.view|admin.panel')
            ->name('summary');

        Route::middleware('permission:audit-logs.view')->group(function () {
            Route::get('security-events', [SecurityLogController::class, 'securityEvents'])->name('security-events');
            Route::get('security-events/{event}', [SecurityLogController::class, 'showSecurityEvent'])->name('security-events.show');
            Route::get('audit-trail', [SecurityLogController::class, 'auditTrail'])->name('audit-trail');
            Route::get('reviews', [SecurityLogController::class, 'reviews'])->name('reviews');
        });

        Route::middleware('permission:users.view')->group(function () {
            Route::get('users', [UserManagementController::class, 'index'])->name('users');
            Route::get('users/create', [UserManagementController::class, 'create'])
                ->middleware('permission:users.create')
                ->name('users.create');
            Route::post('users', [UserManagementController::class, 'store'])
                ->middleware('permission:users.create')
                ->name('users.store');
            Route::get('users/{user}/edit', [UserManagementController::class, 'edit'])
                ->middleware('permission:users.update')
                ->name('users.edit');
            Route::put('users/{user}', [UserManagementController::class, 'update'])
                ->middleware('permission:users.update')
                ->name('users.update');
        });

        Route::middleware('permission:roles.view')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('roles');
            Route::get('roles/{role}/edit', [RoleController::class, 'edit'])
                ->middleware('permission:roles.manage')
                ->name('roles.edit');
            Route::put('roles/{role}', [RoleController::class, 'update'])
                ->middleware('permission:roles.manage')
                ->name('roles.update');
        });

        Route::get('permissions', [PermissionController::class, 'index'])
            ->middleware('permission:permissions.view')
            ->name('permissions');

        Route::middleware('permission:access-requests.view|access-requests.approve')->group(function () {
            Route::get('access-requests', [AccessRequestController::class, 'index'])->name('access-requests');
            Route::get('access-requests/{accessRequest}', [AccessRequestController::class, 'show'])->name('access-requests.show');
            Route::post('access-requests/{accessRequest}/approve', [AccessRequestController::class, 'approve'])
                ->middleware('permission:access-requests.approve')
                ->name('access-requests.approve');
            Route::post('access-requests/{accessRequest}/reject', [AccessRequestController::class, 'reject'])
                ->middleware('permission:access-requests.approve')
                ->name('access-requests.reject');
        });
    });
