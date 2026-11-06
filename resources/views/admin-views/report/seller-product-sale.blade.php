@extends('layouts.admin.app')

@section('title', translate('Vendor product sale Report'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        @include('admin-views.report._page-header', [
            'icon' => 'seller-reports.png',
            'title' => 'Vendor Sales',
        ])

        <div class="card mb-3">
            <div class="card-body">
                <form action="" id="form-data" method="GET">
                    <h3 class="mb-3">{{translate('Filter Data')}}</h3>
                    <div class="bg-section rounded p-3 mb-20">
                        <div class="row g-3 align-items-end">
                            <div class="col-sm-6 col-md-3">
                                <label class="mb-2">{{translate('Select Vendor')}}</label>
                                <select class="custom-select" name="seller_id">
                                    <option value="all">{{ translate('All Vendors') }}</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor['id'] }}" {{ $sellerId === $vendor['id'] ? 'selected' : '' }}>
                                            {{ $vendor?->shop?->name ?? translate('Not found') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label class="mb-2">{{translate('Select Date')}}</label>
                                <div class="select-wrapper">
                                    <select class="form-select" name="date_type" id="date_type">
                                        <option
                                            value="this_year" {{ $dateType == 'this_year'? 'selected' : '' }}>{{translate('This Year')}}</option>
                                        <option
                                            value="this_month" {{ $dateType == 'this_month'? 'selected' : '' }}>{{translate('This Month')}}</option>
                                        <option
                                            value="this_week" {{ $dateType == 'this_week'? 'selected' : '' }}>{{translate('This Week')}}</option>
                                        <option
                                            value="today" {{ $dateType == 'today'? 'selected' : '' }}>{{translate('Today')}}</option>
                                        <option
                                            value="custom_date" {{ $dateType == 'custom_date'? 'selected' : '' }}>{{translate('Custom Date')}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3" id="from_div">
                                <div>
                                    <label class="mb-2">{{translate('Start Date')}}</label>
                                    <input type="date" name="from" value="{{$from}}" id="from_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3" id="to_div">
                                <div class="">
                                    <label class="mb-2">{{translate('End Date')}}</label>
                                    <input type="date" value="{{$to}}" name="to" id="to_date" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 justify-content-end">
                        <button type="reset" class="btn btn-secondary min-w-120">
                            {{ translate('Reset') }}
                        </button>
                        <div class="filter-btn">
                            <button type="submit" class="btn btn-primary min-w-120">
                                {{translate('Filter')}}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-6">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex justify-content-between gap-3 align-items-center">
                                <div class="info">
                                    <h4 class="subtitle h1 text-dark mb-1">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $vendorSaleSummary['total_store_earning']), currencyCode: getCurrencyCode()) }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">{{ translate('Total Vendor Earnings')}}</h5>
                                </div>
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-t-inhouse.png')}}" alt="" class="object-fit-contain">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-6">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex justify-content-between gap-3 align-items-center">
                                <div class="info">
                                    <h4 class="subtitle h1 text-dark mb-1">{{ $vendorSaleSummary['active_vendor_count'] }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">{{ translate('Active Vendors')}}</h5>
                                </div>
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-vendor-active.png')}}" alt="" class="object-fit-contain">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-6">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex justify-content-between gap-3 align-items-center">
                                <div class="info">
                                    <h4 class="subtitle h1 text-dark mb-1">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $vendorSaleSummary['average_vendor_revenue']), currencyCode: getCurrencyCode()) }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">
                                        {{ translate('Average Vendor Revenue')}}
                                    </h5>
                                </div>
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-t-commission.png')}}" alt="" class="object-fit-contain">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-6">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex justify-content-between gap-3 align-items-center">
                                <div class="info">
                                    <h4 class="subtitle h1 text-dark mb-1">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $vendorSaleSummary['average_order_value']), currencyCode: getCurrencyCode()) }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">{{ translate('Average Order Value')}}</h5>
                                </div>
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/order-revenue.png')}}" alt="" class="object-fit-contain">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-3 mb-3">
            @php($chartValues = array_map(fn ($point) => usdToDefaultCurrency(amount: $point['order_amount']), $orderAmountChart))
            @php($chartLabels = array_map(fn ($point) => $dateType === 'this_month' ? $point['duration'].' '.now()->format('M') : $point['duration'], $orderAmountChart))
            <div class="center-chart-area p-0 flex-grow-1">
                @include('layouts.admin.partials._apexcharts', [
                    'title' => 'order_statistics',
                    'statisticsValue' => $chartValues,
                    'label' => $chartLabels,
                    'statisticsTitle' => 'total_order_amount',
                ])
            </div>
            <div class="flex-grow-1">
                <div class="card h-100 bg-white payment-statistics-shadow">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
                        <div class="earning-statistics-content">
                            <img class="mb-4" src="{{ dynamicAsset(path: 'public/assets/back-end/img/earnings.svg')}}"
                                 alt="back-end/img">
                            <h3 class="title h2">
                                {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $vendorSaleSummary['total_store_earning']), currencyCode: getCurrencyCode()) }}
                            </h3>
                            <h4 class="subtitle">{{translate('Total Shop Earnings')}}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center mb-4">
                    <h3 class="mb-0 mr-auto">
                        {{ translate('Total Vendor') }}
                        <span class="badge badge-info text-bg-info">{{ $vendorSaleRows->total() }}</span>
                    </h3>

                    <div class="d-flex flex-wrap gap-3">
                        <form action="{{ url()->full() }}" method="GET" class="mb-0">
                            <div class="input-group">
                                <input type="hidden" name="seller_id" value="{{ $sellerId ?? 'all' }}">
                                <input type="hidden" name="date_type" value="{{ $dateType }}">
                                <input type="hidden" name="from" value="{{ $from }}">
                                <input type="hidden" name="to" value="{{ $to }}">
                                <input id="datatableSearch_" type="search" value="{{ $search }}" name="search"
                                       class="form-control" placeholder="{{translate('Search by vendor information')}}" aria-label="Search orders"
                                      >
                                <div class="input-group-append search-submit">
                                    <button type="submit">
                                        <i class="fi fi-rr-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <a type="button" class="btn btn-outline-primary" href="{{ route('admin.report.retail.vendor-report-export', ['date_type' => $dateType, 'seller_id' => $sellerId ?? 'all', 'from' => $from, 'to' => $to, 'search' => $search]) }}">
                            <i class="fi fi-sr-inbox-in"></i>
                            <span class="fs-12">{{ translate('Export') }}</span>
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="datatable"
                           class="table __table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{translate('Serial')}}</th>
                            <th>{{ translate('Vendor Information') }}</th>
                            <th>{{translate('Total Order')}}</th>
                            <th class="text-center">{{translate('Total Revenue')}}</th>
                            <th>{{translate('Commission')}}</th>
                            <th class="text-center">{{translate('Refund Rate')}}</th>
                            <th class="text-center">{{translate('Action')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($vendorSaleRows as $key => $vendorSale)
                            <tr>
                                <td>{{ $vendorSaleRows->firstItem() + $key }}</td>
                                <td>
                                    <div>
                                        @if ($vendorSale['shop_name'] !== null)
                                            <a class="title-color" href="{{ route('admin.people.vendors.view', ['id' => $vendorSale['seller_id']]) }}">
                                                <h4 class="mb-1 d-flex align-items-center gap-1">
                                                    {{ \Str::limit($vendorSale['shop_name'], 20) }}
                                                    @include('admin-views.vendor.partials._verified-badge', ['isVerified' => $vendorSale['is_verified'], 'size' => 16])
                                                </h4>
                                                <span class="mb-1 text-capitalize">{{ $vendorSale['vendor_name'] }}</span>
                                            </a>
                                        @else
                                            {{ translate('Not found') }}
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $vendorSale['total_order_count'] }}</td>
                                <td class="text-center">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $vendorSale['total_order_amount']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $vendorSale['total_admin_commission']), currencyCode: getCurrencyCode()) }}</td>
                                <td class="text-center">{{ $vendorSale['refund_rate'] === null ? '0%' : number_format($vendorSale['refund_rate'], 2).'%' }}</td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        @if($vendorSale['seller_id'])
                                            <a href="{{ route('admin.people.vendors.view', ['id' => $vendorSale['seller_id']]) }}" class="btn btn-outline-primary icon-btn">
                                                <i class="fi fi-rr-eye"></i>
                                            </a>
                                        @else
                                            <span class="btn btn-outline-primary icon-btn disabled">
                                                <i class="fi fi-rr-eye"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="table-responsive mt-4">
                    <div class="px-4 d-flex justify-content-lg-end">
                        {!! $vendorSaleRows->links() !!}
                    </div>
                </div>
                @if($vendorSaleRows->isEmpty())
                    @include('layouts.admin.partials._empty-state',['text'=>'no_order_found'],['image'=>'default'])
                @endif
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/apexcharts.js')}}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/apexcharts-data-show.js')}}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/admin/seller-earning-report.js') }}"></script>
@endpush
