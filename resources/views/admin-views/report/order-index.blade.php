@extends('layouts.admin.app')
@section('title', translate('Order Report'))

@section('content')
    <div class="content container-fluid">
        @include('admin-views.report._page-header', [
            'icon' => 'order_report.png',
            'title' => 'Order Report',
        ])

        <div class="card mb-3">
            <div class="card-body">
                <form action="" id="form-data" method="GET">
                    <h3 class="mb-3">{{translate('Filter Data')}}</h3>
                    <div class="bg-section rounded p-3 p-sm-20 mb-20">
                        <div class="row g-3 align-items-end">
                            <div class="col-sm-6 col-lg-3">
                                <label class="mb-2">{{ translate('Select Vendor')}}</label>
                                <select class="custom-select text-ellipsis" name="seller_id">
                                    <option value="all" {{ $sellerId === 'all' ? 'selected' : '' }}>{{translate('All')}}</option>
                                    <option value="inhouse" {{ $sellerId === 'inhouse' ? 'selected' : '' }}>{{translate('In-house')}}</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor['id'] }}" {{ $sellerId === $vendor['id'] ? 'selected' : '' }}>
                                            {{ $vendor['f_name'] }} {{ $vendor['l_name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label class="mb-2">{{ translate('Select Date')}}</label>
                                <div class="select-wrapper">
                                    <select class="form-select" name="date_type" id="date_type">
                                        <option value="this_year" {{ $dateType === 'this_year' ? 'selected' : '' }}>{{translate('This Year')}}</option>
                                        <option value="this_month" {{ $dateType === 'this_month' ? 'selected' : '' }}>{{translate('This Month')}}</option>
                                        <option value="this_week" {{ $dateType === 'this_week' ? 'selected' : '' }}>{{translate('This Week')}}</option>
                                        <option value="today" {{ $dateType === 'today' ? 'selected' : '' }}>{{translate('Today')}}</option>
                                        <option value="custom_date" {{ $dateType === 'custom_date' ? 'selected' : '' }}>{{translate('Custom Date')}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3" id="from_div">
                                <div>
                                    <label class="mb-2">{{ ucwords(translate('Start Date'))}}</label>
                                    <input type="date" name="from" value="{{$from}}" id="from_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3" id="to_div">
                                <div>
                                    <label class="mb-2">{{ ucwords(translate('End Date'))}}</label>
                                    <input type="date" value="{{$to}}" name="to" id="to_date" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end flex-wrap gap-3">
                        <button type="reset" class="btn btn-secondary min-w-120">
                            {{translate('Reset')}}
                        </button>
                        <button type="submit" class="btn btn-primary min-w-120 filter-btn">
                            {{translate('Filter')}}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex flex-xxl-nowrap flex-wrap gap-3 mb-3">
            <div class="d-flex flex-column gap-3 flex-grow-1">
                <div class="card card-body">
                    <div class="d-flex gap-3 align-items-center mb-4">
                        <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/cart.svg')}}" alt="{{translate('Image')}}">
                        <div class="info">
                            <h4 class="subtitle h1">{{ $orderStatusCounts['total_order'] }}</h4>
                            <h5 class="subtext">{{translate('Total Orders')}}</h5>
                        </div>
                    </div>
                    <div class="coupon__discount d-flex flex-wrap justify-content-around gap-2">
                        <div class="text-center">
                            <strong class="text-danger fs-12 fw-bold">{{ $orderStatusCounts['canceled_order'] }}</strong>
                            <div class="d-flex gap-2 align-items-center fs-12">
                                <span>{{translate('Canceled')}}</span>
                                <span class="lh-1" data-bs-toggle="tooltip" data-bs-title="{{translate('This count is the summation of')}} {{translate('Failed to deliver')}}, {{translate('Canceled')}}, {{translate('And')}} {{translate('Returned orders')}}">
                                      <i class="fi fi-rr-info"></i>
                                </span>
                            </div>
                        </div>
                        <div class="text-center">
                            <strong class="text-primary fs-12 fw-bold">{{ $orderStatusCounts['ongoing_order'] }}</strong>
                            <div class="d-flex gap-2 align-items-center fs-12">
                                <span>{{translate('Ongoing')}}</span>
                                <span class="lh-1" data-bs-toggle="tooltip" data-bs-title="{{translate('This count is the summation of')}} {{translate('Pending')}}, {{translate('Confirmed')}}, {{translate('Packaging')}}, {{translate('Out for delivery orders')}}">
                                      <i class="fi fi-rr-info"></i>
                                </span>
                            </div>
                        </div>
                        <div class="text-center">
                            <strong class="text-success fs-12 fw-bold">{{ $orderStatusCounts['delivered_order'] }}</strong>
                            <div class="d-flex gap-2 align-items-center fs-12">
                                <span>{{translate('Completed')}}</span>
                                <span class="lh-1" data-bs-toggle="tooltip" data-bs-title="{{translate('This count is the summation of delivered orders')}}">
                                      <i class="fi fi-rr-info"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card card-body">
                    <div class="d-flex gap-3 align-items-center mb-4">
                        <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/products.svg')}}" alt="{{translate('Image')}}">
                        <div class="info">
                            <h4 class="subtitle h1">
                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderAmountSummary['total_order_amount']), currencyCode: getCurrencyCode()) }}
                            </h4>
                            <h5 class="subtext">{{translate('Total Order Amount')}}</h5>
                        </div>
                    </div>
                    <div class="coupon__discount d-flex gap-2 justify-content-around">
                        <div class="text-center">
                            <strong class="text-danger">
                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderAmountSummary['due_amount']), currencyCode: getCurrencyCode()) }}
                            </strong>
                            <div class="d-flex gap-2 align-items-center fs-12">
                                <span>{{translate('Due Amount')}}</span>
                                <span class="trx-y-2 ml-2" data-bs-toggle="tooltip" data-bs-title="{{translate('The ongoing order amount will be shown here')}}">
                                      <i class="fi fi-rr-info"></i>
                                </span>
                            </div>
                        </div>
                        <div class="text-center">
                            <strong class="text-success">
                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderAmountSummary['settled_amount']), currencyCode: getCurrencyCode()) }}
                            </strong>
                            <div class="d-flex gap-2 align-items-center fs-12">
                                <span>{{translate('Already Settled')}}</span>
                                <span class="trx-y-2 ml-2" data-bs-toggle="tooltip" data-bs-title="{{translate('After the order is delivered total order amount will be shown here')}}">
                                      <i class="fi fi-rr-info"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @php($chartValues = array_map(fn ($point) => usdToDefaultCurrency(amount: $point['order_amount']), $orderAmountChart))
            @php($chartLabels = array_map(fn ($point) => $dateType === 'this_month' ? $point['duration'].' '.now()->format('M') : $point['duration'], $orderAmountChart))
            <div class="center-chart-area p-0 flex-grow-1">
                @include('layouts.admin.partials._apexcharts', ['title' => 'order_Statistics', 'statisticsValue' => $chartValues, 'label' => $chartLabels, 'statisticsTitle' => 'total_settled_amount'])
            </div>
            <div class="flex-grow-1">
                <div class="card h-100 bg-white payment-statistics-shadow">
                    <div class="card-header border-0 ">
                        <h3 class="card-title">{{translate('Payment Statistics')}}</h3>
                    </div>
                    <div class="card-body px-0 pt-0">
                        <div class="position-relative pie-chart">
                            <div id="dognut-pie" class="label-hide"></div>
                            <div class="total--orders">
                                <h3 class="mb-1">
                                    {{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}{{getFormatCurrency(amount: usdToDefaultCurrency(amount: $paymentStatistics['total_payment'])) }}
                                </h3>
                                <span>{{translate('Completed')}} <br> {{translate('Payments')}}</span>
                            </div>
                        </div>
                        <div class="apex-legends flex-column">
                            <div data-color="#A2CEEE">
                                <span>{{translate('Cash payments')}} ({{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $paymentStatistics['cash_payment']), currencyCode: getCurrencyCode()) }})</span>
                            </div>
                            <div data-color="#004188">
                                <span>{{translate('Digital Payments')}} ({{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $paymentStatistics['digital_payment']), currencyCode: getCurrencyCode()) }})</span>
                            </div>
                            <div data-color="#0177CD">
                                <span>{{translate('Wallet')}} ({{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $paymentStatistics['wallet_payment']), currencyCode: getCurrencyCode()) }})</span>
                            </div>
                            <div data-color="#7B94A4">
                                <span>{{translate('Offline Payments')}} ({{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $paymentStatistics['offline_payment']), currencyCode: getCurrencyCode()) }})</span>
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
                    <h4 class="mb-0">
                        {{translate('Total Orders')}}
                        <span class="badge badge-info text-bg-info">{{ $orders->total() }}</span>
                    </h4>

                    <div class="d-flex gap-3 flex-wrap">
                        <form action="" method="GET" class="mb-0">
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="hidden" value="{{ $sellerId }}" name="seller_id">
                                    <input type="hidden" value="{{ $dateType }}" name="date_type">
                                    <input type="hidden" value="{{ $from }}" name="from">
                                    <input type="hidden" value="{{ $to }}" name="to">
                                    <input id="datatableSearch_" value="{{ $search }}" name="search" type="search" class="form-control" placeholder="{{ translate('Search by Order ID')}}">
                                    <div class="input-group-append search-submit">
                                        <button type="submit">
                                            <i class="fi fi-rr-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div class="dropdown">
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown">
                                <i class="fi fi-sr-inbox-in"></i>
                                <span class="fs-12">{{ translate('Export') }}</span>
                                <i class="fi fi-rr-angle-small-down"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.report.retail.order-report-excel', ['date_type' => $dateType, 'seller_id' => $sellerId, 'from' => $from, 'to' => $to, 'search' => $search]) }}">
                                        <span class="text-success pt-1"><i class="fi fi-sr-file-excel"></i></span>
                                        {{ translate('Excel') }}
                                    </a>
                                </li>

                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2"
                                       href="{{ route('admin.report.retail.order-report-pdf', ['date_type' => $dateType, 'seller_id' => $sellerId, 'from' => $from, 'to' => $to, 'search' => $search]) }}">
                                        <span class="text-warning pt-1"><i class="fi fi-sr-file-pdf"></i></span>
                                        {{ translate('Download PDF') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="datatable" class="table __table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                        <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>{{translate('Serial')}}</th>
                                <th>{{translate('Order ID')}}</th>
                                <th>{{translate('Total Amount')}}</th>
                                <th>{{translate('Due Amount')}}</th>
                                <th>{{translate('Return Amount')}}</th>
                                <th>{{translate('Product Discount')}}</th>
                                <th>{{translate('Coupon Discount')}}</th>
                                <th>{{translate('Referral Discount')}}</th>
                                <th>{{translate('Shipping Charge')}}</th>
                                <th>{{translate('VAT/TAX')}}</th>
                                <th>{{translate('Commission')}}</th>
                                <th>{{translate('Delivery man incentive')}}</th>
                                <th class="text-center">{{translate('Status')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($orders as $key=>$order)
                            <tr>
                                <td>{{ $orders->firstItem()+$key }}</td>
                                <td>
                                    <a class="title-color"
                                       href="{{route('admin.retail.orders.details',['id'=>$order->id])}}">{{$order->id}}</a>
                                </td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($order?->order_amount + $order?->edit_due_amount - $order?->edit_return_amount)), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->edit_due_amount??0), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->edit_return_amount??0), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->details_sum_discount??0), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->discount_amount??0), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->refer_and_earn_discount ?? 0), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order->shipping_cost - ($order->extra_discount_type == 'free_shipping_over_order_amount' ? $order->extra_discount : 0)), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->total_tax_amount ?? $order?->details_sum_tax), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->admin_commission??0), currencyCode: getCurrencyCode()) }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->deliveryman_charge??0), currencyCode: getCurrencyCode()) }}</td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        @if($order['order_status'] === 'pending')
                                            <span class="badge badge-soft-info fs-12">
                                        @elseif(in_array($order['order_status'], ['processing', 'out_for_delivery'], true))
                                            <span class="badge badge-soft-warning fs-12">
                                        @elseif(in_array($order['order_status'], ['confirmed', 'delivered'], true))
                                            <span class="badge badge-soft-success fs-12">
                                        @else
                                            <span class="badge badge-soft-danger fs-12">
                                        @endif
                                            {{ $orderStatusLabels[$order['order_status']] ?? $order['order_status'] }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if(count($orders)==0)
                    @include('layouts.admin.partials._empty-state',['text'=>'no_order_found'],['image'=>'default'])
                @endif
            </div>
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
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/admin/order-report.js') }}"></script>
@endpush
