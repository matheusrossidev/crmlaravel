<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\CustomFieldDefinition;
use App\Models\Pipeline;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KanbanTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithEvents
{
    private const VALID_SOURCES = [
        'manual', 'facebook', 'google', 'instagram',
        'whatsapp', 'site', 'indicacao', 'api', 'importado',
    ];

    /** @var Collection<int, CustomFieldDefinition> */
    private Collection $customFields;

    public function __construct(
        private readonly Pipeline   $pipeline,
        private readonly Collection $existingTags,
    ) {
        $this->customFields = CustomFieldDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function headings(): array
    {
        $base = ['Nome*', 'Telefone', 'E-mail', 'Valor', 'Etapa', 'Origem', 'Tags', 'Notas', 'Criado em'];
        foreach ($this->customFields as $cf) {
            $base[] = $cf->label;
        }
        return $base;
    }

    public function array(): array
    {
        $stages     = $this->pipeline->stages->sortBy('position');
        $firstStage = $stages->first();

        $stagesHint  = $stages->pluck('name')->implode(' | ');
        $sourcesHint = implode(' | ', self::VALID_SOURCES);
        $tagsHint    = $this->existingTags->isNotEmpty()
            ? $this->existingTags->implode(' | ')
            : 'Ex: vip, quente, retorno';

        // Custom field hints and examples
        $cfHints    = [];
        $cfExamples = [];
        $typeHints  = [
            'text'        => ['Texto livre', 'Exemplo'],
            'textarea'    => ['Texto longo', 'Detalhes aqui...'],
            'number'      => ['Somente números', '42'],
            'currency'    => ['Valor monetário. Ex: 1500,50', '1500'],
            'date'        => ['Formato: dd/mm/aaaa', '15/01/2025'],
            'select'      => ['Ver opções na aba Referência', ''],
            'multiselect' => ['Separar por vírgula', ''],
            'checkbox'    => ['sim ou não', 'sim'],
            'url'         => ['URL completa', 'https://exemplo.com'],
            'phone'       => ['Ex: (11) 99999-9999', '(11) 99999-9999'],
            'email'       => ['Ex: contato@email.com', 'contato@email.com'],
        ];

        foreach ($this->customFields as $cf) {
            $hint = $typeHints[$cf->field_type] ?? ['(opcional)', ''];
            if (in_array($cf->field_type, ['select', 'multiselect']) && !empty($cf->options_json)) {
                $opts = is_array($cf->options_json) ? implode(' | ', array_slice($cf->options_json, 0, 5)) : '';
                $hint[0] = $opts ?: $hint[0];
                $hint[1] = is_array($cf->options_json) ? ($cf->options_json[0] ?? '') : '';
            }
            $cfHints[]    = $hint[0];
            $cfExamples[] = $hint[1];
        }

        return [
            // Linha 2 — dicas de preenchimento (laranja claro)
            array_merge([
                'Obrigatório. Nome completo do lead',
                'Ex: (11) 99999-9999',
                'Ex: joao@email.com',
                'Somente números. Ex: 1500 ou 1500,50',
                $stagesHint ?: 'Ver aba Referência',
                $sourcesHint,
                'Separadas por vírgula. ' . ($this->existingTags->isNotEmpty() ? 'Existentes: ' . $tagsHint : $tagsHint),
                'Texto livre (opcional)',
                'Formato: dd/mm/aaaa',
            ], $cfHints),
            // Linha 3 — exemplo real (azul claro)
            array_merge([
                'João Silva',
                '(11) 99999-9999',
                'joao@email.com',
                '1500',
                $firstStage?->name ?? ($stages->first()?->name ?? ''),
                'manual',
                $this->existingTags->take(2)->implode(', ') ?: 'vip, quente',
                'Cliente indicado pelo parceiro X',
                '15/01/2025',
            ], $cfExamples),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Cabeçalho — azul escuro
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3B82F6']],
            ],
            // Dicas — fundo âmbar suave, texto laranja
            2 => [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
                'font' => ['italic' => true, 'color' => ['rgb' => 'B45309'], 'size' => 9],
            ],
            // Exemplo — fundo azul claro, texto cinza
            3 => [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
                'font' => ['italic' => true, 'color' => ['rgb' => '6B7280'], 'size' => 10],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $ws          = $event->sheet->getDelegate();
                $spreadsheet = $ws->getParent();

                // ── Congelar linha 1 (cabeçalho fixo ao rolar) ──────────────
                $ws->freezePane('A2');

                // ── Aba "Referência" ─────────────────────────────────────────
                $ref = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Referência');
                $spreadsheet->addSheet($ref);

                $row = 1;

                // Título
                $ref->setCellValue("A{$row}", 'Referência de preenchimento — ' . $this->pipeline->name);
                $ref->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
                $ref->getStyle("A{$row}")->getFont()->getColor()->setARGB('FF1A1D23');
                $row += 2;

                // ── Etapas ───────────────────────────────────────────────────
                $ref->setCellValue("A{$row}", '📌  ETAPAS VÁLIDAS  (coluna "Etapa")');
                $ref->getStyle("A{$row}")->getFont()->setBold(true);
                $ref->getStyle("A{$row}")->getFont()->getColor()->setARGB('FF3B82F6');
                $row++;

                foreach ($this->pipeline->stages->sortBy('position') as $stage) {
                    $ref->setCellValue("A{$row}", $stage->name);
                    $ref->getStyle("A{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFF0F4FF');
                    $row++;
                }
                $row++;

                // ── Tags ─────────────────────────────────────────────────────
                $ref->setCellValue("A{$row}", '🏷️  TAGS EXISTENTES  (coluna "Tags" — separe por vírgula)');
                $ref->getStyle("A{$row}")->getFont()->setBold(true);
                $ref->getStyle("A{$row}")->getFont()->getColor()->setARGB('FF6366F1');
                $row++;

                if ($this->existingTags->isEmpty()) {
                    $ref->setCellValue("A{$row}", '(nenhuma tag cadastrada ainda — crie tags livremente na importação)');
                    $ref->getStyle("A{$row}")->getFont()->setItalic(true);
                    $ref->getStyle("A{$row}")->getFont()->getColor()->setARGB('FF9CA3AF');
                    $row++;
                } else {
                    foreach ($this->existingTags as $tag) {
                        $ref->setCellValue("A{$row}", $tag);
                        $ref->getStyle("A{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFF5F3FF');
                        $row++;
                    }
                }
                $row++;

                // ── Origens ──────────────────────────────────────────────────
                $ref->setCellValue("A{$row}", '🌐  ORIGENS VÁLIDAS  (coluna "Origem")');
                $ref->getStyle("A{$row}")->getFont()->setBold(true);
                $ref->getStyle("A{$row}")->getFont()->getColor()->setARGB('FF10B981');
                $row++;

                foreach (self::VALID_SOURCES as $source) {
                    $ref->setCellValue("A{$row}", $source);
                    $ref->getStyle("A{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFF0FDF4');
                    $row++;
                }
                $row++;

                // ── Criado em ────────────────────────────────────────────────
                $ref->setCellValue("A{$row}", '📅  CRIADO EM  — formato aceito');
                $ref->getStyle("A{$row}")->getFont()->setBold(true);
                $ref->getStyle("A{$row}")->getFont()->getColor()->setARGB('FFF59E0B');
                $row++;
                foreach (['dd/mm/aaaa  →  15/01/2025', 'dd/mm/aa  →  15/01/25', 'aaaa-mm-dd  →  2025-01-15'] as $fmt) {
                    $ref->setCellValue("A{$row}", $fmt);
                    $row++;
                }

                // ── Campos Personalizados ────────────────────────────────
                if ($this->customFields->isNotEmpty()) {
                    $row++;
                    $ref->setCellValue("A{$row}", '📝  CAMPOS PERSONALIZADOS');
                    $ref->getStyle("A{$row}")->getFont()->setBold(true);
                    $ref->getStyle("A{$row}")->getFont()->getColor()->setARGB('FF8B5CF6');
                    $row++;

                    foreach ($this->customFields as $cf) {
                        $typeLabel = match ($cf->field_type) {
                            'text'        => 'Texto',
                            'textarea'    => 'Texto longo',
                            'number'      => 'Número',
                            'currency'    => 'Moeda',
                            'date'        => 'Data (dd/mm/aaaa)',
                            'select'      => 'Seleção única',
                            'multiselect' => 'Múltipla escolha (vírgula)',
                            'checkbox'    => 'Sim/Não',
                            'url'         => 'URL',
                            'phone'       => 'Telefone',
                            'email'       => 'Email',
                            default       => $cf->field_type,
                        };
                        $ref->setCellValue("A{$row}", $cf->label . "  ({$typeLabel})");
                        $ref->getStyle("A{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFF5F3FF');

                        if (in_array($cf->field_type, ['select', 'multiselect']) && !empty($cf->options_json)) {
                            $ref->setCellValue("B{$row}", 'Opções: ' . implode(', ', $cf->options_json));
                            $ref->getStyle("B{$row}")->getFont()->setItalic(true);
                            $ref->getStyle("B{$row}")->getFont()->getColor()->setARGB('FF6B7280');
                        }
                        $row++;
                    }
                }

                $ref->getColumnDimension('A')->setAutoSize(true);
                $ref->getColumnDimension('B')->setAutoSize(true);

                $spreadsheet->setActiveSheetIndex(0);
            },
        ];
    }
}
