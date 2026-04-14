<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WhatsappInstance;
use App\Notifications\WhatsappCloudTokenExpiring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Percorre todas as instâncias Cloud API e verifica a validade dos tokens
 * junto à Meta via GET /debug_token.
 *
 * Atualiza o campo `token_status` na instance:
 *   - valid    : tudo ok, expira em >7 dias
 *   - expiring : expira em <=7 dias
 *   - expired  : expirado
 *   - invalid  : Meta retornou que o token é inválido
 *
 * Dispara WhatsappCloudTokenExpiring notification pra admins do tenant
 * quando muda pra expiring/expired/invalid pela primeira vez (evita spam
 * checando o status anterior).
 *
 * Rodar manualmente pra debug:
 *   php artisan whatsapp:cloud-token-health
 *   php artisan whatsapp:cloud-token-health --dry-run
 *   php artisan whatsapp:cloud-token-health --instance=83
 */
class CheckWhatsappCloudTokens extends Command
{
    protected $signature = 'whatsapp:cloud-token-health
                            {--dry-run : não salva, só mostra o que faria}
                            {--instance= : checa apenas essa instância específica}';

    protected $description = 'Verifica validade de tokens WhatsApp Cloud API e notifica expirações';

    public function handle(): int
    {
        $query = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('provider', 'cloud_api');

        if ($this->option('instance')) {
            $query->where('id', (int) $this->option('instance'));
        }

        $instances = $query->get();

        if ($instances->isEmpty()) {
            $this->info('Nenhuma instância Cloud API encontrada.');
            return self::SUCCESS;
        }

        $this->info("Verificando {$instances->count()} instância(s)...");

        $appId     = (string) config('services.whatsapp_cloud.app_id');
        $appSecret = (string) config('services.whatsapp_cloud.app_secret');

        if (! $appId || ! $appSecret) {
            $this->error('WHATSAPP_CLOUD_APP_ID ou WHATSAPP_CLOUD_APP_SECRET não configurados.');
            return self::FAILURE;
        }

        // App Access Token = app_id|app_secret (formato aceito pela Meta em debug_token)
        $appAccessToken = $appId . '|' . $appSecret;
        $version        = (string) config('services.whatsapp_cloud.api_version', 'v22.0');

        $stats = ['valid' => 0, 'expiring' => 0, 'expired' => 0, 'invalid' => 0, 'errors' => 0];

        foreach ($instances as $instance) {
            $result = $this->checkInstance($instance, $appAccessToken, $version);
            $stats[$result]++;

            $this->line(sprintf(
                '  #%d (tenant %d, %s) → %s',
                $instance->id,
                $instance->tenant_id,
                $instance->phone_number ?: $instance->phone_number_id,
                $result,
            ));
        }

        $this->newLine();
        $this->info(sprintf(
            'Resumo: %d valid, %d expiring, %d expired, %d invalid, %d errors',
            $stats['valid'],
            $stats['expiring'],
            $stats['expired'],
            $stats['invalid'],
            $stats['errors'],
        ));

        return self::SUCCESS;
    }

    /**
     * Checa uma instância e retorna o status resultante.
     */
    private function checkInstance(WhatsappInstance $instance, string $appToken, string $version): string
    {
        // Escolhe qual token checar:
        //   1. Se tem system_user_token na instance, checa ele (ideal)
        //   2. Senão, checa o access_token do user (que é o que pode expirar)
        //   3. Se nenhum dos dois, marca como invalid
        $tokenToCheck = $instance->system_user_token ?: $instance->access_token;

        if (! $tokenToCheck) {
            $this->updateStatus($instance, 'invalid', null);
            return 'invalid';
        }

        try {
            $res = Http::timeout(15)
                ->get("https://graph.facebook.com/{$version}/debug_token", [
                    'input_token'  => $tokenToCheck,
                    'access_token' => $appToken,
                ]);

            if (! $res->successful()) {
                $stats['errors'] ?? null;
                Log::warning('TokenHealth: debug_token failed', [
                    'instance_id' => $instance->id,
                    'status'      => $res->status(),
                    'body'        => substr($res->body(), 0, 200),
                ]);
                return 'errors';
            }

            $data = (array) $res->json('data', []);
            $isValid   = (bool) ($data['is_valid'] ?? false);
            $expiresAt = (int) ($data['expires_at'] ?? 0); // unix timestamp, 0 = never

            if (! $isValid) {
                $this->updateStatus($instance, 'invalid', null);
                $this->notifyIfFirstTime($instance, 'invalid');
                return 'invalid';
            }

            // expires_at = 0 significa "nunca" (system user token permanente)
            if ($expiresAt === 0) {
                $this->updateStatus($instance, 'valid', null);
                return 'valid';
            }

            $now          = time();
            $secondsLeft  = $expiresAt - $now;
            $daysLeft     = (int) floor($secondsLeft / 86400);

            if ($secondsLeft <= 0) {
                $status = 'expired';
            } elseif ($daysLeft <= 7) {
                $status = 'expiring';
            } else {
                $status = 'valid';
            }

            $this->updateStatus($instance, $status, now()->setTimestamp($expiresAt));

            if (in_array($status, ['expiring', 'expired'], true)) {
                $this->notifyIfFirstTime($instance, $status, $daysLeft);
            }

            return $status;
        } catch (\Throwable $e) {
            Log::warning('TokenHealth: exception checking instance', [
                'instance_id' => $instance->id,
                'error'       => $e->getMessage(),
            ]);
            return 'errors';
        }
    }

    /**
     * Atualiza os campos do banco (respeitando --dry-run).
     */
    private function updateStatus(
        WhatsappInstance $instance,
        string $status,
        ?\Carbon\Carbon $expiresAt,
    ): void {
        if ($this->option('dry-run')) {
            return;
        }

        $data = [
            'token_status'          => $status,
            'token_last_checked_at' => now(),
        ];

        if ($expiresAt !== null) {
            $data['token_expires_at'] = $expiresAt;
        }

        // updateQuietly pra não disparar observer (evita loops + invalidação desnecessária)
        $instance->updateQuietly($data);
    }

    /**
     * Dispara notification pros admins do tenant, mas só se o status
     * mudou pra expiring/expired/invalid AGORA (evita spam diário).
     */
    private function notifyIfFirstTime(
        WhatsappInstance $instance,
        string $newStatus,
        int $daysLeft = 0,
    ): void {
        if ($this->option('dry-run')) {
            return;
        }

        // Se já estava no mesmo status antes, não notifica de novo
        $previousStatus = $instance->getOriginal('token_status');
        if ($previousStatus === $newStatus) {
            return;
        }

        $admins = User::where('tenant_id', $instance->tenant_id)
            ->whereIn('role', ['admin', 'manager'])
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        foreach ($admins as $admin) {
            try {
                $admin->notify(new WhatsappCloudTokenExpiring($instance, $newStatus, $daysLeft));
            } catch (\Throwable $e) {
                Log::warning('TokenHealth: notify failed', [
                    'admin_id' => $admin->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        $this->line("    → notificados {$admins->count()} admin(s) do tenant {$instance->tenant_id}");
    }
}
