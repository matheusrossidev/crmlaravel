<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Master\DashboardController as MasterDashboardController;
use App\Http\Controllers\Master\LogController as MasterLogController;
use App\Http\Controllers\Master\NotificationController as MasterNotificationController;
use App\Http\Controllers\Master\PlanController as MasterPlanController;
use App\Http\Controllers\Master\TokenIncrementPlanController as MasterTokenIncrementPlanController;
use App\Http\Controllers\Master\SystemController as MasterSystemController;
use App\Http\Controllers\Master\TenantController as MasterTenantController;
use App\Http\Controllers\Master\PaymentController as MasterPaymentController;
use App\Http\Controllers\Master\ToolboxController as MasterToolboxController;
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
use App\Http\Controllers\Tenant\ProductController;
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
use App\Http\Controllers\Tenant\QuickMessageController;
use App\Http\Controllers\Tenant\AutomationController;
use App\Http\Controllers\Auth\AgencyRegisterController;
use App\Http\Controllers\Master\PartnerAgencyCodeController as MasterPartnerAgencyCodeController;
use App\Http\Controllers\Tenant\AgencyAccessController;
use App\Http\Controllers\Tenant\BillingController;
use App\Http\Controllers\Tenant\TokenIncrementController;
use App\Http\Controllers\Tenant\CalendarController;
use App\Http\Controllers\Tenant\TaskController;
use App\Http\Controllers\Tenant\OnboardingController;
use App\Http\Controllers\Tenant\MasterNotificationReadController;
use App\Http\Controllers\Tenant\ScheduledMessageController;
use App\Http\Controllers\Tenant\InstagramAutomationController;
use App\Http\Controllers\Tenant\DepartmentController;
use App\Http\Controllers\Tenant\HelpChatController;
use App\Http\Controllers\Tenant\LeadScoringController;
use App\Http\Controllers\Tenant\NurtureSequenceController;
use App\Http\Controllers\Tenant\NotificationController;
use App\Http\Controllers\Tenant\NotificationPreferenceController;
use App\Http\Controllers\Tenant\PushSubscriptionController;
use App\Http\Controllers\Tenant\UpsellBannerController;
use App\Http\Controllers\WhatsappWebhookController;
use App\Http\Controllers\Master\UpsellTriggerController as MasterUpsellTriggerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas de Autenticação
|--------------------------------------------------------------------------
*/
Route::middleware(['guest', 'locale'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post')->middleware('throttle:register');
    Route::get('/parceiros', [AgencyRegisterController::class, 'show'])->name('agency.register');
    Route::post('/parceiros', [AgencyRegisterController::class, 'store'])->name('agency.register.store')->middleware('throttle:register');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:password-reset');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// 2FA Challenge (sem middleware guest nem auth — user ainda não está logado)
Route::middleware(['locale'])->group(function () {
    Route::get('/2fa/challenge', [\App\Http\Controllers\Auth\TwoFactorController::class, 'showChallenge'])->name('2fa.challenge');
    Route::post('/2fa/challenge', [\App\Http\Controllers\Auth\TwoFactorController::class, 'verifyChallenge'])->name('2fa.verify');
});

// Survey público (sem auth — UUID identifica tudo)
Route::get('/s/{uuid}', [\App\Http\Controllers\SurveyPublicController::class, 'showByUuid'])->name('survey.show');
Route::post('/s/{uuid}', [\App\Http\Controllers\SurveyPublicController::class, 'answer'])->name('survey.answer');
Route::get('/pesquisa/{slug}', [\App\Http\Controllers\SurveyPublicController::class, 'showBySlug'])->name('survey.slug');

// Certificado público de parceiro
Route::get('/certificado/{code}', function (string $code) {
    $cert = \App\Models\PartnerCertificate::where('certificate_code', $code)
        ->with(['tenant:id,name', 'course:id,title'])
        ->firstOrFail();
    return view('partner.certificate-public', compact('cert'));
})->name('certificate.public');

// Verificação de email e cadastro pendente (sem middleware guest para evitar loop)
Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail'])->name('verify.email');
Route::get('/cadastro-pendente', fn() => view('auth.pending'))->middleware('locale')->name('register.pending');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Páginas de bloqueio de conta (auth required, tenant NOT required)
Route::middleware('auth')->group(function () {
    Route::view('/conta/suspensa', 'tenant.account.suspended')->name('account.suspended');
    Route::view('/conta/trial-expirado', 'tenant.account.trial-expired')->name('trial.expired');
    Route::view('/conta/em-analise', 'auth.pending-approval')->name('account.pending-approval');
});

// Email templates preview (dev only)
if (app()->environment('local')) {
    Route::get('/email-templates/{template}', function (string $template) {
        $user   = \App\Models\User::first() ?? new \App\Models\User(['name' => 'João Silva', 'email' => 'joao@test.com']);
        $tenant = \App\Models\Tenant::first() ?? new \App\Models\Tenant(['name' => 'Empresa Teste']);

        return match ($template) {
            'verify'           => view('emails.verify', ['user' => $user, 'verifyUrl' => url('/verify-email/abc123')]),
            'welcome'          => view('emails.welcome', ['user' => $user, 'tenant' => $tenant, 'loginUrl' => route('login')]),
            'reset-password'   => view('emails.reset-password', ['user' => $user, 'resetUrl' => url('/reset-password/abc123')]),
            'verify-agency'    => view('emails.verify-agency', ['user' => $user, 'tenant' => $tenant, 'verifyUrl' => url('/verify-email/abc123')]),
            'partner-approved' => view('emails.partner-approved', ['user' => $user, 'tenant' => $tenant, 'code' => 'DIGITALLABS-A3F2', 'loginUrl' => route('login')]),
            default            => abort(404),
        };
    })->name('email.preview');
}

// Páginas públicas (sem autenticação)
Route::view('/politica-de-privacidade', 'public.privacy')->name('privacy');
Route::view('/termos-de-uso', 'public.terms')->name('terms');
Route::get('/chat/{tenantSlug}/{botSlug}', [\App\Http\Controllers\Api\WebsiteWidgetController::class, 'hostedPage'])->name('chatbot.hosted');

// Redirect de WhatsApp com tracking server-side (público)
Route::get('/wa/{token}', [\App\Http\Controllers\Api\WebsiteWidgetController::class, 'waRedirect'])
    ->middleware('throttle:120,1')
    ->name('wa.redirect');

/*
|--------------------------------------------------------------------------
| Rotas do Painel do Tenant
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tenant', 'locale'])->group(function () {
    // Onboarding (primeiro acesso)
    // Help assistant (Sophia)
    Route::post('help-chat', [HelpChatController::class, 'chat'])->name('help.chat');
    Route::post('help-chat/execute', [HelpChatController::class, 'execute'])->name('help.execute');

    // Global search (Cmd+K)
    Route::get('busca', [\App\Http\Controllers\Tenant\GlobalSearchController::class, 'search'])->name('global.search');

    // Interactive tour
    Route::post('tour/complete', [\App\Http\Controllers\Tenant\TourController::class, 'complete'])->name('tour.complete');
    Route::post('tour/reset', [\App\Http\Controllers\Tenant\TourController::class, 'reset'])->name('tour.reset');

    Route::get('onboarding',             [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('onboarding/generate',  [OnboardingController::class, 'generate'])->name('onboarding.generate');
    Route::get('onboarding/loading',    [OnboardingController::class, 'loading'])->name('onboarding.loading');
    Route::get('onboarding/progress',   [OnboardingController::class, 'progress'])->name('onboarding.progress');
    Route::get('onboarding/result',     [OnboardingController::class, 'result'])->name('onboarding.result');
    Route::post('onboarding/skip',      [OnboardingController::class, 'skip'])->name('onboarding.skip');

    // Cobrança / Checkout (admin only)
    Route::get('cobranca/checkout', [BillingController::class, 'showCheckout'])->name('billing.checkout');
    Route::middleware('role:admin')->group(function () {
        Route::post('cobranca/assinar',  [BillingController::class, 'subscribe'])->name('billing.subscribe');
        Route::post('cobranca/cancelar', [BillingController::class, 'cancel'])->name('billing.cancel');
        // Stripe Checkout
        Route::post('cobranca/stripe/assinar', [BillingController::class, 'stripeSubscribe'])->name('billing.stripe.subscribe');
        Route::get('cobranca/stripe/success',  [BillingController::class, 'stripeSuccess'])->name('billing.stripe.success');
        Route::get('cobranca/stripe/cancel',   [BillingController::class, 'stripeCancel'])->name('billing.stripe.cancel');
        Route::get('cobranca/stripe/portal',   [BillingController::class, 'stripePortal'])->name('billing.stripe.portal');
    });

    // Upsell banner (dismiss/click)
    Route::post('upsell/{log}/dismiss', [UpsellBannerController::class, 'dismiss'])->name('upsell.dismiss');
    Route::post('upsell/{log}/click',   [UpsellBannerController::class, 'click'])->name('upsell.click');

    // Agenda (Google Calendar)
    Route::prefix('agenda')->name('calendar.')->group(function () {
        Route::get('/',                [CalendarController::class, 'index'])->name('index');
        Route::get('/eventos',         [CalendarController::class, 'events'])->name('events');
        Route::get('/calendarios',     [CalendarController::class, 'calendars'])->name('calendars');
        Route::middleware('role:admin,manager')->group(function () {
            Route::post('/eventos',        [CalendarController::class, 'store'])->name('store');
            Route::put('/eventos/{id}',    [CalendarController::class, 'update'])->name('update');
            Route::delete('/eventos/{id}', [CalendarController::class, 'destroy'])->name('destroy');
            Route::post('/preferencias',   [CalendarController::class, 'savePreferences'])->name('preferences');
        });
    });

    // ── Tarefas ─────────────────────────────────────────────────────────
    Route::get('/tarefas',               [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tarefas/data',          [TaskController::class, 'data'])->name('tasks.data');
    Route::get('/tarefas/buscar-leads',  [TaskController::class, 'searchLeads'])->name('tasks.search-leads');
    Route::get('/contatos/{lead}/tarefas', [TaskController::class, 'forLead'])->name('leads.tasks.index');

    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/tarefas',              [TaskController::class, 'store'])->name('tasks.store');
        Route::put('/tarefas/{task}',        [TaskController::class, 'update'])->name('tasks.update');
        Route::patch('/tarefas/{task}/toggle', [TaskController::class, 'toggleStatus'])->name('tasks.toggle');
        Route::delete('/tarefas/{task}',     [TaskController::class, 'destroy'])->name('tasks.destroy');
    });

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::redirect('/dashboard', '/');
    Route::get('/inicio', [DashboardController::class, 'index'])->name('inicio');
    Route::post('/dashboard/config', [DashboardController::class, 'saveConfig'])->name('dashboard.config');
    Route::get('/dashboard/leads-chart', [DashboardController::class, 'leadsChart'])->name('dashboard.leads-chart');

    // Agência parceira — acesso a contas de clientes
    Route::post('/agencia/acessar/{tenant}',  [AgencyAccessController::class, 'enter'])->name('agency.access.enter');
    Route::post('/agencia/sair',              [AgencyAccessController::class, 'exit'])->name('agency.access.exit');
    Route::get('/agencia/meus-clientes',      [AgencyAccessController::class, 'clients'])->name('agency.clients');

    // Portal do Parceiro
    Route::get('/parceiro', [\App\Http\Controllers\Partner\PartnerDashboardController::class, 'index'])->name('partner.dashboard');

    // Feedback / Sugestões
    Route::get('/sugestoes', [\App\Http\Controllers\Tenant\FeedbackController::class, 'create'])->name('feedback.create');
    Route::post('/sugestoes', [\App\Http\Controllers\Tenant\FeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/parceiro/recursos', [\App\Http\Controllers\Partner\PartnerResourceController::class, 'index'])->name('partner.resources.index');
    Route::get('/parceiro/recursos/{slug}', [\App\Http\Controllers\Partner\PartnerResourceController::class, 'show'])->name('partner.resources.show');
    Route::get('/parceiro/cursos', [\App\Http\Controllers\Partner\PartnerCourseController::class, 'index'])->name('partner.courses.index');
    Route::get('/parceiro/cursos/{slug}', [\App\Http\Controllers\Partner\PartnerCourseController::class, 'show'])->name('partner.courses.show');
    Route::post('/parceiro/aulas/{lesson}/concluir', [\App\Http\Controllers\Partner\PartnerCourseController::class, 'completeLesson'])->name('partner.lessons.complete');
    Route::post('/parceiro/cursos/{course}/certificado', [\App\Http\Controllers\Partner\PartnerCourseController::class, 'issueCertificate'])->name('partner.courses.certificate');
    Route::post('/parceiro/saque', [\App\Http\Controllers\Partner\PartnerWithdrawalController::class, 'store'])->name('partner.withdrawal.store');

    // Redireciona /configuracoes para perfil
    Route::get('/configuracoes', fn () => redirect()->route('settings.profile'));

    // CRM Kanban
    Route::get('/crm', [KanbanController::class, 'index'])->name('crm.kanban');
    Route::get('/crm/poll', [KanbanController::class, 'poll'])->name('crm.poll');
    Route::get('/crm/exportar', [KanbanController::class, 'export'])->name('crm.export');
    Route::get('/crm/template', [KanbanController::class, 'template'])->name('crm.template');
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/crm/importar/preview', [KanbanController::class, 'preview'])->name('crm.import.preview');
        Route::post('/crm/importar', [KanbanController::class, 'import'])->name('crm.import');
        Route::post('/crm/lead/{lead}/stage', [KanbanController::class, 'updateStage'])->name('crm.lead.stage');
    });

    // Lead Lists
    Route::prefix('listas')->name('lists.')->controller(\App\Http\Controllers\Tenant\LeadListController::class)->group(function () {
        Route::get('/',                          'index')->name('index');
        Route::post('/',                         'store')->name('store');
        Route::post('/preview',                  'preview')->name('preview');
        Route::get('/search-leads',              'searchLeads')->name('search-leads');
        Route::get('/{list}',                    'show')->name('show');
        Route::put('/{list}',                    'update')->name('update');
        Route::delete('/{list}',                 'destroy')->name('destroy');
        Route::post('/{list}/members',           'addMembers')->name('members.add');
        Route::delete('/{list}/members/{lead}',  'removeMember')->name('members.remove');
    });

    // NPS / Pesquisas de Satisfação
    Route::prefix('nps')->name('nps.')->controller(\App\Http\Controllers\Tenant\NpsSurveyController::class)->group(function () {
        Route::get('/',              'index')->name('index');
        Route::post('/',             'store')->name('store');
        Route::put('/{survey}',      'update')->name('update');
        Route::delete('/{survey}',   'destroy')->name('destroy');
        Route::post('/{survey}/send', 'sendBulk')->name('send');
    });

    // Metas de Vendas
    Route::prefix('metas')->name('goals.')->controller(\App\Http\Controllers\Tenant\SalesGoalController::class)->group(function () {
        Route::get('/',                  'index')->name('index');
        Route::post('/',                 'store')->name('store');
        Route::put('/{goal}',            'update')->name('update');
        Route::delete('/{goal}',         'destroy')->name('destroy');
        Route::get('/historico/{user?}', 'history')->name('history');
    });

    // Lead duplicates & merge — ANTES do {lead} wildcard
    Route::get('/contatos/duplicatas', [\App\Http\Controllers\Tenant\LeadMergeController::class, 'duplicates'])->name('leads.duplicates');
    Route::get('/contatos/duplicatas/data', [\App\Http\Controllers\Tenant\LeadMergeController::class, 'duplicatesData'])->name('leads.duplicates.data');
    Route::post('/contatos/detectar-duplicatas', [\App\Http\Controllers\Tenant\LeadMergeController::class, 'detect'])->name('leads.detect-duplicates');
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('/contatos/{primary}/merge/{secondary}/preview', [\App\Http\Controllers\Tenant\LeadMergeController::class, 'preview'])->name('leads.merge.preview');
        Route::post('/contatos/{primary}/merge/{secondary}', [\App\Http\Controllers\Tenant\LeadMergeController::class, 'merge'])->name('leads.merge');
        Route::post('/contatos/duplicatas/{duplicate}/ignorar', [\App\Http\Controllers\Tenant\LeadMergeController::class, 'ignore'])->name('leads.duplicates.ignore');
    });

    // Leads / Contatos — exportar e importar ANTES do {lead} wildcard
    Route::get('/contatos/exportar', [LeadController::class, 'export'])->name('leads.export');

    Route::get('/contatos', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/contatos/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::get('/contatos/{lead}/perfil', [LeadController::class, 'showPage'])->name('leads.profile');

    // Lead write operations (admin + manager)
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/contatos/importar', [LeadController::class, 'import'])->name('leads.import')->middleware('plan.limit:leads');
        Route::post('/contatos/custom-fields/upload', [LeadController::class, 'uploadCustomFieldFile'])->name('leads.cf-upload');
        Route::post('/contatos', [LeadController::class, 'store'])->name('leads.store')->middleware('plan.limit:leads');
        Route::put('/contatos/{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::delete('/kanban/leads/{lead}', [LeadController::class, 'removeFromPipeline'])->name('leads.kanban-remove');
        Route::delete('/contatos/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

        // Lead notes
        Route::post('/contatos/{lead}/notas', [LeadController::class, 'addNote'])->name('leads.notes.store');
        Route::put('/contatos/{lead}/notas/{note}', [LeadController::class, 'updateNote'])->name('leads.notes.update');
        Route::delete('/contatos/{lead}/notas/{note}', [LeadController::class, 'deleteNote'])->name('leads.notes.destroy');

        // Lead contacts (people)
        Route::get('/contatos/{lead}/contacts', [LeadController::class, 'leadContacts'])->name('leads.contacts.index');
        Route::post('/contatos/{lead}/contacts', [LeadController::class, 'storeContact'])->name('leads.contacts.store');
        Route::put('/contatos/{lead}/contacts/{contact}', [LeadController::class, 'updateContact'])->name('leads.contacts.update');
        Route::delete('/contatos/{lead}/contacts/{contact}', [LeadController::class, 'destroyContact'])->name('leads.contacts.destroy');

        // Lead attachments
        Route::post('/contatos/{lead}/anexos', [LeadController::class, 'uploadAttachment'])->name('leads.attachments.store');
        Route::delete('/contatos/{lead}/anexos/{attachment}', [LeadController::class, 'deleteAttachment'])->name('leads.attachments.destroy');

        // Lead products
        Route::get('/contatos/{lead}/produtos', [LeadController::class, 'getProducts'])->name('leads.products.index');
        Route::post('/contatos/{lead}/produtos', [LeadController::class, 'addProduct'])->name('leads.products.store');
        Route::put('/contatos/{lead}/produtos/{leadProduct}', [LeadController::class, 'updateProduct'])->name('leads.products.update');
        Route::delete('/contatos/{lead}/produtos/{leadProduct}', [LeadController::class, 'removeProduct'])->name('leads.products.destroy');

        // Mensagens agendadas
        Route::post  ('/contatos/{lead}/mensagens-agendadas',            [ScheduledMessageController::class, 'store'])  ->name('leads.scheduled.store');
        Route::delete('/contatos/{lead}/mensagens-agendadas/{scheduled}',[ScheduledMessageController::class, 'destroy'])->name('leads.scheduled.destroy');
    });

    // Mensagens agendadas (leitura)
    Route::get('/contatos/{lead}/mensagens-agendadas', [ScheduledMessageController::class, 'index'])->name('leads.scheduled.index');

    // Relatórios
    Route::get('/relatorios', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/relatorios/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');

    // Campanhas (leitura para todos, escrita admin+manager)
    Route::get('/campanhas',                     [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campanhas/relatorios',          [CampaignController::class, 'reports'])->name('campaigns.reports');
    Route::get('/campanhas/relatorios/pdf',      [CampaignController::class, 'exportReportPdf'])->name('campaigns.reports.pdf');
    Route::get('/campanhas/drill-down',          [CampaignController::class, 'drillDown'])->name('campaigns.drill-down');
    Route::get('/campanhas/analytics',           [CampaignController::class, 'analytics'])->name('campaigns.analytics');
    Route::middleware('role:admin,manager')->group(function () {
        Route::post  ('/campanhas',                     [CampaignController::class, 'store'])->name('campaigns.store');
        Route::put   ('/campanhas/{campaign}',          [CampaignController::class, 'update'])->name('campaigns.update');
        Route::delete('/campanhas/{campaign}',          [CampaignController::class, 'destroy'])->name('campaigns.destroy');
    });

    // Integrações (leitura para todos, escrita admin only)
    Route::prefix('configuracoes/integracoes')->name('settings.integrations.')->group(function () {
        Route::get('/',                 [IntegrationController::class, 'index'])->name('index');
        Route::middleware('role:admin')->group(function () {
            Route::get('facebook/redirect', [IntegrationController::class, 'redirectFacebook'])->name('facebook.redirect');
            Route::get('facebook/callback', [IntegrationController::class, 'callbackFacebook'])->name('facebook.callback');
            Route::get('google/redirect',   [IntegrationController::class, 'redirectGoogle'])->name('google.redirect');
            Route::get('google/callback',   [IntegrationController::class, 'callbackGoogle'])->name('google.callback');
            // WhatsApp
            Route::post('whatsapp/connect',                          [IntegrationController::class, 'connectWhatsapp'])->name('whatsapp.connect');
            Route::get('whatsapp/{instance}/qr',                     [IntegrationController::class, 'getWhatsappQr'])->name('whatsapp.qr');
            Route::post('whatsapp/{instance}/restart',               [IntegrationController::class, 'restartWhatsapp'])->name('whatsapp.restart');
            Route::post('whatsapp/{instance}/import',                [IntegrationController::class, 'importHistoryWhatsapp'])->name('whatsapp.import');
            Route::get('whatsapp/{instance}/import/progress',        [IntegrationController::class, 'importProgress'])->name('whatsapp.import.progress');
            Route::put('whatsapp/{instance}',                        [IntegrationController::class, 'updateWhatsappInstance'])->name('whatsapp.update');
            Route::delete('whatsapp/{instance}/disconnect',          [IntegrationController::class, 'disconnectWhatsapp'])->name('whatsapp.disconnect');
            Route::delete('whatsapp/{instance}',                     [IntegrationController::class, 'deleteWhatsappInstance'])->name('whatsapp.delete');
            // Instagram OAuth
            Route::get('instagram/redirect', [IntegrationController::class, 'redirectInstagram'])->name('instagram.redirect');
            Route::get('instagram/callback', [IntegrationController::class, 'callbackInstagram'])->name('instagram.callback');
            Route::delete('instagram',        [IntegrationController::class, 'disconnectInstagram'])->name('instagram.disconnect');
            // WhatsApp Button (botão de página)
            Route::post('wa-button',                [IntegrationController::class, 'storeWaButton'])->name('wa-button.store');
            Route::put('wa-button/{waButton}',      [IntegrationController::class, 'updateWaButton'])->name('wa-button.update');
            Route::delete('wa-button/{waButton}',   [IntegrationController::class, 'destroyWaButton'])->name('wa-button.destroy');
            // Facebook Lead Ads
            Route::get('facebook-leadads/redirect',                         [IntegrationController::class, 'redirectFacebookLeadAds'])->name('facebook-leadads.redirect');
            Route::get('facebook-leadads/callback',                         [IntegrationController::class, 'callbackFacebookLeadAds'])->name('facebook-leadads.callback');
            Route::get('facebook-leadads/pages',                            [IntegrationController::class, 'getFacebookLeadAdsPages'])->name('facebook-leadads.pages');
            Route::get('facebook-leadads/search-page',                      [IntegrationController::class, 'searchFacebookLeadAdsPage'])->name('facebook-leadads.search-page');
            Route::get('facebook-leadads/forms',                            [IntegrationController::class, 'getFacebookLeadAdsForms'])->name('facebook-leadads.forms');
            Route::post('facebook-leadads/connections',                     [IntegrationController::class, 'storeFbLeadConnection'])->name('facebook-leadads.connections.store');
            Route::put('facebook-leadads/connections/{connection}',         [IntegrationController::class, 'updateFbLeadConnection'])->name('facebook-leadads.connections.update');
            Route::delete('facebook-leadads/connections/{connection}',      [IntegrationController::class, 'destroyFbLeadConnection'])->name('facebook-leadads.connections.destroy');
            Route::delete('facebook-leadads',                               [IntegrationController::class, 'disconnectFacebookLeadAds'])->name('facebook-leadads.disconnect');
            // WhatsApp Cloud API (Embedded Signup)
            Route::get('whatsapp-cloud/redirect',                           [IntegrationController::class, 'redirectWhatsappCloud'])->name('whatsapp-cloud.redirect');
            Route::get('whatsapp-cloud/callback',                           [IntegrationController::class, 'callbackWhatsappCloud'])->name('whatsapp-cloud.callback');
            Route::delete('whatsapp-cloud/{instance}',                      [IntegrationController::class, 'disconnectWhatsappCloud'])->name('whatsapp-cloud.disconnect');
            // Wildcards (OAuth)
            Route::delete('{platform}',     [IntegrationController::class, 'disconnect'])->name('disconnect');
            Route::post('{platform}/sync',  [IntegrationController::class, 'syncNow'])->name('sync');
        });
    });

    // Chat Inbox (WhatsApp e outros canais)
    Route::prefix('chats')->name('chats.')->group(function () {
        // Leitura — todos os roles
        Route::get('/',                                       [WhatsappController::class, 'index'])->name('index');
        Route::get('/poll',                                   [WhatsappController::class, 'poll'])->name('poll');
        Route::get('/conversations/{conversation}',           [WhatsappController::class, 'show'])->name('conversations.show');
        Route::post('/conversations/{conversation}/read',     [WhatsappController::class, 'markRead'])->name('conversations.read');
        Route::get ('/instagram-conversations/{conversation}',         [WhatsappController::class, 'showInstagram'])->name('ig-conversations.show');
        Route::post('/instagram-conversations/{conversation}/read',    [WhatsappController::class, 'markReadInstagram'])->name('ig-conversations.read');
        Route::get ('/leads/search',                                       [WhatsappController::class, 'searchLeads'])->name('leads.search');
        Route::get ('/website-conversations/{websiteConversation}',               [WhatsappController::class, 'showWebsite'])->name('website-conversations.show');
        Route::post('/website-conversations/{websiteConversation}/read',          [WhatsappController::class, 'markReadWebsite'])->name('website-conversations.read');

        // AI Analyst — leitura
        Route::get ('{conversation}/analyst-suggestions',             [AiAnalystController::class, 'index'])->name('analyst.index');

        // Mensagens Rápidas — leitura
        Route::get('/quick-messages', [QuickMessageController::class, 'index'])->name('quick-messages.index');

        // Escrita — admin + manager
        Route::middleware('role:admin,manager')->group(function () {
            Route::put('/conversations/{conversation}/assign',    [WhatsappController::class, 'assign'])->name('conversations.assign');
            Route::put('/conversations/{conversation}/status',    [WhatsappController::class, 'updateStatus'])->name('conversations.status');
            Route::put('/conversations/{conversation}/lead',      [WhatsappController::class, 'updateLead'])->name('conversations.lead');
            Route::put('/conversations/{conversation}/link-lead',   [WhatsappController::class, 'linkLead'])->name('conversations.link-lead');
            Route::put('/conversations/{conversation}/unlink-lead',[WhatsappController::class, 'unlinkLead'])->name('conversations.unlink-lead');
            Route::put('/conversations/{conversation}/contact',    [WhatsappController::class, 'updateContact'])->name('conversations.contact');
            Route::post('/conversations/{conversation}/messages', [WhatsappMessageController::class, 'store'])->name('messages.store');
            Route::post('/conversations/{conversation}/react',    [WhatsappMessageController::class, 'react'])->name('messages.react');
            Route::put('/conversations/{conversation}/ai-agent',      [WhatsappController::class, 'assignAiAgent'])->name('conversations.ai-agent');
            Route::put('/conversations/{conversation}/chatbot-flow',   [WhatsappController::class, 'assignChatbotFlow'])->name('conversations.chatbot-flow');
            Route::put('/conversations/{conversation}/department',     [WhatsappController::class, 'assignDepartment'])->name('conversations.department');
            Route::delete('/conversations/{conversation}',             [WhatsappController::class, 'destroy'])->name('conversations.destroy');
            // Instagram write
            Route::post  ('/instagram-conversations/{conversation}/messages',[WhatsappController::class, 'sendInstagramMessage'])->name('ig-conversations.messages');
            Route::delete('/instagram-conversations/{conversation}',         [WhatsappController::class, 'destroyInstagram'])->name('ig-conversations.destroy');
            Route::put('/instagram-conversations/{conversation}/link-lead',   [WhatsappController::class, 'linkLeadInstagram'])->name('ig-conversations.link-lead');
            Route::put('/instagram-conversations/{conversation}/unlink-lead', [WhatsappController::class, 'unlinkLeadInstagram'])->name('ig-conversations.unlink-lead');
            // Website write
            Route::put ('/website-conversations/{websiteConversation}/status',        [WhatsappController::class, 'updateStatusWebsite'])->name('website-conversations.status');
            Route::put ('/website-conversations/{websiteConversation}/link-lead',     [WhatsappController::class, 'linkLeadWebsite'])->name('website-conversations.link-lead');
            Route::put   ('/website-conversations/{websiteConversation}/unlink-lead',   [WhatsappController::class, 'unlinkLeadWebsite'])->name('website-conversations.unlink-lead');
            Route::delete('/website-conversations/{websiteConversation}',               [WhatsappController::class, 'destroyWebsite'])->name('website-conversations.destroy');

            // AI Analyst — ações
            Route::post('{conversation}/analyst-suggestions/approve-all', [AiAnalystController::class, 'approveAll'])->name('analyst.approve-all');
            Route::post('{conversation}/analyze',                         [AiAnalystController::class, 'trigger'])->name('analyst.trigger');

            // Mensagens Rápidas — escrita
            Route::post  ('/quick-messages',      [QuickMessageController::class, 'store'])->name('quick-messages.store');
            Route::put   ('/quick-messages/{qm}', [QuickMessageController::class, 'update'])->name('quick-messages.update');
            Route::delete('/quick-messages/{qm}', [QuickMessageController::class, 'destroy'])->name('quick-messages.destroy');
        });
    });

    // AI Analyst — ações globais por sugestão
    Route::post('/analyst-suggestions/{suggestion}/approve', [AiAnalystController::class, 'approve'])->name('analyst.approve');
    Route::post('/analyst-suggestions/{suggestion}/reject',  [AiAnalystController::class, 'reject'])->name('analyst.reject');
    Route::get ('/analyst-suggestions/pending-count',        [AiAnalystController::class, 'pendingCount'])->name('analyst.pending-count');
    Route::get ('/notificacoes/master',                      [MasterNotificationReadController::class, 'index'])->name('master-notifications.index');

    // Notificações — lista, leitura, push subscriptions
    Route::get ('/notificacoes',              [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notificacoes/{id}/lida',    [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notificacoes/marcar-todas', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get ('/notificacoes/nao-lidas',    [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get ('/notificacoes/recentes',    [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post  ('/push-subscriptions',      [PushSubscriptionController::class, 'store'])->name('push.store');
    Route::delete('/push-subscriptions',      [PushSubscriptionController::class, 'destroy'])->name('push.destroy');

    // Chatbot Builder (admin only, leitura para todos)
    Route::prefix('chatbot/fluxos')->name('chatbot.flows.')->group(function () {
        Route::get('pipelines',      [ChatbotFlowController::class, 'getPipelines'])->name('pipelines');
        Route::get('',               [ChatbotFlowController::class, 'index'])->name('index');
        Route::get('onboarding',    [ChatbotFlowController::class, 'onboarding'])->name('onboarding');
        Route::get('{flow}/resultados', [ChatbotFlowController::class, 'results'])->name('results');
        Route::middleware('role:admin')->group(function () {
            Route::get('criar',          [ChatbotFlowController::class, 'create'])->name('create')->middleware('plan.limit:chatbot_flows');
            Route::post('',              [ChatbotFlowController::class, 'store'])->name('store')->middleware('plan.limit:chatbot_flows');
            Route::get('{flow}/editar',  [ChatbotFlowController::class, 'edit'])->name('edit');
            Route::put('{flow}',         [ChatbotFlowController::class, 'update'])->name('update');
            Route::delete('{flow}',      [ChatbotFlowController::class, 'destroy'])->name('destroy');
            Route::post('upload-image',  [ChatbotFlowController::class, 'uploadImage'])->name('upload-image');
            Route::post('{flow}/toggle',    [ChatbotFlowController::class, 'toggle'])->name('toggle');
            Route::put('{flow}/graph',      [ChatbotFlowController::class, 'saveGraph'])->name('graph');
            Route::put('{flow}/graph-react', [ChatbotFlowController::class, 'saveGraphReact'])->name('graph-react');
        });
    });

    // Automações de Instagram (admin only)
    Route::prefix('configuracoes/instagram-automacoes')->name('settings.ig-automations.')->group(function () {
        Route::get('posts',                 [InstagramAutomationController::class, 'posts'])->name('posts');
        Route::get('',                      [InstagramAutomationController::class, 'index'])->name('index');
        Route::middleware('role:admin')->group(function () {
            Route::post('',                     [InstagramAutomationController::class, 'store'])->name('store');
            Route::put('{automation}',          [InstagramAutomationController::class, 'update'])->name('update');
            Route::delete('{automation}',       [InstagramAutomationController::class, 'destroy'])->name('destroy');
            Route::patch('{automation}/toggle', [InstagramAutomationController::class, 'toggleActive'])->name('toggle');
        });
    });

    // Configurações
    Route::prefix('configuracoes')->name('settings.')->group(function () {
        // Notificações — todos os roles
        Route::get('notificacoes',  [NotificationPreferenceController::class, 'index'])->name('notifications');
        Route::put('notificacoes',  [NotificationPreferenceController::class, 'update'])->name('notifications.update');

        // Leitura — todos os roles
        Route::get('pipelines', [PipelineController::class, 'index'])->name('pipelines');
        Route::get('motivos-perda', [LostSaleReasonController::class, 'index'])->name('lost-reasons');
        Route::get('tags',              [WhatsappTagController::class, 'index'])->name('tags');
        Route::get('cobranca', [BillingController::class, 'index'])->name('billing');
        Route::get('departamentos',     [DepartmentController::class, 'index'])->name('departments');
        Route::get('automacoes',        [AutomationController::class, 'index'])->name('automations');
        Route::get('scoring',           [LeadScoringController::class, 'index'])->name('scoring');
        Route::get('campos-extras',     [CustomFieldController::class, 'index'])->name('custom-fields');
        Route::get('produtos',          [ProductController::class, 'index'])->name('products');
        Route::get('api-keys',          [ApiKeyController::class, 'index'])->name('api-keys');
        Route::get('usuarios',          [UserController::class, 'index'])->name('users');
        Route::get('instalar-app',       fn () => view('tenant.settings.pwa'))->name('pwa');
        Route::get('auditoria',         [\App\Http\Controllers\Tenant\AuditLogController::class, 'index'])->name('audit-log');
        Route::get('auditoria/{log}',   [\App\Http\Controllers\Tenant\AuditLogController::class, 'show'])->name('audit-log.show');

        // Perfil (todos podem editar o próprio perfil)
        Route::get('perfil',         [ProfileController::class, 'index'])->name('profile');
        Route::put('perfil',         [ProfileController::class, 'update'])->name('profile.update');
        Route::put('perfil/senha',   [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::put('perfil/idioma', [ProfileController::class, 'updateLocale'])->name('profile.locale');
        Route::post('perfil/avatar',         [ProfileController::class, 'updateAvatar'])->name('profile.avatar');

        // Admin only — escrita em configurações
        Route::middleware('role:admin')->group(function () {
            // Pipelines + Stages
            Route::post('pipelines', [PipelineController::class, 'store'])->name('pipelines.store')->middleware('plan.limit:pipelines');
            Route::put('pipelines/{pipeline}', [PipelineController::class, 'update'])->name('pipelines.update');
            Route::delete('pipelines/{pipeline}', [PipelineController::class, 'destroy'])->name('pipelines.destroy');
            Route::post('pipelines/{pipeline}/stages/reorder', [PipelineController::class, 'reorderStages'])->name('pipelines.stages.reorder');
            Route::post('pipelines/{pipeline}/stages', [PipelineController::class, 'storeStage'])->name('pipelines.stages.store');
            Route::put('pipelines/{pipeline}/stages/{stage}', [PipelineController::class, 'updateStage'])->name('pipelines.stages.update');
            Route::delete('pipelines/{pipeline}/stages/{stage}', [PipelineController::class, 'destroyStage'])->name('pipelines.stages.destroy');

            // Motivos de Perda
            Route::post('motivos-perda', [LostSaleReasonController::class, 'store'])->name('lost-reasons.store');
            Route::put('motivos-perda/{reason}', [LostSaleReasonController::class, 'update'])->name('lost-reasons.update');
            Route::delete('motivos-perda/{reason}', [LostSaleReasonController::class, 'destroy'])->name('lost-reasons.destroy');

            // Lead Scoring
            Route::post('scoring', [LeadScoringController::class, 'store'])->name('scoring.store');
            Route::put('scoring/{rule}', [LeadScoringController::class, 'update'])->name('scoring.update');
            Route::delete('scoring/{rule}', [LeadScoringController::class, 'destroy'])->name('scoring.destroy');

            // API Keys
            Route::post('api-keys',             [ApiKeyController::class, 'store'])->name('api-keys.store');
            Route::delete('api-keys/{apiKey}',  [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

            // Tags
            Route::post('tags',             [WhatsappTagController::class, 'store'])->name('tags.store');
            Route::put('tags/{tag}',        [WhatsappTagController::class, 'update'])->name('tags.update');
            Route::delete('tags/{tag}',     [WhatsappTagController::class, 'destroy'])->name('tags.destroy');

            // Incremento de tokens
            Route::post('tokens/comprar', [TokenIncrementController::class, 'purchase'])->name('tokens.purchase');

            // Workspace logo
            Route::post('workspace/logo',        [ProfileController::class, 'uploadWorkspaceLogo'])->name('workspace.logo');

            // Vínculo de agência parceira
            Route::post('agencia-parceira',          [AgencyAccessController::class, 'linkCode'])->name('agency.link');
            Route::post('agencia-parceira/desvincular', [AgencyAccessController::class, 'unlinkPartner'])->name('agency.unlink');
            Route::post('agencia-parceira/trocar',   [AgencyAccessController::class, 'switchPartner'])->name('agency.switch');

            // Usuários
            Route::post('usuarios',           [UserController::class, 'store'])->name('users.store')->middleware('plan.limit:users');
            Route::put('usuarios/{user}',     [UserController::class, 'update'])->name('users.update');
            Route::delete('usuarios/{user}',  [UserController::class, 'destroy'])->name('users.destroy');

            // Campos Personalizados
            Route::post('campos-extras',              [CustomFieldController::class, 'store'])->name('custom-fields.store')->middleware('plan.limit:custom_fields');
            Route::put('campos-extras/{field}',       [CustomFieldController::class, 'update'])->name('custom-fields.update');
            Route::delete('campos-extras/{field}',    [CustomFieldController::class, 'destroy'])->name('custom-fields.destroy');

            // Produtos / Serviços
            Route::post('produtos',                          [ProductController::class, 'store'])->name('products.store');
            Route::put('produtos/{product}',                 [ProductController::class, 'update'])->name('products.update');
            Route::delete('produtos/{product}',              [ProductController::class, 'destroy'])->name('products.destroy');
            Route::post('produtos/{product}/media',          [ProductController::class, 'uploadMedia'])->name('products.media.upload');
            Route::delete('produtos/{product}/media/{media}', [ProductController::class, 'deleteMedia'])->name('products.media.delete');
            // Categorias de produtos
            Route::post('produtos/categorias',                      [ProductController::class, 'storeCategory'])->name('products.categories.store');
            Route::put('produtos/categorias/{category}',            [ProductController::class, 'updateCategory'])->name('products.categories.update');
            Route::delete('produtos/categorias/{category}',         [ProductController::class, 'destroyCategory'])->name('products.categories.destroy');

            // Departamentos
            Route::post('departamentos',                    [DepartmentController::class, 'store'])->name('departments.store')->middleware('plan.limit:departments');
            Route::put('departamentos/{department}',        [DepartmentController::class, 'update'])->name('departments.update');
            Route::delete('departamentos/{department}',     [DepartmentController::class, 'destroy'])->name('departments.destroy');

            // Automações
            Route::get('automacoes/criar',                  [AutomationController::class, 'create'])->name('automations.create');
            Route::get('automacoes/{automation}/editar',    [AutomationController::class, 'edit'])->name('automations.edit');
            Route::post('automacoes',                       [AutomationController::class, 'store'])->name('automations.store');
            Route::put('automacoes/{automation}',           [AutomationController::class, 'update'])->name('automations.update');
            Route::delete('automacoes/{automation}',        [AutomationController::class, 'destroy'])->name('automations.destroy');
            Route::patch('automacoes/{automation}/toggle',  [AutomationController::class, 'toggle'])->name('automations.toggle');
            Route::post('automacoes/test-webhook',          [AutomationController::class, 'testWebhook'])->name('automations.test-webhook');

            // Sequências de Nutrição
            Route::get('sequencias',                          [NurtureSequenceController::class, 'index'])->name('sequences');
            Route::get('sequencias/criar',                    [NurtureSequenceController::class, 'create'])->name('sequences.create');
            Route::get('sequencias/{sequence}/editar',        [NurtureSequenceController::class, 'edit'])->name('sequences.edit');
            Route::post('sequencias',                         [NurtureSequenceController::class, 'store'])->name('sequences.store');
            Route::put('sequencias/{sequence}',               [NurtureSequenceController::class, 'update'])->name('sequences.update');
            Route::delete('sequencias/{sequence}',            [NurtureSequenceController::class, 'destroy'])->name('sequences.destroy');
            Route::patch('sequencias/{sequence}/toggle',      [NurtureSequenceController::class, 'toggle'])->name('sequences.toggle');
            Route::post('sequencias/{sequence}/enroll',       [NurtureSequenceController::class, 'enroll'])->name('sequences.enroll');
            Route::delete('sequencias/{sequence}/unenroll',   [NurtureSequenceController::class, 'unenroll'])->name('sequences.unenroll');
        });
    });

    // Sinais de intenção do Agente IA
    Route::prefix('ia/sinais')->name('ai.intent-signals.')->group(function () {
        Route::get('',                   [AiIntentSignalController::class, 'list'])->name('list');
        Route::post('{signal}/lida',     [AiIntentSignalController::class, 'markRead'])->name('read');
        Route::post('marcar-todas',      [AiIntentSignalController::class, 'markAllRead'])->name('read-all');
        Route::get('nao-lidas/contagem', [AiIntentSignalController::class, 'unreadCount'])->name('unread-count');
    });

    // Agentes de IA (admin only para escrita)
    Route::prefix('ia/agentes')->name('ai.agents.')->group(function () {
        Route::get('',                           [AiAgentController::class, 'index'])->name('index');
        Route::get('onboarding',                 [AiAgentController::class, 'onboarding'])->name('onboarding');
        Route::middleware('role:admin')->group(function () {
            Route::get('voices',                     [AiAgentController::class, 'voices'])->name('voices');
            Route::get('criar',                      [AiAgentController::class, 'create'])->name('create')->middleware('plan.limit:ai_agents');
            Route::post('',                          [AiAgentController::class, 'store'])->name('store')->middleware('plan.limit:ai_agents');
            Route::get('{agent}/editar',             [AiAgentController::class, 'edit'])->name('edit');
            Route::put('{agent}',                    [AiAgentController::class, 'update'])->name('update');
            Route::delete('{agent}',                 [AiAgentController::class, 'destroy'])->name('destroy');
            Route::post('{agent}/toggle',            [AiAgentController::class, 'toggleActive'])->name('toggle');
            Route::post('{agent}/test-chat',         [AiAgentController::class, 'testChat'])->name('test-chat');
            Route::post('{agent}/knowledge-files',           [AiAgentController::class, 'uploadKnowledgeFile'])->name('knowledge-files.store');
            Route::delete('{agent}/knowledge-files/{file}',  [AiAgentController::class, 'deleteKnowledgeFile'])->name('knowledge-files.destroy');
            Route::post('{agent}/media',             [AiAgentController::class, 'uploadMedia'])->name('media.store');
            Route::delete('{agent}/media/{media}',   [AiAgentController::class, 'deleteMedia'])->name('media.destroy');
        });
    });
});

// ── Master (super_admin only) ──────────────────────────────────────────────────
// ── Customer Success ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'cs_agent'])->prefix('cs')->name('cs.')->group(function () {
    Route::get('/',        [\App\Http\Controllers\Cs\CsDashboardController::class, 'index'])->name('index');
    Route::get('/{tenant}', [\App\Http\Controllers\Cs\CsDashboardController::class, 'show'])->name('show');
});

// ── Master Admin ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'super_admin', '2fa'])->prefix('master')->name('master.')->group(function () {

    // 2FA Setup
    Route::get('2fa/setup',          [\App\Http\Controllers\Auth\TwoFactorController::class, 'showSetup'])->name('2fa.setup');
    Route::post('2fa/setup',         [\App\Http\Controllers\Auth\TwoFactorController::class, 'confirmSetup'])->name('2fa.confirm');
    Route::post('2fa/disable',       [\App\Http\Controllers\Auth\TwoFactorController::class, 'disable'])->name('2fa.disable');
    Route::get('2fa/backup-codes',   [\App\Http\Controllers\Auth\TwoFactorController::class, 'showBackupCodes'])->name('2fa.backup-codes');
    Route::post('2fa/backup-codes',  [\App\Http\Controllers\Auth\TwoFactorController::class, 'regenerateBackupCodes'])->name('2fa.regenerate-codes');

    // Dashboard
    Route::get('',                                     [MasterDashboardController::class, 'index'])->name('dashboard');

    // Empresas (tenants)
    Route::get('empresas',             [MasterTenantController::class, 'index'])->name('tenants');
    Route::post('empresas',            [MasterTenantController::class, 'store'])->name('tenants.store');
    Route::get('empresas/{tenant}',    [MasterTenantController::class, 'show'])->name('tenants.show');
    Route::put('empresas/{tenant}',    [MasterTenantController::class, 'update'])->name('tenants.update');
    Route::delete('empresas/{tenant}', [MasterTenantController::class, 'destroy'])->name('tenants.destroy');
    Route::post('empresas/{tenant}/approve-partner', [MasterTenantController::class, 'approvePartner'])->name('tenants.approve-partner');
    Route::post('empresas/{tenant}/reject-partner',  [MasterTenantController::class, 'rejectPartner'])->name('tenants.reject-partner');

    // Usuários por tenant
    Route::post('empresas/{tenant}/usuarios',          [MasterUserController::class, 'store'])->name('tenants.users.store');
    Route::put('empresas/{tenant}/usuarios/{user}',    [MasterUserController::class, 'update'])->name('tenants.users.update');
    Route::delete('empresas/{tenant}/usuarios/{user}', [MasterUserController::class, 'destroy'])->name('tenants.users.destroy');

    // Planos
    Route::get('planos',                               [MasterPlanController::class, 'index'])->name('plans');
    Route::post('planos',                              [MasterPlanController::class, 'store'])->name('plans.store');
    Route::put('planos/{plan}',                        [MasterPlanController::class, 'update'])->name('plans.update');
    Route::delete('planos/{plan}',                     [MasterPlanController::class, 'destroy'])->name('plans.destroy');

    // Códigos de agências parceiras
    Route::get('codigos-agencia',                                      [MasterPartnerAgencyCodeController::class, 'index'])->name('agency-codes.index');
    Route::post('codigos-agencia',                                     [MasterPartnerAgencyCodeController::class, 'store'])->name('agency-codes.store');
    Route::post('codigos-agencia/gerar',                               [MasterPartnerAgencyCodeController::class, 'generate'])->name('agency-codes.generate');
    Route::put('codigos-agencia/{partnerAgencyCode}',                  [MasterPartnerAgencyCodeController::class, 'update'])->name('agency-codes.update');
    Route::delete('codigos-agencia/{partnerAgencyCode}',               [MasterPartnerAgencyCodeController::class, 'destroy'])->name('agency-codes.destroy');

    // Ranks de parceiros
    Route::get('partner-ranks',               [\App\Http\Controllers\Master\PartnerRankController::class, 'index'])->name('partner-ranks.index');
    Route::post('partner-ranks',              [\App\Http\Controllers\Master\PartnerRankController::class, 'store'])->name('partner-ranks.store');
    Route::put('partner-ranks/{rank}',        [\App\Http\Controllers\Master\PartnerRankController::class, 'update'])->name('partner-ranks.update');
    Route::delete('partner-ranks/{rank}',     [\App\Http\Controllers\Master\PartnerRankController::class, 'destroy'])->name('partner-ranks.destroy');

    // Recursos para parceiros
    Route::get('partner-recursos',                [\App\Http\Controllers\Master\PartnerResourceController::class, 'index'])->name('partner-resources.index');
    Route::post('partner-recursos',               [\App\Http\Controllers\Master\PartnerResourceController::class, 'store'])->name('partner-resources.store');
    Route::put('partner-recursos/{resource}',     [\App\Http\Controllers\Master\PartnerResourceController::class, 'update'])->name('partner-resources.update');
    Route::delete('partner-recursos/{resource}',  [\App\Http\Controllers\Master\PartnerResourceController::class, 'destroy'])->name('partner-resources.destroy');

    // Cursos para parceiros
    Route::get('partner-cursos',                  [\App\Http\Controllers\Master\PartnerCourseController::class, 'index'])->name('partner-courses.index');
    Route::post('partner-cursos',                 [\App\Http\Controllers\Master\PartnerCourseController::class, 'store'])->name('partner-courses.store');
    Route::put('partner-cursos/{course}',         [\App\Http\Controllers\Master\PartnerCourseController::class, 'update'])->name('partner-courses.update');
    Route::delete('partner-cursos/{course}',      [\App\Http\Controllers\Master\PartnerCourseController::class, 'destroy'])->name('partner-courses.destroy');
    Route::post('partner-cursos/{course}/aulas',  [\App\Http\Controllers\Master\PartnerCourseController::class, 'storeLesson'])->name('partner-lessons.store');
    Route::put('partner-aulas/{lesson}',          [\App\Http\Controllers\Master\PartnerCourseController::class, 'updateLesson'])->name('partner-lessons.update');
    Route::delete('partner-aulas/{lesson}',       [\App\Http\Controllers\Master\PartnerCourseController::class, 'destroyLesson'])->name('partner-lessons.destroy');

    // Comissões e saques de parceiros
    Route::get('partner-comissoes',                         [\App\Http\Controllers\Master\PartnerCommissionController::class, 'index'])->name('partner-commissions.index');
    Route::post('partner-saques/{withdrawal}/aprovar',      [\App\Http\Controllers\Master\PartnerCommissionController::class, 'approveWithdrawal'])->name('partner-withdrawals.approve');
    Route::post('partner-saques/{withdrawal}/rejeitar',     [\App\Http\Controllers\Master\PartnerCommissionController::class, 'rejectWithdrawal'])->name('partner-withdrawals.reject');
    Route::post('partner-saques/{withdrawal}/pago',         [\App\Http\Controllers\Master\PartnerCommissionController::class, 'markPaid'])->name('partner-withdrawals.paid');

    // Pacotes de incremento de tokens
    Route::get('token-incrementos',                                                [MasterTokenIncrementPlanController::class, 'index'])->name('token-increments');
    Route::post('token-incrementos',                                               [MasterTokenIncrementPlanController::class, 'store'])->name('token-increments.store');
    Route::put('token-incrementos/{tokenIncrementPlan}',                           [MasterTokenIncrementPlanController::class, 'update'])->name('token-increments.update');
    Route::delete('token-incrementos/{tokenIncrementPlan}',                        [MasterTokenIncrementPlanController::class, 'destroy'])->name('token-increments.destroy');

    // Feedbacks dos clientes
    Route::get('feedbacks',                    [\App\Http\Controllers\Master\FeedbackController::class, 'index'])->name('feedbacks.index');
    Route::get('feedbacks/{feedback}',         [\App\Http\Controllers\Master\FeedbackController::class, 'show'])->name('feedbacks.show');
    Route::put('feedbacks/{feedback}/status',  [\App\Http\Controllers\Master\FeedbackController::class, 'updateStatus'])->name('feedbacks.status');

    // Upsell Triggers
    Route::get('upsell',                               [MasterUpsellTriggerController::class, 'index'])->name('upsell');
    Route::post('upsell',                              [MasterUpsellTriggerController::class, 'store'])->name('upsell.store');
    Route::put('upsell/{trigger}',                     [MasterUpsellTriggerController::class, 'update'])->name('upsell.update');
    Route::delete('upsell/{trigger}',                  [MasterUpsellTriggerController::class, 'destroy'])->name('upsell.destroy');
    Route::get('upsell/{trigger}/logs',                [MasterUpsellTriggerController::class, 'logs'])->name('upsell.logs');

    // Uso de tokens IA
    Route::get('uso',                                  [MasterUsageController::class, 'index'])->name('usage');
    Route::get('uso/{tenant}',                         [MasterUsageController::class, 'show'])->name('usage.show');

    // Logs
    Route::get('logs',                                 [MasterLogController::class, 'index'])->name('logs');
    Route::get('logs/content',                         [MasterLogController::class, 'content'])->name('logs.content');

    // Sistema
    Route::get('sistema',                              [MasterSystemController::class, 'index'])->name('system');
    Route::get('sistema/stats',                        [MasterSystemController::class, 'stats'])->name('system.stats');

    // Feature Flags
    Route::get('features',                             [\App\Http\Controllers\Master\FeatureController::class, 'index'])->name('features');
    Route::post('features/{feature}/toggle-global',    [\App\Http\Controllers\Master\FeatureController::class, 'toggleGlobal'])->name('features.toggle-global');
    Route::put('features/{feature}/tenants',           [\App\Http\Controllers\Master\FeatureController::class, 'updateTenants'])->name('features.update-tenants');

    // Reengajamento
    Route::get('reengajamento',                        [\App\Http\Controllers\Master\ReengagementController::class, 'index'])->name('reengagement');
    Route::put('reengajamento',                        [\App\Http\Controllers\Master\ReengagementController::class, 'update'])->name('reengagement.update');
    Route::post('reengajamento/teste',                 [\App\Http\Controllers\Master\ReengagementController::class, 'sendTest'])->name('reengagement.test');
    Route::get('reengajamento/preview',                [\App\Http\Controllers\Master\ReengagementController::class, 'preview'])->name('reengagement.preview');

    // Notificações
    Route::get('notificacoes',                         [MasterNotificationController::class, 'index'])->name('notifications');
    Route::post('notificacoes',                        [MasterNotificationController::class, 'store'])->name('notifications.store');

    // Recebimentos
    Route::get('recebimentos', [MasterPaymentController::class, 'index'])->name('payments');

    // Ferramentas
    Route::get ('ferramentas',        [MasterToolboxController::class, 'index'])->name('toolbox');
    Route::post('ferramentas/{tool}', [MasterToolboxController::class, 'run'])->name('toolbox.run');

    // Administradores Master
    Route::get ('administradores',              [\App\Http\Controllers\Master\MasterAdminController::class, 'index'])->name('admins');
    Route::post('administradores',              [\App\Http\Controllers\Master\MasterAdminController::class, 'store'])->name('admins.store');
    Route::put ('administradores/{user}',       [\App\Http\Controllers\Master\MasterAdminController::class, 'update'])->name('admins.update');
    Route::delete('administradores/{user}',     [\App\Http\Controllers\Master\MasterAdminController::class, 'destroy'])->name('admins.destroy');

    // Customer Success Agents
    Route::get ('cs-agentes',           [\App\Http\Controllers\Master\CsAgentController::class, 'index'])->name('cs-agents');
    Route::post('cs-agentes',           [\App\Http\Controllers\Master\CsAgentController::class, 'store'])->name('cs-agents.store');
    Route::put ('cs-agentes/{user}',    [\App\Http\Controllers\Master\CsAgentController::class, 'update'])->name('cs-agents.update');
    Route::delete('cs-agentes/{user}',  [\App\Http\Controllers\Master\CsAgentController::class, 'destroy'])->name('cs-agents.destroy');

});
// Configuração LLM (provider/api_key/model) via ENV: LLM_PROVIDER, LLM_API_KEY, LLM_MODEL

// ── Webhook público WAHA (sem autenticação) ───────────────────────────────────
Route::post('/webhook/whatsapp', [WhatsappWebhookController::class, 'handle'])
    ->name('whatsapp.webhook')
    ->withoutMiddleware(['web']);
