@extends('layouts.vendor.app')
@section('title', translate('Order Report'))

@section('content')
    <div class="content container-fluid">
        @include('vendor-views.report._page-header', [
            'icon' => 'order_report.png',
            'title' => 'Order Report',
            'switchPage' => 'order',
        ])

        <div class="card mb-2">
            <div class="card-body">
                <form action="" id="form-data" method="GET">
                    <h4 class="mb-3">{{ translate('Filter Data') }}</h4>
                    <div class="row gx-2 gy-3 align-items-center text-left">
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
                                <input type="date" name="from" value="{{$from}}" id="from_date" class="form-control">
                                <label>{{ translate('Start Date') }}</label>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3" id="to_div">
                            <div class="form-floating">
                                <input type="date" value="{{$to}}" name="to" id="to_date" class="form-control">
                                <label>{{ translate('End Date') }}</label>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-1">
                            <button type="submit" class="btn btn--primary px-4 px-md-5">
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
                    <img src="{{dynamicAsset(path: 'public/assets/back-end/img/cart.svg') }}" alt="{{ translate('Image') }}">
                    <div class="info">
                        <h4 class="subtitle">{{ $orderStatusCounts['total_order'] }}</h4>
                        <h6 class="subtext">{{ translate('Total Orders') }}</h6>
                    </div>
                    <div class="coupon__discount w-100 text-right d-flex flex-wrap justify-content-between gap-2">
                        <div class="text-center">
                            <strong class="text-danger">{{ $orderStatusCounts['canceled_order'] }}</strong>
                            <div>
                                <span>{{ translate('Canceled') }}</span>
                                <span class="ml-2" data-toggle="tooltip" data-placement="top"
                                      title="{{ translate('This count is the summation of') }} {{ translate('Failed to deliver') }}, {{ translate('Canceled') }}, {{ translate('And') }} {{ translate('Returned orders') }}">
                                    <img class="info-img" src="{{dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}"
                                         alt="{{ translate('Image') }}">
                                </span>
                            </div>
                        </div>
                        <div class="text-center">
                            <strong class="text-primary">{{ $orderStatusCounts['ongoing_order'] }}</strong>
                            <div>
                                <span>{{ translate('Ongoing') }}</span>
                                <span class="ml-2" data-toggle="tooltip" data-placement="top"
                                      title="{{ translate('This count is the summation of') }} {{ translate('Pending') }}, {{ translate('Confirmed') }}, {{ translate('Packaging') }}, {{ translate('Out for delivery orders') }}">
                                    <img class="info-img" src="{{dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}"
                                         alt="{{ translate('Image') }}">
                                </span>
                            </div>
                        </div>
                        <div class="text-center">
                            <strong class="text-success">{{ $orderStatusCounts['delivered_order'] }}</strong>
                            <div class="d-flex">
                                <span>{{ translate('Completed') }}</span>
                                <span class="ml-2" data-toggle="tooltip" data-placement="top"
                                      title="{{ translate('This count is the summation of delivered orders') }}">
                                    <img class="info-img" src="{{dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}"
                                         alt="{{ translate('Image') }}">
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="left-content-card">
                    <img src="{{dynamicAsset(path: 'public/assets/back-end/img/products.svg') }}" alt="{{ translate('Image') }}">
                    <div class="info">
                        <h4 class="subtitle">
                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderAmountSummary['due_amount'] + $orderAmountSummary['settled_amount']), currencyCode: getCurrencyCode()) }}
                        </h4>
                        <h6 class="subtext">{{ translate('Total Order Amount') }}</h6>
                    </div>
                    <div class="coupon__discount w-100 text-right d-flex justify-content-between">
                        <div class="text-center">
                            <strong class="text-danger">
                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderAmountSummary['due_amount']), currencyCode: getCurrencyCode()) }}
                            </strong>
                            <div class="d-flex">
                                <span>{{ translate('Due Amount') }}</span>
                                <span class="trx-y-2 ml-2" data-toggle="tooltip" data-placement="top"
                                      title="{{ translate('The ongoing order amount will be shown here') }}">
                                    <img src="{{dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="{{ translate('Image') }}">
                                </span>
                            </div>
                        </div>
                        <div class="text-center">
                            <strong class="text-success">
                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderAmountSummary['settled_amount']), currencyCode: getCurrencyCode()) }}
                            </strong>
                            <div class="d-flex">
                                <span>{{ translate('Already Settled') }}</span>
                                <span class="trx-y-2 ml-2" data-toggle="tooltip" data-placement="top"
                                      title="{{ translate('After the order is delivered total order amount will be shown here') }}">
                                    <img src="{{dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="{{ translate('Image') }}">
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @php($chartValues = array_map(fn ($point) => usdToDefaultCurrency(amount: $point['order_amount']), $orderAmountChart))
            @php($chartLabels = array_map(fn ($point) => $dateType === 'this_month' ? $point['duration'].' '.now()->format('M') : $point['duration'], $orderAmountChart))
            <div class="center-chart-area">
                @include('layouts.vendor.partials._apexcharts',['title'=>'order_Statistics','statisticsValue'=>$chartValues,'label'=> $chartLabels,'statisticsTitle'=>'total_settled_amount'])
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
                                <h3 class="mb-1">
                                    {{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}{{ getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['total_payment'])) }}
                                </h3>
                                <span>{{ translate('Completed') }} <br> {{ translate('Payments') }}</span>
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
            <div class="card-header border-0">
                <div class="d-flex flex-wrap w-100 gap-3 align-items-center">
                    <h4 class="mb-0 flex-grow-1">
                        {{ translate('Total Orders') }}
                        <span class="badge badge-soft-dark radius-50 fz-14">{{ $orders->total() }}</span>
                        <span class="badge badge-soft-dark radius-50 fz-14"></span>
                    </h4>
                    <form action="" method="GET" class="mb-0">
                        <div class="input-group input-group-merge input-group-custom">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <i class="tio-search"></i>
                                </div>
                            </div>
                            <input type="hidden" value="{{ $dateType }}" name="date_type">
                            <input type="hidden" value="{{ $from }}" name="from">
                            <input type="hidden" value="{{ $to }}" name="to">
                            <input id="datatableSearch_" type="search" value="{{ $search }}" name="search"
                                   class="form-control" placeholder="{{ translate('Search by Order ID') }}"
                                   aria-label="Search orders" required>
                            <button type="submit" class="btn btn--primary">{{ translate('Search') }}</button>
                        </div>
                    </form>
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline--primary text-nowrap btn-block"
                                data-toggle="dropdown">
                            <i class="tio-download-to"></i>
                            {{ translate('Export') }}
                            <i class="tio-chevron-down"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right">
                            <li>
                                <a class="dropdown-item"
                                   href="{{ route('vendor.report.retail.order-report-excel', ['date_type' => $dateType, 'from' => $from, 'to' => $to, 'search' => $search]) }}">
                                    <img width="14" src="{{dynamicAsset(path: 'public/assets/back-end/img/excel.png') }}" alt="">
                                    {{ translate('Excel') }}
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item"
                                   href="{{ route('vendor.report.retail.order-report-pdf', ['date_type' => $dateType, 'from' => $from, 'to' => $to, 'search' => $search]) }}">
                                    <span class="text-warning"><i class="tio-file-text"></i></span>
                                    {{ translate('Download PDF') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table id="datatable"
                       class="table __table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                    <thead class="thead-light thead-50 text-capitalize">
                    <tr>
                        <th>{{ translate('Serial') }}</th>
                        <th>{{ translate('Order ID') }}</th>
                        <th>{{ translate('Total Amount') }}</th>
                        <th>{{ translate('Product Discount') }}</th>
                        <th>{{ translate('Coupon Discount') }}</th>
                        <th>{{ translate('Referral Discount') }}</th>
                        <th>{{ translate('Shipping Charge') }}</th>
                        <th>{{ translate('VAT/TAX') }}</th>
                        <th>{{ translate('Commission') }}</th>
                        <th>{{ translate('Delivery man incentive') }}</th>
                        <th class="text-center">{{ translate('Status') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td>{{ $orders->firstItem() + $loop->index }}</td>
                            <td>
                                <a class="title-color hover-c1"
                                   href="{{route('vendor.retail.orders.details', ['id' => $order->id]) }}">{{$order->id}}</a>
                            </td>
                            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order->order_amount), currencyCode: getCurrencyCode()) }}</td>
                            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order->details_sum_discount), currencyCode: getCurrencyCode()) }}</td>
                            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order->discount_amount), currencyCode: getCurrencyCode()) }}</td>
                            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->refer_and_earn_discount ?? 0), currencyCode: getCurrencyCode()) }}</td>
                            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order->shipping_cost - ($order->extra_discount_type == 'free_shipping_over_order_amount' ? $order->extra_discount : 0)), currencyCode: getCurrencyCode()) }}</td>
                            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order->total_tax_amount ?? $order->details_sum_tax), currencyCode: getCurrencyCode()) }}</td>
                            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order->admin_commission), currencyCode: getCurrencyCode()) }}</td>
                            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order->deliveryman_charge), currencyCode: getCurrencyCode()) }}</td>
                            <td>
                                <div class="d-flex justify-content-center">
                                    @php($statusLabel = $orderStatusLabels[$order['order_status']] ?? $order['order_status'])
                                    @if(in_array($order['order_status'], ['pending'], true))
                                        <span class="badge badge-soft-info fs-12">{{ $statusLabel }}</span>
                                    @elseif(in_array($order['order_status'], ['processing', 'out_for_delivery'], true))
                                        <span class="badge badge-soft-warning fs-12">{{ $statusLabel }}</span>
                                    @elseif(in_array($order['order_status'], ['confirmed', 'delivered'], true))
                                        <span class="badge badge-soft-success fs-12">{{ $statusLabel }}</span>
                                    @else
                                        <span class="badge badge-soft-danger fs-12">{{ $statusLabel }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($orders->isEmpty())
                @include('layouts.vendor.partials._empty-state',['text'=>'no_order_found', 'image'=>'default', 'width' => 75])
            @endif
        </div>
        <div class="table-responsive mt-4">
            <div class="px-4 d-flex justify-content-center justify-content-md-end">
                {!! $orders->links() !!}
            </div>
        </div>
    </div>

    <span id="currency_symbol" data-text="{{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}"></span>

    <span id="cash_payment" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['cash_payment']) }}"></span>
    <span id="digital_payment" data-text="{{ usdToDefaultCurrency(amount: $paymentStatistics['digital_payment']) }}"></span>
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

@push('script_2')
    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/apexcharts.js') }}"></script>
    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/apexcharts-data-show.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/vendor/order-report.js') }}"></script>
@endpush
