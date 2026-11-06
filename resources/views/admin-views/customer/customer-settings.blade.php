@extends('layouts.admin.app')

@section('title', translate('Customer Settings'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h2 class="h1 mb-0 text-capitalize d-flex flex-column flex-sm-row align-items-sm-center gap-sm-2">
                {{ translate('Business Setup') }}
            </h2>

            @include('admin-views.business-settings._business-module-switcher')
        </div>

        @include('admin-views.business-settings.business-setup-inline-menu')

        <form action="{{ route('admin.settings.business-settings.customer-settings') }}" method="post" id="update-settings">
            @csrf

            <div class="d-flex flex-column gap-3">
                <div class="card">
                    <div class="card-body d-flex flex-column gap-3 gap-sm-20">
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <h2>{{ translate('Customer Wallet') }}</h2>
                                    <p class="mb-0">
                                        {{ translate('Customers get refunds in the wallet and can pay orders with it.') }}
                                    </p>
                                </div>
                                <div>
                                    <label class="switcher" for="customer-wallet-status">
                                        <input class="switcher_input custom-modal-plugin" type="checkbox" value="1"
                                            name="customer_wallet" id="customer-wallet-status"
                                            {{ $walletStatus ? 'checked' : '' }} data-modal-type="input-change"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/customer-wallet-on.png') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/customer-wallet-off.png') }}"
                                            data-on-title="{{ translate('Want to Turn ON Customer Wallet') }}"
                                            data-off-title="{{ translate('Want to Turn OFF Customer Wallet') }}"
                                            data-on-message="<p>{{ translate('Customers get a wallet for placing orders and receiving refunds.') }}</p>"
                                            data-off-message="<p>{{ translate('If disabled customer wallet option will be hidden from their account') }}</p>">
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="row g-4">
                                <div class="col-xl-4 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label text-capitalize" for="">
                                            {{ translate('Add funds to wallet') }}
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-bs-title="{{ translate('Customers can add funds to the wallet with an available payment method.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                        </label>
                                        <label
                                            class="d-flex justify-content-between align-items-center gap-3 border rounded px-3 py-10 bg-white user-select-none">
                                            <span class="fw-medium text-dark">{{ translate('Status') }}</span>
                                            <label class="switcher" for="add-funds-to-wallet">
                                                <input class="switcher_input custom-modal-plugin" type="checkbox"
                                                    value="1" name="add_funds_to_wallet" id="add-funds-to-wallet"
                                                    {{ $walletStatus && $addFundsToWallet ? 'checked' : '' }}
                                                    {{ $walletStatus ? '' : 'disabled' }}
                                                    data-modal-type="input-change"
                                                    data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/wallet-on.png') }}"
                                                    data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/wallet-off.png') }}"
                                                    data-on-title="{{ translate('Want to Turn ON Add Fund to Wallet option') }}"
                                                    data-off-title="{{ translate('Want to Turn OFF Add Fund to Wallet option') }}"
                                                    data-on-message="<p>{{ translate('If enabled customers can add money to their wallet') }}</p>"
                                                    data-off-message="<p>{{ translate('Customers cannot add money to their wallet.') }}</p>">
                                                <span class="switcher_control"></span>
                                            </label>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-xl-4 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label text-capitalize" for="">
                                            {{ translate('Minimum add amount') }}
                                            ({{ getCurrencySymbol(type: 'default') }})
                                        </label>
                                        <input type="number" min="1" step="any" class="form-control" name="minimum_add_fund_amount"
                                            id="minimum_add_fund_amount" placeholder="{{ translate('Ex') . ': ' . '10' }}"
                                            value="{{ $minimumAddFundAmount }}"
                                            {{ $walletStatus ? '' : 'disabled' }}>
                                    </div>
                                </div>
                                <div class="col-xl-4 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label text-capitalize" for="">
                                            {{ translate('Maximum Add Amount') }}
                                            ({{ getCurrencySymbol(type: 'default') }})
                                        </label>
                                        <input type="number" min="1" step="any" class="form-control" name="maximum_add_fund_amount"
                                            id="maximum_add_fund_amount" placeholder="{{ translate('Ex') . ': ' . '10' }}"
                                            value="{{ $maximumAddFundAmount }}"
                                            {{ $walletStatus ? '' : 'disabled' }}>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($isAiShoppingAssistantAvailable)
                <div class="card">
                    <div class="card-body">
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <h2>{{ translate('AI Shopping Assistant') }}</h2>
                                    <p class="mb-0">
                                        {{ translate('Shows the AI shopping assistant icon on the storefront.') }}
                                    </p>
                                </div>
                                <div>
                                    <label class="switcher {{ $isDemoMode ? 'call-demo-alert' : '' }}" for="ai-shopping-assistant-status">
                                        <input class="switcher_input custom-modal-plugin" type="checkbox" value="1"
                                            name="ai_shopping_assistant_status" id="ai-shopping-assistant-status"
                                            {{ $aiShoppingAssistantStatus ? 'checked' : '' }}
                                            {{ $isDemoMode ? 'disabled' : '' }}
                                            data-modal-type="input-change"
                                            data-on-title="{{ translate('Want to Turn ON AI Shopping Assistant') }}"
                                            data-off-title="{{ translate('Want to Turn OFF AI Shopping Assistant') }}"
                                            data-on-message="<p>{{ translate('Shows the AI shopping assistant icon to customers.') }}</p>"
                                            data-off-message="<p>{{ translate('Hides the AI shopping assistant from all pages.') }}</p>">
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="card">
                    <div class="card-body d-flex flex-column gap-3 gap-sm-20">
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <h2 class="text-capitalize">
                                        {{ translate('Customer Loyalty Point') }}
                                    </h2>
                                    <p class="mb-0">
                                        {{ translate('Set the rules for earning and using loyalty points.') }}
                                    </p>
                                </div>
                                <div>
                                    <label class="switcher" for="customer-loyalty-point">
                                        <input class="switcher_input custom-modal-plugin" type="checkbox" value="1"
                                            name="customer_loyalty_point" id="customer-loyalty-point"
                                            {{ $loyaltyPointStatus ? 'checked' : '' }} data-modal-type="input-change"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/loyalty-on.png') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/loyalty-off.png') }}"
                                            data-on-title="{{ translate('Want to Turn ON Loyalty Point') }}"
                                            data-off-title="{{ translate('Want to Turn OFF Loyalty Point') }}"
                                            data-on-message="<p>{{ translate('Loyalty points are available in the customer account.') }}</p>"
                                            data-off-message="<p>{{ translate('If disabled loyalty point option will be hidden from the customers account') }}</p>">
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="row g-4">
                                <div class="col-xl-4 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label text-capitalize" for="loyalty_point_exchange_rate">
                                            {{ translate('Equivalent Points Needed to Redeem') }}
                                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: 1), currencyCode: getCurrencyCode()) }}
                                        </label>
                                        <input type="text" class="form-control" name="loyalty_point_exchange_rate"
                                            {{ $loyaltyPointStatus ? 'required' : '' }}
                                            id="loyalty_point_exchange_rate"
                                            placeholder="{{ translate('Ex') . ': ' . '10' }}"
                                            value="{{ $loyaltyPointExchangeRate }}" required>
                                    </div>
                                </div>
                                <div class="col-xl-4 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label text-capitalize" for="minimum_transfer_point">
                                            {{ translate('Minimum Point Required To Convert') }}
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right"
                                                data-bs-title="{{ translate('Points needed before they can be converted to wallet balance.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                        </label>
                                        <input type="text" class="form-control" name="minimum_transfer_point"
                                            id="minimum_transfer_point" placeholder="{{ translate('Ex') . ': ' . '2' }}"
                                            {{ $loyaltyPointStatus ? 'required' : '' }}
                                            value="{{ $loyaltyPointMinimumPoint }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="row g-4 align-items-center">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <h4 class="fw-medium text-capitalize">
                                            {{ translate('Earn Loyalty Point on Each Order') }}
                                        </h4>
                                        <p class="mb-0">
                                            {{ translate('Setup loyalty point percentage earned by customer based on order amount') }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="switcher" for="customer-loyalty-point-each-order">
                                            <input class="switcher_input custom-modal-plugin" type="checkbox" value="1"
                                                   name="loyalty_point_for_each_order" id="customer-loyalty-point-each-order"
                                                   {{ $loyaltyPointStatus && $loyaltyPointForEachOrder ? 'checked' : '' }}
                                                   {{ $loyaltyPointStatus ? '' : 'disabled' }}
                                                   data-modal-type="input-change"
                                                   data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/loyalty-on.png') }}"
                                                   data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/loyalty-off.png') }}"
                                                   data-on-title="{{ translate('Want to Turn ON Loyalty Point on Each Order') }}"
                                                   data-off-title="{{ translate('Want to Turn OFF Loyalty Point on Each Order') }}"
                                                   data-on-message="<p>{{ translate('Customers earn loyalty points on each order.') }}</p>"
                                                   data-off-message="<p>{{ translate('Hides loyalty points from customers at checkout.') }}</p>">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label text-capitalize" for="">
                                            {{ translate('Earning Percentage') }} (%)
                                        </label>
                                        <input type="number" class="form-control" name="item_purchase_point"
                                            id="" placeholder="{{ translate('Ex') . ': ' . '2' }}"
                                            value="{{ $loyaltyPointItemPurchasePoint }}" min="0" step="any"
                                            {{ $loyaltyPointStatus ? 'required' : '' }}>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body d-flex flex-column gap-3 gap-sm-20">
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <h2 class="text-capitalize">
                                        {{ translate('Customer Referral Earning Settings') }}
                                    </h2>
                                    <p class="mb-0">
                                        {{ translate('Customers refer friends with a code and earn rewards.') }}
                                    </p>
                                </div>
                                <div>
                                    <label class="switcher" for="ref-earning-status">
                                        <input class="switcher_input custom-modal-plugin" type="checkbox" value="1"
                                            name="ref_earning_status" id="ref-earning-status"
                                            {{ $refEarningStatus ? 'checked' : '' }} data-modal-type="input-change"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/referral-earning-on.png') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/referral-earning-off.png') }}"
                                            data-on-title="{{ translate('Want to Turn ON Referral And Earning option') }}?"
                                            data-off-title="{{ translate('Want to Turn OFF Referral And Earning option') }}?"
                                            data-on-message="<p>{{ translate('Customers earn rewards when someone registers with their referral code.') }}</p>"
                                            data-off-message="<p>{{ translate('If disabled Customers will no longer earn rewards for successful referrals.') }}</p>">
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="row g-4 align-items-center">
                                <div class="col-lg-4">
                                    <h2 class="text-capitalize">
                                        {{ translate('Who share the Code') }}
                                    </h2>
                                    <p>
                                        {{ translate('Reward for the customer who shares their referral code.') }}
                                    </p>
                                </div>
                                <div class="col-lg-8">
                                    <div class="form-group">
                                        <label class="form-label text-capitalize" for="ref_earning_exchange_rate">
                                            {{ translate('Earnings to Each Referral') }}
                                            ({{ getCurrencySymbol(type: 'default') }})
                                        </label>
                                        <input type="text" class="form-control" name="ref_earning_exchange_rate"
                                            id="ref_earning_exchange_rate"
                                            placeholder="{{ translate('Ex') . ': ' . '10' }}"
                                            {{ $refEarningStatus ? 'required' : '' }}
                                            value="{{ $refEarningExchangeRate }}">
                                        <p class="text-danger mt-1 mb-0 {{ $walletStatus ? 'd-none' : '' }}" id="referral-wallet-warning">
                                            {{ translate('Customer wallet must be on for customers to receive rewards.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="row g-4 align-items-center">
                                <div class="col-lg-4">
                                    <h2 class="text-capitalize">{{ translate('Who use the code') }}</h2>
                                    <p class="mb-0">
                                        {{ translate('Discount for using a referral code on a first order.') }}
                                    </p>
                                </div>
                                <div class="col-lg-8">
                                    <div class="row g-4">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label class="form-label text-capitalize" for="">
                                                    {{ translate('Customer will get discount on first order') }}
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <label
                                                    class="d-flex justify-content-between align-items-center gap-3 border rounded px-3 py-10 bg-white user-select-none">
                                                    <span class="fw-medium text-dark">{{ translate('Status') }}</span>
                                                    <label class="switcher" for="ref_earning_discount_status">

                                                        <input class="switcher_input custom-modal-plugin" type="checkbox"
                                                            @if (($referralDiscount['ref_earning_discount_status'] ?? 0) == 1) checked @endif value="1"
                                                            name="ref_earning_discount_status"
                                                            id="ref_earning_discount_status"
                                                            data-modal-type="input-change"
                                                           data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/referral-earning-on.png') }}"
                                                           data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/referral-earning-off.png') }}"
                                                            data-on-title="{{ translate('Are you sure you want to turn on new user referral reward') }}?"
                                                            data-off-title="{{ translate('Are you sure you want to turn off new user referral reward') }}?"
                                                            data-on-message="<p>{{ translate('Referral rewards need both referral options enabled.') }}</p>"
                                                            data-off-message="<p>{{ translate('Referral rewards need both referral options enabled.') }}</p>">
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label text-capitalize" for="">
                                                    {{ translate('Discount Amount') }}
                                                    <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                        data-bs-placement="right" data-bs-title="{{ translate('Discount the referred customer gets on their first order.') }} {{ translate('You can choose a fixed amount or a percentage .') }}">
                                                        <i class="fi fi-sr-info"></i>
                                                    </span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" name="discount_amount" id="discount_amount" min="0"
                                                        value="{{ $referralDiscount['discount_amount'] ?? '' }}"
                                                        class="form-control" placeholder="{{ translate('Ex') }}: 10">
                                                    <div class="input-group-append select-wrapper">
                                                        <select name="discount_type" class="form-select shadow-none">
                                                            <option value="percentage" {{ ($referralDiscount['discount_type'] ?? '') == 'percentage' ? 'selected' : '' }}>%</option>
                                                            <option value="flat" {{ ($referralDiscount['discount_type'] ?? '') == 'flat' ? 'selected' : '' }}>$</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label text-capitalize" for="">
                                                    {{ translate('Validity') }}
                                                    <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                        data-bs-placement="right" data-bs-title="{{ translate('How many days the referral discount stays active after signup.') }}">
                                                        <i class="fi fi-sr-info"></i>
                                                    </span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" name="validity" id="validity"
                                                        value="{{ $referralDiscount['validity'] ?? '' }}"
                                                        class="form-control" placeholder="{{ translate('Ex') }}: 10" min="1" >
                                                    <div class="input-group-append select-wrapper">
                                                        <select class="form-select shadow-none" name="validity_type">
                                                            <option disabled value="">
                                                                {{ translate('Select Type') }}
                                                            </option>
                                                            @foreach ($referralValidityTypes as $validityType => $validityLabel)
                                                                <option value="{{ $validityType }}"
                                                                    {{ ($referralDiscount['validity_type'] ?? '') == $validityType ? 'selected' : '' }}>
                                                                    {{ $validityLabel }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                <div
                    class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="submit" class="btn btn-primary px-3 px-sm-4 text-capitalize">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
    @include('layouts.admin.partials.offcanvas._customer-settings')
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/business-setting.js') }}"></script>
@endpush
