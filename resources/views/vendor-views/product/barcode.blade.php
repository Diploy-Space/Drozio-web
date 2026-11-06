@extends('layouts.vendor.app')

@section('title', $product?->name . ' '.translate('Barcode').' ' . date('Y/m/d'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/barcode.css') }}"/>
    @if ($layout === $compactLayout)
        <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/barcode-sticker.css') }}"/>
    @else
        <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/barcode-a4.css') }}"/>
    @endif
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h1 class="h1 mb-0 text-capitalize d-flex flex-wrap align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/inhouse-product-list.png') }}" alt="">
                {{ translate('Generate Barcode') }}@if (!empty($product?->name)) - {{ $product->name }}@endif
            </h1>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ url()->current() }}" method="GET">
                    <div class="row g-3">
                        <div class="col-6 col-lg-2">
                            <div class="form-group mb-0">
                                <label class="form-label">{{ translate('Code') }}</label>
                                @if ($product->code)
                                    <div class="text-break">{{ $product->code }}</div>
                                @else
                                    <a class="title-color hover-c1"
                                       href="{{ route('vendor.retail.products.update', [$product['id']]) }}">
                                        {{ translate('Update your product code') }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="col-6 col-lg-2">
                            <div class="form-group mb-0">
                                <label class="form-label">{{ translate('Name') }}</label>
                                <div class="text-break">{{ Str::limit($product->name, 20) }}</div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <div class="form-group mb-0">
                                <label for="limit" class="form-label">{{ translate('Quantity') }}</label>
                                <input id="limit" class="form-control" type="number" name="limit" min="1"
                                       value="{{ request('limit') ?? 4 }}">
                                <span class="text-danger fs-12 mt-1 d-block">
                                    {{ translate('Maximum quantity 270') }}
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <div class="form-group mb-0">
                                <label class="form-label">{{ translate('Layout') }}</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input" id="barcode-layout-a4"
                                               name="layout" value="{{ $defaultLayout }}"
                                               @checked($layout !== $compactLayout)>
                                        <label class="form-check-label" for="barcode-layout-a4">A4</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input" id="barcode-layout-roll"
                                               name="layout" value="{{ $compactLayout }}"
                                               @checked($layout === $compactLayout)>
                                        <label class="form-check-label" for="barcode-layout-roll">
                                            {{ translate('Roll size') }}
                                        </label>
                                    </div>
                                </div>
                                <span class="text-muted fs-12 mt-1 d-block">
                                    {{ $layout === $compactLayout ? translate('Bars and code only, sized for product stickers.') : translate('Full labels arranged on an A4 sheet.') }}
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4 align-self-lg-end">
                            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                                <button class="btn btn-outline-info flex-grow-1 flex-sm-grow-0" type="submit">
                                    {{ translate('Generate Barcode') }}
                                </button>
                                <a href="{{ route('vendor.retail.products.barcode', [$product['id']]) }}"
                                   class="btn btn-outline-danger flex-grow-1 flex-sm-grow-0">
                                    {{ translate('Reset') }}
                                </a>
                                <button type="button" id="print_bar" data-value="print-area"
                                        class="btn btn-outline--primary flex-grow-1 flex-sm-grow-0 action-print-invoice">
                                    {{ translate('Print') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-5">
            <h1 class="style-one-br show-div2">
                {{ translate('Built for A4 printing, so it is hidden on smaller devices.') }}
            </h1>
        </div>

        <div id="print-area" class="show-div pb-5">
            @foreach($barcodes as $key => $array)
                <div class="barcode-a4">
                    @for ($i = 0; $i < count($array); $i++)
                        @include('vendor-views.product.partials._barcode-label-'.$layout)
                    @endfor
                </div>
            @endforeach
        </div>
    </div>
@endsection
