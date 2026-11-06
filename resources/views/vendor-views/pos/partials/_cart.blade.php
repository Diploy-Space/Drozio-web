@php($cartLines = collect($cartItems['cartItemValue'])->filter(fn ($item) => is_array($item)))
@php($hasCartLines = $cartLines->isNotEmpty())
@php($systemTaxConfig = getTaxModuleSystemTypesConfig())
@php($walletStatus = getWebConfig('wallet_status') ?? 0)
@php($currencySymbolPosition = getWebConfig('currency_symbol_position'))
@php($payableAmount = usdToDefaultCurrency(amount: $cartItems['total'] + ($cartItems['totalTax'] ?? 0) - $cartItems['couponDiscount']))

<form action="{{ route('vendor.retail.pos.order-place') }}" method="post" id="order-place">
    @csrf
    <div class="pos-cart-stack">
        <div class="pos-block pos-block--cart">
            @if($hasCartLines)
                <button class="pos-block__head" type="button"
                        data-toggle="collapse" data-target="#collapsecCartList"
                        aria-expanded="true">
                    <span>{{ translate('Cart Item List') }}</span>
                    <span class="d-flex align-items-center gap-2">
                        <span class="pos-count-chip pos-num">{{ $cartLines->count() }}</span>
                        <i class="fi fi-rr-angle-small-down pos-collapse-icon"></i>
                    </span>
                </button>
                <div class="collapse show" id="collapsecCartList">
                    <div class="pos-cart-scroll">
                        @foreach($cartLines as $key => $item)
                            <div class="pos-line">
                                <img class="pos-line__thumb"
                                     src="{{ getStorageImages(path: $item['image'], type: 'backend-product') }}"
                                     alt="{{ $item['name'] }}">
                                <div class="pos-line__body">
                                    <span class="pos-line__name" title="{{ $item['name'] }}">{{ $item['name'] }}</span>
                                    <span class="pos-line__meta pos-num">
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item['price']), currencyCode: getCurrencyCode()) }}
                                        @if($item['variant'])
                                            &middot; {{ $item['variant'] }}
                                        @endif
                                    </span>
                                </div>
                                <div class="qty-input-group">
                                    <button type="button" class="qty-count qty-count--minus" data-action="minus">&minus;</button>
                                    <input class="product-qty action-pos-update-quantity pos-num"
                                           type="number" name="product-qty" min="1" max="600"
                                           value="{{ $item['quantity'] }}" data-key="{{ $key }}"
                                           data-product-key="{{ $item['id'] }}"
                                           data-product-variant="{{ $item['variant'] }}"
                                           aria-label="{{ translate('Quantity') }}">
                                    <button type="button" class="qty-count qty-count--add" data-action="plus">+</button>
                                </div>
                                <div class="pos-line__actions">
                                    <div class="pos-line__amount pos-num">
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item['productSubtotal']), currencyCode: getCurrencyCode()) }}
                                    </div>
                                    <a href="javascript:" data-id="{{ $item['id'] }}" data-variant="{{ $item['variant'] }}"
                                       class="pos-line__remove remove-from-cart" aria-label="{{ translate('Remove') }}">
                                        <i class="fi fi-rr-cross-small d-flex"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="pos-block__head">{{ translate('Cart Item List') }}</div>
                <div class="pos-cart-empty">
                    <i class="fi fi-rr-shopping-cart"></i>
                    <div class="pos-empty__title">{{ translate('Your cart is empty.') }}</div>
                    <div class="pos-empty__text">{{ translate('Select a product to start a new sale') }}</div>
                </div>
            @endif
        </div>

        <div class="pos-block">
            <div class="pos-block__head">{{ translate('Billing Summary') }}</div>

            <dl class="pos-sum pos-num">
                <div class="pos-sum__row">
                    <dt>{{ translate('Subtotal') }}</dt>
                    <dd>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $cartItems['subtotal'] + $cartItems['discountOnProduct']), currencyCode: getCurrencyCode()) }}</dd>
                </div>

                <div class="pos-sum__row">
                    <dt>{{ translate('Product Discount') }}</dt>
                    <dd>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: round($cartItems['discountOnProduct'], 2)), currencyCode: getCurrencyCode()) }}</dd>
                </div>

                <div class="pos-sum__row">
                    <dt>{{ translate('Coupon Discount') }}</dt>
                    <dd>
                        <button id="coupon_discount" class="pos-sum__edit" type="button" @disabled(! $hasCartLines)
                                data-toggle="modal" data-target="#add-coupon-discount">
                            <i class="fi fi-rr-pencil d-flex"></i>
                            <span class="text-underline">
                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $cartItems['couponDiscount']), currencyCode: getCurrencyCode()) }}
                            </span>
                        </button>
                    </dd>
                </div>

                <div class="pos-sum__row">
                    <dt>{{ translate('Extra discount') }}</dt>
                    <dd class="d-flex align-items-center gap-3">
                        @if($cartItems['extraDiscount'] > 0)
                            <a href="#" class="pos-sum__remove" id="pos-extra-discount-remove-vendor"
                               aria-label="{{ translate('Remove') }}">
                                <i class="fi fi-rr-trash"></i>
                            </a>
                        @endif
                        <button id="extra_discount" class="pos-sum__edit" type="button" @disabled(! $hasCartLines)
                                data-toggle="modal" data-target="#add-discount">
                            <i class="fi fi-rr-pencil d-flex"></i>
                            <span class="text-underline">
                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $cartItems['extraDiscount']), currencyCode: getCurrencyCode()) }}
                            </span>
                        </button>
                    </dd>
                </div>

                @if($systemTaxConfig['SystemTaxVat']['is_active'] && !$systemTaxConfig['is_included'])
                    <div class="pos-sum__row">
                        <dt>{{ translate('VAT/TAX') }}</dt>
                        <dd>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: round($cartItems['totalTax'] ?? 0, 2)), currencyCode: getCurrencyCode()) }}</dd>
                    </div>
                @endif

                <div class="pos-sum__total">
                    <dt>{{ translate('Total') }}</dt>
                    <dd>{{ setCurrencySymbol(amount: $payableAmount, currencyCode: getCurrencyCode()) }}</dd>
                </div>
            </dl>
        </div>

        <input type="hidden" class="form-control total-amount" name="amount" min="0" step="0.01"
               value="{{ $payableAmount }}" readonly>

        <div class="pos-pay">
            <span class="pos-pay__label">{{ translate('Paid By') }}</span>
            <ul class="option-buttons">
                <li>
                    <input type="radio" class="paid-by-cash" id="cash" value="cash" name="type" hidden checked>
                    <label for="cash">{{ translate('Cash') }}</label>
                </li>
                <li>
                    <input type="radio" value="card" id="card" name="type" hidden>
                    <label for="card">{{ translate('Card') }}</label>
                </li>
                @if ($walletStatus)
                    <li class="{{ (str_contains(session('current_user'), 'walk-in-customer')) ? 'd-none' : '' }}">
                        <input type="radio" value="wallet" id="wallet" name="type" hidden>
                        <label for="wallet">{{ translate('Wallet') }}</label>
                    </li>
                @endif
            </ul>

            <div class="cash-change-amount cash-change-section">
                <div class="pos-pay__row">
                    <span class="pos-pay__key">{{ translate('Paid Amount') }}</span>
                    <div class="pos-pay__val">
                        <div class="pos-amount-field {{ $currencySymbolPosition === 'right' ? 'pos-amount-field--suffix' : '' }}">
                            <span class="pos-amount-field__affix">{{ getCurrencySymbol() }}</span>
                            <input type="number" class="pos-paid-amount-element remove-spin pos-num"
                                   placeholder="{{ translate('Ex') }}: 1000"
                                   value="{{ $payableAmount }}"
                                   name="paid_amount" {{ $hasCartLines ? '' : 'disabled' }}
                                   min="{{ $payableAmount }}"
                                   data-currency-position="{{ getWebConfig('currency_symbol_position') }}"
                                   data-currency-symbol="{{ getCurrencySymbol() }}">
                        </div>
                    </div>
                </div>
                <div class="pos-pay__row">
                    <span class="pos-pay__key">{{ translate('Change Amount') }}</span>
                    <span class="pos-pay__val pos-change-value pos-change-amount-element pos-num">{{ setCurrencySymbol(amount: 0) }}</span>
                </div>
            </div>

            <div class="cash-change-card cash-change-section d-none">
                <div class="pos-pay__row">
                    <span class="pos-pay__key">{{ translate('Paid Amount') }}</span>
                    <div class="pos-pay__val">
                        <div class="pos-amount-field {{ $currencySymbolPosition === 'right' ? 'pos-amount-field--suffix' : '' }}">
                            <span class="pos-amount-field__affix">{{ getCurrencySymbol() }}</span>
                            <input type="number" class="pos-num" placeholder="{{ translate('Ex') }}: 1000"
                                   value="{{ $payableAmount }}" disabled>
                        </div>
                    </div>
                </div>
                <div class="pos-pay__row">
                    <span class="pos-pay__key">{{ translate('Change Amount') }}</span>
                    <span class="pos-pay__val pos-change-value pos-num">{{ setCurrencySymbol(amount: 0) }}</span>
                </div>
            </div>

            <div class="cash-change-wallet cash-change-section d-none">
                <div class="pos-pay__row">
                    <span class="pos-pay__key">
                        {{ translate('Paid Amount') }}
                        <span class="badge badge-soft-danger" id="message-insufficient-balance"
                              data-text="{{ translate('Insufficient balance') }}"></span>
                    </span>
                    <div class="pos-pay__val">
                        <div class="pos-amount-field {{ $currencySymbolPosition === 'right' ? 'pos-amount-field--suffix' : '' }}">
                            <span class="pos-amount-field__affix">{{ getCurrencySymbol() }}</span>
                            <input type="number" class="wallet-balance-input pos-num"
                                   placeholder="{{ translate('Ex') }}: 1000"
                                   value="{{ $payableAmount }}" disabled>
                        </div>
                    </div>
                </div>
                <div class="pos-pay__row">
                    <span class="pos-pay__key">{{ translate('Change Amount') }}</span>
                    <span class="pos-pay__val pos-change-value pos-num">{{ setCurrencySymbol(amount: 0) }}</span>
                </div>
            </div>
        </div>

        <div class="pos-actions">
            @if($hasCartLines)
                <button type="button" class="pos-action pos-action--ghost pos-clear-cart-btn"
                        data-toggle="modal" data-target="#clearCartModal">
                    {{ translate('Clear Cart') }}
                </button>
                <button type="button" class="pos-action pos-action--hold pos-hold-btn"
                        data-toggle="modal" data-target="#holdOrderModal">
                    <i class="fi fi-rr-pause d-flex fs-12"></i>
                    {{ translate('Hold') }}
                </button>
                <div class="place-order-wrapper">
                    <button id="submit_order" type="button" class="pos-action pos-action--primary action-form-submit"
                            data-message="{{ translate('Want to place this order').'?' }}"
                            data-toggle="modal" data-target="#paymentModal">
                        <i class="fi fi-sr-shopping-bag d-flex fs-13"></i>
                        {{ translate('Place Order') }}
                    </button>
                </div>
            @else
                @php($emptyCartTooltip = translate('No product added to the cart'))
                <div title="{{ $emptyCartTooltip }}" data-toggle="tooltip">
                    <button type="button" class="pos-action pos-action--ghost action-clear-cart pos-clear-cart-btn" disabled>
                        {{ translate('Clear Cart') }}
                    </button>
                </div>
                <div title="{{ translate('No product added to the cart to hold the order') }}" data-toggle="tooltip">
                    <button type="button" class="pos-action pos-action--hold action-hold pos-hold-btn" disabled>
                        <i class="fi fi-rr-pause d-flex fs-12"></i>
                        {{ translate('Hold') }}
                    </button>
                </div>
                <div title="{{ $emptyCartTooltip }}" data-toggle="tooltip" class="place-order-wrapper">
                    <button id="submit_order" type="button"
                            class="pos-action pos-action--primary action-form-submit" disabled>
                        <i class="fi fi-sr-shopping-bag d-flex fs-13"></i>
                        {{ translate('Place Order') }}
                    </button>
                </div>
            @endif
        </div>
    </div>
</form>
@include('vendor-views.pos.partials.modals._clear-cart-modal')
@include('vendor-views.pos.partials.modals._hold-order-modal')

@push('script_2')
<script>
    'use strict';
    $(document).on('change', '#type_ext_dis', function (){
        let type = $(this).val();
        if(type === 'amount'){
            $('#dis_amount').attr('placeholder', 'Ex: 500');
        }else if(type === 'percent'){
            $('#dis_amount').attr('placeholder', 'Ex: 10%');
        }
    });
</script>
@endpush
