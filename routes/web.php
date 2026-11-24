<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UnlockController;
use Illuminate\Support\Facades\Route;

// The kitchen screen. Module routes are added by App\Dashboard\DashboardServiceProvider.
Route::get('/', DashboardController::class)->name('dashboard');

// PIN page for visitors outside the home (App\Http\Middleware\RestrictTunnelVisitors).
Route::get('/unlock', [UnlockController::class, 'show'])->name('unlock');
Route::post('/unlock', [UnlockController::class, 'store'])->middleware('throttle:unlock');
