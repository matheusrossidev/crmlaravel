<?php

use App\Http\Controllers\Api\AgnoToolsController;
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

// ── Webhook WhatsApp Cloud API (público, sem autenticação) ────────────────
Route::get ('/webhook/whatsapp-cloud', [\App\Http\Controllers\WhatsappCloudWebhookController::class, 'verify'])->name('whatsapp-cloud.webhook.verify');
Route::post('/webhook/whatsapp-cloud', [\App\Http\Controllers\WhatsappCloudWebhookController::class, 'handle'])
    ->middleware('throttle:webhooks')
    ->name('whatsapp-cloud.webhook.handle');

// ── Webhook Instagram / Meta (público, sem autenticação) ──────────────────
Route::get ('/webhook/instagram', [InstagramWebhookController::class, 'verify'])->name('instagram.webhook.verify');
Route::post('/webhook/instagram', [InstagramWebhookController::class, 'handle'])->middleware('throttle:webhooks')->name('instagram.webhook.handle');

// Facebook Lead Ads webhook
Route::get ('/webhook/facebook/leadgen', [\App\Http\Controllers\FacebookLeadgenWebhookController::class, 'verify'])->name('facebook.leadgen.webhook.verify');
Route::post('/webhook/facebook/leadgen', [\App\Http\Controllers\FacebookLeadgenWebhookController::class, 'handle'])->middleware('throttle:webhooks')->name('facebook.leadgen.webhook.handle');

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
});
