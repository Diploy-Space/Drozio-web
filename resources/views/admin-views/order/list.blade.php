@extends('layouts.admin.app')

@section('title', translate('Order List'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/order-items-popover.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div>
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <h2 class="h1 mb-0">
                    <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/all-orders.png') }}" class="mb-1 mr-1"
                         alt="">
                    <span class="page-header-title">
                        @if ($status == 'processing')
                            {{ translate('Packaging Orders') }}
                        @elseif($status == 'failed')
                            {{ translate('Failed to deliver orders') }}
                        @elseif($status == 'all')
                            {{ translate('All Orders') }}
                        @else
                            {{ translate(str_replace('_', ' ', $status)) }} {{ translate('Orders') }}
                        @endif
                    </span>
                </h2>
                <span class="badge text-dark bg-body-secondary fw-semibold rounded-45">{{ $orders->total() }}</span>
            </div>

            @if($status == 'all')
                <div class="card card-body mb-20">
                    <h3 class="mb-20">{{ translate('Current Order Summary') }}</h3>
                    <div class="row g-3">
                        <div class="col-lg-6 col-xl-3">
                            <a class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded"
                               href="{{ route('admin.retail.orders.list',['pending']) }}">
                                <div class="d-flex gap-3 align-items-center">
                                    <img width="20"
                                         src="{{ dynamicAsset(path: 'public/assets/back-end/img/pending.png') }}"
                                         alt="">
                                    <h4 class="mb-0">{{ translate('Pending') }}</h4>
                                </div>
                                <span class="text-primary h3 mb-0 overflow-wrap-anywhere">
                                {{ $allOrdersInfo['pending_order'] }}
                            </span>
                            </a>
                        </div>
                        <div class="col-lg-6 col-xl-3">
                            <a class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded order-stats_confirmed"
                               href="{{ route('admin.retail.orders.list',['confirmed']) }}">
                                <div class="d-flex gap-3 align-items-center">
                                    <img width="20"
                                         src="{{ dynamicAsset(path: 'public/assets/back-end/img/confirmed.png') }}"
                                         alt="">
                                    <h4 class="mb-0">{{ translate('Confirmed') }}</h4>
                                </div>
                                <span class="text-success h3 mb-0 overflow-wrap-anywhere">
                                {{ $allOrdersInfo['confirmed_order'] }}
                            </span>
                            </a>
                        </div>

                        <div class="col-lg-6 col-xl-3">
                            <a class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded order-stats_packaging"
                               href="{{ route('admin.retail.orders.list',['processing']) }}">
                                <div class="d-flex gap-3 align-items-center">
                                    <img width="20"
                                         src="{{ dynamicAsset(path: 'public/assets/back-end/img/packaging.png') }}"
                                         alt="">
                                    <h4 class="mb-0">{{ translate('Packaging') }}</h4>
                                </div>
                                <span class="text-danger h3 mb-0 overflow-wrap-anywhere">
                                {{ $allOrdersInfo['processing_order'] }}
                            </span>
                            </a>
                        </div>

                        <div class="col-lg-6 col-xl-3">
                            <a class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded order-stats_out-for-delivery"
                               href="{{ route('admin.retail.orders.list',['out_for_delivery']) }}">
                                <div class="d-flex gap-3 align-items-center">
                                    <img width="20"
                                         src="{{ dynamicAsset(path: 'public/assets/back-end/img/out-of-delivery.png') }}"
                                         alt="">
                                    <h4 class="mb-0">{{ translate('Out For Delivery') }}</h4>
                                </div>
                                <span class="text-success h3 mb-0 overflow-wrap-anywhere">
                                {{ $allOrdersInfo['out_for_delivery_order'] }}
                            </span>
                            </a>
                        </div>

                        <div class="col-lg-6 col-xl-3">
                            <a class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded order-stats_delivered cursor-pointer"
                               href="{{ route('admin.retail.orders.list',['delivered']) }}">
                                <div class="d-flex gap-3 align-items-center">
                                    <img width="20"
                                         src="{{ dynamicAsset(path: 'public/assets/back-end/img/delivered.png') }}"
                                         alt="">
                                    <h4 class="mb-0">{{ translate('Delivered') }}</h4>
                                </div>
                                <span class="text-primary h3 mb-0 overflow-wrap-anywhere">
                                {{ $allOrdersInfo['delivered_order'] }}
                            </span>
                            </a>
                        </div>

                        <div class="col-lg-6 col-xl-3">
                            <a class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded order-stats_canceled cursor-pointer"
                               href="{{ route('admin.retail.orders.list',['canceled']) }}">
                                <div class="d-flex gap-3 align-items-center">
                                    <img width="20"
                                         src="{{ dynamicAsset(path: 'public/assets/back-end/img/canceled.png') }}"
                                         alt="">
                                    <h4 class="mb-0">{{ translate('Canceled') }}</h4>
                                </div>
                                <span class="text-danger h3 mb-0 overflow-wrap-anywhere">
                                {{ $allOrdersInfo['canceled_order'] }}
                            </span>
                            </a>
                        </div>

                        <div class="col-lg-6 col-xl-3">
                            <a class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded order-stats_returned cursor-pointer"
                               href="{{ route('admin.retail.orders.list',['returned']) }}">
                                <div class="d-flex gap-3 align-items-center">
                                    <img width="20"
                                         src="{{ dynamicAsset(path: 'public/assets/back-end/img/returned.png') }}"
                                         alt="">
                                    <h4 class="mb-0">{{ translate('Returned') }}</h4>
                                </div>
                                <span class="text-warning h3 mb-0 overflow-wrap-anywhere">
                                {{ $allOrdersInfo['returned_order'] }}
                            </span>
                            </a>
                        </div>

                        <div class="col-lg-6 col-xl-3">
                            <a class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded order-stats_failed cursor-pointer"
                               href="{{ route('admin.retail.orders.list',['failed']) }}">
                                <div class="d-flex gap-3 align-items-center">
                                    <img width="20"
                                         src="{{ dynamicAsset(path: 'public/assets/back-end/img/failed-to-deliver.png') }}"
                                         alt="">
                                    <h4 class="mb-0">{{ translate('Failed to deliver') }}</h4>
                                </div>
                                <span class="text-danger h3 mb-0 overflow-wrap-anywhere">
                                {{ $allOrdersInfo['failed_order'] }}
                            </span>
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card mt-3">
                <div class="card-body d-flex flex-column gap-20">

                    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center">
                        <h3 class="mb-0 d-flex gap-2 align-items-center">
                            {{ translate('Order List') }}
                            <span class="badge text-dark bg-body-secondary fw-semibold rounded-45">
                                {{ $orders->total() }}
                            </span>
                        </h3>

                        <div class="d-flex gap-3 align-items-center flex-wrap">
                            <form action="{{ url()->current() }}" method="GET" class="flex-grow-1">
                                <div class="form-group">
                                    <div class="input-group min-w-300">
                                        <input id="datatableSearch_" type="search" name="searchValue"
                                               class="form-control" placeholder="{{ translate('Search by Order ID') }}"
                                               aria-label="Search by Order ID" value="{{ $searchValue }}">
                                        <div class="input-group-append search-submit">
                                            <button type="submit">
                                                <i class="fi fi-rr-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <a type="button" class="btn btn-outline-primary"
                               href="{{ route('admin.retail.orders.export-excel', $exportQuery) }}">
                                <i class="fi fi-sr-inbox-in"></i>
                                <span class="fs-12">{{ translate('Export') }}</span>
                            </a>
                            <div class="position-relative">
                                @if($hasActiveFilters)
                                    <div
                                        class="position-absolute inset-inline-end-0 top-0 mt-n1 me-n1 btn-circle bg-danger border border-white border-2"
                                        style="--size: 12px;"></div>
                                @endif
                                <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas"
                                        data-bs-target="#offcanvasOrderFilter">
                                    <i class="fi fi-sr-settings-sliders"></i>
                                    {{ translate('Filter') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless">
                            <thead class="text-capitalize">
                            <tr>
                                <th>{{ translate('Serial') }}</th>
                                <th>{{ translate('Order ID') }}</th>
                                <th class="text-capitalize">{{ translate('Order Date') }}</th>
                                <th class="text-capitalize">{{ translate('Customer Information') }}</th>
                                <th>{{ translate('Store') }}</th>
                                <th class="text-capitalize text-end">{{ translate('Total Amount') }}</th>
                                @if ($status == 'all')
                                    <th class="text-center">{{ translate('Order Status') }} </th>
                                @else
                                    <th class="text-capitalize">{{ translate('Payment method') }} </th>
                                @endif
                                <th class="text-center">{{ translate('Action') }}</th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach ($orders as $key => $order)

                                <tr class="status-{{ $order['order_status'] }} class-all">
                                    <td class="">
                                        {{ $orders->firstItem() + $key }}
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-column">
                                            <div class="d-flex align-items-center gap-1">
                                                <a class="hover-primary text-dark"
                                                   href="{{ route('admin.retail.orders.details', ['id' => $order['id']]) }}">
                                                    {{ $order['id'] }}
                                                    {!! $order->order_type == 'POS' ? '<span class="text--primary">(POS)</span>' : '' !!}
                                                </a>
                                                @if($order->edited_status == 1 && $order?->latestEditHistory?->order_due_payment_status == 'unpaid' && $order?->latestEditHistory?->order_due_payment_method != "offline_payment" && $order?->latestEditHistory?->order_due_payment_method != "cash_on_delivery" && $order?->latestEditHistory?->order_due_amount > 0)
                                                    <span class="flex-shrink-0" data-bs-toggle="tooltip"
                                                          title="{{ translate('Customer will pay the due amount') }}">
                                                        <img width="14"
                                                             src="{{ dynamicAsset(path: 'public/assets/back-end/img/due-amount-icon.png') }}"
                                                             alt="">
                                                    </span>
                                                @elseif($order->edited_status == 1 && $order?->latestEditHistory?->order_return_payment_status == 'pending' && $order?->latestEditHistory?->order_return_amount > 0)
                                                    <span class="flex-shrink-0" data-bs-toggle="tooltip"
                                                          title="{{ translate('You need to return the excess amount to the customer.') }}">
                                                        <img width="16"
                                                             src="{{ dynamicAsset(path: 'public/assets/back-end/img/return-amount-icon.png') }}"
                                                             alt="">
                                                    </span>
                                                @endif
                                            </div>
                                            @if (count($itemPreviews[$order['id']] ?? []) > 0)
                                                @include('admin-views.order.partials._items-popover', ['orderId' => $order['id'], 'items' => $itemPreviews[$order['id']]])
                                            @endif
                                            @if($order->edited_status == 1)
                                                <span class="badge badge-info text-bg-info w-max-content">
                                                        {{ translate('Edited') }}
                                                    </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ date('d M Y', strtotime($order['created_at'])) }},</div>
                                        <div>{{ date('h:i A', strtotime($order['created_at'])) }}</div>
                                    </td>
                                    <td>
                                        @if ($order->is_guest)
                                            <strong class="text-dark">{{ translate('Guest customer') }}</strong>
                                        @elseif($order->customer_id == 0)
                                            <strong class="text-dark">{{ translate('Walk-In-Customer') }}</strong>
                                        @else
                                            @if ($order->customer)
                                                <a class="text-capitalize hover-primary text-dark"
                                                   href="{{ route('admin.people.customer.view', ['user_id' => $order->customer['id']]) }}">
                                                    <strong class="title-name max-w-250 text-wrap line-1">
                                                        {{ $order->customer['f_name'] . ' ' . $order->customer['l_name'] }}
                                                    </strong>
                                                </a>
                                                @if ($order->customer['phone'])
                                                    <a class="d-block text-dark"
                                                       href="tel:{{ $order->customer['phone'] }}">{{ $order->customer['phone'] }}</a>
                                                @else
                                                    <a class="d-block text-dark"
                                                       href="mailto:{{ $order->customer['email'] }}">{{ $order->customer['email'] }}</a>
                                                @endif
                                            @else
                                                <label class="badge badge-danger text-bg-danger">
                                                    {{ translate('Not found') }}
                                                </label>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if (isset($order->seller_id) && isset($order->seller_is))
                                            <div class="d-flex align-items-center gap-1">
                                                <a href="{{ $order->seller_is == \App\Enums\UserType::SELLER && $order->seller?->shop ? route('admin.people.vendors.view', ['id' => $order->seller_id]) : route('admin.settings.business-settings.inhouse-shop') }}"
                                                   class="store-name fw-medium hover-primary text-dark text-wrap d-block max-w-360">
                                                    @if ($order->seller_is == \App\Enums\UserType::SELLER)
                                                        {{ isset($order->seller?->shop) ? $order->seller?->shop?->name : translate('Not found') }}
                                                    @elseif($order->seller_is == \App\Enums\UserType::ADMIN)
                                                        {{ $inHouseShopName }}
                                                    @endif
                                                </a>
                                                @if ($order->seller_is == \App\Enums\UserType::SELLER && $order->seller?->shop)
                                                    @include('admin-views.vendor.partials._verified-badge', ['isVerified' => $order->seller->hasVerifiedBadge(), 'size' => 16])
                                                @endif
                                            </div>
                                        @else
                                            {{ translate('Not found') }}
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div>
                                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummaries[$order['id']]['totalAmount']), currencyCode: getCurrencyCode()) }}
                                        </div>

                                        @if ($order->payment_status == 'paid')
                                            <span
                                                class="fs-12 fw-medium text-success">{{ translate('Paid') }}</span>
                                        @else
                                            <span
                                                class="fs-12 fw-medium text-danger">{{ translate('Unpaid') }}</span>
                                        @endif
                                    </td>
                                    @if ($status == 'all')
                                        <td class="text-center text-capitalize">
                                            @if ($order['order_status'] == 'pending')
                                                <span class="badge badge-info text-bg-info">
                                                        {{ translate($order['order_status']) }}
                                                    </span>
                                            @elseif($order['order_status'] == 'processing' || $order['order_status'] == 'out_for_delivery')
                                                <span class="badge badge-warning text-bg-warning">
                                                        {{ str_replace('_', ' ', $order['order_status'] == 'processing' ? translate('Packaging') : translate($order['order_status'])) }}
                                                    </span>
                                            @elseif($order['order_status'] == 'confirmed')
                                                <span class="badge badge-success text-bg-success">
                                                        {{ translate($order['order_status']) }}
                                                    </span>
                                            @elseif($order['order_status'] == 'failed')
                                                <span class="badge badge-danger text-bg-danger">
                                                        {{ translate('Failed to deliver') }}
                                                    </span>
                                            @elseif($order['order_status'] == 'delivered')
                                                <span class="badge badge-success text-bg-success">
                                                        {{ translate($order['order_status']) }}
                                                    </span>
                                            @else
                                                <span class="badge badge-danger text-bg-danger">
                                                        {{ translate($order['order_status']) }}
                                                    </span>
                                            @endif
                                        </td>
                                    @else
                                        <td class="text-capitalize">
                                            {{ str_replace('_', ' ', $order['payment_method']) }}
                                        </td>
                                    @endif
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            @if($order->edited_status == 1 && $order?->edit_return_amount > 0)
                                                <button type="button" class="btn btn-outline-warning icon-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#returnDueAmountModal-{{$order['id']}}">
                                                    <i class="fi fi-sr-undo-alt d-flex"></i>
                                                </button>
                                            @endif
                                            <a class="btn btn-outline-success btn-outline-success-dark icon-btn"
                                               title="{{ translate('View') }}"
                                               href="{{ route('admin.retail.orders.details', ['id' => $order['id']]) }}">
                                                <i class="fi fi-sr-eye d-flex"></i>
                                            </a>
                                            <a class="btn btn-outline-success btn-outline-success-dark icon-btn"
                                               target="_blank" title="{{ translate('Invoice') }}"
                                               href="{{ route('admin.retail.orders.generate-invoice', [$order['id']]) }}">
                                                <i class="fi fi-sr-down-to-line d-flex"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="table-responsive">
                        <div class="d-flex justify-content-lg-end">
                            {!! $orders->links() !!}
                        </div>
                    </div>
                    @if (count($orders) == 0)
                        @include(
                            'layouts.admin.partials._empty-state',
                            ['text' => 'no_order_found'],
                            ['image' => 'default']
                        )
                    @endif
                </div>
            </div>
            <div class="js-nav-scroller hs-nav-scroller-horizontal d-none">
                <span class="hs-nav-scroller-arrow-prev d-none">
                    <a class="hs-nav-scroller-arrow-link" href="javascript:">
                        <i class="fi fi-rr-angle-left"></i>
                    </a>
                </span>

                <span class="hs-nav-scroller-arrow-next d-none">
                    <a class="hs-nav-scroller-arrow-link" href="javascript:">
                        <i class="fi fi-rr-angle-right"></i>
                    </a>
                </span>
                <ul class="nav nav-tabs page-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">{{ translate('Order List') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <span id="message-date-range-text" data-text="{{ translate('Invalid date range') }}"></span>

    @include('admin-views.order.partials._filter-offcanvas')

    @if($orders->isNotEmpty())
        @foreach($orders as $order)
            @include('admin-views.order.partials.modal.order-edit-return-amount-modal',['order'=>$order])
        @endforeach
    @endif
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/order.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/order-items-popover.js') }}"></script>
@endpush
