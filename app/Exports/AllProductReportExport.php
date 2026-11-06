<?php

namespace App\Exports;

use App\Services\New\ProductReportService;
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

class AllProductReportExport implements FromView, ShouldAutoSize, WithColumnWidths, WithEvents, WithStyles
{
    use Exportable;

    private const HEADER_ROWS = 3;

    public function __construct(private readonly array $data) {}

    public function view(): View
    {
        return view('file-exports.all-product-report', [
            'data' => $this->data,
            'dateTypeLabel' => $this->dateTypeLabel(),
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 40,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle('A3:H3')->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('A3:H3')->getFill()->applyFromArray([
            'fillType' => 'solid',
            'rotation' => 0,
            'color' => ['rgb' => '063C93'],
        ]);
        $sheet->setShowGridlines(false);

        return [
            'A1:H'.$this->lastRow() => [
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
                $event->sheet->getStyle('A1:H1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle('A3:H'.$this->lastRow())->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle('A2:H2')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $event->sheet->mergeCells('A1:H1');
                $event->sheet->mergeCells('A2:B2');
                $event->sheet->mergeCells('C2:H2');
                $event->sheet->getRowDimension(2)->setRowHeight(80);
                $event->sheet->getDefaultRowDimension()->setRowHeight(30);
            },
        ];
    }

    private function dateTypeLabel(): string
    {
        return match ($this->data['dateType']) {
            ProductReportService::DATE_TYPE_THIS_MONTH => translate('This Month'),
            ProductReportService::DATE_TYPE_THIS_WEEK => translate('This Week'),
            ProductReportService::DATE_TYPE_TODAY => translate('Today'),
            ProductReportService::DATE_TYPE_CUSTOM => translate('Custom Date'),
            default => translate('This Year'),
        };
    }

    private function lastRow(): int
    {
        return $this->data['products']->count() + self::HEADER_ROWS;
    }
}
