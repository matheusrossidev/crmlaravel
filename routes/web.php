<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Master\DashboardController as MasterDashboardController;
use App\Http\Controllers\Master\LogController as MasterLogController;
use App\Http\Controllers\Master\NotificationController as MasterNotificationController;
use App\Http\Controllers\Master\PlanController as MasterPlanController;
use App\Http\Controllers\Master\SystemController as MasterSystemController;
use App\Http\Controllers\Master\TenantController as MasterTenantController;
use App\Http\Controllers\Master\UsageController as MasterUsageController;
use App\Http\Controllers\Master\UserController as MasterUserController;
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
use App\Http\Controllers\Tenant\AiIntentSignalController;
use App\Http\Controllers\Tenant\ChatbotFlowController;
use App\Http\Controllers\Tenant\WhatsappController;
use App\Http\Controllers\Tenant\WhatsappMessageController;
use App\Http\Controllers\Tenant\WhatsappTagController;
use App\Http\Controllers\Tenant\AiAnalystController;
use App\Http\Controllers\Tenant\InstagramAutomationController;
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

// Páginas de bloqueio de conta (auth required, tenant NOT required)
Route::middleware('auth')->group(function () {
    Route::view('/conta/suspensa', 'tenant.account.suspended')->name('account.suspended');
    Route::view('/conta/trial-expirado', 'tenant.account.trial-expired')->name('trial.expired');
});

// Páginas públicas (sem autenticação)
Route::view('/politica-de-privacidade', 'public.privacy')->name('privacy');
Route::view('/termos-de-uso', 'public.terms')->name('terms');

/*
|--------------------------------------------------------------------------
| Rotas do Painel do Tenant
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/inicio', [DashboardController::class, 'index'])->name('inicio');
    Route::post('/dashboard/config', [DashboardController::class, 'saveConfig'])->name('dashboard.config');

    // Redireciona /configuracoes para perfil
    Route::get('/configuracoes', fn () => redirect()->route('settings.profile'));

    // CRM Kanban
    Route::get('/crm', [KanbanController::class, 'index'])->name('crm.kanban');
    Route::get('/crm/poll', [KanbanController::class, 'poll'])->name('crm.poll');
    Route::get('/crm/exportar', [KanbanController::class, 'export'])->name('crm.export');
    Route::post('/crm/importar/preview', [KanbanController::class, 'preview'])->name('crm.import.preview');
    Route::post('/crm/importar', [KanbanController::class, 'import'])->name('crm.import');
    Route::get('/crm/template', [KanbanController::class, 'template'])->name('crm.template');
    Route::post('/crm/lead/{lead}/stage', [KanbanController::class, 'updateStage'])->name('crm.lead.stage');

    // Leads / Contatos — exportar e importar ANTES do {lead} wildcard
    Route::get('/contatos/exportar', [LeadController::class, 'export'])->name('leads.export');
    Route::post('/contatos/importar', [LeadController::class, 'import'])->name('leads.import');
    Route::post('/contatos/custom-fields/upload', [LeadController::class, 'uploadCustomFieldFile'])->name('leads.cf-upload');

    Route::get('/contatos', [LeadController::class, 'index'])->name('leads.index');
    Route::post('/contatos', [LeadController::class, 'store'])->name('leads.store');
    Route::get('/contatos/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::put('/contatos/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::delete('/contatos/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

    // Lead notes
    Route::post('/contatos/{lead}/notas', [LeadController::class, 'addNote'])->name('leads.notes.store');
    Route::delete('/contatos/{lead}/notas/{note}', [LeadController::class, 'deleteNote'])->name('leads.notes.destroy');

    // Lead profile page
    Route::get('/contatos/{lead}/perfil', [LeadController::class, 'showPage'])->name('leads.profile');

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
        Route::post('whatsapp/import',  [IntegrationController::class, 'importHistoryWhatsapp'])->name('whatsapp.import');
        Route::delete('whatsapp',       [IntegrationController::class, 'disconnectWhatsapp'])->name('whatsapp.disconnect');
        // Instagram OAuth
        Route::get('instagram/redirect', [IntegrationController::class, 'redirectInstagram'])->name('instagram.redirect');
        Route::get('instagram/callback', [IntegrationController::class, 'callbackInstagram'])->name('instagram.callback');
        Route::delete('instagram',        [IntegrationController::class, 'disconnectInstagram'])->name('instagram.disconnect');
        // Wildcards (OAuth) — após as rotas específicas
        Route::delete('{platform}',     [IntegrationController::class, 'disconnect'])->name('disconnect');
        Route::post('{platform}/sync',  [IntegrationController::class, 'syncNow'])->name('sync');
    });

    // Chat Inbox (WhatsApp e outros canais)
    Route::prefix('chats')->name('chats.')->group(function () {
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
        Route::put('/conversations/{conversation}/ai-agent',      [WhatsappController::class, 'assignAiAgent'])->name('conversations.ai-agent');
        Route::put('/conversations/{conversation}/chatbot-flow',   [WhatsappController::class, 'assignChatbotFlow'])->name('conversations.chatbot-flow');
        Route::delete('/conversations/{conversation}',             [WhatsappController::class, 'destroy'])->name('conversations.destroy');
        // Instagram conversations
        Route::get ('/instagram-conversations/{conversation}',         [WhatsappController::class, 'showInstagram'])->name('ig-conversations.show');
        Route::post('/instagram-conversations/{conversation}/read',    [WhatsappController::class, 'markReadInstagram'])->name('ig-conversations.read');
        Route::post  ('/instagram-conversations/{conversation}/messages',[WhatsappController::class, 'sendInstagramMessage'])->name('ig-conversations.messages');
        Route::delete('/instagram-conversations/{conversation}',         [WhatsappController::class, 'destroyInstagram'])->name('ig-conversations.destroy');

        // AI Analyst — sugestões por conversa
        Route::get ('{conversation}/analyst-suggestions',             [AiAnalystController::class, 'index'])->name('analyst.index');
        Route::post('{conversation}/analyst-suggestions/approve-all', [AiAnalystController::class, 'approveAll'])->name('analyst.approve-all');
        Route::post('{conversation}/analyze',                         [AiAnalystController::class, 'trigger'])->name('analyst.trigger');
    });

    // AI Analyst — ações globais por sugestão
    Route::post('/analyst-suggestions/{suggestion}/approve', [AiAnalystController::class, 'approve'])->name('analyst.approve');
    Route::post('/analyst-suggestions/{suggestion}/reject',  [AiAnalystController::class, 'reject'])->name('analyst.reject');
    Route::get ('/analyst-suggestions/pending-count',        [AiAnalystController::class, 'pendingCount'])->name('analyst.pending-count');

    // Chatbot Builder
    Route::prefix('chatbot/fluxos')->name('chatbot.flows.')->group(function () {
        Route::get('pipelines',      [ChatbotFlowController::class, 'getPipelines'])->name('pipelines');
        Route::get('',               [ChatbotFlowController::class, 'index'])->name('index');
        Route::get('criar',          [ChatbotFlowController::class, 'create'])->name('create');
        Route::post('',              [ChatbotFlowController::class, 'store'])->name('store');
        Route::get('{flow}/editar',  [ChatbotFlowController::class, 'edit'])->name('edit');
        Route::put('{flow}',         [ChatbotFlowController::class, 'update'])->name('update');
        Route::delete('{flow}',      [ChatbotFlowController::class, 'destroy'])->name('destroy');
        Route::post('upload-image',  [ChatbotFlowController::class, 'uploadImage'])->name('upload-image');
        Route::post('{flow}/toggle',    [ChatbotFlowController::class, 'toggle'])->name('toggle');
        Route::put('{flow}/graph',      [ChatbotFlowController::class, 'saveGraph'])->name('graph');
        Route::post('{flow}/test-step', [ChatbotFlowController::class, 'testStep'])->name('test-step');
    });

    // Automações de Instagram
    Route::prefix('configuracoes/instagram-automacoes')->name('settings.ig-automations.')->group(function () {
        Route::get('posts',                 [InstagramAutomationController::class, 'posts'])->name('posts');
        Route::get('',                      [InstagramAutomationController::class, 'index'])->name('index');
        Route::post('',                     [InstagramAutomationController::class, 'store'])->name('store');
        Route::put('{automation}',          [InstagramAutomationController::class, 'update'])->name('update');
        Route::delete('{automation}',       [InstagramAutomationController::class, 'destroy'])->name('destroy');
        Route::patch('{automation}/toggle', [InstagramAutomationController::class, 'toggleActive'])->name('toggle');
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
        Route::post('perfil/avatar',         [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::post('workspace/logo',        [ProfileController::class, 'uploadWorkspaceLogo'])->name('workspace.logo');

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

        // Tags
        Route::get('tags',              [WhatsappTagController::class, 'index'])->name('tags');
        Route::post('tags',             [WhatsappTagController::class, 'store'])->name('tags.store');
        Route::put('tags/{tag}',        [WhatsappTagController::class, 'update'])->name('tags.update');
        Route::delete('tags/{tag}',     [WhatsappTagController::class, 'destroy'])->name('tags.destroy');
    });

    // Sinais de intenção do Agente IA
    Route::prefix('ia/sinais')->name('ai.intent-signals.')->group(function () {
        Route::get('',                   [AiIntentSignalController::class, 'list'])->name('list');
        Route::post('{signal}/lida',     [AiIntentSignalController::class, 'markRead'])->name('read');
        Route::post('marcar-todas',      [AiIntentSignalController::class, 'markAllRead'])->name('read-all');
        Route::get('nao-lidas/contagem', [AiIntentSignalController::class, 'unreadCount'])->name('unread-count');
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
        Route::post('{agent}/knowledge-files',           [AiAgentController::class, 'uploadKnowledgeFile'])->name('knowledge-files.store');
        Route::delete('{agent}/knowledge-files/{file}',  [AiAgentController::class, 'deleteKnowledgeFile'])->name('knowledge-files.destroy');
    });
});

// ── Master (super_admin only) ──────────────────────────────────────────────────
Route::middleware(['auth', 'super_admin'])->prefix('master')->name('master.')->group(function () {

    // Dashboard
    Route::get('',                                     [MasterDashboardController::class, 'index'])->name('dashboard');

    // Empresas (tenants)
    Route::get('empresas',             [MasterTenantController::class, 'index'])->name('tenants');
    Route::post('empresas',            [MasterTenantController::class, 'store'])->name('tenants.store');
    Route::get('empresas/{tenant}',    [MasterTenantController::class, 'show'])->name('tenants.show');
    Route::put('empresas/{tenant}',    [MasterTenantController::class, 'update'])->name('tenants.update');
    Route::delete('empresas/{tenant}', [MasterTenantController::class, 'destroy'])->name('tenants.destroy');

    // Usuários por tenant
    Route::post('empresas/{tenant}/usuarios',          [MasterUserController::class, 'store'])->name('tenants.users.store');
    Route::put('empresas/{tenant}/usuarios/{user}',    [MasterUserController::class, 'update'])->name('tenants.users.update');
    Route::delete('empresas/{tenant}/usuarios/{user}', [MasterUserController::class, 'destroy'])->name('tenants.users.destroy');

    // Planos
    Route::get('planos',                               [MasterPlanController::class, 'index'])->name('plans');
    Route::post('planos',                              [MasterPlanController::class, 'store'])->name('plans.store');
    Route::put('planos/{plan}',                        [MasterPlanController::class, 'update'])->name('plans.update');
    Route::delete('planos/{plan}',                     [MasterPlanController::class, 'destroy'])->name('plans.destroy');

    // Uso de tokens IA
    Route::get('uso',                                  [MasterUsageController::class, 'index'])->name('usage');
    Route::get('uso/{tenant}',                         [MasterUsageController::class, 'show'])->name('usage.show');

    // Logs
    Route::get('logs',                                 [MasterLogController::class, 'index'])->name('logs');
    Route::get('logs/content',                         [MasterLogController::class, 'content'])->name('logs.content');

    // Sistema
    Route::get('sistema',                              [MasterSystemController::class, 'index'])->name('system');
    Route::get('sistema/stats',                        [MasterSystemController::class, 'stats'])->name('system.stats');

    // Notificações
    Route::get('notificacoes',                         [MasterNotificationController::class, 'index'])->name('notifications');
    Route::post('notificacoes',                        [MasterNotificationController::class, 'store'])->name('notifications.store');

});
// Configuração LLM (provider/api_key/model) via ENV: LLM_PROVIDER, LLM_API_KEY, LLM_MODEL

// ── Webhook público WAHA (sem autenticação) ───────────────────────────────────
Route::post('/webhook/whatsapp', [WhatsappWebhookController::class, 'handle'])
    ->name('whatsapp.webhook')
    ->withoutMiddleware(['web']);
