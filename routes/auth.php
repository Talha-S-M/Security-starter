<?php

use Illuminate\Support\Facades\Route;
use Pitbphp\Security\Http\Controllers\Auth\ForgotPasswordController;
use Pitbphp\Security\Http\Controllers\Auth\LoginController;
use Pitbphp\Security\Http\Controllers\Auth\LogoutController;
use Pitbphp\Security\Http\Controllers\Auth\RegisterController;
use Pitbphp\Security\Http\Controllers\Auth\ResetPasswordController;

if (! config('security.auth.enabled', true)) {
    return;
}

Route::middleware(['web'])->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'show'])->name('login');
        Route::post('login', [LoginController::class, 'login']);

        if (config('security.auth.register', true)) {
            Route::get('register', [RegisterController::class, 'show'])->name('register');
            Route::post('register', [RegisterController::class, 'store']);
        }

        Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

        Route::get('reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [ResetPasswordController::class, 'store'])->name('password.store');
    });

    Route::post('logout', [LogoutController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');
});
