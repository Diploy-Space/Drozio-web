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
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductListExport implements FromView, ShouldAutoSize, WithStyles, WithColumnWidths, WithEvents
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
        return view('file-exports.product-list', [
            'data' => $this->data,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'C' => 25,
            'D' => 25,
            'F' => 50,
            'G' => 20,
            'H' => 20,
            'I' => 20,
            'J' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->lastColumn($sheet);

        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle("A3:{$lastColumn}3")->getFont()->setBold(true)->getColor()
            ->setARGB('FFFFFF');

        $sheet->getStyle("A3:{$lastColumn}3")->getFill()->applyFromArray([
            'fillType' => 'solid',
            'rotation' => 0,
            'color' => ['rgb' => '063C93'],
        ]);

        $sheet->setShowGridlines(false);

        return [
            "A1:{$lastColumn}" . ($this->data['products']->count() + 3) => [
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

    public function setImage($workSheet)
    {
        $this->data['products']->each(function ($item, $index) use ($workSheet) {
            $tempImagePath = null;
            $filePath = 'product/thumbnail/' . $item->thumbnail_full_url['key'];
            $fileCheck = fileCheck(disk: 'public', path: $filePath);
            if ($item->thumbnail_full_url['path'] && !$fileCheck) {
                $tempImagePath = getTemporaryImageForExport($item->thumbnail_full_url['path']);
                $imagePath = getImageForExport($item->thumbnail_full_url['path']);
                $drawing = new MemoryDrawing();
                $drawing->setImageResource($imagePath);
            } else {
                $drawing = new Drawing();
                $drawing->setPath(is_file(storage_path('app/public/' . $filePath)) ? storage_path('app/public/' . $filePath) : public_path('assets/back-end/img/products.png'));
            }
            $drawing->setName($item->name);
            $drawing->setDescription($item->name);
            $drawing->setHeight(50);
            $drawing->setOffsetX(45);
            $drawing->setOffsetY(70);
            $drawing->setResizeProportional(true);
            $index += 4;
            $drawing->setCoordinates("B$index");
            $drawing->setWorksheet($workSheet);
            if ($tempImagePath) {
                imagedestroy($tempImagePath);
            }
        });
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
                $event->sheet->getStyle("A3:{$lastColumn}" . ($this->data['products']->count() + 3))
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
                $event->sheet->getRowDimension(1)->setRowHeight(30);
                $event->sheet->getRowDimension(3)->setRowHeight(30);
                $event->sheet->getDefaultRowDimension()->setRowHeight(150);

                $workSheet = $event->sheet->getDelegate();
                $this->setImage($workSheet);
            },
        ];
    }

}
