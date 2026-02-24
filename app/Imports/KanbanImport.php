<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\LostSale;
use App\Models\Pipeline;
use Carbon\Carbon;
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
        private readonly int       $pipelineId,
        private readonly int       $defaultStageId,
        private readonly Collection $stagesByName,
        private readonly ?Pipeline  $pipeline = null,
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

            // Parse tags (comma-separated)
            $tagsRaw = $row['tags'] ?? $row['etiquetas'] ?? '';
            $tags    = [];
            if (is_string($tagsRaw) && $tagsRaw !== '') {
                $tags = array_values(array_filter(array_map('trim', explode(',', $tagsRaw))));
            }

            // Parse created_at — suporta serial numérico do Excel, dd/mm/yyyy, dd/mm/yy, yyyy-mm-dd
            $createdAtRaw = $row['criado_em'] ?? $row['created_at'] ?? null;
            $createdAt    = null;
            if ($createdAtRaw !== null && $createdAtRaw !== '') {
                try {
                    if (is_numeric($createdAtRaw)) {
                        $dt        = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $createdAtRaw);
                        $createdAt = Carbon::instance($dt);
                    } elseif (preg_match('/^\d{1,2}\/\d{1,2}\/(\d{4}|\d{2})$/', trim((string) $createdAtRaw))) {
                        $parts     = explode('/', trim((string) $createdAtRaw));
                        $fmt       = strlen($parts[2]) === 4 ? 'd/m/Y' : 'd/m/y';
                        $createdAt = Carbon::createFromFormat($fmt, trim((string) $createdAtRaw));
                    } else {
                        $createdAt = Carbon::parse(trim((string) $createdAtRaw));
                    }
                } catch (\Exception) {
                    $createdAt = null;
                }
            }

            $lead = Lead::create([
                'name'        => $name,
                'phone'       => trim((string) ($row['telefone'] ?? $row['phone'] ?? '')),
                'email'       => strtolower(trim((string) ($row['email'] ?? ''))),
                'value'       => $value,
                'source'      => trim((string) ($row['origem'] ?? $row['source'] ?? 'importado')),
                'notes'       => trim((string) ($row['notas'] ?? $row['notes'] ?? '')),
                'tags'        => $tags ?: null,
                'pipeline_id' => $this->pipelineId,
                'stage_id'    => $stageId,
                'created_by'  => auth()->id(),
            ]);

            // Sobrescreve created_at com data histórica fornecida na planilha
            if ($createdAt) {
                $lead->timestamps = false;
                $lead->created_at = $createdAt;
                $lead->save();
            }

            LeadEvent::create([
                'lead_id'      => $lead->id,
                'event_type'   => 'created',
                'description'  => 'Lead importado via planilha (Kanban)',
                'performed_by' => auth()->id(),
                'created_at'   => $createdAt ?? now(),
            ]);

            // Registrar LostSale se a etapa for de perda
            if ($this->pipeline !== null) {
                $resolvedStage = $this->pipeline->stages->firstWhere('id', $stageId);
                if ($resolvedStage?->is_lost) {
                    LostSale::create([
                        'lead_id'     => $lead->id,
                        'pipeline_id' => $this->pipelineId,
                        'campaign_id' => null,
                        'reason_id'   => null,
                        'lost_at'     => $createdAt ?? now(),
                        'lost_by'     => auth()->id(),
                    ]);
                }
            }

            $this->imported++;
        }
    }

    public function getImported(): int { return $this->imported; }
    public function getSkipped(): int  { return $this->skipped; }
}
