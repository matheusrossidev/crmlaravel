<?php

declare(strict_types=1);

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KanbanPreviewImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /** @var array<int, array<string, mixed>> */
    private array $rows = [];

    /**
     * @param Collection<string, int> $stagesByName  lowercase stage name => stage_id
     */
    /**
     * @param array<string, string> $headerToField  slug_header => campo_crm
     */
    public function __construct(
        private readonly Collection $stagesByName,
        private readonly array $headerToField = [],
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
        foreach ($rows as $row) {
            $name = $this->field($row, 'nome', ['nome', 'name']);

            // Resolve stage
            $stageRaw   = $this->field($row, 'etapa', ['etapa', 'stage']);
            $stageLower = mb_strtolower($stageRaw);
            $stageFound = $stageLower !== '' && $this->stagesByName->has($stageLower);

            // Parse value
            $valueRaw    = $this->fieldRaw($row, 'valor', ['valor', 'value']);
            $valueParsed = null;
            if ($valueRaw !== null && $valueRaw !== '') {
                $clean = str_replace(['.', ','], ['', '.'], (string) $valueRaw);
                $valueParsed = is_numeric($clean) ? (float) $clean : null;
            }

            // Parse tags (comma-separated)
            $tagsRaw = $this->field($row, 'tags', ['tags', 'etiquetas']);
            $tags    = [];
            if ($tagsRaw !== '') {
                $tags = array_values(array_filter(array_map('trim', explode(',', $tagsRaw))));
            }

            // Parse created_at with Excel serial + dd/mm/yyyy support
            $createdAtRaw = $this->fieldRaw($row, 'criado_em', ['criado_em', 'created_at']);
            $createdAtFmt = null;
            if ($createdAtRaw !== null && $createdAtRaw !== '') {
                try {
                    if (is_numeric($createdAtRaw)) {
                        $dt           = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $createdAtRaw);
                        $createdAtFmt = Carbon::instance($dt)->format('d/m/Y');
                    } elseif (preg_match('/^\d{1,2}\/\d{1,2}\/(\d{4}|\d{2})$/', trim((string) $createdAtRaw))) {
                        $parts = explode('/', trim((string) $createdAtRaw));
                        $fmt   = strlen($parts[2]) === 4 ? 'd/m/Y' : 'd/m/y';
                        $createdAtFmt = Carbon::createFromFormat($fmt, trim((string) $createdAtRaw))->format('d/m/Y');
                    } else {
                        $createdAtFmt = Carbon::parse(trim((string) $createdAtRaw))->format('d/m/Y');
                    }
                } catch (\Exception) {
                    $createdAtFmt = trim((string) $createdAtRaw);
                }
            }

            $this->rows[] = [
                'name'        => $name,
                'phone'       => $this->field($row, 'telefone', ['telefone', 'phone']),
                'email'       => strtolower($this->field($row, 'email', ['email'])),
                'value'       => $valueParsed,
                'value_fmt'   => $valueParsed !== null
                    ? 'R$ ' . number_format($valueParsed, 0, ',', '.')
                    : (($valueRaw !== null && $valueRaw !== '') ? (string) $valueRaw : ''),
                'stage_raw'   => $stageRaw,
                'stage_found' => $stageFound,
                'tags'        => $tags,
                'source'      => $this->field($row, 'origem', ['origem', 'source']) ?: 'importado',
                'created_at'  => $createdAtFmt,
                'will_skip'   => $name === '',
            ];
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function getRows(): array
    {
        return $this->rows;
    }
}
