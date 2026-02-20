<?php

use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\PipelineController;
use Illuminate\Support\Facades\Route;

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
