<?php

use App\Http\Controllers\Auth\OtpLoginController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::post('login', [OtpLoginController::class, 'login'])->name('login.store');
Route::get('otp', [OtpLoginController::class, 'showOtpForm'])->name('otp.form');
Route::post('otp', [OtpLoginController::class, 'verifyOtp'])->name('otp.verify');

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
