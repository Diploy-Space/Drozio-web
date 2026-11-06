<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class VendorWithdrawRequest implements FromView, ShouldAutoSize, WithStyles, WithColumnWidths, WithEvents
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
        return view('file-exports.vendor-withdraw-request', [
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

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->lastColumn($sheet);

        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle("A4:{$lastColumn}4")->getFont()->setBold(true)->getColor()
            ->setARGB('FFFFFF');

        $sheet->getStyle("A4:{$lastColumn}4")->getFill()->applyFromArray([
            'fillType' => 'solid',
            'rotation' => 0,
            'color' => ['rgb' => '063C93'],
        ]);
        $sheet->setShowGridlines(false);

        return [
            "A1:{$lastColumn}" . ($this->data['withdraw_request']->count() + 4) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ],
        ];
    }

    private function lastColumn(Worksheet $sheet): string
    {
        return $this->lastColumn ??= $sheet->getHighestDataColumn();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastColumn = $this->lastColumn($event->sheet->getDelegate());

                $event->sheet->getStyle("A1:{$lastColumn}1")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle("A4:{$lastColumn}" . ($this->data['withdraw_request']->count() + 4))
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle("A2:{$lastColumn}3")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $event->sheet->mergeCells("A1:{$lastColumn}1");
                $event->sheet->mergeCells('A2:B2');
                $event->sheet->mergeCells("C2:{$lastColumn}2");
                $event->sheet->mergeCells('A3:B3');
                $event->sheet->mergeCells("C3:{$lastColumn}3");
                $event->sheet->getRowDimension(2)->setRowHeight(70);
                $event->sheet->getDefaultRowDimension()->setRowHeight(30);
            },
        ];
    }

}
