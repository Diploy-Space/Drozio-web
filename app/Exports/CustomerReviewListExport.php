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

class CustomerReviewListExport implements FromView, ShouldAutoSize, WithColumnWidths, WithEvents, WithStyles
{
    use Exportable;

    protected $data;

    private ?string $lastColumn = null;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('file-exports.customer-review-list', [
            'data' => $this->data,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $this->lastColumn($sheet);

        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle("A3:{$lastColumn}3")->getFont()->setBold(true)->getColor()
            ->setARGB('FFFFFF');

        $sheet->getStyle("A3:{$lastColumn}3")->getFill()->applyFromArray([
            'fillType' => 'solid',
            'rotation' => 0,
            'color' => ['rgb' => '063C93'],
        ]);

        $sheet->setShowGridlines(false);

        return [
            "A1:{$lastColumn}".($this->data['reviews']->count() + 3) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
                'alignment' => [
                    'wrapText' => true,
                ],
            ],
        ];
    }

    private function lastColumn(Worksheet $sheet): string
    {
        return $this->lastColumn ??= $sheet->getHighestDataColumn();
    }

    private function headerTitle(): string
    {
        $businessModule = $this->data['business_module'] ?? null;

        return $businessModule === null
            ? translate('Customer Reviews')
            : translate('Customer Reviews').' - '.$businessModule;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastColumn = $this->lastColumn($event->sheet->getDelegate());

                $event->sheet->setCellValue('A1', $this->headerTitle());
                $event->sheet->getStyle("A1:{$lastColumn}1")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle("A3:{$lastColumn}".($this->data['reviews']->count() + 3))
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle("A2:{$lastColumn}2")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $event->sheet->mergeCells("A1:{$lastColumn}1");
                $event->sheet->mergeCells('A2:B2');
                $event->sheet->mergeCells("C2:{$lastColumn}2");
                $event->sheet->getRowDimension(2)->setRowHeight(100);
                $event->sheet->getDefaultRowDimension()->setRowHeight(30);
            },
        ];
    }
}
