<?php

use Illuminate\Support\Facades\Route;
use Pitbphp\Security\Http\Controllers\HomeController;
use Pitbphp\Security\Http\Controllers\MfaController;
use Pitbphp\Security\Http\Controllers\MfaSetupController;
use Pitbphp\Security\Http\Controllers\PasswordController;
use Pitbphp\Security\Support\SecurityRoutes;

Route::prefix(SecurityRoutes::path())
    ->name(SecurityRoutes::name(''))
    ->middleware(['web'])
    ->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');

        Route::get('assets/password-strength.js', function () {
            $path = __DIR__.'/../resources/assets/js/pitb-password-strength.js';

            abort_unless(is_file($path), 404);

            return response()->file($path, [
                'Content-Type' => 'application/javascript; charset=UTF-8',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        })->name('assets.password-strength');

        Route::get('assets/temporary-password.js', function () {
            $path = __DIR__.'/../resources/assets/js/pitb-temporary-password.js';

            abort_unless(is_file($path), 404);

            return response()->file($path, [
                'Content-Type' => 'application/javascript; charset=UTF-8',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        })->name('assets.temporary-password');
    });

Route::prefix(SecurityRoutes::path())
    ->name(SecurityRoutes::name(''))
    ->middleware(['web', 'auth'])
    ->group(function () {
        Route::get('password/expired', [PasswordController::class, 'expired'])->name('password.expired');
        Route::get('password/update', [PasswordController::class, 'showUpdateForm'])->name('password.update');
        Route::post('password/update', [PasswordController::class, 'update'])->name('password.update.submit');

        Route::get('mfa/setup', [MfaSetupController::class, 'show'])->name('mfa.setup');
        Route::post('mfa/setup', [MfaSetupController::class, 'store'])->name('mfa.setup.submit');
        Route::post('mfa/setup/resend', [MfaSetupController::class, 'resend'])->name('mfa.setup.resend');

        Route::get('mfa/verify', [MfaController::class, 'show'])->name('mfa.verify');
        Route::post('mfa/verify', [MfaController::class, 'verify'])->name('mfa.verify.submit');
        Route::post('mfa/resend', [MfaController::class, 'resend'])->name('mfa.resend');
    });
