<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\ReengagementEmail;
use App\Models\Lead;
use App\Models\ReengagementTemplate;
use App\Models\Sale;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappConversation;
use App\Models\InstagramConversation;
use App\Services\WahaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReengagement extends Command
{
    protected $signature = 'users:send-reengagement {--test-email= : Send test email to this address} {--test-phone= : Send test WA to this number} {--test-stage=7d : Stage for test}';
    protected $description = 'Send reengagement emails and WhatsApp to inactive users';

    private const STAGES = [
        ['days' => 7,  'max_days' => 13, 'stage' => '7d'],
        ['days' => 14, 'max_days' => 29, 'stage' => '14d'],
        ['days' => 30, 'max_days' => 90, 'stage' => '30d'],
    ];

    private const WA_SESSION = 'tenant_12';

    public function handle(): int
    {
        // Test mode
        if ($this->option('test-email') || $this->option('test-phone')) {
            return $this->sendTest();
        }

        $totalEmail = 0;
        $totalWa    = 0;

        foreach (self::STAGES as $s) {
            $users = User::whereNotNull('last_login_at')
                ->where('last_login_at', '<', now()->subDays($s['days']))
                ->where('last_login_at', '>=', now()->subDays($s['max_days'] + 1))
                ->where(fn ($q) => $q->whereNull('reengagement_stage')
                    ->orWhere('reengagement_stage', '!=', $s['stage']))
                ->whereIn('role', ['admin', 'manager'])
                ->whereHas('tenant', fn ($q) => $q
                    ->withoutGlobalScope('tenant')
                    ->whereIn('status', ['active', 'trial']))
                ->get();

            foreach ($users as $user) {
                $tenant = Tenant::withoutGlobalScope('tenant')->find($user->tenant_id);
                if (!$tenant) continue;

                $vars = $this->buildVariables($user, $tenant);

                // Email
                $emailTemplate = ReengagementTemplate::where('stage', $s['stage'])
                    ->where('channel', 'email')
                    ->where('is_active', true)
                    ->first();

                if ($emailTemplate) {
                    try {
                        Mail::to($user->email)->queue(
                            new ReengagementEmail($user, $tenant, $emailTemplate, $vars)
                        );
                        $totalEmail++;
                    } catch (\Throwable $e) {
                        Log::warning('Reengagement email failed', [
                            'user_id' => $user->id,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                }

                // WhatsApp (via tenant_12 official instance)
                if ($user->phone) {
                    $waTemplate = ReengagementTemplate::where('stage', $s['stage'])
                        ->where('channel', 'whatsapp')
                        ->where('is_active', true)
                        ->first();

                    if ($waTemplate) {
                        try {
                            $message = $waTemplate->render($vars);
                            $chatId  = preg_replace('/\D/', '', $user->phone) . '@c.us';
                            $waha    = app(WahaService::class);
                            $waha->sendText(self::WA_SESSION, $chatId, $message);
                            $totalWa++;
                        } catch (\Throwable $e) {
                            Log::warning('Reengagement WhatsApp failed', [
                                'user_id' => $user->id,
                                'error'   => $e->getMessage(),
                            ]);
                        }
                    }
                }

                $user->update([
                    'last_reengagement_sent_at' => now(),
                    'reengagement_stage'        => $s['stage'],
                ]);
            }
        }

        $this->info("Sent {$totalEmail} emails, {$totalWa} WhatsApp messages.");
        Log::info("Reengagement: {$totalEmail} emails, {$totalWa} WA sent.");

        return self::SUCCESS;
    }

    /**
     * Build all available template variables for a user.
     */
    private function buildVariables(User $user, Tenant $tenant): array
    {
        $baseUrl = config('app.url', 'https://app.syncro.chat');

        // Leads stats (scoped to tenant)
        $leadsTotal = Lead::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->count();

        $leadsSemContato = Lead::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->where('updated_at', '<', now()->subDays(5))
            ->count();

        $leadsNovosSemana = Lead::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // Conversations
        $conversasAbertas = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'open')
            ->count()
            + InstagramConversation::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('status', 'open')
                ->count();

        // Tasks
        $tarefasPendentes = Task::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->count();

        // Sales
        $vendasMes = Sale::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('closed_at', '>=', now()->startOfMonth())
            ->count();

        $diasSemLogin = $user->last_login_at
            ? (int) now()->diffInDays($user->last_login_at)
            : 0;

        return [
            '{{nome}}'               => $user->name,
            '{{empresa}}'            => $tenant->name,
            '{{dias_sem_login}}'     => $diasSemLogin,
            '{{leads_total}}'        => $leadsTotal,
            '{{leads_sem_contato}}'  => $leadsSemContato,
            '{{leads_novos_semana}}' => $leadsNovosSemana,
            '{{conversas_abertas}}'  => $conversasAbertas,
            '{{tarefas_pendentes}}'  => $tarefasPendentes,
            '{{vendas_mes}}'         => $vendasMes,
            '{{link_crm}}'           => $baseUrl,
            '{{link_leads}}'         => $baseUrl . '/contatos',
            '{{link_chats}}'         => $baseUrl . '/chats',
        ];
    }

    /**
     * Send test message to a specific email/phone with mock data.
     */
    private function sendTest(): int
    {
        $stage = $this->option('test-stage') ?: '7d';

        $mockVars = [
            '{{nome}}'               => 'Matheus',
            '{{empresa}}'            => 'Syncro Demo',
            '{{dias_sem_login}}'     => $stage === '7d' ? 8 : ($stage === '14d' ? 15 : 32),
            '{{leads_total}}'        => 47,
            '{{leads_sem_contato}}'  => 12,
            '{{leads_novos_semana}}' => 5,
            '{{conversas_abertas}}'  => 8,
            '{{tarefas_pendentes}}'  => 3,
            '{{vendas_mes}}'         => 6,
            '{{link_crm}}'           => 'https://app.syncro.chat',
            '{{link_leads}}'         => 'https://app.syncro.chat/contatos',
            '{{link_chats}}'         => 'https://app.syncro.chat/chats',
        ];

        if ($email = $this->option('test-email')) {
            $template = ReengagementTemplate::where('stage', $stage)
                ->where('channel', 'email')->first();

            if (!$template) {
                $this->error("No email template for stage {$stage}. Run the seeder first.");
                return self::FAILURE;
            }

            $mockUser   = new User(['name' => 'Matheus', 'email' => $email]);
            $mockTenant = new Tenant(['name' => 'Syncro Demo']);

            Mail::to($email)->send(new ReengagementEmail($mockUser, $mockTenant, $template, $mockVars));
            $this->info("Test email sent to {$email} (stage: {$stage})");
        }

        if ($phone = $this->option('test-phone')) {
            $template = ReengagementTemplate::where('stage', $stage)
                ->where('channel', 'whatsapp')->first();

            if (!$template) {
                $this->error("No WhatsApp template for stage {$stage}. Run the seeder first.");
                return self::FAILURE;
            }

            $message = $template->render($mockVars);
            $chatId  = preg_replace('/\D/', '', $phone) . '@c.us';
            $waha    = app(WahaService::class);
            $waha->sendText(self::WA_SESSION, $chatId, $message);
            $this->info("Test WhatsApp sent to {$phone} (stage: {$stage})");
        }

        return self::SUCCESS;
    }
}
