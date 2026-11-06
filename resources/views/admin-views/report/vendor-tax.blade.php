@extends('layouts.admin.app')

@section('title', translate('Vendor Vat Report'))

@section('content')
    <div class="content container-fluid">
        @include('admin-views.report._page-header', [
            'icon' => 'total-vat-amount.png',
            'title' => 'Vendor Vat Report',
            'tooltip' => translate('Admin can also check inhouse shop tax information from this list'),
        ])

        <div class="card card-body mb-20">
            <form action="{{ route('admin.report.retail.vendor-wise-taxes') }}" method="get">
                <div class="bg-section rounded p-3 mb-20">
                    <div class="row g-4 align-items-end">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label" for="">{{ translate('Select Vendor') }}</label>
                                <select class="custom-select" name="shop_id" data-placeholder="{{ translate('Select Vendor') }}">
                                    <option value="all" selected>{{ translate('All Vendor') }}</option>
                                    @foreach($shops as $shop)
                                        <option value="{{ $shop['id'] }}" {{ request('shop_id') == $shop['id'] ? 'selected' : '' }}>
                                            {{ $shop['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label" for="">{{ translate('Date Range') }}</label>
                                <div class="position-relative">
                                    <span class="fi fi-sr-calendar icon-absolute-on-right"></span>
                                    @if(isset($startDate) && isset($endDate))
                                        <input type="text" class="js-daterangepicker_till_current form-control" name="dates"
                                               value="{{ $startDate?->format('m/d/Y') }} - {{ $endDate?->format('m/d/Y') }}">
                                    @else
                                        <input type="text" class="js-daterangepicker_till_current form-control" name="dates" value="">
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-3">
                    <a class="btn btn-secondary min-w-120" href="{{ route('admin.report.retail.vendor-wise-taxes') }}">
                        {{ translate('Reset') }}
                    </a>
                    <button class="btn btn-primary min-w-120">
                        {{ translate('Filter') }}
                    </button>
                </div>
            </form>
        </div>
        <div class="card card-body mb-20">
            <div class="row g-4">
                <div class="col-lg-6 col-xl-4">
                    <div class="d-flex align-items-center h-100">
                        <div class="w-100 h-100 bg-info-dark bg-opacity-10 d-flex align-items-center gap-2 justify-content-between p-3 rounded-10 flex-sm-nowrap flex-wrap">
                            <div class="d-flex gap-2 align-items-center">
                                <img width="30" class="aspect-1" src="{{ dynamicAsset(path: 'public/assets/back-end/img/total-order.png') }}" alt="">
                                <h4 class="fw-medium line-1 mb-0" title="{{ translate('Total Orders') }}">
                                    {{ translate('Total Orders') }}
                                </h4>
                            </div>
                            <span class="overflow-wrap-anywhere fw-bold text-info h2 mb-0">
                                {{ $totalOrders }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-xl-4">
                    <div class="d-flex align-items-center h-100">
                        <div class="w-100 h-100 bg-success-dark bg-opacity-10 d-flex align-items-center gap-2 justify-content-between p-3 rounded-10 flex-sm-nowrap flex-wrap">
                            <div class="d-flex gap-2 align-items-center">
                                <img width="30" class="aspect-1" src="{{ dynamicAsset(path: 'public/assets/back-end/img/total-order-amount.png') }}" alt="">
                                <h4 class="fw-medium line-1 mb-0" title="{{ translate('Total Order Amount') }}">
                                    {{ translate('Total Order Amount') }}
                                </h4>
                            </div>
                            <span class="overflow-wrap-anywhere fw-bold text-success h2 mb-0">
                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $totalOrderAmount), currencyCode: getCurrencyCode()) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-xl-4">
                    <div class="d-flex align-items-center h-100">
                        <div class="w-100 h-100 bg-warning-dark bg-opacity-10 d-flex align-items-center gap-2 justify-content-between p-3 rounded-10 flex-sm-nowrap flex-wrap">
                            <div class="d-flex gap-2 align-items-center">
                                <img width="30" class="aspect-1" src="{{ dynamicAsset(path: 'public/assets/back-end/img/total-vat-amount.png') }}" alt="">
                                <h4 class="fw-medium line-1 mb-0" title="{{ translate('Total VAT Amount') }}">
                                    {{ translate('Total VAT Amount') }}
                                </h4>
                            </div>
                            <span class="overflow-wrap-anywhere fw-bold text-warning h2 mb-0">
                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $totalTax), currencyCode: getCurrencyCode()) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body d-flex flex-column gap-20">
                <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                    <h3 class="mb-0">
                        {{ translate('All Vendor Vat List') }}
                        <span class="badge text-dark bg-body-secondary fw-semibold rounded-50 ms-1">
                            {{ $shopTaxList->total() }}
                        </span>
                    </h3>
                    <div class="d-flex flex-wrap gap-3 align-items-center justify-content-sm-end flex-grow-1">
                        <div class="flex-grow-1 max-w-300 min-w-100-mobile">
                            <form action="{{ url()->current() }}" method="GET">
                                <div class="input-group">
                                    <input id="" type="search" name="search" class="form-control"
                                           placeholder="{{ translate('Search here') }}"
                                           value="{{ request('search') }}">
                                    <div class="input-group-append search-submit">
                                        <button type="submit">
                                            <i class="fi fi-rr-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        @if($shopTaxList->total() > 0)
                            <div class="dropdown">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown">
                                    <i class="fi fi-sr-inbox-in"></i>
                                    <span class="fs-12">{{ translate('Export') }}</span>
                                    <i class="fi fi-rr-angle-small-down"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2"
                                           href="{{ route('admin.report.retail.vendor-wise-tax-export', ['export_type' => 'excel', request()->getQueryString()]) }}">
                                            <span class="text-success pt-1"><i class="fi fi-sr-file-excel"></i></span>
                                            {{ translate('Excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2"
                                           href="{{ route('admin.report.retail.vendor-wise-tax-export', ['export_type' => 'csv', request()->getQueryString()]) }}">
                                            <span class="text-info pt-1"><i class="fi fi-sr-file-csv"></i></span>
                                            {{ translate('CSV') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-borderless align-middle text-dark">
                        <thead class="text-capitalize">
                        <tr>
                            <th>{{ translate('Serial') }}</th>
                            <th>{{ translate('Vendor Information') }}</th>
                            <th>{{ translate('Total Order') }}</th>
                            <th>{{ translate('Total Order Amount') }}</th>
                            <th>{{ translate('VAT Amount') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                            @forelse($shopTaxList as $key => $shopTaxItem)
                                @php($shopItem = $shopTaxItem['shop'])
                                <tr>
                                    <td>{{ $shopTaxList->firstItem() + $key }}</td>
                                    <td>
                                        @if($shopItem['author_type'] === \App\Services\New\ShopService::AUTHOR_TYPE_ADMIN)
                                            <div class="d-flex align-items-center gap-1">
                                                <div class="line-1 max-w-200">
                                                    <a class="text-dark text-hover-primary" href="{{ route('admin.settings.business-settings.inhouse-shop') }}">
                                                        {{ $shopItem['name'] }}
                                                    </a>
                                                </div>
                                                @include('admin-views.vendor.partials._verified-badge', ['isVerified' => $isInHouseShopVerified, 'isInHouse' => true, 'size' => 16])
                                            </div>
                                            <p class="text-body fs-12 mb-0">
                                                <a class="text-dark text-hover-primary" href="tel:{{ $shopItem['contact'] }}">
                                                    {{ $shopItem['contact'] }}
                                                </a>
                                            </p>
                                        @else
                                            <div class="d-flex align-items-center gap-1">
                                                <div class="line-1 max-w-200">
                                                    <a class="text-dark text-hover-primary" href="{{ route('admin.people.vendors.view', ['id' => $shopItem['seller_id']]) }}">
                                                        {{ $shopItem['name'] }}
                                                    </a>
                                                </div>
                                                @include('admin-views.vendor.partials._verified-badge', ['isVerified' => $shopItem['seller']?->hasVerifiedBadge(), 'size' => 16])
                                            </div>
                                            <p class="text-body fs-12 mb-0">
                                                <a class="text-dark text-hover-primary" href="tel:{{ $shopItem['contact'] }}">
                                                    {{ $shopItem['contact'] }}
                                                </a>
                                            </p>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $shopTaxItem['total_orders'] }}
                                    </td>
                                    <td>
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $shopTaxItem['total_order_amount']), currencyCode: getCurrencyCode()) }}
                                    </td>
                                    <td>
                                        <div>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td>{{ translate('Total') }}</td>
                                                        <td><span class="px-2">:</span>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $shopTaxItem['total_tax']), currencyCode: getCurrencyCode()) }}</td>
                                                    </tr>
                                                    @foreach($shopTaxItem['taxes'] as $orderTaxItemKey => $orderTaxItem)
                                                        <tr>
                                                            @if($orderTaxItemKey == 'basic')
                                                                <td colspan="2" class="fs-12 fw-semibold pt-2">{{ ucwords('Order Tax') }}</td>
                                                            @else
                                                                <td colspan="2" class="fs-12 fw-semibold pt-2">{{ ucwords(str_replace('_', ' ', $orderTaxItemKey)) }}</td>
                                                            @endif
                                                        </tr>
                                                        @foreach($orderTaxItem as $taxItemKey => $taxAmount)
                                                            <tr>
                                                                <td class=" fs-12 text-body">{{ ucwords($taxItemKey) }}</td>
                                                                <td class=" fs-12 text-body">
                                                                    <span class="px-2">:</span>
                                                                    {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $taxAmount), currencyCode: getCurrencyCode()) }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-3">
                                            <a href="{{ route('admin.report.retail.vendor-tax', ['shop_id' => $shopTaxItem['shop_id'], 'dates' => request('dates')]) }}" class="btn btn-outline-info btn-outline-info-dark icon-btn">
                                                <i class="fi fi-sr-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.report.retail.vendor-tax-export', ['export_type' => 'excel', 'shop_id' => $shopTaxItem['shop_id'], request()->getQueryString()]) }}" class="btn btn-outline-success btn-outline-success-dark icon-btn">
                                                <i class="fi fi-sr-down-to-line"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="d-flex justify-content-center align-items-center py-5">
                                            <div class="text-center">
                                                <img width="60" class="aspect-1 mb-30"
                                                     src="{{ dynamicAsset(path: 'public/assets/back-end/img/tax.png') }}"
                                                     alt="">
                                                <h4 class="mb-2">{{ translate('No Data Found') }}</h4>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-4">
                    <div class="d-flex justify-content-lg-end">
                        {!! $shopTaxList->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include("taxmodule::6valley.offcanvas._vendor-report")
@endsection

