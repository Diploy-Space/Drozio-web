@extends('layouts.admin.app')
@section('title', translate('Product Report'))
@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        @include('admin-views.report._page-header', [
            'icon' => 'seller_sale.png',
            'title' => 'Product Report',
        ])

        @include('admin-views.report.product-report-inline-menu')

        <div class="card card-body mb-3">
            <form action="" id="form-data" method="GET">
                <input type="hidden" name="search" value="{{ $search }}">
                <h3 class="mb-3">{{ translate('Filter Data') }}</h3>
                <div class="bg-section rounded p-12 p-sm-20 mb-20">
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-6 col-xl-3">
                            <label class="text-dark mb-2">{{ translate('Select Vendor') }}</label>
                            <select class="custom-select" name="seller_id">
                                <option class="text-center" value="all" {{ $sellerId === 'all' ? 'selected' : '' }}>
                                    {{ translate('All') }}
                                </option>
                                <option class="text-center" value="inhouse" {{ $sellerId === 'inhouse' ? 'selected' : '' }}>
                                    {{ translate('In-house') }}
                                </option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor['id'] }}" {{ $sellerId === $vendor['id'] ? 'selected' : '' }}>
                                        {{ $vendor?->shop?->name ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <label class="text-dark mb-2">{{ translate('Select Date') }}</label>
                            <div class="select-wrapper">
                                <select class="form-select" name="date_type" id="date_type">
                                    <option value="this_year" {{ $dateType === 'this_year' ? 'selected' : '' }}>{{ translate('This Year') }}</option>
                                    <option value="this_month" {{ $dateType === 'this_month' ? 'selected' : '' }}>{{ translate('This Month') }}</option>
                                    <option value="this_week" {{ $dateType === 'this_week' ? 'selected' : '' }}>{{ translate('This Week') }}</option>
                                    <option value="today" {{ $dateType === 'today' ? 'selected' : '' }}>{{ translate('Today') }}</option>
                                    <option value="custom_date" {{ $dateType === 'custom_date' ? 'selected' : '' }}>{{ translate('Custom Date') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3" id="from_div">
                            <div>
                                <label class="text-dark mb-2">{{ ucwords(translate('Start Date')) }}</label>
                                <input type="date" name="from" value="{{ $from }}" id="from_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3" id="to_div">
                            <div>
                                <label class="text-dark mb-2">{{ ucwords(translate('End Date')) }}</label>
                                <input type="date" value="{{ $to }}" name="to" id="to_date" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ url()->current() }}" class="btn btn-secondary flex-grow-1 max-w-120">
                        {{ translate('Reset') }}
                    </a>
                    <button type="submit" class="btn btn-primary flex-grow-1 max-w-120 filter-btn">
                        {{ translate('Filter') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="card card-body mb-3">
            <div class="row g-3">
                <div class="col-xl-8">
                    <div class="bg-section rounded-10 p-12 p-sm-20 h-100">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="d-flex gap-3 align-items-center bg-white rounded-10 p-2 overflow-wrap-anywhere">
                                    <div class="flex-shrink-0 aspect-1 border rounded-circle w-60px d-grid place-items-center bg-white">
                                        <img width="30" src="{{ dynamicAsset(path: 'public/assets/back-end/img/packaging-new.png') }}" alt="" class="aspect-1">
                                    </div>
                                    <div>
                                        <h2 class="fs-26 fw-bold mb-2 text-primary">{{ $productStatusCounts['total_product_count'] }}</h2>
                                        <div class="text-dark">{{ translate('Total Product') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="bg-white rounded-10 px-3 py-2 overflow-wrap-anywhere">
                                    <h3 class="fs-20">
                                        <strong class="text-danger">{{ $productStatusCounts['rejected_product_count'] }}</strong>
                                    </h3>
                                    <div class="text-dark">{{ translate('Rejected') }}</div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="bg-white rounded-10 px-3 py-2 overflow-wrap-anywhere">
                                    <h3 class="fs-20">
                                        <strong class="text-primary">{{ $productStatusCounts['pending_product_count'] }}</strong>
                                    </h3>
                                    <div class="text-dark">{{ translate('Pending') }}</div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="bg-white rounded-10 px-3 py-2 overflow-wrap-anywhere">
                                    <h3 class="fs-20">
                                        <strong class="text-success">{{ $productStatusCounts['active_product_count'] }}</strong>
                                    </h3>
                                    <div class="text-dark">{{ translate('Active') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="d-flex flex-column gap-3 h-100">
                        <div class="d-flex gap-3 align-items-center bg-success bg-opacity-10 rounded-10 p-2 px-sm-5 overflow-wrap-anywhere h-100">
                            <div class="flex-shrink-0 aspect-1 border rounded-circle w-60px d-grid place-items-center bg-white">
                                <img width="30" src="{{ dynamicAsset(path: 'public/assets/back-end/img/total-product-sale.png') }}" alt="" class="aspect-1">
                            </div>
                            <div>
                                <h2 class="fs-26 fw-bold mb-2 text-success">{{ $productSaleSummary['total_sold_quantity'] }}</h2>
                                <div class="text-dark">{{ translate('Total Product Sale') }}</div>
                            </div>
                        </div>
                        <div class="d-flex gap-3 align-items-center bg-warning bg-opacity-10 rounded-10 p-2 px-sm-5 overflow-wrap-anywhere h-100">
                            <div class="flex-shrink-0 aspect-1 border rounded-circle w-60px d-grid place-items-center bg-white">
                                <img width="30" src="{{ dynamicAsset(path: 'public/assets/back-end/img/discount.png') }}" alt="" class="aspect-1">
                            </div>
                            <div>
                                <h2 class="fs-26 fw-bold mb-2 text-warning">
                                    {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $productSaleSummary['total_discount']), currencyCode: getCurrencyCode()) }}
                                </h2>
                                <div class="text-dark d-flex gap-1 align-items-center">
                                    {{ translate('Total Discount Given') }}
                                    <span class="lh-1" data-bs-toggle="tooltip" data-bs-title="{{ translate('Product wise discounted amount will be shown here') }}">
                                        <i class="fi fi-rr-info"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php($chartLabels = array_map(fn ($point) => $dateType === 'this_month' ? $point['duration'].' '.now()->format('M') : $point['duration'], $productCountChart))
        @include('layouts.admin.partials._apexcharts', [
            'title' => 'product_Statistics',
            'statisticsValue' => array_column($productCountChart, 'product_count'),
            'label' => $chartLabels,
            'statisticsTitle' => 'total_product',
            'getCurrency' => false,
        ])

        <div class="card card-body mt-3">
            <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center mb-4">
                <form action="" method="GET">
                    <div class="form-group">
                        <div class="input-group">
                            <input type="hidden" name="seller_id" value="{{ $sellerId }}">
                            <input type="hidden" name="date_type" value="{{ $dateType }}">
                            <input type="hidden" name="from" value="{{ $from }}">
                            <input type="hidden" name="to" value="{{ $to }}">
                            <input id="datatableSearch_" type="search" name="search" class="form-control min-w-300" placeholder="{{ translate('Search Product Name') }}" value="{{ $search }}">
                            <div class="input-group-append search-submit">
                                <button type="submit">
                                    <i class="fi fi-rr-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="dropdown">
                    <a type="button" class="btn btn-outline-primary" href="{{ route('admin.report.retail.all-product-excel', ['seller_id' => $sellerId, 'search' => $search, 'date_type' => $dateType, 'from' => $from, 'to' => $to]) }}">
                        <i class="fi fi-sr-inbox-in"></i>
                        <span class="fs-12">{{ translate('Export') }}</span>
                    </a>
                </div>
            </div>

            <div class="table-responsive" id="products-table">
                <table class="table table-hover __table table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 {{ Session::get('direction') === 'rtl' ? 'text-right' : 'text-left' }}">
                    <thead class="thead-light thead-50 text-capitalize">
                    <tr>
                        <th>{{ translate('Serial') }}</th>
                        <th>{{ translate('Product Name') }}</th>
                        <th>{{ translate('Product Unit Price') }}</th>
                        <th>{{ translate('Total Amount Sold') }}</th>
                        <th>{{ translate('Total Quantity Sold') }}</th>
                        <th><span>{{ translate('Average Product Value') }}</span></th>
                        <th>{{ translate('Current Stock Amount') }}</th>
                        <th>{{ translate('Average Ratings') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($products as $key => $product)
                        <tr>
                            <td>{{ $products->firstItem() + $key }}</td>
                            <td>
                                @include('admin-views.report._product-name', ['product' => $product, 'url' => route('admin.retail.products.view', ['addedBy' => $product['added_by'] === 'seller' ? 'vendor' : 'in-house', 'id' => $product['id']])])
                            </td>
                            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $product['unit_price']), currencyCode: getCurrencyCode()) }}</td>
                            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $product['total_sold_amount']), currencyCode: getCurrencyCode()) }}</td>
                            <td>{{ $product['total_sold_quantity'] }}</td>
                            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $product['average_sold_price']), currencyCode: getCurrencyCode()) }}</td>
                            <td>
                                {{ $product['is_digital'] ? ($product['is_active'] ? translate('Available') : translate('Not available')) : $product['current_stock'] }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1 lh-1">
                                    <div class="rating d-flex align-items-center gap-1">
                                        <i class="fi fi-sr-star text-warning-dark fs-12"></i>
                                        <span class="fw-semibold">
                                            {{ $product['average_rating'] > 0 ? number_format($product['average_rating'], 2, '.', ' ') : 0 }}
                                        </span>
                                    </div>
                                    <div>
                                        ({{ $product['review_count'] }})
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-responsive mt-4">
                <div class="px-4 d-flex justify-content-center justify-content-md-end">
                    {!! $products->links() !!}
                </div>
            </div>
            @if(count($products) === 0)
                @include('layouts.admin.partials._empty-state', ['text' => 'no_product_found'], ['image' => 'default'])
            @endif
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/apexcharts.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/apexcharts-data-show.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/admin/product-report.js') }}"></script>
@endpush
