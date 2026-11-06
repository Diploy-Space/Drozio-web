@extends('layouts.vendor.app')
@section('title', translate('Order Transactions'))
@section('content')
    <div class="content container-fluid ">
        @include('vendor-views.report._page-header', [
            'icon' => 'order_report.png',
            'title' => 'Transaction report',
            'switchPage' => 'order-transaction',
        ])
        @include('vendor-views.transaction.transaction-report-inline-menu')

        <div class="card mb-2">
            <div class="card-body">
                <h4 class="mb-3">{{ translate('Filter Data') }}</h4>
                <form action="#" id="form-data" method="GET" class="w-100">
                    <div class="row  gx-2 gy-3 align-items-center text-{{Session::get('direction') === "rtl" ? 'right' : 'left'}}">
                        <div class="col-sm-6 col-md-3">
                            <div class="">
                                <select class="form-control __form-control" name="status">
                                    <option class="text-center" value="0" disabled>
                                        ---{{ translate('Select status') }}---
                                    </option>
                                    <option class="text-capitalize"
                                            value="all" {{ $status == 'all'? 'selected' : '' }} >{{ translate('All') }} </option>
                                    <option class="text-capitalize"
                                            value="disburse" {{ $status == 'disburse'? 'selected' : '' }} >{{ translate('Disburse') }} </option>
                                    <option class="text-capitalize"
                                            value="hold" {{ $status == 'hold'? 'selected' : '' }}>{{ translate('Hold') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="">
                                <select class="js-select2-custom form-control __form-control" name="customer_id">
                                    <option class="text-center"
                                            value="all" {{ $customerId == 'all' ? 'selected' : '' }}>
                                        {{ translate('All Customer') }}
                                    </option>

                                    @foreach($customers as $customer)
                                        <option class="text-left text-capitalize"
                                                value="{{ $customer->id }}" {{ $customer->id == $customerId ? 'selected' : '' }}>
                                            {{ $customer->f_name.' '.$customer->l_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <select class="form-control __form-control" name="date_type" id="date_type">
                                <option value="this_year" {{ $dateType == 'this_year'? 'selected' : '' }}>{{ translate('This Year') }}</option>
                                <option value="this_month" {{ $dateType == 'this_month'? 'selected' : '' }}>{{ translate('This Month') }}</option>
                                <option value="this_week" {{ $dateType == 'this_week'? 'selected' : '' }}>{{ translate('This Week') }}</option>
                                <option value="today" {{ $dateType == 'today'? 'selected' : '' }}>{{ translate('Today') }}</option>
                                <option value="custom_date" {{ $dateType == 'custom_date'? 'selected' : '' }}>{{ translate('Custom Date') }}</option>
                            </select>
                        </div>
                        <div class="col-sm-6 col-md-3" id="from_div">
                            <div class="form-floating">
                                <input type="date" name="from" value="{{ $from}}" id="from_date"
                                       class="form-control __form-control">
                                <label>{{ translate('Start Date') }}</label>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3" id="to_div">
                            <div class="form-floating">
                                <input type="date" value="{{ $to}}" name="to" id="to_date"
                                       class="form-control __form-control">
                                <label>{{ translate('End Date') }}</label>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn--primary px-4 min-w-120 __h-45px"
                                    id="formUrlChange"
                                    data-action="{{ url()->current() }}">
                                {{ translate('Filter') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="store-report-content mb-2">
            <div class="left-content">
                <div class="left-content-card">
                    <img src="{{dynamicAsset(path: 'public/assets/back-end/img/cart.svg') }}" alt="">
                    <div class="info">
                        <h4 class="subtitle">{{ $productCounts['total_products'] }}</h4>
                        <h6 class="subtext">{{ translate('Total Products') }}</h6>
                    </div>
                </div>
                <div class="left-content-card">
                    <img src="{{dynamicAsset(path: 'public/assets/back-end/img/products.svg') }}" alt="">
                    <div class="info">
                        <h4 class="subtitle">{{ $productCounts['active_products'] }}</h4>
                        <h6 class="subtext">{{ translate('Active Products') }}</h6>
                    </div>
                </div>
                <div class="left-content-card">
                    <img src="{{dynamicAsset(path: 'public/assets/back-end/img/inactive-product.svg') }}" alt="">
                    <div class="info">
                        <h4 class="subtitle">{{ $productCounts['inactive_products'] }}</h4>
                        <h6 class="subtext">{{ translate('Inactive Products') }}</h6>
                    </div>
                </div>
                <div class="left-content-card">
                    <img src="{{dynamicAsset(path: 'public/assets/back-end/img/pending_products.svg') }}" alt="">
                    <div class="info">
                        <h4 class="subtitle">{{ $productCounts['pending_products'] }}</h4>
                        <h6 class="subtext">{{ translate('Pending Products') }}</h6>
                    </div>
                </div>
            </div>
            @php($chartValues = array_map(fn ($point) => usdToDefaultCurrency(amount: $point['order_amount']), $orderAmountChart))
            @php($chartLabels = array_map(fn ($point) => $dateType === 'this_month' ? $point['duration'].' '.now()->format('M') : $point['duration'], $orderAmountChart))
            <div class="center-chart-area">
                @include('layouts.vendor.partials._apexcharts',['title'=>'order_Statistics','statisticsValue'=>$chartValues,'label'=> $chartLabels, 'statisticsTitle'=>'total_order_amount'])
            </div>
            <div class="right-content">
                <div class="card h-100 bg-white payment-statistics-shadow">
                    <div class="card-header border-0 ">
                        <h5 class="card-title">
                            <span>{{ translate('Payment Statistics') }}</span>
                        </h5>
                    </div>
                    <div class="card-body px-0 pt-0">
                        <div class="position-relative pie-chart">
                            <div id="dognut-pie" class="label-hide"></div>
                            <div class="total--orders">
                                <h3>{{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}{{ getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['total_payment'])) }}</h3>
                                <span>{{ translate('Completed payments') }}</span>
                            </div>
                        </div>
                        <div class="apex-legends">
                            <div class="before-bg-A2CEEE">
                                <span>{{ translate('Cash payments') }} ({{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $paymentStatistics['cash_payment']), currencyCode: getCurrencyCode()) }})</span>
                            </div>
                            <div class="before-bg-004188">
                                <span>{{ translate('Digital Payments') }} ({{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $paymentStatistics['digital_payment']), currencyCode: getCurrencyCode()) }}) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                            </div>
                            <div class="before-bg-0177CD">
                                <span>{{ translate('Wallet') }} ({{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $paymentStatistics['wallet_payment']), currencyCode: getCurrencyCode()) }})</span>
                            </div>
                            <div class="before-bg-7B94A4">
                                <span>{{ translate('Offline Payments') }} ({{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $paymentStatistics['offline_payment']), currencyCode: getCurrencyCode()) }})</span>
                            </div>
                            <div class="before-bg-FFA500">
                                <span>{{translate('Returned Amount')}} ({{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $paymentStatistics['return_amount']), currencyCode: getCurrencyCode()) }})</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="px-3 py-4">
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <h4 class="mb-0 flex-grow-1">
                        {{ translate('Total Transactions') }}
                        <span class="badge badge-soft-dark radius-50 fs-12">{{ $transactionRows->total() }}</span>
                    </h4>
                    <form action="{{ url()->current() }}" method="GET" class="mb-0">
                        <input type="hidden" name="date_type" value="{{ $dateType }}">
                        <input type="hidden" name="from" value="{{ $from }}">
                        <input type="hidden" name="to" value="{{ $to }}">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="hidden" name="customer_id" value="{{ $customerId }}">
                        <div class="input-group input-group-merge input-group-custom">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <i class="tio-search"></i>
                                </div>
                            </div>
                            <input id="datatableSearch_" type="search" name="search" class="form-control"
                                   placeholder="{{ translate('Search by orders id or transaction id') }}"
                                   aria-label="Search orders"
                                   value="{{ $search }}"
                                   required>
                            <button type="submit" class="btn btn--primary">{{ translate('Search') }}</button>
                        </div>
                    </form>
                    <div>
                        <a href="{{ route('vendor.report.retail.transaction.order-transaction-summary-pdf', ['date_type' => $dateType, 'customer_id' => $customerId, 'status' => $status, 'from' => $from, 'to' => $to, 'search' => $search]) }}"
                           class="btn btn-outline--primary text-nowrap btn-block">
                            <i class="tio-file-text"></i>
                            {{ translate('Download PDF') }}
                        </a>
                    </div>
                    <div class="dropdown">
                        <a type="button" class="btn btn-outline--primary text-nowrap" href="{{ route('vendor.report.retail.transaction.order-transaction-export-excel', ['date_type' => $dateType, 'customer_id' => $customerId, 'search' => $search, 'status' => $status, 'from' => $from, 'to' => $to]) }}">
                            <img width="14" src="{{dynamicAsset(path: 'public/assets/back-end/img/excel.png') }}" class="excel" alt="">
                            <span class="ps-2">{{ translate('Export') }}</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="datatable" class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 __table">
                    <thead class="thead-light thead-50 text-capitalize">
                    <tr>
                        <th>{{ translate('Serial') }}</th>
                        <th>{{ translate('Order ID') }}</th>
                        <th>{{ translate('Customer Name') }}</th>
                        <th>{{ translate('Total Product Amount') }}</th>
                        <th>{{ translate('Product Discount') }}</th>
                        <th>{{ translate('Coupon Discount') }}</th>
                        <th>{{ translate('Referral Discount') }}</th>
                        <th>{{ translate('Discounted Amount') }}</th>
                        <th>{{ translate('VAT/TAX') }}</th>
                        <th>{{ translate('Shipping Charge') }}</th>
                        <th>{{ translate('Order Amount') }}</th>
                        <th>{{ translate('Delivered by') }}</th>
                        <th>{{ translate('Delivery man incentive') }}</th>
                        <th>{{ translate('Admin Discount') }}</th>
                        <th>{{ translate('Vendor Discount') }}</th>
                        <th>{{ translate('Admin Commission') }}</th>
                        <th>{{ translate('Vendor Net Income') }}</th>
                        <th>{{ translate('Payment Method') }}</th>
                        <th>{{ translate('Payment Status') }}</th>
                        <th class="text-center">{{ translate('Action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($transactionRows as $transaction)
                        <tr>
                            <td>{{ $transactionRows->firstItem() + $loop->index }}</td>
                            <td>
                                <a class="title-color" href="{{ route('vendor.retail.orders.details', ['id' => $transaction['order_id']]) }}">
                                    {{ $transaction['order_id'] }}
                                </a>
                            </td>
                            <td>
                                @if (!$transaction['is_guest'] && $transaction['customer_id'])
                                    {{ $transaction['customer_name'] }}
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
                            <td>{{ $transaction['delivered_by'] }}</td>
                            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['deliveryman_incentive']), currencyCode: getCurrencyCode()) }}</td>
                            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['admin_discount']), currencyCode: getCurrencyCode()) }}</td>
                            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['vendor_discount']), currencyCode: getCurrencyCode()) }}</td>
                            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['admin_commission']), currencyCode: getCurrencyCode()) }}</td>
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
                                    <a href="{{ route('vendor.report.retail.transaction.pdf-order-wise-transaction', ['order_id' => $transaction['order_id']]) }}"
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
                @include('layouts.vendor.partials._empty-state',['text'=>'no_data_found'], ['image'=>'default'])
            @endif
        </div>
    </div>

    <span id="currency_symbol" data-text="{{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}"></span>

    <span id="digital_payment" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['digital_payment']) }}"></span>
    <span id="cash_payment" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['cash_payment']) }}"></span>
    <span id="wallet_payment" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['wallet_payment']) }}"></span>
    <span id="offline_payment" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['offline_payment']) }}"></span>
    <span id="return_amount" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['return_amount']) }}"></span>

    <span id="digital_payment_text" data-text="{{ translate('Digital Payment') }}"></span>
    <span id="cash_payment_text" data-text="{{ translate('Cash payment') }}"></span>
    <span id="wallet_payment_text" data-text="{{ translate('Wallet payment') }}"></span>
    <span id="offline_payment_text" data-text="{{ translate('Offline Payments') }}"></span>
    <span id="return_amount_text" data-text="{{ translate('Returned Amount') }}"></span>

    <span id="digital_payment_format" data-text="{{ getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['digital_payment'])) }}"></span>
    <span id="cash_payment_format" data-text="{{ getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['cash_payment'])) }}"></span>
    <span id="wallet_payment_format" data-text="{{ getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['wallet_payment'])) }}"></span>
    <span id="offline_payment_format" data-text="{{ getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['offline_payment'])) }}"></span>
    <span id="return_amount_format" data-text="{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['return_amount'])) }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/apexcharts.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/apexcharts-data-show.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/vendor/transaction-report.js') }}"></script>
@endpush
