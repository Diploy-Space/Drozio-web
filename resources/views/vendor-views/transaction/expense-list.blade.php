@extends('layouts.vendor.app')
@section('title', translate('Expense Transactions'))
@section('content')
    <div class="content container-fluid ">
        @include('vendor-views.report._page-header', [
            'icon' => 'order_report.png',
            'title' => 'Transaction report',
            'switchPage' => 'expense-transaction',
        ])

        @include('vendor-views.transaction.transaction-report-inline-menu')

        <div class="card mb-2">
            <div class="card-body">
                <form action="#" id="form-data" method="GET">
                    <h4 class="mb-3">{{translate('Filter Data')}}</h4>
                    <div class="row  gy-2 align-items-center text-{{Session::get('direction') === "rtl" ? 'right' : 'left'}}">
                        <div class="col-sm-6 col-md-3">
                            <select class="form-control __form-control" name="date_type" id="date_type">
                                <option value="this_year" {{ $dateType == 'this_year'? 'selected' : '' }}>{{translate('This Year')}}</option>
                                <option value="this_month" {{ $dateType == 'this_month'? 'selected' : '' }}>{{translate('This Month')}}</option>
                                <option value="this_week" {{ $dateType == 'this_week'? 'selected' : '' }}>{{translate('This Week')}}</option>
                                <option value="today" {{ $dateType == 'today'? 'selected' : '' }}>{{translate('Today')}}</option>
                                <option value="custom_date" {{ $dateType == 'custom_date'? 'selected' : '' }}>{{translate('Custom Date')}}</option>
                            </select>
                        </div>
                        <div class="col-sm-6 col-md-3" id="from_div">
                            <div class="form-floating">
                                <input type="date" name="from" value="{{$from}}" id="from_date"
                                       class="form-control __form-control">
                                <label>{{translate('Start Date')}}</label>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3" id="to_div">
                            <div class="form-floating">
                                <input type="date" value="{{$to}}" name="to" id="to_date"
                                       class="form-control __form-control">
                                <label>{{translate('End Date')}}</label>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <button type="submit" class="btn btn--primary px-4 w-100" id="formUrlChange"
                                    data-action="{{ url()->current() }}">
                                {{translate('Filter')}}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="store-report-content mb-2">
            <div class="left-content expense--content">
                <div class="left-content-card">
                    <img src="{{dynamicAsset(path: 'public/assets/back-end/img/expense.svg')}}" alt="">
                    <div class="info">
                        <h4 class="subtitle">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $expenseSummary['total_expense']), currencyCode: getCurrencyCode()) }}</h4>
                        <h6 class="subtext">
                            <span>{{translate('Total Expense')}}</span>
                            <span class="ml-2" data-toggle="tooltip" data-placement="top"
                                  title="{{translate('Free Delivery')}}, {{translate('Coupon discount will be shown here')}}">
                                <img class="info-img" src="{{dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg')}}"
                                     alt="img">
                            </span>
                        </h6>
                    </div>
                </div>
                <div class="left-content-card">
                    <img src="{{dynamicAsset(path: 'public/assets/back-end/img/free-delivery.svg')}}" alt="">
                    <div class="info">
                        <h4 class="subtitle">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $expenseSummary['total_free_delivery']), currencyCode: getCurrencyCode()) }}</h4>
                        <h6 class="subtext">{{translate('Free Delivery')}}</h6>
                    </div>
                </div>
                <div class="left-content-card">
                    <img src="{{dynamicAsset(path: 'public/assets/back-end/img/coupon-discount.svg')}}" alt="">
                    <div class="info">
                        <h4 class="subtitle">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $expenseSummary['total_coupon_discount']), currencyCode: getCurrencyCode()) }}</h4>
                        <h6 class="subtext">
                            <span>{{translate('Coupon Discount')}}</span>
                            <span class="ml-2" data-toggle="tooltip" data-placement="top"
                                  title="{{translate('Discount on purchase and first order coupon amount will be shown here')}}">
                                <img class="info-img" src="{{dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg')}}"
                                     alt="img">
                            </span>
                        </h6>
                    </div>
                </div>
            </div>
            <div class="center-chart-area">
                @include('layouts.vendor.partials._apexcharts', [
                    'title' => 'expense_Statistics',
                    'statisticsValue' => array_map(fn ($point) => usdToDefaultCurrency(amount: $point['expense_amount']), $expenseChart),
                    'label' => array_map(fn ($point) => $dateType === 'this_month' ? $point['duration'].' '.now()->format('M') : $point['duration'], $expenseChart),
                    'statisticsTitle' => 'total_expense_amount',
                ])
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0">
                <div class="w-100 d-flex flex-wrap gap-3 align-items-center">
                    <h4 class="mb-0 flex-grow-1">
                        {{translate('Total Transactions')}}
                        <span class="badge badge-soft-dark radius-50 fs-12">{{ $expenseTransactions->total() }}</span>
                    </h4>
                    <form action="" method="GET" class="mb-0">
                        <div class="input-group input-group-merge input-group-custom">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <i class="tio-search"></i>
                                </div>
                            </div>
                            <input type="hidden" name="date_type" value="{{ $dateType }}">
                            <input type="hidden" name="from" value="{{ $from }}">
                            <input type="hidden" name="to" value="{{ $to }}">
                            <input id="datatableSearch_" type="search" name="search" class="form-control"
                                   placeholder="{{ translate('Search by Order ID or Transaction ID')}}"
                                   aria-label="Search orders"
                                   value="{{ $search }}"
                                   required>
                            <button type="submit"
                                    class="btn btn--primary">{{ translate('Search')}}</button>
                        </div>
                    </form>
                    <div>
                        <a href="{{ route('vendor.report.retail.transaction.expense-transaction-summary-pdf', ['search' => $search, 'date_type' => $dateType, 'from' => $from, 'to' => $to]) }}"
                           class="btn btn-outline--primary text-nowrap btn-block">
                            <i class="tio-file-text"></i>
                            {{translate('Download PDF')}}
                        </a>
                    </div>
                    <div class="dropdown">
                        <a type="button" class="btn btn-outline--primary text-nowrap" href="{{ route('vendor.report.retail.transaction.expense-transaction-export-excel', ['date_type' => $dateType, 'from' => $from, 'to' => $to, 'search' => $search]) }}">
                            <img width="14" src="{{dynamicAsset(path: 'public/assets/back-end/img/excel.png')}}" class="excel" alt="">
                            <span class="ps-2">{{ translate('Export') }}</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="datatable"
                           class="table __table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{translate('Serial')}}</th>
                            <th>XID</th>
                            <th>{{translate('Transaction Date')}}</th>
                            <th>{{translate('Order ID')}}</th>
                            <th>{{translate('Expense Amount')}}</th>
                            <th>{{translate('Expense Type')}}</th>
                            <th class="text-center">{{translate('Action')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($expenseTransactions as $key => $transaction)
                            <tr>
                                <td>{{ $expenseTransactions->firstItem() + $key }}</td>
                                <td>{{ $transaction->orderTransaction->transaction_id }}</td>
                                <td>{{ date_format($transaction->updated_at, 'd F Y, h:i:s a') }}</td>
                                <td>
                                    <a class="title-color"
                                       href="{{route('vendor.retail.orders.details',['id'=>$transaction->id])}}">
                                        {{$transaction->id}}
                                    </a>
                                </td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['expense_amount']), currencyCode: getCurrencyCode()) }}</td>
                                <td>
                                    @if ($transaction['expense_types']['coupon_type'] === 'free_delivery')
                                        {{ translate('Free Delivery Promotion') }}
                                    @elseif ($transaction['expense_types']['coupon_type'] !== null)
                                        {{ ucwords(str_replace('_', ' ', $transaction['expense_types']['coupon_type'])) }}
                                    @endif
                                    <br>
                                    @if ($transaction['expense_types']['free_delivery_type'] !== null)
                                        {{ ucwords(str_replace('_', ' ', $transaction['expense_types']['free_delivery_type'])) }}
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        <a href="{{ route('vendor.report.retail.transaction.pdf-order-wise-expense-transaction', ['id' => $transaction->id]) }}"
                                           class="btn btn-outline-success square-btn btn-sm">
                                            <i class="tio-download-to"></i>
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
                        {{ $expenseTransactions->links() }}
                    </div>
                </div>
                @if(count($expenseTransactions) == 0)
                    @include('layouts.vendor.partials._empty-state',['text'=>'no_data_found'],['image'=>'default'])
                @endif
            </div>
        </div>

    </div>
@endsection

@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/apexcharts.js')}}"></script>
    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/apexcharts-data-show.js')}}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/expense-report.js') }}"></script>
@endpush
