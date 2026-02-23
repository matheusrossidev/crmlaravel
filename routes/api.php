<?php

use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\PipelineController;
use App\Http\Controllers\InstagramWebhookController;
use App\Http\Controllers\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

// ── Webhook WAHA (público, sem autenticação) ──────────────────────────────
Route::post('/webhook/waha', [WhatsappWebhookController::class, 'handle'])
    ->name('waha.webhook');

// ── Webhook Instagram / Meta (público, sem autenticação) ──────────────────
Route::get ('/webhook/instagram', [InstagramWebhookController::class, 'verify'])->name('instagram.webhook.verify');
Route::post('/webhook/instagram', [InstagramWebhookController::class, 'handle'])->name('instagram.webhook.handle');

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1/*
|--------------------------------------------------------------------------
|
| Autenticadas via X-API-Key header (ApiKeyMiddleware)
| Exemplo: X-API-Key: crm_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
|
*/

Route::prefix('v1')->middleware(['api_key'])->group(function () {

    // ── Leads ──────────────────────────────────────────────────────────────
    Route::post  ('leads',              [LeadController::class, 'store']);
    Route::get   ('leads/{lead}',       [LeadController::class, 'show']);
    Route::put   ('leads/{lead}/stage', [LeadController::class, 'stage']);
    Route::put   ('leads/{lead}/won',   [LeadController::class, 'won']);
    Route::put   ('leads/{lead}/lost',  [LeadController::class, 'lost']);
    Route::delete('leads/{lead}',       [LeadController::class, 'destroy']);

    // ── Pipelines ──────────────────────────────────────────────────────────
    Route::get('pipelines', [PipelineController::class, 'index']);
});
