<?php

declare(strict_types=1);

namespace App\Exports;

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

    public function __construct(
        private readonly Pipeline   $pipeline,
        private readonly Collection $existingTags,
    ) {}

    public function headings(): array
    {
        return ['Nome*', 'Telefone', 'E-mail', 'Valor', 'Etapa', 'Origem', 'Tags', 'Notas', 'Criado em'];
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

        return [
            // Linha 2 — dicas de preenchimento (laranja claro)
            [
                'Obrigatório. Nome completo do lead',
                'Ex: (11) 99999-9999',
                'Ex: joao@email.com',
                'Somente números. Ex: 1500 ou 1500,50',
                $stagesHint ?: 'Ver aba Referência',
                $sourcesHint,
                'Separadas por vírgula. ' . ($this->existingTags->isNotEmpty() ? 'Existentes: ' . $tagsHint : $tagsHint),
                'Texto livre (opcional)',
                'Formato: dd/mm/aaaa',
            ],
            // Linha 3 — exemplo real (azul claro)
            [
                'João Silva',
                '(11) 99999-9999',
                'joao@email.com',
                '1500',
                $firstStage?->name ?? ($stages->first()?->name ?? ''),
                'manual',
                $this->existingTags->take(2)->implode(', ') ?: 'vip, quente',
                'Cliente indicado pelo parceiro X',
                '15/01/2025',
            ],
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

                $ref->getColumnDimension('A')->setAutoSize(true);

                $spreadsheet->setActiveSheetIndex(0);
            },
        ];
    }
}
