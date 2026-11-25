<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiagnosticsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UnlockController;
use App\Http\Middleware\RequireSettingsPin;
use Illuminate\Support\Facades\Route;

// The kitchen screen. Module routes are added by App\Dashboard\DashboardServiceProvider.
Route::get('/', DashboardController::class)->name('dashboard');

// PIN page for visitors outside the home (App\Http\Middleware\RestrictTunnelVisitors).
Route::get('/unlock', [UnlockController::class, 'show'])->name('unlock');
Route::post('/unlock', [UnlockController::class, 'store'])->middleware('throttle:unlock');

// The dashboard reports why it unloaded, to track down unexpected reloads.
Route::post('/diagnostics/unload', [DiagnosticsController::class, 'unload'])->middleware('throttle:20,1')->name('diagnostics.unload');

// Settings (opened from the side menu's ⚙ in a sheet): behind the PIN, 10 minutes per unlock.
Route::get('/settings', [SettingsController::class, 'index'])->middleware(RequireSettingsPin::class)->name('settings');
Route::get('/settings/unlock', [SettingsController::class, 'showUnlock'])->name('settings.unlock');
Route::post('/settings/unlock', [SettingsController::class, 'unlock'])->middleware('throttle:20,1')->name('settings.unlock.store');
Route::post('/settings/lock', [SettingsController::class, 'lock'])->name('settings.lock');
