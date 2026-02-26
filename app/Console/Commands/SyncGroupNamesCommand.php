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
        $instance = WhatsappInstance::first();

        if (! $instance || $instance->status !== 'connected') {
            $this->error('Nenhuma instância WhatsApp conectada.');
            return self::FAILURE;
        }

        $query = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('is_group', true);

        if (! $this->option('all')) {
            $query->where(function ($q) {
                $q->whereNull('contact_name')->orWhere('contact_name', '');
            });
        }

        $conversations = $query->get();

        if ($conversations->isEmpty()) {
            $this->info('Nenhum grupo para atualizar.');
            return self::SUCCESS;
        }

        $this->info("Atualizando {$conversations->count()} grupo(s)...");

        $waha    = new WahaService($instance->session_name);
        $updated = 0;
        $errors  = 0;

        foreach ($conversations as $conv) {
            try {
                $info = $waha->getGroupInfo($conv->phone);
                $name = $info['subject'] ?? $info['name'] ?? null;

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

        $this->info("Concluído: {$updated} atualizado(s), {$errors} erro(s).");
        return self::SUCCESS;
    }
}
