<?php

use Illuminate\Support\Facades\Route;
use Pitbphp\Security\Http\Controllers\MfaController;
use Pitbphp\Security\Http\Controllers\PasswordController;
use Pitbphp\Security\Support\SecurityRoutes;

Route::prefix(SecurityRoutes::path())
    ->name(SecurityRoutes::name(''))
    ->middleware(['web', 'auth'])
    ->group(function () {
        Route::get('password/expired', [PasswordController::class, 'expired'])->name('password.expired');
        Route::get('password/update', [PasswordController::class, 'showUpdateForm'])->name('password.update');
        Route::post('password/update', [PasswordController::class, 'update'])->name('password.update.submit');

        Route::get('mfa/verify', [MfaController::class, 'show'])->name('mfa.verify');
        Route::post('mfa/verify', [MfaController::class, 'verify'])->name('mfa.verify.submit');
        Route::post('mfa/resend', [MfaController::class, 'resend'])->name('mfa.resend');
    });
