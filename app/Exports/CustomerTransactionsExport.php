<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerTransactionsExport implements FromView, ShouldAutoSize, WithColumnWidths, WithEvents, WithStyles
{
    use Exportable;

    private const LAST_COLUMN = 'G';

    private const HEADER_ROW = 4;

    public function __construct(
        private readonly array $data,
        private readonly string $view,
    ) {}

    public function view(): View
    {
        return view($this->view, ['data' => $this->data]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 32,
            'C' => 32,
            'D' => 24,
            'E' => 14,
            'F' => 14,
            'G' => 22,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = self::HEADER_ROW;
        $lastColumn = self::LAST_COLUMN;

        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getFill()->applyFromArray([
            'fillType' => 'solid',
            'rotation' => 0,
            'color' => ['rgb' => '063C93'],
        ]);
        $sheet->setShowGridlines(false);

        return [
            'A1:'.$lastColumn.$this->lastRow() => [
                'alignment' => ['wrapText' => true],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastColumn = self::LAST_COLUMN;
                $lastRow = $this->lastRow();

                $event->sheet->getStyle("A1:{$lastColumn}1")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle("A3:{$lastColumn}{$lastRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle("A2:{$lastColumn}3")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $event->sheet->mergeCells("A1:{$lastColumn}1");
                $event->sheet->mergeCells('A2:B2');
                $event->sheet->mergeCells("C2:{$lastColumn}2");
                $event->sheet->mergeCells('A3:B3');
                $event->sheet->mergeCells('C3:D3');
                $event->sheet->mergeCells('E3:F3');

                $event->sheet->getRowDimension(2)->setRowHeight(80);
                $event->sheet->getDefaultRowDimension()->setRowHeight(40);
            },
        ];
    }

    private function lastRow(): int
    {
        return $this->data['transactions']->count() + self::HEADER_ROW;
    }
}
