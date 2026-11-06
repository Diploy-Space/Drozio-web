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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrderTransactionReportExport implements FromView, ShouldAutoSize, WithColumnWidths, WithEvents, WithStyles
{
    use Exportable;

    private const DATA_FROM_ADMIN = 'admin';

    private const LEADING_COLUMNS = 2;

    private const AMOUNT_COLUMNS = 9;

    private const EDIT_AMOUNT_COLUMNS = 2;

    private const DISCOUNT_AND_COMMISSION_COLUMNS = 5;

    private const PAYMENT_COLUMNS = 2;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('file-exports.order-transaction-report-export', [
            'data' => $this->data,
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
        $lastColumn = $this->lastColumn();
        $lastRow = $this->lastRow();

        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle('A3:'.$lastColumn.'3')->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('A3:'.$lastColumn.'3')->getFill()->applyFromArray([
            'fillType' => 'solid',
            'rotation' => 0,
            'color' => ['rgb' => '063C93'],
        ]);
        $sheet->getStyle($this->netIncomeColumnRange().$lastRow)->getFill()->applyFromArray([
            'fillType' => 'solid',
            'rotation' => 0,
            'color' => ['rgb' => 'FFF9D1'],
        ]);

        $sheet->setShowGridlines(false);

        return [
            'A1:'.$lastColumn.$lastRow => [
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
                $lastColumn = $this->lastColumn();

                $event->sheet->getStyle('A1:'.$lastColumn.'1')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle('A3:'.$lastColumn.$this->lastRow())
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle('A2:'.$lastColumn.'2')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->mergeCells('A1:'.$lastColumn.'1');
                $event->sheet->mergeCells('A2:B2');
                $event->sheet->mergeCells('C2:'.$lastColumn.'2');
                $event->sheet->getRowDimension(2)->setRowHeight(80);
                $event->sheet->getDefaultRowDimension()->setRowHeight(30);
            },
        ];
    }

    private function isAdminExport(): bool
    {
        return ($this->data['data-from'] ?? null) === self::DATA_FROM_ADMIN;
    }

    private function showsEditAmounts(): bool
    {
        return $this->data['showEditAmounts'] ?? true;
    }

    private function columnsBeforeNetIncome(): int
    {
        return self::LEADING_COLUMNS
            + ($this->isAdminExport() ? 1 : 0)
            + self::AMOUNT_COLUMNS
            + ($this->showsEditAmounts() ? self::EDIT_AMOUNT_COLUMNS : 0)
            + self::DISCOUNT_AND_COMMISSION_COLUMNS;
    }

    private function netIncomeColumnCount(): int
    {
        return $this->isAdminExport() ? 2 : 1;
    }

    private function netIncomeColumnRange(): string
    {
        $firstNetIncomeColumn = $this->columnsBeforeNetIncome() + 1;
        $lastNetIncomeColumn = $this->columnsBeforeNetIncome() + $this->netIncomeColumnCount();

        return Coordinate::stringFromColumnIndex($firstNetIncomeColumn).'4:'.Coordinate::stringFromColumnIndex($lastNetIncomeColumn);
    }

    private function lastColumn(): string
    {
        return Coordinate::stringFromColumnIndex($this->columnsBeforeNetIncome() + $this->netIncomeColumnCount() + self::PAYMENT_COLUMNS);
    }

    private function lastRow(): int
    {
        return $this->data['transactions']->count() + 3;
    }
}
