@extends('layouts.admin.app')

@section('title', translate('Withdraw information View'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 mb-sm-20">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <a href="{{ route('admin.people.vendors.withdraw-list') }}" class="btn btn-circle border-0 fs-12 text-body bg-section2 shadow-none"
                   style="--size: 2rem;" aria-label="{{ translate('Back') }}">
                    <i class="fi fi-rr-angle-left d-flex"></i>
                </a>
                <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                    <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/withdraw-icon.png') }}" alt="">
                    {{ translate('Withdraw') }}
                </h2>
                <span class="badge badge-{{ $statusVariant }} text-bg-{{ $statusVariant }} rounded-50">{{ $statusLabel }}</span>
            </div>
            @if($isPending)
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#withdrawStatusModal">
                    {{ translate('Proceed') }} <i class="fi fi-rr-angle-double-small-right"></i>
                </button>
            @endif
        </div>

        <div class="row g-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-4 align-items-center">
                            <div class="col-xl-4">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="d-flex align-items-center justify-content-center rounded-circle bg-primary--light flex-shrink-0"
                                          style="width: 3.5rem; height: 3.5rem;">
                                        <i class="fi fi-rr-wallet fs-20 text-primary d-flex"></i>
                                    </span>
                                    <div>
                                        <div class="fs-12 text-muted">{{ translate('Withdraw Amount') }}</div>
                                        <h2 class="mb-0 fw-bold text-break">
                                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $withdrawRequest->amount), currencyCode: getCurrencyCode(type: 'default')) }}
                                        </h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-8">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <div class="bg-section rounded-10 p-3 h-100">
                                            <div class="d-flex align-items-center gap-2 fs-12 text-muted mb-1">
                                                <i class="fi fi-rr-clock d-flex"></i>
                                                {{ translate('Request Time') }}
                                            </div>
                                            <div class="fs-14 fw-medium text-dark">
                                                {{ $withdrawRequest->created_at?->format('d M Y, h:i A') ?? translate('N/A') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="bg-section rounded-10 p-3 h-100">
                                            <div class="d-flex align-items-center gap-2 fs-12 text-muted mb-1">
                                                <i class="fi fi-rr-credit-card d-flex"></i>
                                                {{ translate('Withdraw method') }}
                                            </div>
                                            <div class="fs-14 fw-medium text-dark text-break">
                                                {{ $withdrawalMethodName ?? translate('N/A') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($withdrawalMethodFields)
                <div class="col-lg-6 col-xxl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between gap-2 border-bottom pb-3 mb-2">
                                <h3 class="h3 mb-0 text-capitalize">{{ translate('Withdrawal Method') }}</h3>
                                <i class="fi fi-rr-usd-circle fs-5 text-muted d-flex"></i>
                            </div>
                            <ul class="list-unstyled mb-0">
                                @foreach($withdrawalMethodFields as $key => $value)
                                    @include('admin-views.vendor.partials._withdraw-info-row', [
                                        'label' => ucwords(str_replace('_', ' ', $key)),
                                        'value' => $value,
                                    ])
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @elseif($withdrawRequest->seller)
                <div class="col-lg-6 col-xxl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between gap-2 border-bottom pb-3 mb-2">
                                <h3 class="h3 mb-0 text-capitalize">{{ translate('Bank information') }}</h3>
                                <i class="fi fi-rr-bank fs-5 text-muted d-flex"></i>
                            </div>
                            <ul class="list-unstyled mb-0">
                                @include('admin-views.vendor.partials._withdraw-info-row', ['label' => translate('Bank Name'), 'value' => $withdrawRequest->seller->bank_name])
                                @include('admin-views.vendor.partials._withdraw-info-row', ['label' => translate('Branch'), 'value' => $withdrawRequest->seller->branch])
                                @include('admin-views.vendor.partials._withdraw-info-row', ['label' => translate('Holder Name'), 'value' => $withdrawRequest->seller->holder_name])
                                @include('admin-views.vendor.partials._withdraw-info-row', ['label' => translate('Account No'), 'value' => $withdrawRequest->seller->account_no])
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @if($withdrawRequest->seller?->shop)
                <div class="col-lg-6 col-xxl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between gap-2 border-bottom pb-3 mb-2">
                                <h3 class="h3 mb-0 text-capitalize">{{ translate('Shop Information') }}</h3>
                                <i class="fi fi-rr-shop fs-5 text-muted d-flex"></i>
                            </div>
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex flex-wrap align-items-start justify-content-between gap-2 py-2">
                                    <span class="fs-12 text-muted">{{ translate('Vendor') }}</span>
                                    <span class="fs-14 fw-medium text-dark text-break text-end d-flex align-items-center gap-1">
                                        {{ $withdrawRequest->seller->shop->name }}
                                        @include('admin-views.vendor.partials._verified-badge', ['isVerified' => $withdrawRequest->seller->hasVerifiedBadge(), 'size' => 16])
                                    </span>
                                </li>
                                @include('admin-views.vendor.partials._withdraw-info-row', ['label' => translate('Phone'), 'value' => $withdrawRequest->seller->shop->contact])
                                @include('admin-views.vendor.partials._withdraw-info-row', ['label' => translate('Address'), 'value' => $withdrawRequest->seller->shop->address])
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="col-lg-6 col-xxl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between gap-2 border-bottom pb-3 mb-2">
                            <h3 class="h3 mb-0 text-capitalize">{{ translate('Vendor Information') }}</h3>
                            <i class="fi fi-sr-user fs-5 text-muted d-flex"></i>
                        </div>
                        @if($withdrawRequest->seller)
                            <ul class="list-unstyled mb-0">
                                @include('admin-views.vendor.partials._withdraw-info-row', ['label' => translate('Name'), 'value' => trim($withdrawRequest->seller->f_name.' '.$withdrawRequest->seller->l_name)])
                                @include('admin-views.vendor.partials._withdraw-info-row', ['label' => translate('Email'), 'value' => $withdrawRequest->seller->email])
                                @include('admin-views.vendor.partials._withdraw-info-row', ['label' => translate('Phone'), 'value' => $withdrawRequest->seller->phone])
                            </ul>
                        @else
                            <p class="text-muted mb-0">{{ translate('No Data Found') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-xxl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between gap-2 border-bottom pb-3 mb-3">
                            <h3 class="h3 mb-0 text-capitalize">{{ translate('Transaction Note') }}</h3>
                            <i class="fi fi-rr-comment-alt fs-5 text-muted d-flex"></i>
                        </div>
                        <p class="fs-14 text-dark mb-0 text-break">
                            {{ $withdrawRequest->transaction_note ?: translate('No Data Found') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @if($isPending)
            <div class="modal fade" id="withdrawStatusModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <form action="{{ route('admin.people.vendors.withdraw-status', [$withdrawRequest['id']]) }}" method="POST">
                            @csrf
                            <div class="modal-header border-0 p-2 d-flex justify-content-end">
                                <button type="button" class="btn btn-circle border-0 fs-12 text-body bg-section2 shadow-none" style="--size: 2rem;" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}">
                                    <i class="fi fi-sr-cross d-flex"></i>
                                </button>
                            </div>
                            <div class="modal-body py-0">
                                <div class="text-center">
                                    <img class="mb-3" width="60"
                                         src="{{ dynamicAsset(path: 'public/assets/back-end/img/withdraw-icon.png') }}"
                                         alt="{{ translate('Withdraw Request Process') }}">
                                    <h4 class="fs-18 mb-4 mx-auto px-0 px-sm-4">
                                        {{ translate('Withdraw Request Process') }}
                                    </h4>
                                </div>

                                <div class="bg-section rounded-10 p-3 mb-20">
                                    <label class="form-label mb-2">{{ translate('Request') }} <span class="text-danger">*</span></label>
                                    <div class="select-wrapper">
                                        <select name="approved" class="form-select">
                                            <option value="{{ \App\Services\New\WithdrawRequestService::APPROVAL_APPROVED }}">{{ translate('Approve') }}</option>
                                            <option value="{{ \App\Services\New\WithdrawRequestService::APPROVAL_DENIED }}">{{ translate('Deny') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="bg-section rounded-10 p-3">
                                    <label class="form-label mb-2">{{ translate('Note About Transaction Or Request') }}</label>
                                    <textarea class="form-control" name="note" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-30">
                                <div class="d-flex flex-wrap justify-content-end gap-3">
                                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">{{ translate('Close') }}</button>
                                    <button type="submit" class="btn btn-primary px-4">{{ translate('Submit') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
