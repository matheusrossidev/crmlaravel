<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Master\TenantController as MasterTenantController;
use App\Http\Controllers\Tenant\ApiKeyController;
use App\Http\Controllers\Tenant\CampaignController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\IntegrationController;
use App\Http\Controllers\Tenant\KanbanController;
use App\Http\Controllers\Tenant\LeadController;
use App\Http\Controllers\Tenant\LostSaleReasonController;
use App\Http\Controllers\Tenant\PipelineController;
use App\Http\Controllers\Tenant\CustomFieldController;
use App\Http\Controllers\Tenant\ProfileController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\UserController;
use App\Http\Controllers\Tenant\AiAgentController;
use App\Http\Controllers\Tenant\AiConfigurationController;
use App\Http\Controllers\Tenant\WhatsappController;
use App\Http\Controllers\Tenant\WhatsappMessageController;
use App\Http\Controllers\Tenant\WhatsappTagController;
use App\Http\Controllers\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas de Autenticação
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Rotas do Painel do Tenant
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/inicio', [DashboardController::class, 'index'])->name('inicio');

    // Redireciona /configuracoes para perfil
    Route::get('/configuracoes', fn () => redirect()->route('settings.profile'));

    // CRM Kanban
    Route::get('/crm', [KanbanController::class, 'index'])->name('crm.kanban');
    Route::get('/crm/poll', [KanbanController::class, 'poll'])->name('crm.poll');
    Route::post('/crm/lead/{lead}/stage', [KanbanController::class, 'updateStage'])->name('crm.lead.stage');

    // Leads / Contatos — exportar e importar ANTES do {lead} wildcard
    Route::get('/contatos/exportar', [LeadController::class, 'export'])->name('leads.export');
    Route::post('/contatos/importar', [LeadController::class, 'import'])->name('leads.import');

    Route::get('/contatos', [LeadController::class, 'index'])->name('leads.index');
    Route::post('/contatos', [LeadController::class, 'store'])->name('leads.store');
    Route::get('/contatos/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::put('/contatos/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::delete('/contatos/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

    // Relatórios
    Route::get('/relatorios', [ReportController::class, 'index'])->name('reports.index');

    // Campanhas (read-only)
    Route::get('/campanhas', [CampaignController::class, 'index'])->name('campaigns.index');

    // Integrações
    Route::prefix('configuracoes/integracoes')->name('settings.integrations.')->group(function () {
        Route::get('/',                 [IntegrationController::class, 'index'])->name('index');
        Route::get('facebook/redirect', [IntegrationController::class, 'redirectFacebook'])->name('facebook.redirect');
        Route::get('facebook/callback', [IntegrationController::class, 'callbackFacebook'])->name('facebook.callback');
        Route::get('google/redirect',   [IntegrationController::class, 'redirectGoogle'])->name('google.redirect');
        Route::get('google/callback',   [IntegrationController::class, 'callbackGoogle'])->name('google.callback');
        // WhatsApp — rotas específicas ANTES dos wildcards
        Route::post('whatsapp/connect', [IntegrationController::class, 'connectWhatsapp'])->name('whatsapp.connect');
        Route::get('whatsapp/qr',       [IntegrationController::class, 'getWhatsappQr'])->name('whatsapp.qr');
        Route::delete('whatsapp',       [IntegrationController::class, 'disconnectWhatsapp'])->name('whatsapp.disconnect');
        // Wildcards (OAuth) — após as rotas específicas
        Route::delete('{platform}',     [IntegrationController::class, 'disconnect'])->name('disconnect');
        Route::post('{platform}/sync',  [IntegrationController::class, 'syncNow'])->name('sync');
    });

    // WhatsApp Inbox
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::get('/',                                       [WhatsappController::class, 'index'])->name('index');
        Route::get('/poll',                                   [WhatsappController::class, 'poll'])->name('poll');
        Route::get('/conversations/{conversation}',           [WhatsappController::class, 'show'])->name('conversations.show');
        Route::post('/conversations/{conversation}/read',     [WhatsappController::class, 'markRead'])->name('conversations.read');
        Route::put('/conversations/{conversation}/assign',    [WhatsappController::class, 'assign'])->name('conversations.assign');
        Route::put('/conversations/{conversation}/status',    [WhatsappController::class, 'updateStatus'])->name('conversations.status');
        Route::put('/conversations/{conversation}/lead',      [WhatsappController::class, 'updateLead'])->name('conversations.lead');
        Route::put('/conversations/{conversation}/contact',   [WhatsappController::class, 'updateContact'])->name('conversations.contact');
        Route::post('/conversations/{conversation}/messages', [WhatsappMessageController::class, 'store'])->name('messages.store');
        Route::post('/conversations/{conversation}/react',    [WhatsappMessageController::class, 'react'])->name('messages.react');
        Route::delete('/conversations/{conversation}',        [WhatsappController::class, 'destroy'])->name('conversations.destroy');
    });

    // Configurações — Pipelines + Stages
    Route::prefix('configuracoes')->name('settings.')->group(function () {
        Route::get('pipelines', [PipelineController::class, 'index'])->name('pipelines');
        Route::post('pipelines', [PipelineController::class, 'store'])->name('pipelines.store');
        Route::put('pipelines/{pipeline}', [PipelineController::class, 'update'])->name('pipelines.update');
        Route::delete('pipelines/{pipeline}', [PipelineController::class, 'destroy'])->name('pipelines.destroy');
        Route::post('pipelines/{pipeline}/stages/reorder', [PipelineController::class, 'reorderStages'])->name('pipelines.stages.reorder');
        Route::post('pipelines/{pipeline}/stages', [PipelineController::class, 'storeStage'])->name('pipelines.stages.store');
        Route::put('pipelines/{pipeline}/stages/{stage}', [PipelineController::class, 'updateStage'])->name('pipelines.stages.update');
        Route::delete('pipelines/{pipeline}/stages/{stage}', [PipelineController::class, 'destroyStage'])->name('pipelines.stages.destroy');

        // Motivos de Perda
        Route::get('motivos-perda', [LostSaleReasonController::class, 'index'])->name('lost-reasons');
        Route::post('motivos-perda', [LostSaleReasonController::class, 'store'])->name('lost-reasons.store');
        Route::put('motivos-perda/{reason}', [LostSaleReasonController::class, 'update'])->name('lost-reasons.update');
        Route::delete('motivos-perda/{reason}', [LostSaleReasonController::class, 'destroy'])->name('lost-reasons.destroy');

        // API Keys
        Route::get('api-keys',              [ApiKeyController::class, 'index'])->name('api-keys');
        Route::post('api-keys',             [ApiKeyController::class, 'store'])->name('api-keys.store');
        Route::delete('api-keys/{apiKey}',  [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

        // Perfil
        Route::get('perfil',         [ProfileController::class, 'index'])->name('profile');
        Route::put('perfil',         [ProfileController::class, 'update'])->name('profile.update');
        Route::put('perfil/senha',   [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('perfil/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');

        // Usuários (admin+)
        Route::get('usuarios',            [UserController::class, 'index'])->name('users');
        Route::post('usuarios',           [UserController::class, 'store'])->name('users.store');
        Route::put('usuarios/{user}',     [UserController::class, 'update'])->name('users.update');
        Route::delete('usuarios/{user}',  [UserController::class, 'destroy'])->name('users.destroy');

        // Campos Personalizados (admin+)
        Route::get('campos-extras',               [CustomFieldController::class, 'index'])->name('custom-fields');
        Route::post('campos-extras',              [CustomFieldController::class, 'store'])->name('custom-fields.store');
        Route::put('campos-extras/{field}',       [CustomFieldController::class, 'update'])->name('custom-fields.update');
        Route::delete('campos-extras/{field}',    [CustomFieldController::class, 'destroy'])->name('custom-fields.destroy');

        // Tags de WhatsApp
        Route::get('whatsapp-tags',              [WhatsappTagController::class, 'index'])->name('whatsapp-tags');
        Route::post('whatsapp-tags',             [WhatsappTagController::class, 'store'])->name('whatsapp-tags.store');
        Route::put('whatsapp-tags/{tag}',        [WhatsappTagController::class, 'update'])->name('whatsapp-tags.update');
        Route::delete('whatsapp-tags/{tag}',     [WhatsappTagController::class, 'destroy'])->name('whatsapp-tags.destroy');

        // Configuração de IA
        Route::get('ia',     [AiConfigurationController::class, 'show'])->name('ai.config');
        Route::put('ia',     [AiConfigurationController::class, 'update'])->name('ai.config.update');
        Route::post('ia/test', [AiConfigurationController::class, 'testConnection'])->name('ai.test');
    });

    // Agentes de IA
    Route::prefix('ia/agentes')->name('ai.agents.')->group(function () {
        Route::get('',                           [AiAgentController::class, 'index'])->name('index');
        Route::get('criar',                      [AiAgentController::class, 'create'])->name('create');
        Route::post('',                          [AiAgentController::class, 'store'])->name('store');
        Route::get('{agent}/editar',             [AiAgentController::class, 'edit'])->name('edit');
        Route::put('{agent}',                    [AiAgentController::class, 'update'])->name('update');
        Route::delete('{agent}',                 [AiAgentController::class, 'destroy'])->name('destroy');
        Route::post('{agent}/toggle',            [AiAgentController::class, 'toggleActive'])->name('toggle');
        Route::post('{agent}/test-chat',         [AiAgentController::class, 'testChat'])->name('test-chat');
    });
});

// ── Master (super_admin only) ──────────────────────────────────────────────────
Route::middleware(['auth', 'super_admin'])->prefix('master')->name('master.')->group(function () {
    Route::get('empresas',             [MasterTenantController::class, 'index'])->name('tenants');
    Route::post('empresas',            [MasterTenantController::class, 'store'])->name('tenants.store');
    Route::get('empresas/{tenant}',    [MasterTenantController::class, 'show'])->name('tenants.show');
    Route::put('empresas/{tenant}',    [MasterTenantController::class, 'update'])->name('tenants.update');
    Route::delete('empresas/{tenant}', [MasterTenantController::class, 'destroy'])->name('tenants.destroy');
});

// ── Webhook público WAHA (sem autenticação) ───────────────────────────────────
Route::post('/webhook/whatsapp', [WhatsappWebhookController::class, 'handle'])
    ->name('whatsapp.webhook')
    ->withoutMiddleware(['web']);
