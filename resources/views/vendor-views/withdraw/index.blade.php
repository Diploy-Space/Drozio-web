@extends('layouts.vendor.app')

@section('title', translate('Withdraw Request'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="mb-1 text-capitalize">{{ translate('Withdraw') }}</h1>
                <div class="d-flex flex-wrap align-items-center gap-2 fs-12">
                    <span class="text-muted">{{ translate('One wallet for') }}</span>
                    @foreach ($walletBusinessModules as $walletBusinessModule)
                        <span class="badge badge-soft-primary">{{ $walletBusinessModule }}</span>
                    @endforeach
                </div>
            </div>
            @if ($auctionWithdrawUrl)
                <a href="{{ $auctionWithdrawUrl }}" class="btn btn-outline--primary">
                    {{ translate('Auction withdraw') }}
                    <i class="fi fi-rr-arrow-right ml-1"></i>
                </a>
            @endif
        </div>

        <div class="d-flex gap-2 alert alert-soft-info mb-3" role="alert">
            <i class="fi fi-sr-lightbulb-on"></i>
            <p class="fs-12 mb-0 text-dark">
                {{ translate('Earnings from these businesses are collected in one wallet, and every withdraw request below is paid from it.') }}
                @if ($auctionWithdrawUrl)
                    {{ translate('Auction earnings are kept in a separate wallet.') }}
                @endif
            </p>
        </div>

        <div class="d-flex gap-2 alert alert-soft-warning mb-3" role="alert">
            <i class="fi fi-sr-info"></i>
            <p class="fs-12 mb-0 text-dark">
                {{ translate('If you want to add withdraw details you can go to') }}
                <a href="{{ route('vendor.shop.payment-information.index') }}" target="_blank"
                    class="text-underline font-weight-bold">
                    {{ translate('Payment Options Page') }}
                </a>
                {{ translate('This setup will help you send withdraw requests easily.') }}
            </p>
        </div>

        @include('vendor-views.withdraw._wallet-summary')

        <div class="card">
            <div class="card-body">
                <h4 class="d-flex align-items-center gap-10 mb-3 text-capitalize">
                    <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/cb.png') }}" alt="">
                    {{ translate('Withdraw Request') }}
                </h4>
                <div class="inline-page-menu mb-4">
                    <div class="inline-page-menu">
                        <ul class="list-unstyled flex-wrap d-flex gap-2">
                            <li class="{{ $activeStatus === 'all' ? 'active' : '' }}">
                                <a href="{{ route('vendor.business-settings.withdraw.index') }}">
                                    {{ translate('All Request') }}
                                    @if ($activeStatus === 'all' && $withdrawRequestCount > 0)
                                        <span>({{ $withdrawRequestCount }})</span>
                                    @endif
                                </a>
                            </li>
                            <li class="{{ $activeStatus === 'pending' ? 'active' : '' }}">
                                <a href="{{ route('vendor.business-settings.withdraw.index', ['status' => 'pending']) }}">
                                    {{ translate('Pending') }}
                                    @if ($activeStatus === 'pending' && $withdrawRequestCount > 0)
                                        <span>({{ $withdrawRequestCount }})</span>
                                    @endif
                                </a>
                            </li>
                            <li class="{{ $activeStatus === 'approved' ? 'active' : '' }}">
                                <a href="{{ route('vendor.business-settings.withdraw.index', ['status' => 'approved']) }}">
                                    {{ translate('Approved') }}
                                    @if ($activeStatus === 'approved' && $withdrawRequestCount > 0)
                                        <span>({{ $withdrawRequestCount }})</span>
                                    @endif
                                </a>
                            </li>
                            <li class="{{ $activeStatus === 'denied' ? 'active' : '' }}">
                                <a href="{{ route('vendor.business-settings.withdraw.index', ['status' => 'denied']) }}">
                                    {{ translate('Denied') }}
                                    @if ($activeStatus === 'denied' && $withdrawRequestCount > 0)
                                        <span>({{ $withdrawRequestCount }})</span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </div>

                </div>

                <div class="card">
                    <div class="p-3">
                        <div class="d-flex gy-1 align-items-center flex-sm-nowrap flex-wrap justify-content-between">
                            <h4 class="text-capitalize">
                                {{ translate('All Request List') }}
                            </h4>
                            <form action="{{ route('vendor.business-settings.withdraw.index') }}" method="GET">
                                @if ($activeStatus !== 'all')
                                    <input type="hidden" name="status" value="{{ $activeStatus }}">
                                @endif
                                <div class="input-group">
                                    <input type="text" name="searchValue" class="form-control"
                                        placeholder="{{ translate('Search By Amount') }}"
                                           value="{{ $searchValue }}">
                                    <div class="input-group-append">
                                        @if ($searchValue)
                                            <a href="{{ $searchClearUrl }}" class="input-group-text bg-light text-dark"
                                               data-toggle="tooltip" title="{{ translate('Clear search') }}">
                                                <i class="fi fi-rr-cross"></i>
                                            </a>
                                        @endif
                                        <button type="submit" class="input-group-text bg-light">
                                            <i class="fi fi-rr-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div id="status-wise-view">
                        @include('vendor-views.withdraw._table')
                    </div>
                </div>
            </div>
        </div>

        <div class="withdraw-request-offcanvas offcanvas-sidebar">
            <div class="offcanvas-overlay" data-dismiss="offcanvas"></div>

            <div class="offcanvas-content bg-white shadow d-flex flex-column">
                <div class="offcanvas-header bg-light d-flex justify-content-between align-items-center p-3">
                    <h3 class="text-capitalize m-0">{{ translate('Withdraw Request') }}</h3>
                    <button type="button" class="close" data-dismiss="offcanvas" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form action="{{ route('vendor.business-settings.withdraw.add') }}" method="post"
                    class="d-flex flex-column flex-grow-1">
                    @csrf

                    @include('vendor-views.withdraw._withdraw-request-form')

                    <div class="offcanvas-footer offcanvas-footer-sticky p-3 border-top bg-white d-flex gap-3">
                        <button type="reset" class="btn btn-secondary w-100" data-dismiss="offcanvas">
                            {{ translate('Cancel') }}
                        </button>
                        <button type="submit" class="btn btn--primary w-100">
                            {{ translate('Send Request') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($withdrawRequests->count() > 0)
        @foreach ($withdrawRequests as $key => $withdrawRequest)
            @include('vendor-views.withdraw._withdraw-request-preview', ['withdrawRequest' => $withdrawRequest])
            @include('vendor-views.withdraw._withdraw-request-edit', ['withdrawRequest' => $withdrawRequest])
        @endforeach
    @endif

    <span id="get-status-filter-route" data-action="{{ route('vendor.business-settings.withdraw.index') }}"></span>
    @include('layouts.vendor.partials.offcanvas._withdraw')
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/vendor/withdraw.js') }}"></script>
@endpush
