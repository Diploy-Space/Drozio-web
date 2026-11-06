<?php

namespace App\Exports;

use App\Services\New\ProductStockReportService;
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

class StockProductReportExport implements FromView, ShouldAutoSize, WithColumnWidths, WithEvents, WithStyles
{
    use Exportable;

    private const HEADER_ROWS = 3;

    public function __construct(private readonly array $data) {}

    public function view(): View
    {
        return view('file-exports.stock-product-report', [
            'data' => $this->data,
            'sortLabel' => $this->data['sort'] === ProductStockReportService::SORT_STOCK_HIGH_TO_LOW ? translate('High to low') : translate('Low to high'),
            'stockStatusLabels' => [
                ProductStockReportService::STOCK_STATUS_IN_STOCK => translate('In Stock'),
                ProductStockReportService::STOCK_STATUS_OUT_OF_STOCK => translate('Out of Stock'),
                ProductStockReportService::STOCK_STATUS_SOON_STOCK_OUT => translate('Soon Stock Out'),
            ],
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
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle('A3:E3')->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('A3:E3')->getFill()->applyFromArray([
            'fillType' => 'solid',
            'rotation' => 0,
            'color' => ['rgb' => '063C93'],
        ]);
        $sheet->getStyle('E4:E'.$this->lastRow())->getFill()->applyFromArray([
            'fillType' => 'solid',
            'rotation' => 0,
            'color' => ['rgb' => 'FFF9D1'],
        ]);
        $sheet->setShowGridlines(false);

        return [
            'A1:E'.$this->lastRow() => [
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
            AfterSheet::class => function (AfterSheet $event): void {
                $event->sheet->getStyle('A1:E1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle('A3:E'.$this->lastRow())->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle('A2:E2')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $event->sheet->mergeCells('A1:E1');
                $event->sheet->mergeCells('A2:B2');
                $event->sheet->mergeCells('C2:E2');
                $event->sheet->getRowDimension(2)->setRowHeight(80);
                $event->sheet->getDefaultRowDimension()->setRowHeight(30);
            },
        ];
    }

    private function lastRow(): int
    {
        return $this->data['products']->count() + self::HEADER_ROWS;
    }
}
