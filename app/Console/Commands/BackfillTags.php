<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InstagramConversation;
use App\Models\Lead;
use App\Models\Tag;
use App\Models\WhatsappConversation;
use App\Models\WhatsappTag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migra catalogo whatsapp_tags + colunas JSON `tags` (leads,
 * whatsapp_conversations, instagram_conversations) pra estrutura
 * polimorfica `tags` + `taggables`.
 *
 * Idempotente: pode rodar quantas vezes quiser, usa firstOrCreate
 * + updateOrInsert.
 *
 *   php artisan tags:backfill --dry-run
 *   php artisan tags:backfill
 *   php artisan tags:backfill --tenant=12
 */
class BackfillTags extends Command
{
    protected $signature = 'tags:backfill {--tenant= : Limita a um tenant_id especifico} {--dry-run : Nao escreve nada, so reporta o que faria}';

    protected $description = 'Migra whatsapp_tags + JSONs pra tabela polimorfica tags/taggables. Idempotente.';

    public function handle(): int
    {
        $tenantFilter = $this->option('tenant');
        $dry = (bool) $this->option('dry-run');

        if ($dry) {
            $this->warn('=== DRY RUN — nenhuma escrita sera feita ===');
        }

        // 1) Catalogo: copia whatsapp_tags -> tags (firstOrCreate por tenant_id+name)
        $this->info('=== Etapa 1: Copiando catalogo whatsapp_tags -> tags ===');
        $catalogQuery = WhatsappTag::query()->withoutGlobalScope('tenant');
        if ($tenantFilter) {
            $catalogQuery->where('tenant_id', $tenantFilter);
        }
        $catalogCount = 0;
        foreach ($catalogQuery->cursor() as $wt) {
            if ($dry) {
                $this->line("[DRY] catalog tag tenant={$wt->tenant_id} name={$wt->name}");
                $catalogCount++;
                continue;
            }
            Tag::firstOrCreate(
                ['tenant_id' => $wt->tenant_id, 'name' => $wt->name],
                [
                    'color'      => $wt->color ?: '#3B82F6',
                    'sort_order' => (int) ($wt->sort_order ?? 0),
                    'applies_to' => 'both',
                ],
            );
            $catalogCount++;
        }
        $this->info("Catalogo: {$catalogCount} tags processadas");

        // 2) Pra cada model com JSON tags, criar pivot rows
        $models = [
            Lead::class                  => 'leads',
            WhatsappConversation::class  => 'whatsapp_conversations',
            InstagramConversation::class => 'instagram_conversations',
        ];

        foreach ($models as $modelClass => $tableName) {
            $this->info("=== Etapa 2: Backfill pivot para {$tableName} ===");
            $q = $modelClass::withoutGlobalScope('tenant')->whereNotNull('tags');
            if ($tenantFilter) {
                $q->where('tenant_id', $tenantFilter);
            }

            $rowCount = 0;
            $tagCount = 0;

            foreach ($q->cursor() as $row) {
                $rawTags = $row->tags;
                if (is_string($rawTags)) {
                    $decoded = json_decode($rawTags, true);
                    $rawTags = is_array($decoded) ? $decoded : [];
                }
                $names = (array) ($rawTags ?? []);
                $names = array_values(array_unique(array_filter(array_map(
                    fn ($v) => is_scalar($v) ? trim((string) $v) : '',
                    $names,
                ))));
                if (! $names) {
                    continue;
                }
                $rowCount++;

                foreach ($names as $name) {
                    if ($dry) {
                        $tagCount++;
                        continue;
                    }
                    $tag = Tag::firstOrCreate(
                        ['tenant_id' => $row->tenant_id, 'name' => $name],
                        ['color' => '#3B82F6', 'sort_order' => 0, 'applies_to' => 'both'],
                    );
                    DB::table('taggables')->updateOrInsert(
                        [
                            'tag_id'        => $tag->id,
                            'taggable_id'   => $row->id,
                            'taggable_type' => $modelClass,
                        ],
                        [
                            'tenant_id'  => $row->tenant_id,
                            'created_at' => now(),
                        ],
                    );
                    $tagCount++;
                }
            }
            $this->info("{$tableName}: {$rowCount} rows com tags, {$tagCount} taggables criados/atualizados");
        }

        if ($dry) {
            $this->warn('=== DRY RUN concluido. Nenhuma escrita feita. ===');
        } else {
            $this->info('=== Backfill concluido com sucesso. ===');
        }

        return self::SUCCESS;
    }
}
