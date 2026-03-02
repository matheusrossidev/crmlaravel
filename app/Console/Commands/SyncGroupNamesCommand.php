<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Services\WahaService;
use Illuminate\Console\Command;

class SyncGroupNamesCommand extends Command
{
    protected $signature   = 'wa:sync-group-names {--all : Atualizar todos os grupos, mesmo os que já têm nome}';
    protected $description = 'Busca e atualiza o nome dos grupos WhatsApp nas conversas existentes';

    public function handle(): int
    {
        // withoutGlobalScope para ignorar filtro de tenant e buscar todas as instâncias
        $instances = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('status', 'connected')
            ->get();

        if ($instances->isEmpty()) {
            $this->error('Nenhuma instância WhatsApp conectada encontrada.');
            return self::FAILURE;
        }

        $this->info("Instâncias conectadas: {$instances->count()}");

        $updated = 0;
        $errors  = 0;

        foreach ($instances as $instance) {
            $this->line("\n[Tenant {$instance->tenant_id}] Sessão: {$instance->session_name}");

            $query = WhatsappConversation::withoutGlobalScope('tenant')
                ->where('is_group', true)
                ->where('tenant_id', $instance->tenant_id);

            if (! $this->option('all')) {
                $query->where(function ($q) {
                    $q->whereNull('contact_name')->orWhere('contact_name', '');
                });
            }

            $conversations = $query->get();

            if ($conversations->isEmpty()) {
                $this->info('  Nenhum grupo para atualizar neste tenant.');
                continue;
            }

            $this->info("  {$conversations->count()} grupo(s) para atualizar...");

            $waha = new WahaService($instance->session_name);

            foreach ($conversations as $conv) {
                try {
                    $jid  = str_contains($conv->phone, '@') ? $conv->phone : $conv->phone . '@g.us';
                    $info = $waha->getGroupInfo($jid);
                    $name = $info['Name'] ?? $info['subject'] ?? $info['name'] ?? null;

                    if ($name) {
                        $conv->update(['contact_name' => $name]);
                        $this->line("  ✓ {$conv->phone} → {$name}");
                        $updated++;
                    } else {
                        $this->warn("  ? {$conv->phone} — sem nome na resposta");
                    }
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$conv->phone} — {$e->getMessage()}");
                    $errors++;
                }
            }
        }

        $this->info("\nConcluído: {$updated} atualizado(s), {$errors} erro(s).");
        return self::SUCCESS;
    }
}
