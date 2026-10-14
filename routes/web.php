<?php

declare(strict_types=1);

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ConflictLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\UserApprovalController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->middleware('throttle:10,1')->name('auth.google.callback');
});

Route::middleware(['auth', 'approved'])->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/program', [ScheduleController::class, 'index'])->name('schedule.index');

    Route::middleware('can:manage-schedule')->group(function (): void {
        Route::post('/program', [ScheduleController::class, 'store'])->name('schedule.store');
        Route::delete('/program/{kayit}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');

        Route::get('/atamalar', [AssignmentController::class, 'index'])->name('assignments.index');
        Route::post('/atamalar', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::delete('/atamalar/{atama}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

        Route::get('/cakismalar', [ConflictLogController::class, 'index'])->name('conflicts.index');
    });

    Route::middleware('can:manage-users')->group(function (): void {
        Route::get('/kullanici-onaylari', [UserApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/kullanici-onaylari/{user}/onayla', [UserApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/kullanici-onaylari/{user}/reddet', [UserApprovalController::class, 'reject'])->name('approvals.reject');
    });
});
