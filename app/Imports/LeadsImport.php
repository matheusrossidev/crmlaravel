<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\Lead;
use App\Models\LeadDuplicate;
use App\Models\LeadEvent;
use App\Models\Pipeline;
use App\Services\DuplicateLeadDetector;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

/**
 * Importação de leads via planilha.
 *
 * Aceita `$columnMapping` injetado pelo wizard do frontend:
 *   ['Cliente' => 'name', 'Celular' => 'phone', 'Orçamento' => 'value', 'Canal' => '__skip', ...]
 *
 * Fallback: se `$columnMapping` é null, usa auto-detect via LeadsImportMapper.
 * Isso mantém retrocompatibilidade com o endpoint legado `/contatos/importar`.
 */
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

    /** @var array<int, CustomFieldDefinition> */
    private array $customFieldsById = [];

    /**
     * @param array<string, string>|null $columnMapping header (como no arquivo) => fieldKey
     *         Ex: ['Cliente' => 'name', 'Celular' => 'phone', 'Seg.' => 'custom:12']
     *         fieldKey '__skip' ignora a coluna.
     */
    public function __construct(
        ?int $maxRemaining = null,
        private readonly ?array $columnMapping = null,
    ) {
        $pipeline = Pipeline::where('is_default', true)->first() ?? Pipeline::first();
        $this->defaultPipelineId = $pipeline?->id;
        $this->defaultStageId    = $pipeline?->stages()->orderBy('position')->first()?->id;
        $this->maxRemaining      = $maxRemaining;
        $this->detector          = new DuplicateLeadDetector();

        // Indexa custom fields por ID (usado quando mapping aponta pra 'custom:N')
        CustomFieldDefinition::where('is_active', true)
            ->get()
            ->each(fn ($def) => $this->customFieldsById[$def->id] = $def);
    }

    public function collection(Collection $rows): void
    {
        // Resolve o mapping normalizado UMA vez fora do loop
        // (o maatwebsite/excel normaliza headers pra snake_case quando WithHeadingRow está ativo)
        $normalizedMapping = $this->normalizeMapping($this->columnMapping);

        foreach ($rows as $row) {
            $mapped = $this->applyMapping($row->toArray(), $normalizedMapping);

            $name = trim((string) ($mapped['name'] ?? ''));
            if (!$name) {
                $this->skipped++;
                continue;
            }

            if ($this->maxRemaining !== null && $this->imported >= $this->maxRemaining) {
                $this->limitSkipped++;
                continue;
            }

            $rowData = [
                'name'  => $name,
                'phone' => trim((string) ($mapped['phone'] ?? '')),
                'email' => strtolower(trim((string) ($mapped['email'] ?? ''))),
            ];

            $duplicates   = $this->detector->findDuplicatesFromData($rowData, auth()->user()->tenant_id);
            $hasDuplicate = $duplicates->filter(fn ($d) => $d['score'] >= 70)->isNotEmpty();

            $lead = Lead::create([
                'name'        => $name,
                'phone'       => $rowData['phone'],
                'email'       => $rowData['email'],
                'company'     => trim((string) ($mapped['company'] ?? '')) ?: null,
                'value'       => $this->parseMoney($mapped['value'] ?? null),
                'source'      => trim((string) ($mapped['source'] ?? 'importado')) ?: 'importado',
                'notes'       => trim((string) ($mapped['notes'] ?? '')) ?: null,
                'pipeline_id' => $this->defaultPipelineId,
                'stage_id'    => $this->defaultStageId,
                'created_by'  => auth()->id(),
            ]);

            // Tags (separadas por vírgula)
            if (! empty($mapped['tags'])) {
                $tagNames = array_filter(array_map('trim', explode(',', (string) $mapped['tags'])));
                if ($tagNames) {
                    $lead->attachTagsByName($tagNames);
                    $lead->update(['tags' => $tagNames]); // dual write (Fase 3 do refactor de tags)
                }
            }

            LeadEvent::create([
                'lead_id'      => $lead->id,
                'event_type'   => 'created',
                'description'  => 'Lead importado via planilha',
                'performed_by' => auth()->id(),
                'created_at'   => now(),
            ]);

            // Custom fields via mapping explícito
            foreach ($mapped as $fieldKey => $value) {
                if (! str_starts_with($fieldKey, 'custom:')) continue;
                if ($value === null || trim((string) $value) === '') continue;

                $defId = (int) substr($fieldKey, 7);
                $def   = $this->customFieldsById[$defId] ?? null;
                if (! $def) continue;

                $this->saveCustomField($lead, $def, (string) $value);
            }

            // Registra duplicatas pra revisão
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

    /**
     * Normaliza o mapping: maatwebsite aplica heading_row_formatter que snake_case
     * as keys. Precisamos que o lookup bate.
     */
    private function normalizeMapping(?array $mapping): array
    {
        if ($mapping === null) {
            return [];
        }

        $out = [];
        foreach ($mapping as $header => $fieldKey) {
            if ($fieldKey === '__skip' || $fieldKey === null || $fieldKey === '') continue;
            $out[$this->normalizeKey($header)] = $fieldKey;
        }
        return $out;
    }

    /**
     * Aplica mapping na row: retorna {fieldKey => cellValue}. Se mapping é vazio,
     * faz fallback pra procurar headers padrão (nome/name, telefone/phone, etc).
     */
    private function applyMapping(array $rowArray, array $normalizedMapping): array
    {
        $out = [];

        if (! empty($normalizedMapping)) {
            foreach ($rowArray as $header => $value) {
                $key = $this->normalizeKey((string) $header);
                $field = $normalizedMapping[$key] ?? null;
                if ($field) {
                    $out[$field] = $value;
                }
            }
            return $out;
        }

        // Fallback legado (compat com endpoint antigo sem wizard)
        $aliases = [
            'name'    => ['nome', 'name'],
            'phone'   => ['telefone', 'phone', 'celular', 'whatsapp'],
            'email'   => ['email', 'e_mail'],
            'company' => ['empresa', 'company'],
            'value'   => ['valor', 'value'],
            'source'  => ['origem', 'source'],
            'notes'   => ['observacoes', 'observacao', 'notes'],
            'tags'    => ['tags', 'etiquetas'],
        ];
        foreach ($aliases as $field => $keys) {
            foreach ($keys as $key) {
                if (isset($rowArray[$key]) && $rowArray[$key] !== null && trim((string) $rowArray[$key]) !== '') {
                    $out[$field] = $rowArray[$key];
                    break;
                }
            }
        }
        return $out;
    }

    /**
     * Maatwebsite converte "Nome Completo" → "nome_completo" via slug underscore.
     * Replica essa lógica pra casar mapping com a key real do row.
     */
    private function normalizeKey(string $str): string
    {
        $str = mb_strtolower(trim($str));
        $map = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ];
        $str = strtr($str, $map);
        // Caracteres não alfanum viram underscore (mesma regra do maatwebsite default)
        $str = preg_replace('/[^a-z0-9]+/', '_', $str);
        return trim((string) $str, '_');
    }

    private function parseMoney(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') return null;
        $clean = str_replace(['.', ','], ['', '.'], (string) $raw);
        return is_numeric($clean) ? (float) $clean : null;
    }

    private function saveCustomField(Lead $lead, CustomFieldDefinition $def, string $value): void
    {
        $cfData = ['tenant_id' => auth()->user()->tenant_id, 'lead_id' => $lead->id, 'field_id' => $def->id];
        $val    = trim($value);

        match ($def->field_type) {
            'number', 'currency' => CustomFieldValue::create(array_merge($cfData, [
                'value_number' => is_numeric(str_replace(['.', ','], ['', '.'], $val))
                    ? (float) str_replace(['.', ','], ['', '.'], $val)
                    : null,
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

    public function getImported(): int         { return $this->imported; }
    public function getSkipped(): int          { return $this->skipped; }
    public function getLimitSkipped(): int     { return $this->limitSkipped; }
    public function getDuplicatesFound(): int  { return $this->duplicatesFound; }
}
