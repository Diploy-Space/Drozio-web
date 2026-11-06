@extends('layouts.admin.app')

@section('title', translate('Customer Details'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/order-items-popover.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        @include('admin-views.customer.partials._detail-header')

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex gap-3 overflow-x-auto custom-scrollable">
                    @foreach ($orderStatCards as $orderStatCard)
                        <div class="flex-grow-1 flex-shrink-0 user-select-none">
                            <div class="order-stats">
                                <div class="order-stats__content">
                                    <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/customer/'.$orderStatCard['icon']) }}" alt="">
                                    <h4 class="order-stats__subtitle text-capitalize">{{ $orderStatCard['label'] }}</h4>
                                </div>
                                <span class="order-stats__title text-success">{{ $orderStatCard['count'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <h3 class="card-title m-0 d-flex align-items-center gap-2">
                        {{ translate('Order list') }}
                        <span class="badge text-dark bg-body-secondary fw-semibold rounded-50">{{ $orders->total() }}</span>
                    </h3>
                    <div class="d-flex flex-wrap gap-3">
                        <form action="{{ url()->current() }}" method="GET" class="min-w-300 min-w-100-mobile">
                            <input type="hidden" name="sort" value="{{ $sortKey }}">
                            <div class="input-group">
                                <input type="search" name="searchValue" class="form-control"
                                       placeholder="{{ translate('Search by Order ID') }}"
                                       aria-label="{{ translate('Search by Order ID') }}"
                                       value="{{ $searchValue }}">
                                <div class="input-group-append search-submit">
                                    <button type="submit"><i class="fi fi-rr-search"></i></button>
                                </div>
                            </div>
                        </form>
                        <a href="{{ $exportUrl }}" class="btn btn-outline-primary d-flex align-items-center gap-2">
                            <i class="fi fi-sr-inbox-in"></i>
                            {{ translate('Export') }}
                        </a>
                    </div>
                </div>

                @if ($orders->total() > 0)
                    <div class="table-responsive datatable-custom">
                        <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                            <thead class="thead-light thead-50 text-capitalize">
                                <tr>
                                    <th>{{ translate('SL') }}</th>
                                    @foreach ($sortLinks as $sortColumn => $sortLink)
                                        <th class="{{ $sortColumn === 'order_amount' ? 'text-end' : ($sortColumn === 'order_status' ? 'text-center' : '') }}">
                                            <a href="{{ $sortLink['url'] }}" class="d-inline-flex align-items-center gap-1 text-dark text-nowrap">
                                                {{ $sortLink['label'] }}
                                                @if ($sortLink['direction'] === 'asc')
                                                    <i class="fi fi-rr-arrow-small-up"></i>
                                                @elseif ($sortLink['direction'] === 'desc')
                                                    <i class="fi fi-rr-arrow-small-down"></i>
                                                @else
                                                    <i class="fi fi-rr-sort-alt text-muted"></i>
                                                @endif
                                            </a>
                                        </th>
                                    @endforeach
                                    <th class="text-center">{{ translate('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $key => $order)
                                    <tr>
                                        <td>{{ $orders->firstItem() + $key }}</td>
                                        <td>
                                            <div class="text-dark">
                                                {{ $order['id'] }}
                                                @if ($order['order_type'] === 'POS')
                                                    <span class="text-primary">({{ translate('POS') }})</span>
                                                @endif
                                            </div>
                                            @if (count($itemPreviews[$order['id']] ?? []) > 0)
                                                @include('admin-views.order.partials._items-popover', ['orderId' => $order['id'], 'items' => $itemPreviews[$order['id']]])
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ date('d-m-Y', strtotime($order['created_at'])) }},</div>
                                            <div>{{ date('h:i A', strtotime($order['created_at'])) }}</div>
                                        </td>
                                        <td>
                                            @if ($order['seller_is'] === 'seller')
                                                <div class="d-flex align-items-center gap-1">
                                                    <a href="{{ $order->seller?->shop ? route('admin.people.vendors.view', ['id' => $order->seller_id]) : 'javascript:' }}"
                                                       class="fw-medium hover-primary text-dark text-wrap d-block max-w-360">
                                                        {{ $order->seller?->shop?->name ?? translate('Not found') }}
                                                    </a>
                                                    @if ($order->seller?->shop)
                                                        @include('admin-views.vendor.partials._verified-badge', ['isVerified' => $order->seller->hasVerifiedBadge(), 'size' => 14])
                                                    @endif
                                                </div>
                                            @else
                                                <a href="{{ route('admin.settings.business-settings.inhouse-shop') }}"
                                                   class="fw-medium hover-primary text-dark text-wrap d-block max-w-360">
                                                    {{ getInHouseShopConfig(key: 'name') }}
                                                </a>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="text-dark">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order['order_amount'])) }}</div>
                                            <div class="{{ $order['payment_status'] === 'paid' ? 'text-success' : 'text-danger' }}">
                                                {{ $order['payment_status'] === 'paid' ? translate('Paid') : translate('Unpaid') }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $orderStatusBadges[$order['order_status']] ?? 'badge-soft-primary' }}">
                                                {{ $orderStatusLabels[$order['order_status']] ?? $order['order_status'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a class="btn btn-outline-primary icon-btn" title="{{ translate('View') }}"
                                                   href="{{ route('admin.retail.orders.details', ['id' => $order['id']]) }}">
                                                    <i class="fi fi-rr-eye"></i>
                                                </a>
                                                <a class="btn btn-outline-success icon-btn" title="{{ translate('Invoice') }}" target="_blank"
                                                   href="{{ route('admin.retail.orders.generate-invoice', [$order['id']]) }}">
                                                    <i class="fi fi-sr-down-to-line"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        {!! $orders->links() !!}
                    </div>
                @else
                    @include('layouts.admin.partials._empty-state', ['text' => 'No order found', 'image' => 'default'])
                @endif
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/order-items-popover.js') }}"></script>
@endpush
