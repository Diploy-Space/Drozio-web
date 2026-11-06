@extends('layouts.admin.app')

@section('title', translate('Order Transactions'))

@section('content')
    <div class="content container-fluid ">
        @include('admin-views.report._page-header', [
            'icon' => 'order_report.png',
            'title' => 'Transaction report',
        ])

        @include('admin-views.report.transaction-report-inline-menu')

        <div class="card mb-3">
            <div class="card-body">
                <h3 class="mb-3">{{translate('Filter Data')}}</h3>
                <form action="#" id="form-data" method="GET" class="w-100">
                    <div class="bg-section rounded p-3 mb-20">
                        <div class="row  gx-2 gy-3 align-items-center">
                            <div class="col-sm-6 col-md-3">
                                <div class="">
                                    <label class="mb-2">{{translate('Select status')}}</label>
                                    <div class="select-wrapper">
                                        <select class="form-select" name="status">
                                            <option class="text-center" value="0" disabled>
                                                {{'---'.translate('Select status').'---'}}
                                            </option>
                                            <option class="text-capitalize" value="all" {{ $status === 'all'? 'selected' : '' }} >{{translate('All Status')}} </option>
                                            <option class="text-capitalize" value="disburse" {{ $status === 'disburse'? 'selected' : '' }} >{{translate('Disburse')}} </option>
                                            <option class="text-capitalize" value="hold" {{ $status === 'hold'? 'selected' : '' }}>{{translate('Hold')}}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <div class="">
                                    <label class="mb-2">{{translate('Select Vendor')}}</label>
                                    <select class="custom-select" name="seller_id">
                                        <option class="text-center" value="all" {{ $sellerId === 'all' ? 'selected' : '' }}>
                                            {{translate('All')}}
                                        </option>
                                        <option class="text-center"
                                                value="inhouse" {{ $sellerId === 'inhouse' ? 'selected' : '' }}>
                                            {{translate('In-house')}}
                                        </option>
                                        @foreach($vendors as $vendor)
                                            <option class="text-left text-capitalize"
                                                    value="{{ $vendor->id }}" {{ $vendor->id === $sellerId ? 'selected' : '' }}>
                                                {{ $vendor->f_name.' '.$vendor->l_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <div class="">
                                    <label class="mb-2">{{translate('Select Customer')}}</label>
                                    <select class="custom-select" name="customer_id">
                                        <option class="text-center"
                                                value="all" {{ $customerId === 'all' ? 'selected' : '' }}>
                                            {{translate('All Customer')}}
                                        </option>
                                        @foreach($customers as $customer)
                                            <option class="text-left text-capitalize"
                                                    value="{{ $customer->id }}" {{ $customer->id === $customerId ? 'selected' : '' }}>
                                                {{ $customer->f_name.' '.$customer->l_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
    
                            <div class="col-sm-6 col-md-3">
                                <label class="mb-2">{{translate('Select Date')}}</label>
                                <div class="select-wrapper">
                                    <select class="form-select" name="date_type" id="date_type">
                                        <option value="this_year" {{ $dateType === 'this_year'? 'selected' : '' }}>{{translate('This Year')}}</option>
                                        <option value="this_month" {{ $dateType === 'this_month'? 'selected' : '' }}>{{translate('This Month')}}</option>
                                        <option value="this_week" {{ $dateType === 'this_week'? 'selected' : '' }}>{{translate('This Week')}}</option>
                                        <option value="today" {{ $dateType === 'today'? 'selected' : '' }}>{{translate('Today')}}</option>
                                        <option value="custom_date" {{ $dateType === 'custom_date'? 'selected' : '' }}>{{translate('Custom Date')}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3" id="from_div">
                                <div>
                                    <label class="mb-2">{{translate('Start Date')}}</label>
                                    <input type="date" name="from" value="{{$from}}" id="from_date"
                                           class="form-control __form-control">
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3" id="to_div">
                                <div class="">
                                    <label class="mb-2">{{translate('End Date')}}</label>
                                    <input type="date" value="{{$to}}" name="to" id="to_date"
                                           class="form-control __form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 justify-content-end">
                        <button type="reset" class="btn btn-secondary min-w-120">
                            {{ translate('Reset') }}
                        </button>
                        <div class="">
                            <button type="submit" class="btn btn-primary px-4 min-w-120 __h-45px"
                                    id="formUrlChange"
                                    data-action="{{ url()->current() }}">
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
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-body border shadow-none h-100">
                            <div class="d-flex gap-3 justify-content-center align-items-center mb-3">
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-vendor-revenue.png')}}" alt="">
                                <div class="info">
                                    <h4 class="subtitle mb-1 h1 text-info">{{ $orderSummary['total_orders'] }}</h4>
                                    <h5 class="subtext fs-14">{{translate('Total Orders')}}</h5>
                                </div>
                            </div>
                            <div class="coupon__discount d-flex gap-3 justify-content-around">
                                <div class="text-center">
                                    <strong class="text-success fs-16">{{ $orderSummary['in_house_orders'] }}</strong>
                                    <div class="d-flex fs-12">
                                        <span>{{translate('In House Orders')}}</span>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <strong class="text-primary fs-16">{{ $orderSummary['seller_orders'] }}</strong>
                                    <div class="d-flex fs-12">
                                        <span>{{translate('Vendor Orders')}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-body border shadow-none h-100">
                            <div class="d-flex justify-content-center gap-3 align-items-center mb-3">
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/total-product-report.png')}}" alt="">
                                <div class="info">
                                    <h4 class="subtitle text-success h1 mb-1">{{ $orderSummary['total_in_house_products'] + $orderSummary['total_seller_products'] }}</h4>
                                    <h5 class="subtext fs-14">{{translate('Total Products')}}</h5>
                                </div>
                            </div>
    
                            <div class="coupon__discount d-flex justify-content-around">
                                <div class="text-center">
                                    <strong class="text-success fs-16">{{ $orderSummary['total_in_house_products'] }}</strong>
                                    <div class="d-flex">
                                        <span>{{translate('In-house Products')}}</span>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <strong class="text-primary fs-16">{{ $orderSummary['total_seller_products'] }}</strong>
                                    <div class="d-flex">
                                        <span>{{translate('Vendor Products')}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card d-center card-body border shadow-none h-100">
                            <div class="d-flex gap-3 align-items-center">
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/house-theme.png')}}" alt="">
                                <div class="info">
                                    <h4 class="subtitle h1 text-info mb-1">{{ $orderSummary['total_stores'] }}</h4>
                                    <h5 class="subtext fs-14">{{translate('Total Stores')}}</h5>
                                </div>
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
                    'title' => 'order_Statistics',
                    'statisticsValue' => $chartValues,
                    'label' => $chartLabels,
                    'statisticsTitle' => 'total_order_amount',
                ])
            </div>
            <div class="flex-grow-1">
                <div class="card h-100 bg-white payment-statistics-shadow">
                    <div class="card-header border-0 ">
                        <h5 class="card-title fs-16 text-dark">
                            <span>{{translate('Payment Statistics')}}</span>
                        </h5>
                    </div>
                    <div class="card-body px-0 pt-0">
                        <div class="position-relative pie-chart">
                            <div id="dognut-pie" class="label-hide"></div>
                            <div class="total--orders">
                                <h3>{{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['total_payment'])) }}</h3>
                                <span>{{translate('Completed payments')}}</span>
                            </div>
                        </div>
                        <div class="apex-legends">
                            <div data-color="#A2CEEE">
                                <span>{{translate('Cash payments')}} ({{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $paymentStatistics['cash_payment']), currencyCode: getCurrencyCode()) }})</span>
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
                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center mb-4">
                    <h4 class="mb-0 mr-auto">
                        {{translate('Total Transactions')}}
                        <span class="badge badge-info text-bg-info">{{ $transactionRows->total() }}</span>
                    </h4>
                    <div class="d-flex flex-wrap gap-3">
                        <form action="{{ url()->full() }}" method="GET" class="mb-0">
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="hidden" name="date_type" value="{{ $dateType }}">
                                    <input type="hidden" name="from" value="{{ $from }}">
                                    <input type="hidden" name="to" value="{{ $to }}">
                                    <input type="hidden" name="seller_id" value="{{ $sellerId }}">
                                    <input type="hidden" name="status" value="{{ $status }}">
                                    <input type="hidden" name="customer_id" value="{{ $customerId }}">
                                    <input id="datatableSearch_" type="search"  name="search"  class="form-control"  value="{{ $search }}" placeholder="{{ translate('Search by orders id')}}">
                                    <div class="input-group-append search-submit">
                                        <button type="submit">
                                            <i class="fi fi-rr-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div>
                            <a href="{{ route('admin.report.retail.transaction.order-transaction-summary-pdf', ['search' => $search, 'date_type' => $dateType, 'seller_id' => $sellerId, 'customer_id' => $customerId, 'status' => $status, 'from' => $from, 'to' => $to]) }}"
                               class="btn btn-outline-primary text-nowrap btn-block">
                               <i class="fi fi-rr-document"></i>
                                <span class="fs-12">{{translate('Download PDF')}}</span>
                            </a>
                        </div>
                        <div class="dropdown">
                            <a type="button" class="btn btn-outline-primary" href="{{ route('admin.report.retail.transaction.order-transaction-export-excel', ['search' => $search, 'date_type' => $dateType, 'seller_id' => $sellerId, 'customer_id' => $customerId, 'status' => $status, 'from' => $from, 'to' => $to]) }}">
                                <i class="fi fi-sr-inbox-in"></i>
                                <span class="fs-12">{{ translate('Export') }}</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="datatable"
                        class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 __table">
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{translate('Serial')}}</th>
                            <th>{{translate('Order ID')}}</th>
                            <th>{{translate('Shop Name')}}</th>
                            <th>{{translate('Customer Name')}}</th>
                            <th>{{translate('Total Product Amount')}}</th>
                            <th>{{translate('Product Discount')}}</th>
                            <th>{{translate('Coupon Discount')}}</th>
                            <th>{{translate('Referral Discount')}}</th>
                            <th>{{translate('Discounted Amount')}}</th>
                            <th>{{translate('VAT/TAX')}}</th>
                            <th>{{translate('Shipping Charge')}}</th>
                            <th>{{translate('Order Amount')}}</th>
                            <th>{{translate('Due Amount')}}</th>
                            <th>{{translate('Return Amount')}}</th>
                            <th>{{translate('Delivered by')}}</th>
                            <th>{{translate('Delivery man incentive')}}</th>
                            <th>{{translate('Admin Discount')}}</th>
                            <th>{{translate('Vendor Discount') }}</th>
                            <th>{{translate('Admin Commission') }}</th>
                            <th>{{translate('Admin Net Income')}}</th>
                            <th>{{translate('Vendor Net Income')}}</th>
                            <th>{{translate('Payment Method')}}</th>
                            <th>{{translate('Payment Status')}}</th>
                            <th class="text-center">{{translate('Action')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($transactionRows as $transaction)
                            <tr>
                                <td>{{ $transactionRows->firstItem() + $loop->index }}</td>
                                <td>
                                    <a class="title-color" href="{{ route('admin.retail.orders.details', ['id' => $transaction['order_id']]) }}">
                                        {{ $transaction['order_id'] }}
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1 max-w-200">
                                        <div class="text-wrap line-2">
                                            {{ $transaction['shop_name'] ?? translate('Not found') }}
                                        </div>
                                        @include('admin-views.vendor.partials._verified-badge', ['isVerified' => $transaction['is_verified'], 'isInHouse' => $transaction['is_in_house'], 'size' => 16])
                                    </div>
                                </td>
                                <td>
                                    @if (!$transaction['is_guest'] && $transaction['customer_id'])
                                        <a href="{{ route('admin.people.customer.view',[$transaction['customer_id']]) }}"
                                           class="title-color hover-c1 d-flex align-items-center gap-10">
                                            {{ $transaction['customer_name'] }}
                                        </a>
                                    @elseif($transaction['is_guest'])
                                        {{ translate('Guest customer') }}
                                    @else
                                        {{translate('Not found')}}
                                    @endif
                                </td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['total_product_amount']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['product_discount']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['coupon_discount']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['referral_discount']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['discounted_amount']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['tax']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['shipping_charge']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['order_amount']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['edit_due_amount']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['edit_return_amount']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ $transaction['delivered_by'] }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['deliveryman_incentive']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['admin_discount']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['vendor_discount']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['admin_commission']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['admin_net_income']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['vendor_net_income']), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ ucwords(str_replace('_',' ', $transaction['payment_method'])) }}</td>
                                <td>
                                    <div class="text-center">
                                            <span class="badge {{ $transaction['payment_status'] == 'disburse' ? 'badge-soft-success' : 'badge-soft-warning' }}">
                                                {{ translate(str_replace('_',' ', $transaction['payment_status'])) }}
                                            </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        <a href="{{ route('admin.report.retail.transaction.pdf-order-wise-transaction', ['order_id' => $transaction['order_id']]) }}"
                                           class="btn btn-outline-success square-btn btn-sm">
                                            <i class="fi fi-rr-download"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-4">
                    <div class="px-4 d-flex justify-content-lg-end">
                        {{ $transactionRows->links() }}
                    </div>
                </div>
                @if($transactionRows->isEmpty())
                    @include('layouts.admin.partials._empty-state',['text'=>'no_data_found'],['image'=>'default'])
                @endif
            </div>
        </div>
    </div>

    <span id="currency_symbol" data-text="{{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}"></span>

    <span id="digital_payment" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['digital_payment']) }}"></span>
    <span id="cash_payment" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['cash_payment']) }}"></span>
    <span id="wallet_payment" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['wallet_payment']) }}"></span>
    <span id="offline_payment" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['offline_payment']) }}"></span>
    <span id="return_amount" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['return_amount']) }}"></span>

    <span id="digital_payment_text" data-text="{{translate('Digital Payment')}}"></span>
    <span id="cash_payment_text" data-text="{{translate('Cash payment')}}"></span>
    <span id="wallet_payment_text" data-text="{{translate('Wallet payment')}}"></span>
    <span id="offline_payment_text" data-text="{{translate('Offline Payments')}}"></span>
    <span id="return_amount_text" data-text="{{ translate('Returned Amount') }}"></span>

    <span id="digital_payment_format" data-text="{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['digital_payment'])) }}"></span>
    <span id="cash_payment_format" data-text="{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['cash_payment'])) }}"></span>
    <span id="wallet_payment_format" data-text="{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['wallet_payment'])) }}"></span>
    <span id="offline_payment_format" data-text="{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['offline_payment'])) }}"></span>
    <span id="return_amount_format" data-text="{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['return_amount'])) }}"></span>
@endsection

@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/new/back-end/js/apexcharts.js')}}"></script>
    <script src="{{dynamicAsset(path: 'public/assets/new/back-end/js/apexcharts-data-show.js')}}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/admin/transaction-report.js') }}"></script>
@endpush
