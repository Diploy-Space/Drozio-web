@extends('layouts.admin.app')
@section('title', translate('Vendor Earning'))

@section('content')
    <div class="content container-fluid">
        @include('admin-views.report._page-header', [
            'icon' => 'earning_report.png',
            'title' => 'Earning Reports',
        ])

        @include('admin-views.report.earning-report-inline-menu')

        <div class="card mb-3">
            <div class="card-body">
                <form action="" id="form-data" method="GET">
                    <h4 class="mb-3">{{ translate('Filter Data')}}</h4>
                    <div class="bg-section rounded p-3 mb-20">
                        <div class="row gy-3 gx-2 align-items-end">
                            <div class="col-sm-6 col-md-4">
                                <label class="mb-2">{{ translate('Select Date')}}</label>
                                <select class="form-select" name="date_type" id="date_type">
                                    <option value="this_year" {{ $dateType == 'this_year'? 'selected' : '' }}>
                                        {{ translate('This Year') }}
                                    </option>
                                    <option value="this_month" {{ $dateType == 'this_month'? 'selected' : '' }}>
                                        {{ translate('This Month') }}
                                    </option>
                                    <option value="this_week" {{ $dateType == 'this_week'? 'selected' : '' }}>
                                        {{ translate('This Week') }}
                                    </option>
                                    <option value="today" {{ $dateType == 'today'? 'selected' : '' }}>
                                        {{ translate('Today') }}
                                    </option>
                                    <option value="custom_date" {{ $dateType == 'custom_date'? 'selected' : '' }}>
                                        {{ translate('Custom Date') }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-sm-6 col-md-4" id="from_div">
                                <div>
                                    <label class="mb-2">{{ translate('Start Date')}}</label>
                                    <input type="date" name="from" value="{{ $from }}" id="from_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4" id="to_div">
                                <div class="">
                                    <label class="mb-2">{{ translate('End Date')}}</label>
                                    <input type="date" value="{{ $to }}" name="to" id="to_date" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 justify-content-end">
                        <button type="button" class="btn btn-secondary min-w-120">
                            {{ translate('Reset') }}
                        </button>
                        <button type="submit" class="btn btn-primary min-w-120">
                            {{ translate('Filter') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-6">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex gap-3 align-items-center">
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-t-earning.png')}}" alt="" class="object-fit-contain">
                                <div class="info">
                                    <h4 class="subtitle h1 text-info mb-1">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $vendorSalesTotal), currencyCode: getCurrencyCode()) }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">{{ translate('Total Vendor Sales')}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-6">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex gap-3 align-items-center">
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-t-inhouse.png')}}" alt="" class="object-fit-contain">
                                <div class="info">
                                    <h4 class="subtitle h1 text-warning mb-1">{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $vendorEarningSummary['total_earning']), currencyCode: getCurrencyCode()) }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">{{ translate('Total Vendor Earnings')}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-6">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex gap-3 align-items-center">
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-vendor-active.png')}}" alt="" class="object-fit-contain">
                                <div class="info">
                                    <h4 class="subtitle h1 text-primary mb-1">{{ $vendorEarningSummary['active_vendor_count'] }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">{{ translate('Active Vendors')}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-6">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex gap-3 align-items-center">
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-vendor-revenue.png')}}" alt="" class="object-fit-contain">
                                <div class="info">
                                    <h4 class="subtitle h1 text-danger-dark mb-1">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $vendorEarningSummary['average_vendor_revenue']), currencyCode: getCurrencyCode()) }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">{{ translate('Average Vendor Revenue')}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-3 mb-3">
            @php($chartValues = array_map(fn ($point) => usdToDefaultCurrency(amount: $point['total_earning']), $earningChart))
            @php($chartLabels = array_map(fn ($point) => $dateType === 'this_month' ? $point['duration'].' '.now()->format('M') : $point['duration'], $earningChart))
            <div class="center-chart-area p-0 flex-grow-1">
                @include('layouts.admin.partials._apexcharts', [
                    'title' => 'earning_Statistics',
                    'statisticsValue' => $chartValues,
                    'label' => $chartLabels,
                    'statisticsTitle' => 'total_Earnings',
                    'average' => array_sum(array_column($earningChart, 'total_earning')) / max(count($earningChart), 1),
                ])
            </div>
            <div class="flex-grow-1">
                <div class="card h-100 bg-white payment-statistics-shadow">
                    <div class="card-header border-0 ">
                        <h4 class="card-title">{{ translate('Vendor Wallet Status')}}</h4>
                    </div>
                    <div class="card-body px-0 pt-0">
                        <div class="position-relative pie-chart">
                            <div id="dognut-pie" class="label-hide d-flex justify-content-center"></div>
                            <div class="total--orders">
                                <h3>{{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $vendorWallet['wallet_amount'])) }}</h3>
                                <span>{{ translate('Wallet Amount')}}</span>
                            </div>
                        </div>
                        <div class="apex-legends">
                            <div data-color="#004188">
                                <span>{{translate('Withdrawable balance')}} ({{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $vendorWallet['withdrawable_balance']), currencyCode: getCurrencyCode()) }})</span>
                            </div>
                            <div data-color="#0177CD">
                                <span>{{translate('Pending Withdraws')}} ({{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $vendorWallet['pending_withdraw']), currencyCode: getCurrencyCode()) }})</span>
                            </div>
                            <div data-color="#A2CEEE">
                                <span>{{translate('Already Withdrawn')}} ({{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $vendorWallet['already_withdrawn']), currencyCode: getCurrencyCode()) }})</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-4">
                    <h3 class="mb-0 mr-auto">
                        {{translate('Total Vendor')}}
                        <span class="badge badge-info text-bg-info fs-12">{{ count($vendorEarningRows) }}</span>
                    </h3>

                    <a type="button" class="btn btn-outline-primary"
                       href="{{ route('admin.report.retail.vendor-earning-excel-export', ['date_type' => $dateType, 'from' => $from, 'to' => $to]) }}">
                        <i class="fi fi-sr-inbox-in"></i>
                        <span class="fs-12">{{ translate('Export') }}</span>
                    </a>
                </div>

                <div class="table-responsive">
                    <table id="datatable"
                           class="table __table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{translate('Serial')}}</th>
                            <th>{{translate('Vendor Information')}}</th>
                            <th>{{translate('Earn From Order')}}</th>
                            <th>{{translate('Earn From Shipping')}}</th>
                            <th>{{translate('Delivery man incentive')}}</th>
                            <th>{{translate('Commission Given')}}</th>
                            <th>{{translate('Discount Given')}}</th>
                            <th>{{translate('Tax Collected')}}</th>
                            <th>{{translate('Refund Given')}}</th>
                            <th>{{translate('Total Earning')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($vendorEarningRows as $seller_earn)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div>
                                        <h4 class="mb-1">
                                            <a title="{{ $seller_earn['vendor_info'] }}" class="title-color"
                                               href="{{ route('admin.people.vendors.view', ['id' => $seller_earn['vendor_id']]) }}">
                                                {{ Str::limit($seller_earn['vendor_info'], 20, '...') }}
                                            </a>
                                        </h4>
                                    </div>
                                </td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['earn_from_order']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['earn_from_shipping']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['deliveryman_incentive']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['commission_given']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['discount_given']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['tax_collected']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['refund_given']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['total_earning']), currencyCode: getCurrencyCode()) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if(count($vendorEarningRows) === 0)
                    @include('layouts.admin.partials._empty-state',['text'=>'no_data_found'],['image'=>'default'])
                @endif
            </div>
        </div>
    </div>

    <span id="currency_symbol" data-text="{{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}"></span>

    <span id="withdrawable_balance"
          data-text="{{ usdToDefaultCurrency(amount: $vendorWallet['withdrawable_balance']) }}"></span>
    <span id="pending_withdraw"
          data-text="{{ usdToDefaultCurrency(amount: $vendorWallet['pending_withdraw']) }}"></span>
    <span id="already_withdrawn"
          data-text="{{ usdToDefaultCurrency(amount: $vendorWallet['already_withdrawn']) }}"></span>

    <span id="withdrawable_balance_text" data-text="{{translate('Withdrawable balance')}}"></span>
    <span id="pending_withdraw_text" data-text="{{translate('Pending Withdraws')}}"></span>
    <span id="already_withdrawn_text" data-text="{{translate('Already Withdrawn')}}"></span>

    <span id="withdrawable_balance_format"
          data-text="{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $vendorWallet['withdrawable_balance'])) }}"></span>
    <span id="pending_withdraw_format"
          data-text="{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $vendorWallet['pending_withdraw'])) }}"></span>
    <span id="already_withdrawn_format"
          data-text="{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $vendorWallet['already_withdrawn'])) }}"></span>
@endsection

@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/new/back-end/js/apexcharts.js')}}"></script>
    <script src="{{dynamicAsset(path: 'public/assets/new/back-end/js/apexcharts-data-show.js')}}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/admin/seller-earning-report.js') }}"></script>
@endpush
