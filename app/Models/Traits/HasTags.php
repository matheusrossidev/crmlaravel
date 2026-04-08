<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Trait HasTags
 *
 * Adiciona suporte a tags polimorficas via tabela pivot `taggables`.
 *
 * IMPORTANTE: a relacao chama `tagModels()` (e nao `tags()`) pra evitar
 * conflito com a coluna JSON `tags` que ainda existe nos models durante
 * a fase de coexistencia. Sera renomeada pra `tags()` na Fase 5 do refactor,
 * quando a coluna JSON for dropada.
 */
trait HasTags
{
    public function tagModels(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Anexa tags por nome (cria tags inexistentes no catalogo).
     * Nao remove tags existentes que nao estao na lista.
     */
    public function attachTagsByName(array $names): void
    {
        $ids = $this->resolveTagIdsByName($names);
        if (! empty($ids)) {
            $this->tagModels()->syncWithoutDetaching($ids);
        }
    }

    /**
     * Substitui o set inteiro de tags (sync) — adiciona as novas e remove as ausentes.
     */
    public function syncTagsByName(array $names): void
    {
        $ids = $this->resolveTagIdsByName($names);
        $this->tagModels()->sync($ids);
    }

    /**
     * Remove tags por nome.
     */
    public function detachTagsByName(array $names): void
    {
        $clean = array_values(array_unique(array_filter(array_map('trim', $names))));
        if (empty($clean)) {
            return;
        }
        $ids = Tag::where('tenant_id', $this->tenant_id)
            ->whereIn('name', $clean)
            ->pluck('id')
            ->all();
        if (! empty($ids)) {
            $this->tagModels()->detach($ids);
        }
    }

    /**
     * Accessor para retornar array de strings (compat backward com $model->tags JSON).
     * Uso: $lead->tag_names
     */
    public function getTagNamesAttribute(): array
    {
        return $this->tagModels->pluck('name')->all();
    }

    /**
     * Resolve nomes em IDs, criando tags inexistentes no catalogo.
     * Retorna array no formato esperado por sync()/syncWithoutDetaching():
     *   [tag_id => ['tenant_id' => N], ...]
     */
    protected function resolveTagIdsByName(array $names): array
    {
        $tenantId = $this->tenant_id;
        $clean = array_values(array_unique(array_filter(array_map('trim', $names))));
        $ids = [];
        foreach ($clean as $name) {
            $tag = Tag::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $name],
                ['color' => '#3B82F6', 'sort_order' => 0, 'applies_to' => 'both'],
            );
            $ids[$tag->id] = ['tenant_id' => $tenantId];
        }
        return $ids;
    }
}
