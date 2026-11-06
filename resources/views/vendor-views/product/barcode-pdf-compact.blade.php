<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ translate('Product Barcode') }}</title>
    <style>
        @page {
            size: 1.55in 0.85in;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        .barcode-a4 {
            width: 1.55in;
            page-break-after: always;
        }

        .barcode-a4:last-child {
            page-break-after: auto;
        }

        .style-compact {
            padding: 4px;
            overflow: hidden;
            text-align: center;
        }

        .barcode_image {
            padding-top: 6px;
        }

        .barcode_image > div {
            margin-left: auto;
            margin-right: auto;
            max-width: 100%;
        }
    </style>
</head>

<body>
    @foreach ($barcodes as $page)
        <div class="barcode-a4">
            @foreach ($page as $label)
                @include('vendor-views.product.partials._barcode-label-compact')
            @endforeach
        </div>
    @endforeach
</body>

</html>
