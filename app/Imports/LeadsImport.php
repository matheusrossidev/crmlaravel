<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Lead;
use App\Models\LeadDuplicate;
use App\Models\LeadEvent;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Services\DuplicateLeadDetector;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class LeadsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private int $imported = 0;
    private int $skipped  = 0;
    private int $limitSkipped = 0;
    private int $duplicatesFound = 0;

    private ?int $defaultPipelineId;
    private ?int $defaultStageId;
    private ?int $maxRemaining;
    private DuplicateLeadDetector $detector;

    public function __construct(?int $maxRemaining = null)
    {
        $pipeline = Pipeline::where('is_default', true)->first() ?? Pipeline::first();
        $this->defaultPipelineId = $pipeline?->id;
        $this->defaultStageId    = $pipeline?->stages()->orderBy('position')->first()?->id;
        $this->maxRemaining      = $maxRemaining;
        $this->detector          = new DuplicateLeadDetector();
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = trim((string) ($row['nome'] ?? $row['name'] ?? ''));
            if (!$name) {
                $this->skipped++;
                continue;
            }

            if ($this->maxRemaining !== null && $this->imported >= $this->maxRemaining) {
                $this->limitSkipped++;
                continue;
            }

            $valueRaw = $row['valor'] ?? $row['value'] ?? null;
            $value    = null;
            if ($valueRaw !== null && $valueRaw !== '') {
                $clean = str_replace(['.', ','], ['', '.'], (string) $valueRaw);
                $value = is_numeric($clean) ? (float) $clean : null;
            }

            $rowData = [
                'name'  => $name,
                'phone' => trim((string) ($row['telefone'] ?? $row['phone'] ?? '')),
                'email' => strtolower(trim((string) ($row['email'] ?? ''))),
            ];

            // Detect duplicates and register them (but still import)
            $duplicates = $this->detector->findDuplicatesFromData($rowData, auth()->user()->tenant_id);
            $hasDuplicate = $duplicates->filter(fn ($d) => $d['score'] >= 70)->isNotEmpty();

            $lead = Lead::create([
                'name'        => $name,
                'phone'       => $rowData['phone'],
                'email'       => $rowData['email'],
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
                'created_at'   => now(),
            ]);

            // Register duplicate pairs for review
            if ($hasDuplicate) {
                $this->duplicatesFound++;
                foreach ($duplicates->filter(fn ($d) => $d['score'] >= 40) as $dup) {
                    $idA = min($lead->id, $dup['lead']->id);
                    $idB = max($lead->id, $dup['lead']->id);
                    LeadDuplicate::firstOrCreate(
                        ['lead_id_a' => $idA, 'lead_id_b' => $idB],
                        [
                            'tenant_id'   => auth()->user()->tenant_id,
                            'score'       => $dup['score'],
                            'status'      => 'pending',
                            'detected_by' => 'import',
                            'created_at'  => now(),
                        ]
                    );
                }
            }

            $this->imported++;
        }
    }

    public function getImported(): int        { return $this->imported; }
    public function getSkipped(): int         { return $this->skipped; }
    public function getLimitSkipped(): int     { return $this->limitSkipped; }
    public function getDuplicatesFound(): int  { return $this->duplicatesFound; }
}
