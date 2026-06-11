<?php

use Illuminate\Support\Facades\Route;
use Pitbphp\Security\Http\Controllers\Api\MfaController as ApiMfaController;
use Pitbphp\Security\Http\Controllers\Api\PasswordController as ApiPasswordController;
use Pitbphp\Security\Support\SecurityRoutes;

$guard = config('security.api.guard', 'sanctum');
$middleware = array_filter([
    config('security.api.middleware_group', 'api'),
    "auth:{$guard}",
]);

Route::prefix(SecurityRoutes::apiPath())
    ->name(SecurityRoutes::apiName(''))
    ->middleware($middleware)
    ->group(function () {
        Route::get('password/status', [ApiPasswordController::class, 'status'])->name('password.status');
        Route::post('password/update', [ApiPasswordController::class, 'update'])->name('password.update');

        Route::get('mfa/status', [ApiMfaController::class, 'status'])->name('mfa.status');
        Route::post('mfa/verify', [ApiMfaController::class, 'verify'])->name('mfa.verify');
        Route::post('mfa/resend', [ApiMfaController::class, 'resend'])->name('mfa.resend');
    });
