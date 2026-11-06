@extends('layouts.admin.app')
@section('title', translate('Expense transaction'))

@section('content')
    <div class="content container-fluid ">
        @include('admin-views.report._page-header', [
            'icon' => 'order_report.png',
            'title' => 'Transaction report',
        ])

        @include('admin-views.report.transaction-report-inline-menu')

        <div class="card mb-3">
            <div class="card-body">
                <form action="#" id="form-data" method="GET">
                    <h3 class="mb-3">{{ translate('Filter Data') }}</h3>
                    <div class="bg-section rounded p-3 mb-20">
                        <div class="row g-3 align-items-end">
                            <div class="col-sm-6 col-md-4">
                                <label class="mb-2">{{ translate('Select Date') }}</label>
                                <div class="select-wrapper">
                                    <select class="form-select" name="date_type" id="date_type">
                                        <option
                                            value="this_year" {{ $dateType === 'this_year'? 'selected' : '' }}>{{ translate('This Year') }}</option>
                                        <option
                                            value="this_month" {{ $dateType === 'this_month'? 'selected' : '' }}>{{ translate('This Month') }}</option>
                                        <option
                                            value="this_week" {{ $dateType === 'this_week'? 'selected' : '' }}>{{ translate('This Week') }}</option>
                                        <option
                                            value="today" {{ $dateType === 'today'? 'selected' : '' }}>{{ translate('Today') }}</option>
                                        <option
                                            value="custom_date" {{ $dateType === 'custom_date'? 'selected' : '' }}>{{ translate('Custom Date') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4" id="from_div">
                                <div>
                                    <label class="mb-2">{{ translate('Start Date') }}</label>
                                    <input type="date" name="from" value="{{ $from}}" id="from_date"
                                           class="form-control __form-control">
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4" id="to_div">
                                <div>
                                    <label class="mb-2">{{ translate('End Date') }}</label>
                                    <input type="date" value="{{ $to}}" name="to" id="to_date"
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
                            <button type="submit" class="btn btn-primary min-w-120" id="formUrlChange"
                                    data-action="{{ url()->current() }}">
                                {{ translate('Filter') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-3 mb-3">
            <div class="d-flex flex-column gap-3 flex-grow-1 expense--content">
                <div class="card card-body">
                    <div class="d-flex align-items-center gap-3">
                        <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/expense.svg') }}" alt="">
                        <div class="info">
                            <h4 class="subtitle h1">
                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $expenseSummary['total_expense']), currencyCode: getCurrencyCode()) }}
                            </h4>
                            <h5 class="subtext">
                                <span>{{ translate('Total Expense') }}</span>
                                <span class="ml-2" data-bs-toggle="tooltip"
                                      data-bs-title="{{ translate('Free Delivery') }}, {{ translate('Referral Discount') }}, {{ translate('Coupon discount will be shown here') }}">
                                    <i class="fi fi-rr-info"></i>
                                </span>
                            </h5>
                        </div>
                    </div>
                </div>
                <div class="card card-body">
                    <div class="d-flex align-items-center gap-3">
                        <img src="{{dynamicAsset(path: 'public/assets/back-end/img/free-delivery.svg') }}" alt="">
                        <div class="info">
                            <h4 class="subtitle h1">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $expenseSummary['total_free_delivery'] + $expenseSummary['total_free_delivery_over_amount']), currencyCode: getCurrencyCode()) }}</h4>
                            <h5 class="subtext">{{ translate('Free Delivery') }}</h5>
                        </div>
                    </div>
                </div>
                <div class="card card-body">
                    <div class="d-flex align-items-center gap-3">
                        <img src="{{dynamicAsset(path: 'public/assets/back-end/img/coupon-discount.svg') }}" alt="">
                        <div class="info">
                            <h4 class="subtitle h1">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $expenseSummary['total_coupon_discount']), currencyCode: getCurrencyCode()) }}</h4>
                            <h5 class="subtext">
                                <span>{{ translate('Coupon Discount') }}</span>
                                <span class="ml-2" data-bs-toggle="tooltip"
                                      data-bs-title="{{ translate('Discount on purchase and first delivery coupon amount will be shown here') }}">
                                    <i class="fi fi-rr-info"></i>
                                </span>
                            </h5>
                        </div>
                    </div>
                </div>

                <div class="card card-body">
                    <div class="d-flex align-items-center gap-3">
                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/coupon-discount.svg') }}" alt="">
                        <div class="info">
                            <h4 class="subtitle h1">
                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $expenseSummary['total_referral_discount']), currencyCode: getCurrencyCode()) }}
                            </h4>
                            <h5 class="subtext">
                                <span>{{ translate('Referral Discount') }}</span>
                                <span class="ml-2" data-bs-toggle="tooltip"
                                      data-bs-title="{{ translate('Discount on Referral reward amount will be shown here') }}">
                                    <i class="fi fi-rr-info"></i>
                                </span>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="center-chart-area p-0 flex-grow-1">
                @include('layouts.admin.partials._apexcharts', [
                    'title' => 'expense_Statistics',
                    'statisticsValue' => array_map(fn ($point) => usdToDefaultCurrency(amount: $point['expense_amount']), $expenseChart),
                    'label' => array_column($expenseChart, 'duration'),
                    'statisticsTitle' => 'total_expense_amount',
                ])
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-4">
                    <h4 class="mb-0">
                        {{ translate('Total Transactions') }}
                        <span class="badge badge-info text-bg-info">{{ $expenseTransactions->total() }}</span>
                    </h4>

                    <div class="d-flex flex-wrap gap-3">
                        <form action="" method="GET" class="mb-0">
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="hidden" name="date_type" value="{{ $dateType }}">
                                    <input type="hidden" name="from" value="{{ $from }}">
                                    <input type="hidden" name="to" value="{{ $to }}">
                                    <input id="datatableSearch_" type="search" name="search" class="form-control"
                                           value="{{ $search }}"
                                           placeholder="{{ translate('Search by Order ID or Transaction ID') }}">
                                    <div class="input-group-append search-submit">
                                        <button type="submit">
                                            <i class="fi fi-rr-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <a href="{{ route('admin.report.retail.transaction.expense-transaction-summary-pdf', ['search' => $search, 'date_type' => $dateType, 'from' => $from, 'to' => $to]) }}"
                           class="btn btn-outline-primary text-nowrap">
                            <i class="fi fi-rr-document"></i>
                            <span class="fs-12">{{ translate('Download PDF') }}</span>
                        </a>

                        <a type="button" class="btn btn-outline-primary"
                           href="{{ route('admin.report.retail.transaction.expense-transaction-export-excel', ['search' => $search, 'date_type' => $dateType, 'from' => $from, 'to' => $to]) }}">
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
                            <th>{{ translate('Serial') }}</th>
                            <th>XID</th>
                            <th>{{ translate('Transaction Date') }}</th>
                            <th>{{ translate('Order ID') }}</th>
                            <th>{{ translate('Expense Amount') }}</th>
                            <th>{{ translate('Expense Type') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($expenseTransactions as $key => $transaction)
                            <tr>
                                <td>{{ $expenseTransactions->firstItem()+$key }}</td>
                                <td>{{ $transaction->orderTransaction?->transaction_id }}</td>
                                <td>{{ date_format($transaction->updated_at, 'd F Y h:i:s a') }}</td>
                                <td>
                                    <a class="title-color"
                                       href="{{ route('admin.retail.orders.details', ['id' => $transaction->id]) }}">
                                        {{ $transaction->id }}
                                    </a>
                                </td>
                                <td>
                                    {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['expense_amount']), currencyCode: getCurrencyCode()) }}
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @if ($transaction['expense_types']['coupon_type'] === 'free_delivery')
                                            <div>{{ translate('Free Delivery Promotion') }}</div>
                                        @elseif ($transaction['expense_types']['coupon_type'] !== null)
                                            <div>{{ ucwords(str_replace('_', ' ', $transaction['expense_types']['coupon_type'])) }}</div>
                                        @elseif ($transaction['expense_types']['has_untyped_coupon'])
                                            <div>{{ translate('Coupon Discount') }}</div>
                                        @endif
                                        @if ($transaction['expense_types']['free_delivery_type'] !== null)
                                            <div>{{ ucwords(str_replace('_', ' ', $transaction['expense_types']['free_delivery_type'])) }}</div>
                                        @endif
                                        @if ($transaction['expense_types']['has_referral_discount'])
                                            <div>{{ translate('Referral Discount') }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        <a href="{{ route('admin.report.retail.transaction.pdf-order-wise-expense-transaction', ['id' => $transaction->id]) }}"
                                           class="btn btn-outline-success icon-btn">
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
                        {{ $expenseTransactions->links() }}
                    </div>
                </div>
                @if($expenseTransactions->isEmpty())
                    @include('layouts.admin.partials._empty-state',['text'=>'no_data_found'],['image'=>'default'])
                @endif
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/new/back-end/js/apexcharts.js') }}"></script>
    <script src="{{dynamicAsset(path: 'public/assets/new/back-end/js/apexcharts-data-show.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/admin/expense-report.js') }}"></script>
@endpush
