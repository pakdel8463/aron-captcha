<?php

use Illuminate\Support\Facades\Route;
use Aron\Captcha\CaptchaController;

Route::middleware(['web'])->prefix('aron-captcha')->name('aron-captcha.')->group(function () {
    Route::get('/refresh', [CaptchaController::class, 'refresh'])->name('refresh');
});
