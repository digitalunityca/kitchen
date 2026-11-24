<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// The kitchen screen. Module routes are added by App\Dashboard\DashboardServiceProvider.
Route::get('/', DashboardController::class)->name('dashboard');
