<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\HistoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Chat API routes
Route::post('/chat/send', [ChatController::class, 'sendMessage']);
Route::get('/chat/history', [ChatController::class, 'getHistory']);
Route::get('/chat/conversation/{id}', [ChatController::class, 'getConversation']);

// Settings API routes
Route::post('/settings/webhook', [SettingsController::class, 'updateWebhook']);
Route::post('/settings/test-webhook', [SettingsController::class, 'testWebhook']);
Route::get('/settings/test-webhook', [SettingsController::class, 'testWebhookGet']);

// History API routes
Route::get('/history/conversations', [HistoryController::class, 'getConversations']);
Route::delete('/history/conversation/{id}', [HistoryController::class, 'deleteConversation']);
