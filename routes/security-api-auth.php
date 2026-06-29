<?php

use Illuminate\Support\Facades\Route;
use Pitbphp\Security\Http\Controllers\Api\Auth\LoginController;
use Pitbphp\Security\Http\Controllers\Api\Auth\LogoutController;
use Pitbphp\Security\Http\Controllers\Api\Auth\RegisterController;
use Pitbphp\Security\Support\SecurityRoutes;
use Pitbphp\Security\Support\SecurityTier;

if (! config('security.auth.enabled', true) || ! config('security.api.auth.enabled', true)) {
    return;
}

$guard = config('security.api.guard', 'sanctum');
$middleware = array_filter([
    config('security.api.middleware_group', 'api'),
]);

Route::prefix(SecurityRoutes::apiAuthPath())
    ->middleware($middleware)
    ->group(function () use ($guard) {
        Route::post('login', [LoginController::class, 'login'])->name(SecurityRoutes::apiName('login'));

        if (SecurityTier::registrationEnabled()) {
            Route::post('register', [RegisterController::class, 'store'])->name(SecurityRoutes::apiName('register'));

            if (SecurityTier::registrationUsesOtp()) {
                Route::post('register/verify', [RegisterController::class, 'verify'])
                    ->name(SecurityRoutes::apiName('register.verify'));
                Route::post('register/resend', [RegisterController::class, 'resend'])
                    ->name(SecurityRoutes::apiName('register.resend'));
            }
        }

        Route::post('logout', [LogoutController::class, 'logout'])
            ->middleware("auth:{$guard}")
            ->name(SecurityRoutes::apiName('logout'));
    });
