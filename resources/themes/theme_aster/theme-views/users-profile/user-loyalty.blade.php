@extends('theme-views.layouts.profile')

@section('title', translate('My Loyalty Point').' | '.$web_config['company_name'].' '.translate('eCommerce'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ theme_asset('assets/css/daterangepicker.css') }}">
@endpush

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-5">
        <div class="container">
            <div class="row g-3">
                @include('theme-views.partials._profile-aside')
                <div class="col-lg-9">
                    <div class="card mb-md-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between gap-2">
                                <h5 class="mb-4 text-capitalize">{{ translate('Loyalty point') }}</h5>
                                <span class="text-dark d-md-none" data-bs-toggle="modal"
                                      data-bs-target="#instructionModal"><i class="bi bi-info-circle"></i></span>
                            </div>
                            <div class="d-flex flex-column flex-md-row gap-4 justify-content-center">
                                <div class="wallet-card pb-3 rounded-10 ov-hidden mn-w loyalty-point-card"
                                     data-bg-img="{{ theme_asset('assets/img/media/loyalty-card.png') }}">
                                    <div class="card-body d-flex flex-column gap-2 absolute-white">
                                        <img width="34" src="{{theme_asset('assets/img/icons/loyalty-point.png') }}"
                                             alt="" class="dark-support">
                                        <h2 class="fs-36 absolute-white"> {{ $totalLoyaltyPoint }}</h2>
                                        <p>{{ translate('Total points') }}</p>
                                    </div>
                                </div>
                                <div class="">
                                    <div class="d-none d-md-block">
                                        <h6 class="mb-3">{{ translate('How to use') }}</h6>
                                        <ul>
                                            <li>{{ translate('Convert your loyalty point to wallet money.') }}</li>
                                            <li>{{ translate('Minimum').' '.$loyaltyPointMinimumPoint.' '.translate('Points required to convert into currency') }}</li>
                                        </ul>
                                    </div>
                                    <div class="d-flex justify-content-center justify-content-md-start">
                                        @if ($walletStatus == 1 && $loyaltyPointStatus == 1)
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                    data-bs-target="#convertToCurrency">
                                                {{ translate('Convert to currency') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div
                                class="d-flex flex-column flex-md-row gap-2 justify-content-between mb-4 align-items-md-center">
                                <h5 class="text-capitalize">{{ translate('Transaction history') }}</h5>

                                <div class="dropdown">
                                    <button type="button"
                                            id="transactionFilterBtn"
                                            class="btn border-dark border-opacity-25 px-3 py-1 text-dark fs-14 d-flex align-items-center gap-10"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ translate('Filter') }}
                                        <span class="position-relative">
                                            <i class="bi bi-funnel-fill text--primary {{ $filterCount > 0 ? 'fs-20' : '' }}"></i>
                                            @if($filterCount > 0)
                                                <span class="count bg-danger top-0">
                                                {{ $filterCount }}
                                                </span>
                                            @endif
                                        </span>
                                    </button>

                                    <div class="dropdown-menu dropdown-menu-end shadow transaction-filter_dropdown">
                                        <form action="{{ route('loyalty') }}" method="get">
                                            <div
                                                class="d-flex justify-content-between align-items-center p-3 border-bottom">
                                                <h5> {{ translate('Filter Data') }}</h5>
                                                <button id="filterCloseBtn" type="button"
                                                        class="btn bg-badge text-absolute-white border-0 rounded-circle fs-10 lh-1 p-1 m-0">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                            <div class="p-3 overflow-auto max-h-290px">
                                                <div class="mb-4">
                                                    <h6 class="mb-3"> {{ translate('Filter By') }}</h6>
                                                    <div class="d-flex gap-3 transaction_filter_by">
                                                        <label type="button"
                                                               class="btn p-2 min-w-60px {{ $filterBy == '' || $filterBy == 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                                            {{ translate('All') }}
                                                            <input type="radio" name="filter_by" hidden
                                                                   value="all" {{ $filterBy == '' || $filterBy == 'all' ? 'checked' : '' }}>
                                                        </label>
                                                        <label type="button"
                                                               class="btn p-2 min-w-60px {{ $filterBy == 'debit' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                                            {{ translate('Debit') }}
                                                            <input type="radio" name="filter_by" hidden
                                                                   value="debit" {{ $filterBy == 'debit' ? 'checked' : '' }}>
                                                        </label>
                                                        <label type="button"
                                                               class="btn p-2 min-w-60px {{ $filterBy == 'credit' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                                            {{ translate('Credit') }}
                                                            <input type="radio" name="filter_by" hidden
                                                                   value="credit" {{ $filterBy == 'credit' ? 'checked' : '' }}>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="mb-4">
                                                    <h6 class="mb-3"> {{ translate('Date Range') }}</h6>
                                                    <div class="position-relative">
                                                        <span class="bi bi-calendar icon-absolute-on-right"></span>
                                                        <input type="text" id="dateRangeInput" name="transaction_range"
                                                               placeholder="{{ translate('Select Date') }}"
                                                               class="form-control"
                                                               value="{{ $transactionRange ?? '' }}"
                                                        />
                                                    </div>
                                                </div>
                                                <div class="mb-4">
                                                    <h6 class="mb-3"> {{ translate('Earn by') }}</h6>
                                                    <div class="d-flex flex-column gap-3 transaction_earn_by">
                                                        <label
                                                            class="d-flex justify-content-between align-items-center">
                                                            <span>{{ translate('Order Transactions') }}</span>
                                                            <input type="checkbox" class="earn-checkbox" name="types[]"
                                                                   value="order_place"
                                                                {{ in_array('order_place', $transactionTypes) ? 'checked' : '' }}>
                                                        </label>
                                                        <label
                                                            class="d-flex justify-content-between align-items-center">
                                                            <span>{{ translate('Refund Order') }}</span>
                                                            <input type="checkbox" class="earn-checkbox border-dark"
                                                                   name="types[]" value="refund_order"
                                                                {{ in_array('refund_order', $transactionTypes) ? 'checked' : '' }}>
                                                        </label>
                                                        <label
                                                            class="d-flex justify-content-between align-items-center">
                                                            <span>{{ translate('Point to wallet') }}</span>
                                                            <input type="checkbox" class="earn-checkbox border-dark"
                                                                   name="types[]" value="point_to_wallet"
                                                                {{ in_array('point_to_wallet', $transactionTypes) ? 'checked' : '' }}>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card shadow-lg p-3 d-flex flex-row gap-3">
                                                <a href="{{ route('loyalty') }}" class="btn btn-outline-primary w-100">
                                                    {{ translate('Clear Filter') }}
                                                </a>
                                                <button type="submit" class="btn btn-primary w-100">
                                                    {{ translate('Filter') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                @foreach($loyaltyPointList as $key => $item)
                                    <div class="bg-light p-3 p-sm-4 rounded d-flex justify-content-between gap-3">
                                        <div class="align-items-start d-flex flex-column">
                                            <h4 class="mb-2 direction-ltr">
                                                {{ $item['debit'] != 0 ? ' - '.$item['debit'] : ' + '.$item['credit'] }}
                                            </h4>
                                            <h6 class="text-muted d-flex align-items-center gap-2">
                                                {{ $transactionTypeLabels[$item['transaction_type']] ?? str_replace('_', ' ', $item['transaction_type']) }}
                                                @if(in_array($item['transaction_type'], $orderReferenceTypes ?? []))
                                                    <span class="badge text-primary border-primary-1 text-bg-primary rounded-1 fw-normal fs-12 bg-opacity-10">
                                                        {{ $moduleLabels[$item['business_module']] ?? '' }}
                                                    </span>
                                                @endif
                                            </h6>
                                        </div>
                                        <div class="text-end">
                                            <div
                                                class="text-muted mb-1"><span class="d-inline-block direction-ltr">{{date('d M, Y H:i A',strtotime($item['created_at'])) }}</span> </div>
                                            @if($item['debit'] != 0)
                                                <p class="text-danger fs-12">{{ translate('Debit') }}</p>
                                            @else
                                                <p class="text-info fs-12">{{ translate('Credit') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if($loyaltyPointList->count()==0)
                                <div class="d-flex flex-column gap-3 align-items-center text-center my-5">
                                    <img width="72"
                                         src="{{theme_asset('assets/img/media/empty-transaction-history.png') }}"
                                         class="dark-support" alt="">
                                    <h6 class="text-muted">{{ translate('You don’t have any') }}
                                        <br> {{ translate('Transaction yet') }}
                                    </h6>
                                </div>
                            @endif
                            <div class="card-footer bg-transparent border-0 p-0 mt-3">
                                {{ $loyaltyPointList->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <div class="modal fade" id="convertToCurrency" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button"
                        class="close-custom-btn btn d-center border-0 text-muted fs-12 p-1 w-30 h-30 lh-1 rounded-pill position-absolute top-0 inset-inline-end-0 m-2"
                        data-bs-dismiss="modal" aria-label="Close">
                        <i class="fi fi-sr-cross d-flex"></i>
                    </button>
                    <div class="text-center">
                        <h2 class="fw-bold fs-20 mb-1" id="reviewModalLabel">{{translate('Convert to currency')}}</h2>
                        <p class="px-4">{{ translate('Your loyalty points will be converted to currency and transferred to your wallet') }}</p>
                    </div>
                </div>
                <form action="{{ route('loyalty-exchange-currency') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="bg-card p-3 p-sm-4 rounded-10 text-center mx-auto max-w-350">
                            <input class="form-control max-w-200px mx-auto text-center fs-16 fw-medium" type="number" name="point" value="{{ $totalLoyaltyPoint ?? 0 }}" step="any" required>
                            <p class="text-primary fs-12 mt-3">
                                {{ translate('Minimum points for converting to currency is').':'}}
                                <strong>{{ $loyaltyPointMinimumPoint }}</strong>
                                {{ translate('Point &') }}
                                <strong>{{ $loyaltyPointExchangeRate }}</strong>
                                {{ translate('Point') }} =
                                <strong>{{ loyaltyPointToLocalCurrency(amount: $loyaltyPointExchangeRate, type: 'web') }}</strong>
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-center border-0">
                        <div class="d-flex gap-3 justify-content-center align-items-center">
                            <button type="button" data-bs-dismiss="modal" aria-label="Close"
                                    class="btn btn-light min-w-120">{{ translate('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary min-w-120">{{ translate('Submit') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="instructionModal" tabindex="-1" aria-labelledby="instructionModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="instructionModalLabel">{{ translate('How to use') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul>
                        <li>{{ translate('Convert your loyalty point to wallet money.') }}</li>
                        <li>{{ translate('Minimum') }} {{ $loyaltyPointMinimumPoint }} {{ translate('Points required to convert') }}
                            <br>{{ translate('Into currency') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ theme_asset('assets/js/moment.min.js') }}" defer></script>
    <script src="{{ theme_asset('assets/js/daterangepicker.min.js') }}" defer></script>
    <script src="{{ theme_asset('assets/js/user-wallet.js') }}" defer></script>
@endpush
