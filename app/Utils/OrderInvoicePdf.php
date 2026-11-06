<?php

namespace App\Utils;

use App\Traits\PdfGenerator;
use Illuminate\Database\Eloquent\Model;

class OrderInvoicePdf
{
    use PdfGenerator;

    private const FILE_PREFIX = 'order_invoice_';

    private const PDF_TYPE = 'invoice';

    private const REQUEST_FROM = 'web';

    public static function store(Model $order): string
    {
        return self::storePdf(
            view: view(themeViewName(key: 'order_invoice'), [
                'order' => $order,
                'invoiceSettings' => getWebConfig(name: 'invoice_settings'),
            ]),
            filePrefix: self::FILE_PREFIX,
            filePostfix: $order['id'],
            pdfType: self::PDF_TYPE,
            requestFrom: self::REQUEST_FROM,
        );
    }
}
