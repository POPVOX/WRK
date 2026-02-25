<?php

use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('login', 'pages.auth.login')
        ->name('login');

    Route::get('auth/google/redirect', [GoogleLoginController::class, 'redirect'])
        ->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleLoginController::class, 'callback'])
        ->name('auth.google.callback');

    // Google-only auth: keep legacy routes but redirect to login with guidance.
    Route::get('register', fn () => redirect()->route('login')->with('status', 'Use Google sign-in to access WRK.'))
        ->name('register');
    Route::get('forgot-password', fn () => redirect()->route('login')->with('status', 'Password reset is unavailable. Use Google sign-in.'))
        ->name('password.request');
    Route::get('reset-password/{token}', fn () => redirect()->route('login')->with('status', 'Password reset is unavailable. Use Google sign-in.'))
        ->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');

    Route::post('logout', function (Logout $logout) {
        $logout();

        return redirect('/');
    })->name('logout');
});
