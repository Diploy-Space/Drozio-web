@extends('layouts.admin.app')

@section('title', translate('Refund details'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/new/back-end/libs/owl-carousel/owl.carousel.min.css')}}"/>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/refund_transaction.png') }}"
                     alt="{{ translate('Refund details') }}">
                {{ translate('Refund details') }}
            </h2>
            <div class="d-flex flex-wrap gap-3">
                @if($refund['status'] != 'refunded')
                    @if($refund['status'] != 'rejected')
                        <button class="btn btn-danger p-2 px-3"
                                data-bs-toggle="modal"
                                data-bs-target="#rejectModal-{{$refund['id']}}">
                            {{ translate('Reject') }}
                        </button>
                    @endif
                    @if($refund['status'] != 'approved')
                        <button class="btn btn-primary p-2 px-3"
                                data-bs-toggle="modal"
                                data-bs-target="#approveModal-{{ $refund['id'] }}">
                            {{ translate('Approve') }}
                        </button>
                    @endif

                    <button class="btn btn-success p-2 px-3"
                            data-bs-toggle="modal" data-bs-target="#refundModal-{{$refund['id']}}">
                        {{ $refund['status'] != 'approved' ? translate('Approve & Refund') : translate('Refund') }}
                    </button>
                @endif
            </div>
        </div>
        <div class="card card-body mb-20">
            <div class="row g-3">
                <div class="col-xl-4">
                    <div class="card card-body shadow-none h-100 bg-section">
                        <h4 class="mb-3">{{ translate('Refund summary') }}</h4>
                        <ul class="dm-info p-0 m-0 text-dark">
                            <li class="align-items-center">
                                <span class="left">{{ translate('Refund id') }} </span> <span>:</span>
                                <span class="right fw-medium">{{ $refund->id }}</span>
                            </li>
                            <li class="align-items-center">
                                <span class="left text-capitalize">
                                    {{ translate('Refund requested date') }}
                                </span>
                                <span>:</span>
                                <span class="right fw-medium">
                                    {{ date('d M Y, h:s:A',strtotime($refund['created_at'])) }}
                                </span>
                            </li>
                            <li class="align-items-center">
                                <span class="left">{{ translate('Refund status') }}</span> <span>:</span>
                                <span class="right fw-medium">
                                    @if ($refund['status'] == 'pending')
                                        <span class="badge badge-secondary text-bg-secondary text-secondary bg-opacity-10 fw-medium">
                                            {{ translate($refund['status']) }}
                                        </span>
                                    @elseif($refund['status'] == 'approved')
                                        <span class="badge badge-primary text-bg-primary text-primary bg-opacity-10 fw-medium">
                                            {{ translate($refund['status']) }}
                                        </span>
                                    @elseif($refund['status'] == 'refunded')
                                        <span class="badge badge-success text-bg-success text-success bg-opacity-10 fw-medium">
                                            {{ translate($refund['status']) }}
                                        </span>
                                    @elseif($refund['status'] == 'rejected')
                                        <span class="badge badge-danger text-bg-danger text-danger bg-opacity-10 fw-medium">
                                            {{ translate($refund['status']) }}
                                        </span>
                                    @endif
                                </span>
                            </li>
                            <li class="align-items-center">
                                <span class="left">{{ translate('Payment method') }} </span> <span>:</span> <span
                                    class="right fw-medium">{{ str_replace('_',' ',$order->payment_method) }}</span>
                            </li>
                            <li class="align-items-start">
                                <a class="text-primary fw-semibold fa-12 left"
                                        href="{{ route('admin.retail.orders.details',['id'=>$order->id]) }}">{{ translate('View Order Details') }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-xl-8">
                    <div class="card card-body shadow-none h-100 bg-section">
                        <div class="gap-3 mb-4 d-flex justify-content-between flex-wrap align-items-center">
                            <h4 class="">{{ translate('Product Details') }}</h4>
                        </div>
                        <div class="refund-details justify-content-between">
                            <div @if(is_null($refund->product)) class="parent-disabled" data-bs-toggle="tooltip"
                                 title="{{ translate('Product has been deleted') }}" @endif>
                                <div class="d-flex gap-3 flex-wrap align-items-start">
                                    <div class="img max-w-80">
                                        <div class="onerror-image border rounded">
                                            <img class="aspect-1"
                                                 src="{{ getStorageImages(path: ($refund->product ? $refund->product->thumbnail_full_url : ''),type: 'backend-product') }}"
                                                 alt="">
                                        </div>
                                    </div>
                                    <div class="--content w-fit-content max-w-280">
                                        <h4>
                                            @if ($refund->product!=null)
                                                <a class="text-dark fw-medium" href="{{ route('admin.retail.products.view',['addedBy'=>($refund->product->added_by =='seller'?'vendor' : 'in-house'),'id'=>$refund->product->id]) }}">
                                                    {{ $refund->product->name}}
                                                </a>
                                            @else
                                              {{ $orderedProductName ?? translate('Not found') }}
                                            @endif
                                        </h4>
                                        @if ($refund->orderDetails->variant)
                                            <div class="fs-12 text-dark">
                                                <span>{{ translate('Variation') }}</span>
                                                <span>:</span>
                                                <span
                                                    class="fw-medium">{{ $refund->orderDetails->variant}}</span>
                                            </div>
                                        @endif
                                        @if($refund->orderDetails->digital_file_after_sell)
                                            <a href="{{ $digitalFileDownloadPath ?? 'javascript:' }}"
                                               class="btn btn-outline--primary btn-sm mt-3 {{ $digitalFileDownloadPath ?? 'download-path-not-found' }}"
                                               title="{{ translate('Download') }}">
                                                {{ translate('Download') }} <i class="tio-download"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <ul class="dm-info p-0 m-0 w-l-115 text-dark">
                                <li>
                                    <span class="left">{{ translate('QTY') }}</span>
                                    <span>:</span>
                                    <span class="right">
                                    <strong class="fw-medium">
                                        {{ $refund->orderDetails->qty}}
                                    </strong>
                                </span>
                                </li>
                                <li>
                                    <span class="left">{{ translate('Total price') }} </span>
                                    <span>:</span>
                                    <span class="right">
                                    <strong class="fw-medium">
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $refund->orderDetails->price*$refund->orderDetails->qty), currencyCode: getCurrencyCode()) }}
                                    </strong>
                                </span>
                                </li>
                                <li>
                                    <span class="left">{{ translate('Total discount') }} </span>
                                    <span>:</span>
                                    <span class="right">
                                    <strong class="fw-medium">
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $refund->orderDetails->discount), currencyCode: getCurrencyCode()) }}
                                    </strong>
                                </span>
                                </li>
                                <li>
                                    <span class="left">{{ translate('Coupon discount') }} </span>
                                    <span>:</span>
                                    <span class="right">
                                    <strong class="fw-medium">
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $couponDiscount), currencyCode: getCurrencyCode()) }}
                                    </strong>
                                </span>
                                </li>

                                <li>
                                    <span class="left">{{ translate('Referral discount') }} </span>
                                    <span>:</span>
                                    <span class="right">
                                    <strong class="fw-medium">
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $referralDiscount), currencyCode: getCurrencyCode()) }}
                                    </strong>
                                </span>
                                </li>

                                <li>
                                    <span class="left">{{ translate('Total tax') }} </span>
                                    <span>:</span>
                                    <span class="right">
                                    <strong class="fw-medium">
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $refund->orderDetails->tax), currencyCode: getCurrencyCode()) }}
                                    </strong>
                                </span>
                                </li>

                                <li>
                                    <span class="left">{{ translate('Subtotal') }} </span>
                                    <span>:</span>
                                    <span class="right">
                                    <strong class="fw-medium">
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $subtotal), currencyCode: getCurrencyCode()) }}
                                    </strong>
                                </span>
                                </li>

                                <li>
                                    <span class="left">{{ translate('Refundable amount') }} </span>
                                    <span>:</span>
                                    <span class="right">
                                        <strong class="fw-medium">
                                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $refundAmount), currencyCode: getCurrencyCode()) }}
                                        </strong>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="{{ $order?->seller ? 'col-12 col-lg-6 col-xl-4' : 'col-lg-6 col-xl-4' }}">
                    <div class="card card-body shadow-none h-100 bg-section">
                        <h4 class="mb-3 text-capitalize">{{ translate('Refund reason by customer') }}</h4>
                        <p>
                            {{ Str::limit($refund->refund_reason, 100, '...') }}
                            @if(Str::length($refund->refund_reason) > 100)
                                <a href="#refund-reason-modal"
                                   class="text-primary text-underline text-nowrap mx-1"
                                   data-bs-toggle="modal">
                                    {{ translate('See More') }}
                                </a>
                            @endif
                        </p>
                        @if (count($refund->images_full_url) > 0)
                            <div class="position-relative">
                                <div class="refund-image-wrapper d-flex gap-3 overflow-x-auto scrollbar-hidden">
                                    @foreach ($refund->images_full_url as $key => $photo)
                                        <a
                                            href="javascript:void(0)"
                                            data-bs-toggle="modal"
                                            data-bs-target="#imgViewModal"
                                            data-index="{{ $key }}"
                                            class="w-60px aspect-1 d-block border overflow-hidden rounded flex-shrink-0">
                                            <img src="{{ getStorageImages(path: $photo, type:'backend-basic') }}"
                                                class="img-fluid w-100 h-100 object-fit-cover" alt="">
                                        </a>
                                    @endforeach
                                </div>

                                <div class="prev_btn nav--tab__prev">
                                    <button type="button" class="btn btn-circle border-0 btn-primary" style="--size: 1.5rem;">
                                        <i class="fi fi-sr-angle-left d-flex fs-10"></i>
                                    </button>
                                </div>

                                <div class="next_btn nav--tab__next">
                                    <button type="button" class="btn btn-circle border-0 btn-primary" style="--size: 1.5rem;">
                                        <i class="fi fi-sr-angle-right d-flex fs-10"></i>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                @if($vendorContact)
                    <div class="col-lg-6 col-xl-4">
                        <div class="card card-body shadow-none h-100 bg-section">
                            <h4 class="mb-3 text-capitalize">{{ translate('Vendor info') }}</h4>
                            <div class="key-val-list d-flex flex-column gap-2 min-width--60px fs-12 text-dark">
                                <div class="key-val-list-item d-flex gap-2 overflow-wrap-anywhere">
                                    <span
                                        class="text-capitalize w-100px flex-shrink-0">{{ translate('Shop Name') }}</span>:
                                    <span class="d-flex align-items-center gap-1">
                                        <span class="fw-medium">{{ $vendorContact['shop_name'] ?? translate('No Data Found') }}</span>
                                        @include('admin-views.vendor.partials._verified-badge', ['isVerified' => $vendorContact['is_verified'], 'size' => 14])
                                    </span>
                                </div>
                                <div class="key-val-list-item d-flex gap-2 overflow-wrap-anywhere">
                                    <span
                                        class="text-capitalize w-100px flex-shrink-0">{{ translate('Email address') }}</span>:
                                    <span>
                                        <a class="text-dark fw-medium" href="mailto:{{ $vendorContact['email'] }}">
                                            {{ $vendorContact['email'] ?? translate('No Data Found') }}
                                        </a>
                                </span>
                                </div>
                                <div class="key-val-list-item d-flex gap-2 overflow-wrap-anywhere">
                                    <span
                                        class="text-capitalize w-100px flex-shrink-0">{{ translate('Phone number') }}</span>:
                                    <span>
                                        <a class="text-dark fw-medium" href="tel:{{ $vendorContact['phone'] }}">
                                            {{ $vendorContact['phone'] ?? translate('No Data Found') }}
                                        </a>
                                </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="col-lg-6 col-xl-4">
                    <div class="card card-body shadow-none h-100 bg-section">
                        <h4 class="mb-3 text-capitalize">{{ translate('Deliveryman information') }}</h4>
                        <div class="key-val-list d-flex flex-column gap-2 min-width--60px h-100 fs-12 text-dark">
                            @if($order->deliveryMan)
                                <div class="key-val-list-item d-flex gap-2 overflow-wrap-anywhere">
                                    <span
                                        class="text-capitalize w-100px flex-shrink-0">{{ translate('Name') }}</span>:
                                    <span class="fw-medium">{{ $order->deliveryMan->f_name . ' ' .$order->deliveryMan->l_name}}</span>
                                </div>
                                <div class="key-val-list-item d-flex gap-2 overflow-wrap-anywhere">
                                    <span
                                        class="text-capitalize w-100px flex-shrink-0">{{ translate('Email address') }}</span>:
                                    <span>
                                    <a class="text-dark fw-medium"
                                       href="mailto:{{ $order->deliveryMan->email }}">{{ $order->deliveryMan?->email }}
                                    </a>
                                </span>
                                </div>
                                <div class="key-val-list-item d-flex gap-2 overflow-wrap-anywhere">
                                    <span
                                        class="text-capitalize w-100px flex-shrink-0">{{ translate('Phone number') }} </span>:
                                    <span>
                                    <a class="text-dark fw-medium"
                                       href="tel:{{ $order->deliveryMan->phone }}">{{ $order->deliveryMan?->phone }}
                                    </a>
                                </span>
                                </div>
                            @elseif($order->delivery_type)
                                <div class="form-group">
                                    <div class="p-2 bg-light rounded">
                                        <div class="media m-1 gap-3">
                                            <img class="avatar rounded-circle"
                                                 src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/third-party-delivery.png')}}"
                                                 alt="{{ translate('Image') }}">
                                            <div class="media-body">
                                                <h5 class="">{{ $order->delivery_service_name ?? translate('Not assign yet') }}</h5>
                                                <span
                                                    class="fs-12 text-dark">{{ translate('Track ID').' '.':'.' '.$order->third_party_delivery_tracking_id }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="card card-body shadow-none h-100">
                                    <div class="d-flex justify-content-center align-items-center gap-3 h-100">
                                        <h5 class="fs-12 fw-medium mb-0">{{ translate('No delivery man assigned') }}</h5>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card card-body d-flex flex-column gap-20">
            <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                <h3 class="mb-0">{{ translate('Refund status changed log') }}</h3>
            </div>
            <div class="table-responsive">
                <table
                    class="table table-hover table-borderless align-middle">
                    <thead class="text-capitalize">
                    <tr>
                        <th class="text-start">{{ translate('Serial') }}</th>
                        <th class="text-center">{{ translate('Changed by') }}</th>
                        <th class="text-start">{{ translate('Date') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Approved / rejected note') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($refund->refundStatus as $key => $status)
                        <tr>
                            <td>
                                {{ $key+1}}
                            </td>
                            <td class="text-capitalize text-center">
                                {{ $status->change_by == 'seller' ? 'vendor' : $status->change_by}}
                            </td>
                            <td>{{date('d M Y, h:s:A',strtotime($refund['created_at'])) }}</td>
                            <td class="text-capitalize">
                                @if ($status['status'] == 'pending')
                                    <span class="badge badge-secondary text-bg-secondary text-secondary bg-opacity-10">
                                        {{ translate($status['status']) }}
                                    </span>
                                @elseif($status['status'] == 'approved')
                                    <span class="badge badge-primary text-bg-primary text-primary bg-opacity-10">
                                        {{ translate($status['status']) }}
                                    </span>
                                @elseif($status['status'] == 'refunded')
                                    <span class="badge badge-success text-bg-success text-success bg-opacity-10">
                                        {{ translate($status['status']) }}
                                    </span>
                                @elseif($status['status'] == 'rejected')
                                    <span class="badge badge-danger text-bg-danger text-danger bg-opacity-10">
                                        {{ translate($status['status']) }}
                                    </span>
                                @endif
                            </td>

                            <td class="text-break">
                                @if($status?->message)
                                    <div class="word-break max-w-370 min-w-180">
                                        {{ Str::limit($status->message, 100, '...') }}
                                        @if(Str::length($status->message) > 100)
                                            <a href="#note-modal-{{ $status['id'] }}"
                                               class="text-primary text-underline text-nowrap mx-1"
                                               data-bs-toggle="modal">
                                                {{ translate('See More') }}
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    {{ '-' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @if(count($refund->refundStatus)==0)
                    <div class="text-center p-4">
                        <img class="mb-3 w-160"
                             src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/empty-state-icon/default.png') }}"
                             alt="{{ translate('Image description') }}">
                        <p class="mb-0">{{ translate('No data to show') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @if($refund)
        @include('admin-views.refund.partials._approval-modal', ['refund' => $refund, 'walletStatus' => $walletStatus, 'walletAddRefund' => $walletAddRefund])
        @include('admin-views.refund.partials._reject-modal', ['refund' => $refund, 'walletStatus' => $walletStatus, 'walletAddRefund' => $walletAddRefund])
        @include('admin-views.refund.partials._refund-modal', ['refund' => $refund, 'walletStatus' => $walletStatus, 'walletAddRefund' => $walletAddRefund])

        @foreach ($refund->refundStatus as $key => $status)
            @include('admin-views.refund.partials._note-modal', ['status' => $status])
        @endforeach

        @include('admin-views.refund.partials._refund-reason-modal', ['refund' => $refund, 'walletStatus' => $walletStatus, 'walletAddRefund' => $walletAddRefund])
        @include('admin-views.refund.partials._img-view-modal')
    @endif
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/libs/owl-carousel/owl.carousel.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/refund.js') }}"></script>
@endpush
