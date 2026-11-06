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
use GdImage;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class VendorListExport implements FromView, ShouldAutoSize, WithStyles, WithColumnWidths, WithEvents
{
    use Exportable;

    private const LOGO_COLUMN_WIDTH = 18;

    private const LOGO_COLUMN_WIDTH_PX = 131;

    private const LOGO_ROW_HEIGHT_PX = 70;

    private const LOGO_MAX_WIDTH_PX = 110;

    private const LOGO_MAX_HEIGHT_PX = 50;

    private const EXCEL_NATIVE_IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'bmp'];

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('file-exports.vendor-list', [
            'data' => $this->data,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => self::LOGO_COLUMN_WIDTH,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('A4:J4')->getFont()->setBold(true)->getColor()
            ->setARGB('FFFFFF');

        $sheet->getStyle('A4:J4')->getFill()->applyFromArray([
            'fillType' => 'solid',
            'rotation' => 0,
            'color' => ['rgb' => '063C93'],
        ]);
        $sheet->getStyle('H5:J' . ($this->data['vendors']->count() + 4))->getFill()->applyFromArray([
            'fillType' => 'solid',
            'rotation' => 0,
            'color' => ['rgb' => 'FFF9D1'],
        ]);
        $sheet->setShowGridlines(false);
        return [

            'A1:J' . ($this->data['vendors']->count() + 4) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ],
        ];
    }

    public function setImage($workSheet): void
    {
        $this->data['vendors']->each(function ($item, $index) use ($workSheet) {
            $drawing = $this->shopLogoDrawing(image: $item?->shop?->image_full_url);
            $drawing->setName($item->f_name);
            $drawing->setDescription($item->f_name);
            $this->fitInsideLogoCell(drawing: $drawing);
            $drawing->setCoordinates('B'.($index + 5));
            $drawing->setWorksheet($workSheet);
        });
    }

    private function fitInsideLogoCell(BaseDrawing $drawing): void
    {
        $scale = min(self::LOGO_MAX_WIDTH_PX / max($drawing->getWidth(), 1), self::LOGO_MAX_HEIGHT_PX / max($drawing->getHeight(), 1));
        $width = (int) round($drawing->getWidth() * $scale);
        $height = (int) round($drawing->getHeight() * $scale);

        $drawing->setResizeProportional(false);
        $drawing->setWidth($width);
        $drawing->setHeight($height);
        $drawing->setOffsetX((int) round((self::LOGO_COLUMN_WIDTH_PX - $width) / 2));
        $drawing->setOffsetY((int) round((self::LOGO_ROW_HEIGHT_PX - $height) / 2));
    }

    private function shopLogoDrawing(?array $image): BaseDrawing
    {
        $localPath = storage_path('app/public/shop/'.($image['key'] ?? ''));
        $isStoredLocally = !empty($image['key']) && fileCheck(disk: 'public', path: 'shop/'.$image['key']) && is_file($localPath);

        if (!$isStoredLocally && !empty($image['path'])) {
            return $this->memoryDrawing(image: getImageForExport($image['path']));
        }

        $path = $isStoredLocally ? $localPath : public_path('assets/back-end/img/seller_sale.png');

        if (in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::EXCEL_NATIVE_IMAGE_EXTENSIONS, true)) {
            return (new Drawing())->setPath($path);
        }

        return $this->memoryDrawing(image: getImageForExport($path));
    }

    private function memoryDrawing(GdImage $image): MemoryDrawing
    {
        $drawing = new MemoryDrawing();
        $drawing->setImageResource($image);
        $drawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
        $drawing->setMimeType(MemoryDrawing::MIMETYPE_PNG);

        return $drawing;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:J1')
                ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle('A4:J' . ($this->data['vendors']->count() + 4))
                ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle('A2:J3')
                ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $event->sheet->mergeCells('A1:J1');
                $event->sheet->mergeCells('A2:B2');
                $event->sheet->mergeCells('C2:J2');
                $event->sheet->mergeCells('A3:B3');
                $event->sheet->mergeCells('C3:J3');
                $event->sheet->mergeCells('D2:J2');
                $event->sheet->getRowDimension(2)->setRowHeight(60);
                $event->sheet->getRowDimension(1)->setRowHeight(30);
                $event->sheet->getRowDimension(3)->setRowHeight(30);
                $event->sheet->getRowDimension(4)->setRowHeight(30);
                $event->sheet->getDefaultRowDimension()->setRowHeight(50);

                $workSheet = $event->sheet->getDelegate();
                $this->setImage($workSheet);
            },
        ];
    }

}
