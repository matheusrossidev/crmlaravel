<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class LeadsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private int $imported = 0;
    private int $skipped  = 0;

    private ?int $defaultPipelineId;
    private ?int $defaultStageId;

    public function __construct()
    {
        $pipeline = Pipeline::where('is_default', true)->first() ?? Pipeline::first();
        $this->defaultPipelineId = $pipeline?->id;
        $this->defaultStageId    = $pipeline?->stages()->orderBy('position')->first()?->id;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = trim((string) ($row['nome'] ?? $row['name'] ?? ''));
            if (!$name) {
                $this->skipped++;
                continue;
            }

            $valueRaw = $row['valor'] ?? $row['value'] ?? null;
            $value    = null;
            if ($valueRaw !== null && $valueRaw !== '') {
                $clean = str_replace(['.', ','], ['', '.'], (string) $valueRaw);
                $value = is_numeric($clean) ? (float) $clean : null;
            }

            $lead = Lead::create([
                'name'        => $name,
                'phone'       => trim((string) ($row['telefone'] ?? $row['phone'] ?? '')),
                'email'       => strtolower(trim((string) ($row['email'] ?? ''))),
                'value'       => $value,
                'source'      => trim((string) ($row['origem'] ?? $row['source'] ?? 'importado')),
                'pipeline_id' => $this->defaultPipelineId,
                'stage_id'    => $this->defaultStageId,
                'created_by'  => auth()->id(),
            ]);

            LeadEvent::create([
                'lead_id'      => $lead->id,
                'event_type'   => 'created',
                'description'  => 'Lead importado via Excel',
                'performed_by' => auth()->id(),
            ]);

            $this->imported++;
        }
    }

    public function getImported(): int { return $this->imported; }
    public function getSkipped(): int  { return $this->skipped; }
}
