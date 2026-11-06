@extends('theme-views.layouts.app')

@section('title', translate('Download Digital Product').' | '.$web_config['company_name'].' '.translate('eCommerce'))

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-sm-5">
        <div class="container">
            <div class="card h-100">
                <div class="card-body py-4 px-sm-4">
                    <h2 class="mb-30 text-center">{{ translate('Download your product') }}</h2>
                    <form action="{{ route('digital-product-download-pos.index') }}" type="submit" method="get" class="p-sm-3 border rounded mb-4">
                        <div class="d-flex flex-column flex-sm-row flex-wrap gap-3 align-items-sm-end">
                            <div class="flex-grow-1 d-flex gap-3">
                                <div class="form-group flex-grow-1">
                                    <label for="order_id">{{ translate('Order ID') }}</label>
                                    <input type="text" id="order_id" name="order_id" class="form-control"
                                           placeholder="{{ translate('Order ID') }}" value="{{ request('order_id') }}">
                                </div>
                                <div class="form-group flex-grow-1">
                                    <label for="phone_or_email">{{ translate('Email') }}</label>
                                    <input id="phone_or_email" class="form-control" type="email"
                                           placeholder="{{ translate('Email Address') }}" value="{{ request('email') }}" name="email">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary h-45 flex-grow-1">
                                {{ isset($order) ? translate('Verified') : translate('Verify') }}
                            </button>
                        </div>
                    </form>

                    @if(isset($orderDetails))
                        @if($isDigitalProductExist != 0)
                            @if($isDigitalProductReadyCount == 0)
                                <div class="border rounded px-3 py-3 fs-15 text-base font-weight-medium custom-light-primary-color mb-3 d-flex align-items-center gap-3">
                                    <img src="{{ theme_asset('assets/img/icons/info-light.svg') }}" alt="" class="px-2">
                                    <span>
                                        {{ translate('Your digital product is ready.') }}
                                        {{ translate('Once the seller uploads it, download here with your order info.') }}
                                        {{ translate('If the download fails, wait a moment or contact admin via') }}

                                        @if(auth('customer')->check())
                                            <a class="text-base fw-bold text-underline" href="{{route('account-tickets')}}">
                                                {{ translate('Support ticket')}}
                                            </a>
                                        @else
                                            <a class="text-base fw-bold text-underline" href="{{route('customer.auth.login')}}">
                                                {{ translate('Support ticket')}}
                                            </a>
                                        @endif
                                    </span>
                                </div>
                            @else
                                <div class="d-flex flex-column gap-2 p-4 rounded border">
                                    @foreach($orderDetails as $index => $orderDetail)
                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                            <div class="d-flex justify-content-between align-items-center gap-2">
                                                <img width="50" src="{{ getStorageImages(path: $orderDetail?->product?->thumbnail_full_url, type: 'product') }}" alt="" class="border rounded">
                                                <a class="fs-13 font-semi-bold" href="{{ route('product', $orderDetail->product->slug) }}">
                                                    {{ $orderDetail->product->name }}
                                                </a>
                                            </div>
                                            <div>
                                                @php($productDetails = json_decode($orderDetail->product_details, true))

                                                @if($productDetails['digital_product_type'] == 'ready_product')
                                                    <?php
                                                        $checkFilePath = storageLink('product/digital-product', $productDetails['digital_file_ready'], ($productDetails['storage_path'] ?? 'public'));
                                                        $filePath = $checkFilePath['path'];
                                                        $fileExist = $checkFilePath['status'] == 200;
                                                        $fileName = $productDetails['digital_file_ready'];
                                                    ?>
                                                    @if ($fileExist)
                                                        <span class="btn p-0 shadow-none border-0 getDownloadFileUsingFileUrl" data-bs-toggle="tooltip" title="{{ translate('Download') }}" data-file-path="{{ $filePath }}">
                                                            <img src="{{ theme_asset(path: 'assets/img/icons/download-green.svg') }}" alt="">
                                                        </span>
                                                    @else
                                                        <a class="btn p-0 shadow-none border-0" data-bs-toggle="tooltip" title="{{ translate('Not found') }}" href="javascript:" disabled>
                                                            <img src="{{ theme_asset(path: 'assets/img/icons/download-green.svg') }}" alt="">
                                                        </a>
                                                    @endif
                                                @elseif($productDetails['digital_product_type'] == 'ready_after_sell')
                                                    @if($orderDetail['digital_file_after_sell'])
                                                        <?php
                                                            $checkFilePath = $orderDetail->digital_file_after_sell_full_url;
                                                            $filePath = $checkFilePath['path'];
                                                            $fileName = $orderDetail['digital_file_after_sell'];
                                                            $fileExist = $checkFilePath['status'] == 200;
                                                        ?>
                                                        <span class="btn p-0 shadow-none border-0 getDownloadFileUsingFileUrl" data-bs-toggle="tooltip" title="{{ translate('Download') }}" data-file-path="{{ $filePath }}">
                                                            <img src="{{ theme_asset(path: 'assets/img/icons/download-green.svg') }}" alt="">
                                                        </span>
                                                    @else
                                                        <a class="btn p-0 shadow-none border-0" data-bs-toggle="tooltip" title="{{ translate('Product not uploaded yet') }}" disabled>
                                                            <img src="{{ theme_asset(path: 'assets/img/icons/download-green.svg') }}" alt="">
                                                        </a>
                                                    @endif
                                                @endif

                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div class="rounded px-3 py-3 fs-15 text-base font-weight-medium custom-light-primary-color mb-3 d-flex align-items-center gap-3">
                                <img src="{{ theme_asset('public/assets/front-end/img/icons/info-light.svg') }}" alt="" class="px-2">
                                <span>
                                    {{ translate('You have no digital products in your order') }}
                                </span>
                            </div>
                        @endif

                    @endif
                </div>
            </div>
        </div>
    </main>
@endsection
