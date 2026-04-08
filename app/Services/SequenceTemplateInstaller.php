<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NurtureSequence;
use App\Models\NurtureSequenceStep;
use App\Support\SequenceTemplates;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Instala um template de Nurture Sequence para um tenant.
 *
 * Usa transação porque precisa criar a sequence + N steps atomicamente.
 * Se qualquer step falhar, faz rollback total (não deixa sequência "meio criada").
 *
 * Idempotente: se já existe sequência com mesmo nome no tenant, retorna a
 * existente sem duplicar.
 */
class SequenceTemplateInstaller
{
    /**
     * @throws \RuntimeException Quando o slug não existe no catálogo
     */
    public function install(int $tenantId, string $slug): NurtureSequence
    {
        $template = SequenceTemplates::find($slug);
        if (! $template) {
            throw new \RuntimeException("Template de sequência não encontrado: {$slug}");
        }

        $sequence = $template['sequence'];
        $name = (string) $sequence['name'];

        $existing = NurtureSequence::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        if ($existing) {
            Log::info('SequenceTemplateInstaller: já existe, retornando existente', [
                'tenant_id'   => $tenantId,
                'slug'        => $slug,
                'sequence_id' => $existing->id,
            ]);
            return $existing;
        }

        // Transação: cria sequence + steps atomicamente
        return DB::transaction(function () use ($tenantId, $sequence, $template, $name) {
            $seq = NurtureSequence::create([
                'tenant_id'            => $tenantId,
                'name'                 => $name,
                'description'          => $sequence['description'] ?? null,
                'channel'              => $sequence['channel'] ?? 'whatsapp',
                'is_active'            => true,
                'exit_on_reply'        => $sequence['exit_on_reply'] ?? true,
                'exit_on_stage_change' => $sequence['exit_on_stage_change'] ?? false,
            ]);

            foreach (($template['steps'] ?? []) as $step) {
                NurtureSequenceStep::create([
                    'sequence_id'   => $seq->id,
                    'position'      => (int) $step['position'],
                    'delay_minutes' => (int) ($step['delay_minutes'] ?? 0),
                    'type'          => (string) $step['type'],
                    'config'        => $step['config'] ?? [],
                    'is_active'     => true,
                ]);
            }

            return $seq->fresh('steps');
        });
    }
}
