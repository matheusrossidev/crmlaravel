<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
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

    /** @var array<string, CustomFieldDefinition> header_key => definition */
    private array $customFieldMap = [];

    public function __construct(?int $maxRemaining = null)
    {
        $pipeline = Pipeline::where('is_default', true)->first() ?? Pipeline::first();
        $this->defaultPipelineId = $pipeline?->id;
        $this->defaultStageId    = $pipeline?->stages()->orderBy('position')->first()?->id;
        $this->maxRemaining      = $maxRemaining;
        $this->detector          = new DuplicateLeadDetector();

        // Build map of custom fields: normalized header → definition
        $defs = CustomFieldDefinition::where('is_active', true)->get();
        foreach ($defs as $def) {
            // Match by name (slug) or label (display name), normalized to lowercase
            $this->customFieldMap[mb_strtolower($def->name)] = $def;
            $this->customFieldMap[mb_strtolower($def->label)] = $def;
        }
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

            // Save custom field values from extra columns
            $standardKeys = ['nome', 'name', 'telefone', 'phone', 'email', 'valor', 'value', 'origem', 'source'];
            foreach ($row as $header => $cellValue) {
                $headerKey = mb_strtolower(trim((string) $header));
                if (in_array($headerKey, $standardKeys, true) || $cellValue === null || trim((string) $cellValue) === '') {
                    continue;
                }
                $def = $this->customFieldMap[$headerKey] ?? null;
                if (!$def) continue;

                $cfData = ['tenant_id' => auth()->user()->tenant_id, 'lead_id' => $lead->id, 'field_id' => $def->id];
                $val = trim((string) $cellValue);

                match ($def->field_type) {
                    'number', 'currency' => CustomFieldValue::create(array_merge($cfData, [
                        'value_number' => is_numeric(str_replace(['.', ','], ['', '.'], $val)) ? (float) str_replace(['.', ','], ['', '.'], $val) : null,
                    ])),
                    'date' => CustomFieldValue::create(array_merge($cfData, [
                        'value_date' => \Carbon\Carbon::parse($val)->format('Y-m-d'),
                    ])),
                    'checkbox' => CustomFieldValue::create(array_merge($cfData, [
                        'value_boolean' => in_array(mb_strtolower($val), ['sim', 'yes', '1', 'true', 'x'], true),
                    ])),
                    'multiselect' => CustomFieldValue::create(array_merge($cfData, [
                        'value_json' => array_map('trim', explode(',', $val)),
                    ])),
                    default => CustomFieldValue::create(array_merge($cfData, [
                        'value_text' => $val,
                    ])),
                };
            }

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
