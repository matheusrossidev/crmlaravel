<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Pipeline;
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
    public function __construct(private readonly Pipeline $pipeline) {}

    public function headings(): array
    {
        return ['Nome*', 'Telefone', 'E-mail', 'Valor', 'Etapa', 'Origem', 'Tags', 'Notas', 'Convertido em'];
    }

    public function array(): array
    {
        $firstStage = $this->pipeline->stages->sortBy('position')->first();

        return [
            [
                'João Silva Exemplo',
                '(11) 99999-9999',
                'joao@email.com',
                '1500',
                $firstStage?->name ?? '',
                'manual',
                'tag1, tag2',
                'Nota opcional sobre o lead',
                '15/01/2025',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3B82F6']],
            ],
            2 => [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4FF']],
                'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $spreadsheet = $event->sheet->getDelegate()->getParent();

                $stagesSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet(
                    $spreadsheet,
                    'Etapas Válidas'
                );
                $spreadsheet->addSheet($stagesSheet);

                $stagesSheet->setCellValue('A1', 'Funil: ' . $this->pipeline->name);
                $stagesSheet->setCellValue('A2', 'Cole os nomes abaixo na coluna "Etapa" da planilha:');
                $stagesSheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
                $stagesSheet->getStyle('A2')->getFont()->setItalic(true)->setColor(
                    (new \PhpOffice\PhpSpreadsheet\Style\Color('FF6B7280'))
                );

                foreach ($this->pipeline->stages->sortBy('position') as $i => $stage) {
                    $row = $i + 3;
                    $stagesSheet->setCellValue('A' . $row, $stage->name);
                    $stagesSheet->getStyle('A' . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFF8FAFC');
                }

                $stagesSheet->getColumnDimension('A')->setAutoSize(true);

                // Ensure main sheet is active
                $spreadsheet->setActiveSheetIndex(0);
            },
        ];
    }
}
