<div class="col-sm-6 col-lg-4">
    <div class="card card-body h-100 justify-content-center shadow-none border">
        <div class="d-flex gap-2 justify-content-between align-items-center">
            <div class="d-flex flex-column align-items-start">
                <h3 class="mb-1 fz-20 {{ $amountClass ?? '' }}">
                    {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $amount), currencyCode: getCurrencyCode(type: 'default')) }}
                </h3>
                <div class="text-capitalize fs-12 mb-0">{{ $label }}</div>
            </div>
            <img width="36" src="{{ dynamicAsset(path: 'public/assets/back-end/img/'.$icon) }}" alt="">
        </div>
    </div>
</div>
