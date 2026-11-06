@extends('layouts.admin.app')
@section('title', translate('Admin Earning'))

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
                    <h3 class="mb-3">{{ translate('Filter Data')}}</h3>
                    <div class="bg-section rounded p-3 mb-20">
                        <div class="row gy-3 gx-2 align-items-end">
                            <div class="col-sm-6 col-md-4">
                                <label class="mb-2">{{ translate('Select Date')}}</label>
                                <select class="form-select" name="date_type" id="date_type">
                                    <option value="this_year" {{ $dateType == 'this_year'? 'selected' : '' }}>{{translate('This Year')}}</option>
                                    <option value="this_month" {{ $dateType == 'this_month'? 'selected' : '' }}>{{translate('This Month')}}</option>
                                    <option value="this_week" {{ $dateType == 'this_week'? 'selected' : '' }}>{{translate('This Week')}}</option>
                                    <option value="today" {{ $dateType == 'today'? 'selected' : '' }}>{{translate('Today')}}</option>
                                    <option value="custom_date" {{ $dateType == 'custom_date'? 'selected' : '' }}>{{translate('Custom Date')}}</option>
                                </select>
                            </div>
                            <div class="col-sm-6 col-md-4" id="from_div">
                                <div class="">
                                    <label class="mb-2">{{ ucwords(translate('Start Date'))}}</label>
                                    <input type="date" name="from" value="{{ $from }}" id="from_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4" id="to_div">
                                <div class="">
                                    <label class="mb-2">{{ ucwords(translate('End Date'))}}</label>
                                    <input type="date" value="{{ $to }}" name="to" id="to_date" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 justify-content-end">
                        <button type="reset" class="btn btn-secondary min-w-120">
                            {{ translate('Reset') }}
                        </button>
                        <button type="submit" class="btn btn-primary min-w-120">
                            {{ translate('Filter')}}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-4">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex gap-3 align-items-center">
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-t-earning.png')}}" alt="" class="object-fit-contain">
                                <div class="info">
                                    <h4 class="subtitle h1 text-info mb-1">{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earningSummary['total_earning']), currencyCode: getCurrencyCode()) }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">{{ translate('Total earnings')}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex gap-3 align-items-center">
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-t-commission.png')}}" alt="" class="object-fit-contain">
                                <div class="info">
                                    <h4 class="subtitle h1 text-success mb-1">{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earningSummary['total_commission']), currencyCode: getCurrencyCode()) }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">{{ translate('Commission Earning')}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex gap-3 align-items-center">
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-t-shipping.png')}}" alt="" class="object-fit-contain">
                                <div class="info">
                                    <h4 class="subtitle h1 text-warning-dark mb-1">{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earningSummary['total_shipping_earning']), currencyCode: getCurrencyCode()) }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">
                                        {{ translate('Shipping Earnings')}}
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex gap-3 align-items-center">
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-t-inhouse.png')}}" alt="" class="object-fit-contain">
                                <div class="info">
                                    <h4 class="subtitle h1 text-primary mb-1">{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earningSummary['total_in_house_earning']), currencyCode: getCurrencyCode()) }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">{{ translate('In-House Earnings')}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex gap-3 align-items-center">
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/products.svg')}}" alt="" class="object-fit-contain">
                                <div class="info">
                                    <h4 class="subtitle h1 text-warning mb-1">{{ $inHouseProductCount }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">{{ translate('Total In House Products')}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex gap-3 align-items-center">
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-vendor-revenue.png')}}" alt="" class="object-fit-contain">
                                <div class="info">
                                    <h4 class="subtitle h1 text-info mb-1">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $averageOrderValue), currencyCode: getCurrencyCode()) }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">{{ translate('Average Order Value (AOV)')}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-3 mb-3">
            @php($chartValues = array_map(fn ($row) => usdToDefaultCurrency(amount: $row['total_earning']), $earningRows))
            @php($chartLabels = array_map(fn ($row) => $dateType === 'this_month' ? $row['duration'].' '.now()->format('M') : $row['duration'], $earningRows))
            <div class="center-chart-area p-0 flex-grow-1">
                @include('layouts.admin.partials._apexcharts', [
                    'title' => 'earning_Statistics',
                    'statisticsValue' => $chartValues,
                    'label' => $chartLabels,
                    'statisticsTitle' => 'total_Earnings'
                ])
            </div>
            <div class="flex-grow-1">
                <div class="card h-100 bg-white payment-statistics-shadow">
                    <div class="card-header border-0 ">
                        <h4 class="card-title">{{ translate('Payment Statistics')}} </h4>
                    </div>
                    <div class="card-body px-0 pt-0">
                        <div class="position-relative pie-chart">
                            <div id="dognut-pie" class="label-hide"></div>
                            <div class="total--orders">
                                <h3>{{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['total_payment'])) }}</h3>
                                <span>{{ translate('Payments Amount')}}</span>
                            </div>
                        </div>
                        <div class="apex-legends">
                            <div data-color="#A2CEEE">
                                <span>{{translate('Cash payments')}} ({{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $paymentStatistics['cash_payment']), currencyCode: getCurrencyCode()) }})</span>
                            </div>
                            <div data-color="#004188">
                                <span>{{translate('Digital Payments')}} ({{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $paymentStatistics['digital_payment']), currencyCode: getCurrencyCode()) }}) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                            </div>
                            <div data-color="#0177CD">
                                <span>{{translate('Wallet')}} ({{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $paymentStatistics['wallet_payment']), currencyCode: getCurrencyCode()) }})</span>
                            </div>
                            <div data-color="#7B94A4">
                                <span>{{translate('Offline Payments')}} ({{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $paymentStatistics['offline_payment']), currencyCode: getCurrencyCode()) }})</span>
                            </div>
                            <div data-color="#FFA500">
                                <span>{{translate('Returned Amount')}} ({{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $paymentStatistics['return_amount']), currencyCode: getCurrencyCode()) }})</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-4">
                    <h4 class="mb-0">
                        {{translate('Total earnings')}}
                        <span class="badge badge-info text-bg-info fs-12">{{ count($earningRows) }}</span>
                    </h4>

                    <a type="button" class="btn btn-outline-primary" href="{{ route('admin.report.retail.admin-earning-excel-export', ['date_type' => $dateType, 'from' => $from, 'to' => $to]) }}">
                        <i class="fi fi-sr-inbox-in"></i>
                        <span class="fs-12">{{ translate('Export') }}</span>
                    </a>
                </div>

                <div class="table-responsive">
                    <table id="datatable" class="table __table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{translate('Serial')}}</th>
                            <th>{{translate('Duration')}}</th>
                            <th>{{translate('In-House Earning')}}</th>
                            <th>{{translate('Commission Earning')}}</th>
                            <th>{{translate('Earn From Shipping')}}</th>
                            <th>{{translate('Delivery man incentive')}}</th>
                            <th>{{translate('Discount Given')}}</th>
                            <th>{{translate('VAT/TAX')}}</th>
                            <th>{{translate('Refund Given')}}</th>
                            <th>{{translate('Total Earning')}}</th>
                            <th class="text-center">{{translate('Action')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($earningRows as $earning)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $earning['duration'] }}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['in_house_earning']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['commission_earning']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['earn_from_shipping']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['deliveryman_incentive']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['discount_given']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['vat_tax']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['refund_given']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['total_earning']), currencyCode: getCurrencyCode()) }}</td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        <form action="{{ route('admin.report.retail.admin-earning-duration-download-pdf') }}"
                                              method="post">
                                            @csrf
                                            <input type="hidden" name="duration" value="{{ $earning['duration']}}">
                                            <input type="hidden" name="inhouse_earning" value="{{ $earning['in_house_earning'] }}">
                                            <input type="hidden" name="admin_commission"
                                                   value="{{ $earning['commission_earning'] }}">
                                            <input type="hidden" name="shipping_earn" value="{{ $earning['earn_from_shipping'] }}">
                                            <input type="hidden" name="discount_given" value="{{ $earning['discount_given'] }}">
                                            <input type="hidden" name="total_tax" value="{{ $earning['vat_tax'] }}">
                                            <input type="hidden" name="refund_given" value="{{ $earning['refund_given']}}">
                                            <input type="hidden" name="deliveryman_incentive" value="{{ $earning['deliveryman_incentive'] }}">
                                            <input type="hidden" name="total_earning"
                                                   value="{{$earning['total_earning'] }}">
                                            <button type="submit" class="btn btn-outline-success icon-btn">
                                                <i class="fi fi-rr-down-to-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if(count($earningRows)==0)
                    @include('layouts.admin.partials._empty-state',['text'=>'no_data_found'],['image'=>'default'])
                @endif
            </div>
        </div>
    </div>

    <span id="currency_symbol" data-text="{{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}"></span>

    <span id="cash_payment" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['cash_payment']) }}"></span>
    <span id="digital_payment" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['digital_payment']) }}"></span>
    <span id="wallet_payment" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['wallet_payment']) }}"></span>
    <span id="offline_payment" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['offline_payment']) }}"></span>
    <span id="return_amount" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['return_amount']) }}"></span>

    <span id="cash_payment_text" data-text="{{translate('Cash payments')}}"></span>
    <span id="digital_payment_text" data-text="{{translate('Digital Payment')}}"></span>
    <span id="wallet_payment_text" data-text="{{translate('Wallet payment')}}"></span>
    <span id="offline_payment_text" data-text="{{translate('Offline Payment')}}"></span>
    <span id="return_amount_text" data-text="{{ translate('Returned Amount') }}"></span>

    <span id="cash_payment_format" data-text="{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['cash_payment'])) }}"></span>
    <span id="digital_payment_format" data-text="{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['digital_payment'])) }}"></span>
    <span id="wallet_payment_format" data-text="{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['wallet_payment'])) }}"></span>
    <span id="offline_payment_format" data-text="{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['offline_payment'])) }}"></span>
    <span id="return_amount_format" data-text="{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['return_amount'])) }}"></span>
@endsection

@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/new/back-end/js/apexcharts.js')}}"></script>
    <script src="{{dynamicAsset(path: 'public/assets/new/back-end/js/apexcharts-data-show.js')}}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/admin/admin-earning-report.js') }}"></script>
@endpush
