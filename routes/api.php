<?php

use App\Http\Controllers\Api\AgnoToolsController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\PipelineController;
use App\Http\Controllers\Api\WebsiteWidgetController;
use App\Http\Controllers\AsaasWebhookController;
use App\Http\Controllers\InstagramWebhookController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

// ── Widget Website (público, sem autenticação) ────────────────────────────
Route::prefix('widget')->middleware('throttle:widget')->group(function () {
    Route::get('{token}.js', [WebsiteWidgetController::class, 'script']);
    Route::match(['get', 'post'], '{token}/init', [WebsiteWidgetController::class, 'init']);
    Route::post('{token}/message', [WebsiteWidgetController::class, 'message']);
    Route::get('{token}/wa-button.js', [WebsiteWidgetController::class, 'waButtonScript']);
    Route::post('{token}/wa-click', [WebsiteWidgetController::class, 'trackWaClick']);
});

// ── Webhook Asaas (público, sem autenticação) ─────────────────────────────
Route::post('/webhook/asaas', [AsaasWebhookController::class, 'handle'])
    ->middleware('throttle:webhooks')
    ->name('asaas.webhook');

// ── Webhook Stripe (público, sem autenticação) ───────────────────────────
Route::post('/webhook/stripe', [StripeWebhookController::class, 'handle'])
    ->middleware('throttle:webhooks')
    ->name('stripe.webhook');

// ── Webhook WAHA (público, sem autenticação) ──────────────────────────────
Route::post('/webhook/waha', [WhatsappWebhookController::class, 'handle'])
    ->middleware('throttle:webhooks')
    ->name('waha.webhook');

// ── Webhook Instagram / Meta (público, sem autenticação) ──────────────────
Route::get ('/webhook/instagram', [InstagramWebhookController::class, 'verify'])->name('instagram.webhook.verify');
Route::post('/webhook/instagram', [InstagramWebhookController::class, 'handle'])->middleware('throttle:webhooks')->name('instagram.webhook.handle');

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1/*
|--------------------------------------------------------------------------
|
| Autenticadas via X-API-Key header (ApiKeyMiddleware)
| Exemplo: X-API-Key: crm_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
|
*/

// ── Rotas internas do Agno (X-Agno-Token) ────────────────────────────────
Route::prefix('internal/agno')->middleware(['agno_internal'])->group(function () {
    Route::put ('leads/{leadId}/stage',      [AgnoToolsController::class, 'setStage']);
    Route::post('leads/{leadId}/tags',       [AgnoToolsController::class, 'addTag']);
    Route::post('conversations/{convId}/notify-intent', [AgnoToolsController::class, 'notifyIntent']);
    Route::post('conversations/{convId}/transfer',      [AgnoToolsController::class, 'transferToHuman']);
});

Route::prefix('v1')->middleware(['api_key', 'throttle:api'])->group(function () {

    // ── Leads ──────────────────────────────────────────────────────────────
    Route::post  ('leads',              [LeadController::class, 'store']);
    Route::get   ('leads/{lead}',       [LeadController::class, 'show']);
    Route::put   ('leads/{lead}/stage', [LeadController::class, 'stage']);
    Route::put   ('leads/{lead}/won',   [LeadController::class, 'won']);
    Route::put   ('leads/{lead}/lost',  [LeadController::class, 'lost']);
    Route::delete('leads/{lead}',       [LeadController::class, 'destroy']);

    // ── Pipelines ──────────────────────────────────────────────────────────
    Route::get('pipelines', [PipelineController::class, 'index']);

    // ── Campaigns ──────────────────────────────────────────────────────────
    Route::get   ('campaigns',             [CampaignController::class, 'index']);
    Route::post  ('campaigns',             [CampaignController::class, 'store']);
    Route::put   ('campaigns/{campaign}',  [CampaignController::class, 'update']);
    Route::delete('campaigns/{campaign}',  [CampaignController::class, 'destroy']);
});
