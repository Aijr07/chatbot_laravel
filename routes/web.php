<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatbotController;

Route::get('/', [ChatbotController::class, 'index'])->name('chatbot.index');
Route::post('/chat/message', [ChatbotController::class, 'handleMessage'])->name('chatbot.message');