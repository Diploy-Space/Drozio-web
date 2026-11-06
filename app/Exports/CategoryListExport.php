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

class CategoryListExport implements FromView, ShouldAutoSize, WithStyles, WithColumnWidths, WithEvents
{
    use Exportable;

    private const ICON_BOX_SIZE = 50;

    protected $data;

    private ?string $lastColumn = null;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('file-exports.category-list', [
            'data' => $this->data,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
        ];
    }

    public function styles(Worksheet $sheet): array
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
            "A1:{$lastColumn}" . ($this->data['categories']->count() + 4) => [
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

    public function setImage($workSheet): void
    {
        $this->data['categories']->each(function ($item, $index) use ($workSheet) {
            $tempImagePath = null;
            $filePath = 'category/' . $item->icon_full_url['key'];
            $fileCheck = fileCheck(disk: 'public', path: $filePath);
            if ($item->icon_full_url['path'] && !$fileCheck) {
                $tempImagePath = getTemporaryImageForExport($item->icon_full_url['path']);
                $imagePath = getImageForExport($item->icon_full_url['path']);
                $drawing = new MemoryDrawing();
                $drawing->setImageResource($imagePath);
            } else {
                $drawing = new Drawing();
                $drawing->setPath(is_file(storage_path('app/public/' . $filePath)) ? storage_path('app/public/' . $filePath) : public_path('assets/back-end/img/placeholder/category.png'));
            }
            $drawing->setName($item->name);
            $drawing->setDescription($item->name);
            $drawing->setResizeProportional(true);
            $this->fitIconToCell($drawing);
            $drawing->setOffsetX(40);
            $drawing->setOffsetY(7);
            $index += 5;
            $drawing->setCoordinates("B$index");
            $drawing->setWorksheet($workSheet);
            if ($tempImagePath) {
                imagedestroy($tempImagePath);
            }
        });
    }

    private function fitIconToCell(Drawing|MemoryDrawing $drawing): void
    {
        $drawing->getWidth() > $drawing->getHeight()
            ? $drawing->setWidth(self::ICON_BOX_SIZE)
            : $drawing->setHeight(self::ICON_BOX_SIZE);
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
                $event->sheet->getStyle("A4:{$lastColumn}" . ($this->data['categories']->count() + 4))
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
                $event->sheet->getRowDimension(2)->setRowHeight(60);
                $event->sheet->getRowDimension(1)->setRowHeight(30);
                $event->sheet->getRowDimension(3)->setRowHeight(30);
                $event->sheet->getRowDimension(4)->setRowHeight(30);
                $event->sheet->getDefaultRowDimension()->setRowHeight(50);
                if ($this->data['title'] == 'category') {
                    $workSheet = $event->sheet->getDelegate();
                    $this->setImage($workSheet);
                }
            },
        ];
    }
}
