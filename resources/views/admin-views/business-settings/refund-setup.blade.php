@extends('layouts.admin.app')

@section('title', translate('Refund Setup'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h2 class="h1 mb-0 text-capitalize d-flex flex-column flex-sm-row align-items-sm-center gap-sm-2">
                {{ translate('Business Setup') }}
            </h2>

            @include('admin-views.business-settings._business-module-switcher')
        </div>

        @include('admin-views.business-settings.business-setup-inline-menu')

        <form action="{{ route('admin.settings.business-settings.refund-setup') }}" method="post" enctype="multipart/form-data">
            @csrf

            <div class="card">
                <div class="card-body d-flex flex-column gap-3 gap-sm-20">
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <div>
                                <h2 class="text-capitalize">{{ translate('Refund Order') }}</h2>
                                <p class="mb-0">
                                    {{ translate('Set the retail refund time limit and payout method.') }}
                                    {{ translate('To refund into wallet, turn on') }}
                                    <a href="{{ route('admin.settings.business-settings.customer-settings') }}"
                                       target="_blank"
                                       class="text-decoration-underline fw-semibold">
                                        {{ translate('Customer Wallet') }}
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    @php($refundDayLimit = getWebConfig('refund_day_limit'))
                                    <label class="form-label text-capitalize" for="">
                                        {{ translate('Refund Order Validity') }}({{ translate('Days') }})
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right"
                                              data-bs-title="{{ translate('Days a customer can request a refund after delivery. 0 disables refunds.') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <input type="number" class="form-control" name="refund_day_limit"
                                           id="refund_day_limit" step="1" min="0"
                                           placeholder="{{ translate('Ex') . ': ' . '10' }}"
                                           value="{{ $refundDayLimit ?? 0 }}">
                                </div>
                            </div>

                            @php($walletAddRefund = getWebConfig(name: 'wallet_add_refund'))
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label text-capitalize" for="wallet_add_refund">
                                        {{ translate('Add Refund Amount To Wallet') }}
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right"
                                              data-bs-title="{{ translate('Customer Wallet appears as a refund option for admins.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                    </label>
                                    <label
                                        class="d-flex justify-content-between align-items-center gap-3 border rounded px-3 py-10 bg-white user-select-none">
                                        <span class="fw-medium text-dark">{{ translate('Status') }}</span>
                                        <label class="switcher" for="wallet_add_refund">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="wallet_add_refund"
                                                id="wallet_add_refund"
                                                {{ $walletAddRefund ? 'checked' : '' }}
                                                data-modal-type="input-change"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/wallet-on.png') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/wallet-off.png') }}"
                                                data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Refund to Wallet option')]) }}?"
                                                data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Wallet option')]) }}?"
                                                data-on-message="<p>{{ translate('Shows Customer Wallet as a refund method on the Admin Refund page.') }}</p>"
                                                data-off-message="<p>{{ translate('Hides Customer Wallet as a refund method on the Admin Refund page.') }}</p>">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                            <i class="fi fi-sr-bulb text-info fs-16"></i>
                            <span>
                                {{ translate('Set refund validity to 0 days to disable refunds.') }}
                            </span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end trans3 action-btn-wrapper-container">
                        <div class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                            <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">
                                {{ translate('Reset') }}
                            </button>
                            <button type="submit" class="btn btn-primary px-3 px-sm-4">
                                <i class="fi fi-sr-disk"></i>
                                {{ translate('Save Information') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    @include("layouts.admin.partials.offcanvas._refund-setup")
@endsection
