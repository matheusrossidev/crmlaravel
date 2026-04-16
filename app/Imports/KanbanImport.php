<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
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

    public function __construct(
        private readonly int        $pipelineId,
        private readonly int        $defaultStageId,
        private readonly Collection $stagesByName,
        private readonly ?Pipeline  $pipeline = null,
        private readonly array      $headerToField = [],
        private readonly array      $overrides = [],
    ) {}

    private function field(mixed $row, string $crmField, array $defaults): string
    {
        if (! empty($this->headerToField)) {
            foreach ($this->headerToField as $slug => $mapped) {
                if ($mapped === $crmField && isset($row[$slug])) {
                    return trim((string) $row[$slug]);
                }
            }
            return '';
        }
        foreach ($defaults as $k) {
            if (isset($row[$k]) && trim((string) $row[$k]) !== '') {
                return trim((string) $row[$k]);
            }
        }
        return '';
    }

    private function fieldRaw(mixed $row, string $crmField, array $defaults): mixed
    {
        if (! empty($this->headerToField)) {
            foreach ($this->headerToField as $slug => $mapped) {
                if ($mapped === $crmField && isset($row[$slug])) {
                    return $row[$slug];
                }
            }
            return null;
        }
        foreach ($defaults as $k) {
            if (isset($row[$k])) return $row[$k];
        }
        return null;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $ov = $this->overrides[$index] ?? [];

            if (! empty($ov['skip'])) {
                $this->skipped++;
                continue;
            }

            $name = $this->field($row, 'nome', ['nome', 'name']);
            if (! $name) {
                $this->skipped++;
                continue;
            }

            // Stage: override > mapping > default
            $stageId = $this->defaultStageId;
            if (! empty($ov['stage_id'])) {
                $stageId = (int) $ov['stage_id'];
            } else {
                $stageName = mb_strtolower($this->field($row, 'etapa', ['etapa', 'stage']));
                if ($stageName && $this->stagesByName->has($stageName)) {
                    $stageId = $this->stagesByName->get($stageName);
                }
            }

            // Value
            $valueRaw = $this->fieldRaw($row, 'valor', ['valor', 'value']);
            $value    = null;
            if ($valueRaw !== null && $valueRaw !== '') {
                $clean = str_replace(['.', ','], ['', '.'], (string) $valueRaw);
                $value = is_numeric($clean) ? (float) $clean : null;
            }

            // Tags: override merge
            $tagsStr = $this->field($row, 'tags', ['tags', 'etiquetas']);
            $tags    = $tagsStr !== '' ? array_values(array_filter(array_map('trim', explode(',', $tagsStr)))) : [];
            if (! empty($ov['tags'])) {
                $extra = is_array($ov['tags']) ? $ov['tags'] : explode(',', $ov['tags']);
                $tags  = array_values(array_unique(array_merge($tags, array_map('trim', $extra))));
            }

            // Source: override > mapping > fallback
            $source = $this->field($row, 'origem', ['origem', 'source']) ?: 'importado';
            if (! empty($ov['source'])) {
                $source = $ov['source'];
            }

            $createdAt = $this->parseDate($this->fieldRaw($row, 'criado_em', ['criado_em', 'created_at']));

            $lead = Lead::create([
                'name'        => $name,
                'phone'       => $this->field($row, 'telefone', ['telefone', 'phone']),
                'email'       => strtolower($this->field($row, 'email', ['email'])),
                'company'     => $this->field($row, 'empresa', ['empresa', 'company']) ?: null,
                'value'       => $value,
                'source'      => $source,
                'notes'       => $this->field($row, 'notas', ['notas', 'notes']) ?: null,
                'tags'        => $tags ?: null,
                'pipeline_id' => $this->pipelineId,
                'stage_id'    => $stageId,
                'created_by'  => auth()->id(),
            ]);

            if (! empty($tags)) {
                $lead->attachTagsByName($tags);
            }

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

            if ($this->pipeline !== null) {
                $resolvedStage = $this->pipeline->stages->firstWhere('id', $stageId);
                if ($resolvedStage?->is_lost) {
                    LostSale::create([
                        'lead_id'     => $lead->id,
                        'pipeline_id' => $this->pipelineId,
                        'reason_id'   => null,
                        'lost_at'     => $createdAt ?? now(),
                        'lost_by'     => auth()->id(),
                    ]);
                }
            }

            $this->saveCustomFields($lead, $row);

            $this->imported++;
        }
    }

    private function saveCustomFields(Lead $lead, mixed $row): void
    {
        if (empty($this->headerToField)) return;
        $tenantId = auth()->user()->tenant_id ?? null;
        if (! $tenantId) return;

        foreach ($this->headerToField as $slug => $mapped) {
            if (! str_starts_with($mapped, 'custom:')) continue;
            $cfId = (int) str_replace('custom:', '', $mapped);
            $val  = isset($row[$slug]) ? trim((string) $row[$slug]) : '';
            if ($val === '') continue;

            $def = CustomFieldDefinition::find($cfId);
            if (! $def) continue;

            $base = ['tenant_id' => $tenantId, 'lead_id' => $lead->id, 'field_id' => $def->id];
            match ($def->field_type) {
                'number', 'currency' => CustomFieldValue::create(array_merge($base, [
                    'value_number' => is_numeric(str_replace(['.', ','], ['', '.'], $val)) ? (float) str_replace(['.', ','], ['', '.'], $val) : null,
                ])),
                'date' => CustomFieldValue::create(array_merge($base, ['value_date' => Carbon::parse($val)->format('Y-m-d')])),
                'checkbox' => CustomFieldValue::create(array_merge($base, ['value_boolean' => in_array(mb_strtolower($val), ['sim', 'yes', '1', 'true', 'x'], true)])),
                'multiselect' => CustomFieldValue::create(array_merge($base, ['value_json' => array_map('trim', explode(',', $val))])),
                default => CustomFieldValue::create(array_merge($base, ['value_text' => $val])),
            };
        }
    }

    private function parseDate(mixed $raw): ?Carbon
    {
        if ($raw === null || $raw === '') return null;
        try {
            if (is_numeric($raw)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw));
            }
            $str = trim((string) $raw);
            if (preg_match('/^\d{1,2}\/\d{1,2}\/(\d{4}|\d{2})$/', $str)) {
                $parts = explode('/', $str);
                return Carbon::createFromFormat(strlen($parts[2]) === 4 ? 'd/m/Y' : 'd/m/y', $str);
            }
            return Carbon::parse($str);
        } catch (\Exception) {
            return null;
        }
    }

    public function getImported(): int { return $this->imported; }
    public function getSkipped(): int  { return $this->skipped; }
}
