<form action="{{ route('cart.add') }}" method="POST">
    @csrf
    <div class="shipping-method-head">
        <div class="d-flex align-items-center gap-2">
            <span class="shipping-method-head-icon">
                <img src="{{ theme_asset('assets/img/icons/car.svg') }}" alt="">
            </span>
            <div>
                <h5 class="shipping-method-head-title" id="buyNowModalLabel">
                    {{ translate('Shipping Method') }}
                </h5>
                <span class="shipping-method-head-subtitle">
                    {{ translate('Choose shipping method') }}
                </span>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
    </div>

    <div class="shipping-method-list">
        @foreach($shipping_method_list as $shippingMethodKey => $shippingMethod)
            <input type="radio" class="shipping-method-input" name="shipping_method_id"
                   id="shipping-method-{{ $shippingMethod['id'] }}" value="{{ $shippingMethod['id'] }}"
                   @checked($shippingMethodKey == 0)>
            <label class="shipping-method-card" for="shipping-method-{{ $shippingMethod['id'] }}">
                <span class="shipping-method-card-dot"></span>
                <span class="shipping-method-card-info">
                    <span class="shipping-method-card-title">{{ ucfirst($shippingMethod['title']) }}</span>
                    <span class="shipping-method-card-duration">
                        {{ translate('Duration') }}: {{ $shippingMethod['duration'] }}
                    </span>
                </span>
                <span class="shipping-method-card-cost">
                    {{ $shippingMethod['cost'] > 0 ? webCurrencyConverter($shippingMethod['cost']) : translate('Free') }}
                </span>
            </label>
        @endforeach

        <input type="hidden" value="1" name="shipping_method_exist">
    </div>

    <div class="d-none">
        @foreach($productData as $inputKey => $productInputData)
            <input type="hidden" value="{{ $productInputData }}" name="{{ $inputKey }}">
        @endforeach
    </div>

    <div class="shipping-method-footer">
        <button type="submit" class="btn btn-primary w-100 shipping-method-submit">
            {{ translate('Proceed to Checkout') }}
        </button>
    </div>
</form>
