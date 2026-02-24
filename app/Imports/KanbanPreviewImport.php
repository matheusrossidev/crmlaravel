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
    public function __construct(
        private readonly Collection $stagesByName,
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = trim((string) ($row['nome'] ?? $row['name'] ?? ''));

            // Resolve stage
            $stageRaw   = trim((string) ($row['etapa'] ?? $row['stage'] ?? ''));
            $stageLower = mb_strtolower($stageRaw);
            $stageFound = $stageLower !== '' && $this->stagesByName->has($stageLower);

            // Parse value
            $valueRaw    = $row['valor'] ?? $row['value'] ?? null;
            $valueParsed = null;
            if ($valueRaw !== null && $valueRaw !== '') {
                $clean = str_replace(['.', ','], ['', '.'], (string) $valueRaw);
                $valueParsed = is_numeric($clean) ? (float) $clean : null;
            }

            // Parse tags (comma-separated)
            $tagsRaw = $row['tags'] ?? $row['etiquetas'] ?? '';
            $tags    = [];
            if (is_string($tagsRaw) && $tagsRaw !== '') {
                $tags = array_values(array_filter(array_map('trim', explode(',', $tagsRaw))));
            }

            // Parse created_at with Excel serial + dd/mm/yyyy support
            $createdAtRaw = $row['criado_em'] ?? $row['created_at'] ?? null;
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
                'phone'       => trim((string) ($row['telefone'] ?? $row['phone'] ?? '')),
                'email'       => strtolower(trim((string) ($row['email'] ?? ''))),
                'value'       => $valueParsed,
                'value_fmt'   => $valueParsed !== null
                    ? 'R$ ' . number_format($valueParsed, 0, ',', '.')
                    : (($valueRaw !== null && $valueRaw !== '') ? (string) $valueRaw : ''),
                'stage_raw'   => $stageRaw,
                'stage_found' => $stageFound,
                'tags'        => $tags,
                'source'      => trim((string) ($row['origem'] ?? $row['source'] ?? 'importado')),
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
