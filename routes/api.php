<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ConversationController;

Route::post('/webhooks/messages', [WebhookController::class, 'receive']);

Route::get('/conversations', [ConversationController::class, 'index']);
Route::get('/conversations/{id}', [ConversationController::class, 'show']);
Route::post('/conversations/{id}/reply', [ConversationController::class, 'reply'])->middleware(\App\Http\Middleware\RequireApiToken::class);

// UI-friendly reply endpoint (server-side uses env token internally; no client token required)
Route::post('/conversations/{id}/reply-ui', [ConversationController::class, 'replyFromUi']);

// Delete a customer and all related conversations/messages
Route::delete('/customers/{id}', [ConversationController::class, 'deleteCustomer']);
