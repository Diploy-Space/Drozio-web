@extends('layouts.admin.app')

@section('title', translate('Customer Loyalty Point Report'))

@section('content')
    <div class="content container-fluid">
        <h2 class="h1 mb-20 text-capitalize d-flex align-items-center gap-2">
            {{ translate('Loyalty Point Report') }}
        </h2>
        <div class="mb-20">
            @include('admin-views.partials._business-module-tabs')
        </div>
        <div class="card card-body mb-20">
            <h3 class="mb-20">{{ translate('Loyalty Point Overview') }}</h3>
            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded">
                        <h4 class="mb-0">{{ translate('Balance') }} ({{ translate('Points') }})</h4>
                        <span class="text-primary h3 mb-0 overflow-wrap-anywhere">{{ $balance }}</span>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded">
                        <h4 class="mb-0">{{ translate('Credit') }} ({{ translate('Points') }})</h4>
                        <span class="text-success h3 mb-0 overflow-wrap-anywhere">{{ $credit }}</span>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded">
                        <h4 class="mb-0">{{ translate('Debit') }} ({{ translate('Points') }})</h4>
                        <span class="text-danger h3 mb-0 overflow-wrap-anywhere">{{ $debit }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card card-body">
            <div class="d-flex justify-content-end flex-wrap gap-3 align-items-center mb-4">
                <h3 class="mb-0 text-nowrap text-capitalize d-flex gap-1 align-items-center flex-grow-1">
                    {{ translate('Transactions') }}
                    <span class="badge badge-info text-bg-info">{{ $transactions->total() }}</span>
                </h3>

                <form action="{{ url()->current() }}" method="GET" class="min-w-100-mobile min-w-280">
                    <div class="form-group">
                        <div class="input-group">
                            <input type="hidden" name="business_module" value="{{ $activeBusinessModule }}">
                            <input type="hidden" name="date" value="{{ request('date') }}">
                            <input type="hidden" name="transaction_type" value="{{ request('transaction_type') }}">
                            <input type="hidden" name="customer_id" value="{{ request('customer_id') }}">
                            <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                                    placeholder="{{ translate('Search by transaction ID or customer name')}}"  aria-label="{{ translate('Search transactions') }}" value="{{ request('searchValue') }}">
                            <div class="input-group-append search-submit">
                                <button type="submit">
                                    <i class="fi fi-rr-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <a type="button" class="btn btn-outline-primary" href="{{ route('admin.people.customer.loyalty.export', ['business_module' => $activeBusinessModule, 'searchValue' => request('searchValue'), 'date' => request('date'), 'transaction_type' => request('transaction_type'), 'customer_id' => request('customer_id')]) }}">
                    <i class="fi fi-sr-inbox-in"></i>
                    <span class="fs-12">{{ translate('Export') }}</span>
                </a>
                <div class="position-relative">
                    @if ($hasActiveFilter)
                        <div class="position-absolute inset-inline-end-0 top-0 mt-n1 me-n1 btn-circle bg-danger border border-white border-2"
                            style="--size: 12px;"></div>
                    @endif
                    <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasLoyaltyFilter">
                        <i class="fi fi-sr-settings-sliders"></i>
                        {{ translate('Filter') }}
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="datatable"
                    class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table {{Session::get('direction') === "rtl" ? 'text-right' : 'text-left'}}">
                    <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{ translate('Serial') }}</th>
                            <th>{{ translate('Transaction') }}</th>
                            <th>{{ translate('Customer') }}</th>
                            <th>{{ translate('Reference') }}</th>
                            <th class="text-end">{{ translate('Points') }}</th>
                            <th class="text-end">{{ translate('Balance') }}</th>
                            <th>{{ translate('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($transactions as $key => $transaction)
                        <tr>
                            <td>{{ $transactions->firstItem() + $key }}</td>
                            <td>
                                <span class="badge badge-soft-{{ $transactionTypeBadges[$transaction['transaction_type']] ?? 'secondary' }}">
                                    {{ $transactionTypes[$transaction['transaction_type']] ?? $transaction['transaction_type'] }}
                                </span>
                                <div class="fs-12 text-body-light line-1 mt-1" data-bs-toggle="tooltip"
                                    data-bs-placement="top" data-bs-title="{{ $transaction['transaction_id'] }}">
                                    {{ Str::limit($transaction['transaction_id'], 13) }}
                                </div>
                            </td>
                            <td>
                                @if($transaction->user)
                                    <a href="{{ route('admin.people.customer.view', ['user_id' => $transaction['user_id']]) }}"
                                        class="text-dark text-hover-primary">
                                        {{ Str::limit($transaction->user->f_name.' '.$transaction->user->l_name, 20) }}
                                    </a>
                                    <div class="fs-12 text-body-light line-1">{{ $transaction->user->email }}</div>
                                @else
                                    <span class="text-body-light">{{ translate('Not found') }}</span>
                                @endif
                            </td>
                            <td>
                                @if(!$transaction['reference'])
                                    {{ translate('N/A') }}
                                @elseif(in_array($transaction['transaction_type'], $orderReferenceTypes))
                                    <a href="{{ route($orderDetailsRouteName, ['id' => $transaction['reference']]) }}"
                                        class="text-dark text-hover-primary">
                                        {{ translate('Order') }} #{{ $transaction['reference'] }}
                                    </a>
                                @else
                                    <span class="fs-12 text-body-light" data-bs-toggle="tooltip" data-bs-placement="top"
                                        data-bs-title="{{ $transaction['reference'] }}">
                                        {{ Str::limit($transaction['reference'], 13) }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold {{ $transaction['credit'] > 0 ? 'text-success' : 'text-danger' }}">
                                {{ $transaction['credit'] > 0 ? '+'.$transaction['credit'] : '-'.$transaction['debit'] }}
                            </td>
                            <td class="text-end">{{ $transaction['balance'] }}</td>
                            <td>
                                {{ date('d M Y', strtotime($transaction['created_at'])) }}
                                <div class="fs-12 text-body-light line-1">
                                    {{ date('h:i A', strtotime($transaction['created_at'])) }}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>


            @if($transactions->total() == 0)
                @include('layouts.admin.partials._empty-state',['text'=>'no_data_found'],['image'=>'default'])
            @endif
            @if($transactions->hasPages())
                <div class="mt-4">
                    <div class="px-4 d-flex justify-content-center justify-content-md-end">
                        {!! $transactions->appends(request()->query())->links() !!}
                    </div>
                </div>
            @endif
        </div>

        @include('admin-views.customer.loyalty.partials._filter-offcanvas')
    </div>
@endsection
