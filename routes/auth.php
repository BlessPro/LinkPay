<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\PhonePinLoginController;
use App\Http\Controllers\Auth\PhonePinResetController;
use App\Http\Controllers\Auth\PhoneOtpController;
use App\Http\Controllers\Auth\PhoneSignupController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::post('register/phone/send', [PhoneSignupController::class, 'send'])
        ->middleware('phone_auth_enabled')
        ->middleware('throttle:5,1')
        ->name('register.phone.send');

    Route::post('register/phone/complete', [PhoneSignupController::class, 'complete'])
        ->middleware('phone_auth_enabled')
        ->middleware('throttle:10,1')
        ->name('register.phone.complete');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::post('login/phone/pin', [PhonePinLoginController::class, 'store'])
        ->middleware('phone_auth_enabled')
        ->middleware('throttle:10,1')
        ->name('login.phone.pin');

    Route::post('login/phone/pin/reset/send', [PhonePinResetController::class, 'send'])
        ->middleware('phone_auth_enabled')
        ->middleware('throttle:5,1')
        ->name('login.phone.pin.reset.send');

    Route::post('login/phone/pin/reset/complete', [PhonePinResetController::class, 'complete'])
        ->middleware('phone_auth_enabled')
        ->middleware('throttle:10,1')
        ->name('login.phone.pin.reset.complete');

    Route::post('login/phone/send', [PhoneOtpController::class, 'send'])
        ->middleware('phone_auth_enabled')
        ->middleware('throttle:5,1')
        ->name('login.phone.send');

    Route::post('login/phone/verify', [PhoneOtpController::class, 'verify'])
        ->middleware('phone_auth_enabled')
        ->middleware('throttle:10,1')
        ->name('login.phone.verify');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
