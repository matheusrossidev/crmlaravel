<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Lead;
use App\Models\LeadEvent;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KanbanImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private int $imported = 0;
    private int $skipped  = 0;

    /**
     * @param Collection<string, int> $stagesByName  lowercase stage name => stage_id
     */
    public function __construct(
        private readonly int        $pipelineId,
        private readonly int        $defaultStageId,
        private readonly Collection $stagesByName,
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = trim((string) ($row['nome'] ?? $row['name'] ?? ''));
            if (!$name) {
                $this->skipped++;
                continue;
            }

            // Resolve stage by name (case-insensitive)
            $stageName = mb_strtolower(trim((string) ($row['etapa'] ?? $row['stage'] ?? '')));
            $stageId   = $stageName
                ? ($this->stagesByName->get($stageName) ?? $this->defaultStageId)
                : $this->defaultStageId;

            // Parse value (accepts "1500", "1.500,00", "1500.00")
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
                'notes'       => trim((string) ($row['notas'] ?? $row['notes'] ?? '')),
                'pipeline_id' => $this->pipelineId,
                'stage_id'    => $stageId,
                'created_by'  => auth()->id(),
            ]);

            LeadEvent::create([
                'lead_id'      => $lead->id,
                'event_type'   => 'created',
                'description'  => 'Lead importado via planilha (Kanban)',
                'performed_by' => auth()->id(),
                'created_at'   => now(),
            ]);

            $this->imported++;
        }
    }

    public function getImported(): int { return $this->imported; }
    public function getSkipped(): int  { return $this->skipped; }
}
