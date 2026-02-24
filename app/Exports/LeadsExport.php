<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Lead;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LeadsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private readonly array $filters = []
    ) {}

    public function query()
    {
        $query = Lead::with(['stage', 'pipeline', 'campaign'])
            ->orderByDesc('created_at');

        if (!empty($this->filters['search'])) {
            $s = $this->filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        if (!empty($this->filters['stage_id'])) {
            $query->where('stage_id', $this->filters['stage_id']);
        }

        if (!empty($this->filters['source'])) {
            $query->where('source', $this->filters['source']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        if (!empty($this->filters['tag'])) {
            $query->whereJsonContains('tags', $this->filters['tag']);
        }

        if (!empty($this->filters['pipeline_id'])) {
            $query->where('pipeline_id', $this->filters['pipeline_id']);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Nome', 'Telefone', 'E-mail', 'Valor', 'Origem', 'Tags', 'Pipeline', 'Etapa', 'Campanha', 'Notas', 'Criado em', 'Convertido em'];
    }

    public function map($lead): array
    {
        return [
            $lead->name,
            $lead->phone ?? '',
            $lead->email ?? '',
            $lead->value ? number_format((float) $lead->value, 2, ',', '.') : '',
            $lead->source ?? '',
            is_array($lead->tags) ? implode(', ', $lead->tags) : '',
            $lead->pipeline?->name ?? '',
            $lead->stage?->name ?? '',
            $lead->campaign?->name ?? '',
            $lead->notes ?? '',
            $lead->created_at?->format('d/m/Y H:i'),
            $lead->converted_at?->format('d/m/Y H:i') ?? '',
        ];
    }
}
