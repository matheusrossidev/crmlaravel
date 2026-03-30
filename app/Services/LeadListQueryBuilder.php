<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadList;
use Illuminate\Database\Eloquent\Builder;

class LeadListQueryBuilder
{
    /**
     * Resolve leads for a list — returns a query Builder (not executed yet).
     */
    public function resolve(LeadList $list): Builder
    {
        if ($list->type === 'static') {
            return Lead::whereIn('leads.id', function ($q) use ($list) {
                $q->select('lead_id')
                  ->from('lead_list_members')
                  ->where('lead_list_id', $list->id);
            });
        }

        return $this->buildDynamic($list->tenant_id, $list->filters ?? []);
    }

    /**
     * Build query from raw filters (used for preview too).
     */
    public function buildFromFilters(int $tenantId, array $filters): Builder
    {
        return $this->buildDynamic($tenantId, $filters);
    }

    private function buildDynamic(int $tenantId, array $filters): Builder
    {
        $query    = Lead::where('leads.tenant_id', $tenantId);
        $operator = $filters['operator'] ?? 'AND';

        foreach ($filters['conditions'] ?? [] as $cond) {
            $field = $cond['field'] ?? '';
            $op    = $cond['op'] ?? 'eq';
            $value = $cond['value'] ?? null;

            // Resolve relative dates
            if (is_string($value) && str_starts_with($value, '-')) {
                $value = now()->modify($value)->toDateTimeString();
            }

            // Special: tag (JSON contains)
            if ($field === 'tag') {
                $method = $operator === 'AND' ? 'whereJsonContains' : 'orWhereJsonContains';
                $query->$method('tags', $value);
                continue;
            }

            // Special: has open conversation
            if ($field === 'has_open_conversation') {
                $method = $value ? 'whereHas' : 'whereDoesntHave';
                $query->$method('whatsappConversation', fn ($q) => $q->where('status', 'open'));
                continue;
            }

            // Special: is_null / not_null
            if ($op === 'is_null') {
                $method = $operator === 'AND' ? 'whereNull' : 'orWhereNull';
                $query->$method($field);
                continue;
            }
            if ($op === 'not_null') {
                $method = $operator === 'AND' ? 'whereNotNull' : 'orWhereNotNull';
                $query->$method($field);
                continue;
            }

            $where = $operator === 'AND' ? 'where' : 'orWhere';

            match ($op) {
                'eq'       => $query->$where($field, $value),
                'neq'      => $query->$where($field, '!=', $value),
                'gte'      => $query->$where($field, '>=', $value),
                'lte'      => $query->$where($field, '<=', $value),
                'contains' => $query->$where($field, 'like', "%{$value}%"),
                default    => null,
            };
        }

        return $query;
    }
}
