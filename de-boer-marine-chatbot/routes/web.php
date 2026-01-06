<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\HistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('chat.index');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Chat routes
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::post('/settings/webhook', [SettingsController::class, 'updateWebhook'])->name('settings.webhook.update');
Route::get('/settings/test-webhook', [SettingsController::class, 'testWebhookGet'])->name('settings.webhook.test.get');
Route::get('/history', [HistoryController::class, 'index'])->name('history.index');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
