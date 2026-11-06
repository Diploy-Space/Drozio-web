@php($currentCustomerData = $summaryData['currentCustomerData'] ?? null)

@if ($summaryData['currentCustomer'] != 'Walk-In Customer')
    <div class="pos-customer-card">
        <div class="pos-customer-card__label">{{ translate('Customer Information') }}</div>
        <div class="pos-customer-card__row">
            <i class="fi fi-rr-user"></i>
            <span class="text-dark fw-semibold">{{ $currentCustomerData?->f_name.' '.$currentCustomerData?->l_name }}</span>
        </div>
        <div class="pos-customer-card__row">
            <i class="fi fi-rr-phone-flip"></i>
            <a href="tel:{{ $currentCustomerData?->phone }}" class="text-body pos-num">{{ $currentCustomerData?->phone }}</a>
        </div>
        @if($currentCustomerData?->street_address && $currentCustomerData?->city && $currentCustomerData?->country)
            <div class="pos-customer-card__row">
                <i class="fi fi-rr-marker"></i>
                <span>{{ $currentCustomerData->street_address . ', '. $currentCustomerData->city.', '. $currentCustomerData->country }}</span>
            </div>
        @endif
    </div>
    @php($walletStatus = getWebConfig('wallet_status') ?? 0)
    @if ($walletStatus)
        <input type="hidden" class="form-control customer-wallet-balance"
               value="{{ usdToDefaultCurrency(amount: $currentCustomerData?->wallet_balance ?? 0) }}"
               readonly>
    @endif
@endif
