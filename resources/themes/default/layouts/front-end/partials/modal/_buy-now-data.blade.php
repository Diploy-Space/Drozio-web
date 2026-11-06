<form action="{{ route('cart.add') }}" method="POST" class="shipping-method-form">
    @csrf
    <div class="shipping-method-form__header">
        <div class="shipping-method-form__heading">
            <span class="shipping-method-form__icon">
                <img src="{{ theme_asset(path: 'public/assets/front-end/img/icons/car.svg') }}" alt="">
            </span>
            <div>
                <h5 class="shipping-method-form__title" id="buyNowModalLabel">
                    {{ translate('Shipping Method') }}
                </h5>
                <span class="shipping-method-form__subtitle">
                    {{ translate('Choose shipping method') }}
                </span>
            </div>
        </div>
        <button type="button" class="close shipping-method-form__close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <div class="shipping-method-form__body">
        @foreach($shipping_method_list as $shippingMethodKey => $shippingMethod)
            <input type="radio" class="shipping-method-option__input" name="shipping_method_id"
                   id="shipping-method-{{ $shippingMethod['id'] }}" value="{{ $shippingMethod['id'] }}"
                   @checked($shippingMethodKey == 0)>
            <label class="shipping-method-option" for="shipping-method-{{ $shippingMethod['id'] }}">
                <span class="shipping-method-option__radio"></span>
                <span class="shipping-method-option__info">
                    <span class="shipping-method-option__title">{{ ucfirst($shippingMethod['title']) }}</span>
                    <span class="shipping-method-option__duration">
                        {{ translate('Duration') }}: {{ $shippingMethod['duration'] }}
                    </span>
                </span>
                <span class="shipping-method-option__cost">
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

    <div class="shipping-method-form__footer">
        <button type="submit" class="btn text-white web--bg-primary shipping-method-form__action">
            {{ translate('Proceed to Checkout') }}
        </button>
    </div>
</form>
