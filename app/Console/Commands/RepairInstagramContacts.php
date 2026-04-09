<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InstagramConversation;
use App\Models\InstagramInstance;
use App\Models\InstagramMessage;
use App\Services\InstagramService;
use Illuminate\Console\Command;

/**
 * Re-busca username via Graph API pra conversas Instagram que estao com
 * contact_username null. Usa o endpoint GET /{message_id}?fields=from
 * (unica forma documentada no fluxo Instagram API with Instagram Login).
 *
 * O endpoint GET /{IGSID} (que retornaria name+profile_pic) NAO funciona
 * nesse fluxo — Meta retorna erro 100/33 "does not support this operation".
 * Confirmado em prod 08/04/2026.
 *
 * Limitacao: name (display name) e profile_pic NAO sao disponiveis nesse
 * fluxo. UI usa @username como label e avatar fica fallback de letra.
 *
 *   php artisan instagram:repair-contacts --dry-run
 *   php artisan instagram:repair-contacts
 *   php artisan instagram:repair-contacts --tenant=12
 */
class RepairInstagramContacts extends Command
{
    protected $signature = 'instagram:repair-contacts {--tenant= : Limita a um tenant_id especifico} {--dry-run : Nao escreve nada, so reporta o que faria}';

    protected $description = 'Re-busca username via Graph API pra conversas Instagram que estao com contact_username null.';

    public function handle(): int
    {
        $tenant = $this->option('tenant');
        $dry    = (bool) $this->option('dry-run');

        if ($dry) {
            $this->warn('=== DRY RUN — nenhuma escrita sera feita ===');
        }

        // So conversations sem username E que tem pelo menos uma mensagem
        // inbound real (com mid valido). Conversations sem mensagem inbound
        // nao tem como ter username obtido — fica skipped.
        $query = InstagramConversation::withoutGlobalScope('tenant')
            ->where(function ($q) {
                $q->whereNull('contact_name')
                  ->orWhereNull('contact_username');
            })
            ->whereHas('messages', fn ($q) => $q->where('direction', 'inbound')->whereNotNull('ig_message_id'));

        if ($tenant) {
            $query->where('tenant_id', $tenant);
        }

        $total = $query->count();
        $this->info("Conversas a reparar: {$total}");

        if ($total === 0) {
            return self::SUCCESS;
        }

        $fixed   = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($query->cursor() as $conv) {
            $instance = InstagramInstance::withoutGlobalScope('tenant')->find($conv->instance_id);
            if (! $instance || ! $instance->access_token) {
                $this->warn("conv #{$conv->id}: instance #{$conv->instance_id} sem token — skip");
                $skipped++;
                continue;
            }

            // Busca a ultima mensagem inbound real (com mid) dessa conversa
            $lastMsg = InstagramMessage::withoutGlobalScope('tenant')
                ->where('conversation_id', $conv->id)
                ->where('direction', 'inbound')
                ->whereNotNull('ig_message_id')
                ->latest('sent_at')
                ->first();

            if (! $lastMsg) {
                $skipped++;
                continue;
            }

            try {
                $token   = decrypt($instance->access_token);
                $service = new InstagramService($token);
                $result  = $service->getMessageSender($lastMsg->ig_message_id);

                if (! empty($result['error'])) {
                    $this->warn("conv #{$conv->id} mid={$lastMsg->ig_message_id}: API err " . ($result['status'] ?? '?'));
                    $failed++;
                    continue;
                }

                $username = $result['from']['username'] ?? null;
                if (! $username) {
                    $skipped++;
                    continue;
                }

                $update = [];
                if (! $conv->contact_username) {
                    $update['contact_username'] = $username;
                }
                if (! $conv->contact_name) {
                    $update['contact_name'] = $username; // fallback display name
                }

                if (! empty($update)) {
                    if ($dry) {
                        $this->line("[DRY] conv #{$conv->id} igsid={$conv->igsid}: " . json_encode($update, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    } else {
                        $conv->update($update);
                        $this->line("conv #{$conv->id}: " . $username);
                    }
                    $fixed++;
                } else {
                    $skipped++;
                }

                // Rate limit defensivo: Graph API permite ~200 reqs/hora por user.
                // 300ms = ~3 reqs/seg, bem abaixo do limite.
                usleep(300000);
            } catch (\Throwable $e) {
                $this->error("conv #{$conv->id} igsid={$conv->igsid}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("");
        $this->info("=== Resumo ===");
        $this->info("Reparadas: {$fixed}");
        $this->info("Skipped:   {$skipped}");
        $this->info("Falhas:    {$failed}");

        return self::SUCCESS;
    }
}
