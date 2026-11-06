@php($cart=\App\Utils\CartManager::getCartListQuery())
@php($cartUrl = ($web_config['guest_checkout_status'] || auth('customer')->check()) ? route('shop-cart') : route('customer.auth.login'))

<div class="dropdown">
    <a class="sh-tool" href="{{ $cartUrl }}" aria-label="{{ translate('Shopping cart') }}">
        <i class="fi fi-sr-shopping-cart"></i>
        <span class="sh-tool__count {{ $cart->count() > 0 ? '' : 'is-zero' }}">{{ $cart->count() }}</span>
    </a>

    <div class="dropdown-menu dropdown-menu-{{ session('direction') === "rtl" ? 'left' : 'right' }} hm-panel hm-panel--flush hm-cart cart-dropdown">
        <div class="hm-cart__head">
            <h6 class="hm-cart__title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M3.03986 2.29234C2.85209 2.22644 2.64582 2.23782 2.46644 2.324C2.28707 2.41017 2.14927 2.56407 2.08336 2.75184C2.01745 2.93962 2.02884 3.14588 2.11501 3.32526C2.20119 3.50464 2.35509 3.64244 2.54286 3.70834L2.80386 3.79934C3.47186 4.03434 3.91086 4.18934 4.23386 4.34834C4.53686 4.49734 4.66986 4.61834 4.75786 4.74634C4.84786 4.87834 4.91786 5.06034 4.95786 5.42334C4.99786 5.80334 4.99986 6.29834 4.99986 7.03834V9.64034C4.99986 12.5823 5.06286 13.5523 5.92986 14.4663C6.79586 15.3803 8.18986 15.3803 10.9799 15.3803H16.2819C17.8429 15.3803 18.6239 15.3803 19.1749 14.9303C19.7269 14.4803 19.8849 13.7163 20.1999 12.1883L20.6999 9.76334C21.0469 8.02334 21.2199 7.15434 20.7759 6.57734C20.3319 6.00034 18.8159 6.00034 17.1309 6.00034H6.49186C6.4876 5.75386 6.47326 5.50765 6.44886 5.26234C6.39486 4.76534 6.27886 4.31234 5.99686 3.90034C5.71286 3.48434 5.33486 3.21834 4.89386 3.00134C4.48186 2.79934 3.95786 2.61534 3.34186 2.39834L3.03986 2.29234ZM12.9999 8.25034C13.1988 8.25034 13.3895 8.32936 13.5302 8.47001C13.6708 8.61067 13.7499 8.80143 13.7499 9.00034V10.2503H14.9999C15.1988 10.2503 15.3895 10.3294 15.5302 10.47C15.6708 10.6107 15.7499 10.8014 15.7499 11.0003C15.7499 11.1993 15.6708 11.39 15.5302 11.5307C15.3895 11.6713 15.1988 11.7503 14.9999 11.7503H13.7499V13.0003C13.7499 13.1993 13.6708 13.39 13.5302 13.5307C13.3895 13.6713 13.1988 13.7503 12.9999 13.7503C12.8009 13.7503 12.6102 13.6713 12.4695 13.5307C12.3289 13.39 12.2499 13.1993 12.2499 13.0003V11.7503H10.9999C10.8009 11.7503 10.6102 11.6713 10.4695 11.5307C10.3289 11.39 10.2499 11.1993 10.2499 11.0003C10.2499 10.8014 10.3289 10.6107 10.4695 10.47C10.6102 10.3294 10.8009 10.2503 10.9999 10.2503H12.2499V9.00034C12.2499 8.80143 12.3289 8.61067 12.4695 8.47001C12.6102 8.32936 12.8009 8.25034 12.9999 8.25034Z"
                          fill="currentColor"/>
                    <path
                        d="M7.5 18C7.89782 18 8.27936 18.158 8.56066 18.4393C8.84196 18.7206 9 19.1022 9 19.5C9 19.8978 8.84196 20.2794 8.56066 20.5607C8.27936 20.842 7.89782 21 7.5 21C7.10218 21 6.72064 20.842 6.43934 20.5607C6.15804 20.2794 6 19.8978 6 19.5C6 19.1022 6.15804 18.7206 6.43934 18.4393C6.72064 18.158 7.10218 18 7.5 18ZM16.5 18C16.8978 18 17.2794 18.158 17.5607 18.4393C17.842 18.7206 18 19.1022 18 19.5C18 19.8978 17.842 20.2794 17.5607 20.5607C17.2794 20.842 16.8978 21 16.5 21C16.1022 21 15.7206 20.842 15.4393 20.5607C15.158 20.2794 15 19.8978 15 19.5C15 19.1022 15.158 18.7206 15.4393 18.4393C15.7206 18.158 16.1022 18 16.5 18Z"
                        fill="currentColor"/>
                </svg>
                <span class="text-capitalize">{{ translate('Shopping cart') }}</span>
                @if($cart->count() > 0)
                    <span class="hm-cart__badge">{{ $cart->count() }}</span>
                @endif
            </h6>
            <a href="#" class="hm-cart__close" id="cart_dropdown_close" aria-label="{{ translate('Close') }}">
                <i class="czi-close"></i>
            </a>
        </div>

        @if($cart->count() > 0)
            <?php
                $getShippingCostSavedForFreeDelivery=\App\Utils\CartManager::getShippingCostSavedForFreeDelivery();
                $totalDiscountOnProduct = 0;
                foreach ($cart as $cartItem) {
                    $totalDiscountOnProduct += $cartItem->discount * $cartItem->quantity;
                }

                $totalSavedAmount = $totalDiscountOnProduct;
                if(session()->has('coupon_discount') && session('coupon_discount') > 0 && session('coupon_type') !='free_delivery') {
                    $totalSavedAmount += session('coupon_discount');
                }
                if($getShippingCostSavedForFreeDelivery > 0) {
                    $totalSavedAmount += $getShippingCostSavedForFreeDelivery;
                }
            ?>

            <div class="hm-cart__body">
                <div class="hm-cart__saved dropdown-saved-amount {{$totalSavedAmount <= 0 ? 'd-none' : ''}}">
                    <img src="{{theme_asset(path: 'public/assets/front-end/img/party-popper.svg') }}" alt="">
                    <span>
                        {{ translate('You have Saved') }}
                        <span class="total_discount fw-semibold">{{ webCurrencyConverter(amount: $totalSavedAmount)}}</span>!
                    </span>
                </div>

                <div class="hm-cart__items" data-simplebar data-simplebar-auto-hide="false">
                    @php($sub_total=0)
                    @php($total_tax=0)
                    @foreach($cart as  $cartItem)
                        @php($product=$cartItem->allProducts)

                        <?php
                            $getProductCurrentStock = $product->current_stock;
                            if(!empty($product->retailDetail?->variation)) {
                                foreach(json_decode($product->retailDetail?->variation ?? '[]', true) as $productVariantSingle) {
                                    if($productVariantSingle['type'] == $cartItem->variant) {
                                        $getProductCurrentStock = $productVariantSingle['qty'];
                                    }
                                }
                            }
                            $isProductAvailable = isset($product->status) && $product->status == 1;
                        ?>

                        <div class="hm-cart__item">
                            <a class="hm-cart__thumb" href="{{route('product',$cartItem['slug'])}}">
                                <img class="{{ $isProductAvailable ? '' : 'blur-section' }}"
                                     src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'backend-product') }}"
                                     alt="{{ translate('Product') }}"/>
                                @if(!$isProductAvailable)
                                    <span class="hm-cart__flag">{{ translate('N/A') }}</span>
                                @endif
                            </a>

                            <div class="hm-cart__info">
                                <h6 class="hm-cart__name">
                                    <a href="{{route('product',$cartItem['slug'])}}" class="line--limit-1">
                                        {{$cartItem['name']}}
                                    </a>
                                </h6>
                                @if(!empty($cartItem['variant']))
                                    <span class="hm-cart__variant">{{ translate('Variant') }} : {{$cartItem['variant']}}</span>
                                @endif
                                <span class="hm-cart__price discount_price_of_{{$cartItem['id']}}">
                                    {{ webCurrencyConverter(amount: ($cartItem['price']-$cartItem['discount'])*$cartItem['quantity'])}}
                                </span>
                            </div>

                            @if($isProductAvailable)
                                <div class="__quantity">
                                    <div class="quantity__minus cart-qty-btn action-update-cart-quantity"
                                         data-cart-id="{{ $cartItem['id'] }}"
                                         data-product-id="{{ $cartItem['product_id'] }}"
                                         data-action="-1"
                                         data-event="minus">
                                        @if($getProductCurrentStock < $cartItem['quantity'] || $cartItem['quantity'] == (isset($product->minimum_order_qty) ? $product->minimum_order_qty : 1))
                                            <span class="fi fi-rr-trash text-danger"></span>
                                        @else
                                            <i class="tio-remove text-primary"></i>
                                        @endif
                                    </div>
                                    <input type="text"
                                           class="quantity__qty cart-qty-input form-control cartQuantity{{$cartItem['id']}} action-update-cart-quantity"
                                           value="{{$cartItem['quantity']}}" name="quantity"
                                           id="cartQuantity{{$cartItem['id']}}"
                                           data-cart-id="{{ $cartItem['id'] }}"
                                           data-product-id="{{ $cartItem['product_id'] }}"
                                           data-action="0"
                                           data-event=""
                                           data-current-stock="{{ $getProductCurrentStock }}"
                                           data-min="{{ isset($product->minimum_order_qty) ? $product->minimum_order_qty : 1 }}"
                                           autocomplete="off" required
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    <div class="quantity__plus cart-qty-btn action-update-cart-quantity"
                                         data-cart-id="{{ $cartItem['id'] }}"
                                         data-product-id="{{ $cartItem['product_id'] }}"
                                         data-action="1"
                                         data-event="">
                                        <i class="tio-add text-primary"></i>
                                    </div>
                                </div>
                            @else
                                <div class="__quantity">
                                    <div class="quantity__minus cart-qty-btn action-update-cart-quantity"
                                         data-cart-id="{{ $cartItem['id'] }}"
                                         data-product-id="{{ $cartItem['product_id'] }}"
                                         data-action="-1"
                                         data-event="minus">
                                        <span class="fi fi-rr-trash text-danger"></span>
                                    </div>
                                </div>
                            @endif
                        </div>
                        @php($sub_total+=($cartItem['price']-$cartItem['discount'])*$cartItem['quantity'])
                        @php($total_tax+=$cartItem['tax']*$cartItem['quantity'])
                    @endforeach
                </div>

                @php($free_delivery_status = \App\Utils\OrderManager::getFreeDeliveryOrderAmountArray($cart[0]->cart_group_id))
                @if ($free_delivery_status['status'] && (session()->missing('coupon_type') || session('coupon_type') !='free_delivery'))
                    <div class="hm-cart__delivery">
                        <div class="hm-cart__delivery-text">
                            <img src="{{theme_asset(path: 'public/assets/front-end/img/truck.svg') }}" alt="">
                            <span class="amount-fill-up {{$free_delivery_status['amount_need'] <= 0 ? '' :'d-none'}}">
                                {{ translate('You Get Free Delivery Bonus') }}
                            </span>
                            <span class="amount-need-to-fill-up {{$free_delivery_status['amount_need'] <= 0 ? 'd-none' :''}}">
                                {!! translate('Add :amount more for free delivery', [
                                    'amount' => '<span class="fw-semibold free_delivery_amount_need">'.e(webCurrencyConverter(amount: $free_delivery_status['amount_need'])).'</span>',
                                ]) !!}
                            </span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{$free_delivery_status['percentage']}}%;"></div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="hm-cart__foot">
                <div class="hm-cart__subtotal">
                    <span class="hm-cart__subtotal-label">{{ translate('Subtotal') }}</span>
                    <span class="hm-cart__subtotal-value cart_total_amount">
                        {{ webCurrencyConverter(amount: $sub_total) }}
                    </span>
                </div>
                <div class="hm-cart__actions">
                    <a class="btn btn-outline-secondary" href="{{route('shop-cart') }}">
                        {{ translate('Expand cart') }}
                        <i class="fi fi-rr-angle-small-right"></i>
                    </a>
                    <a class="btn btn--primary" href="{{route('checkout-details') }}">
                        {{ translate('Checkout') }}
                    </a>
                </div>
            </div>
        @else
            <div class="hm-cart__empty">
                <img src="{{theme_asset(path: 'public/assets/front-end/img/icons/empty-cart.svg') }}"
                     alt="{{ translate('Cart') }}" loading="eager">
                <p>{{ translate('Your cart is empty.') }}</p>
                <a class="btn btn--primary btn-sm px-4" href="{{ route('products') }}">
                    {{ translate('Start shopping') }}
                </a>
            </div>
        @endif
    </div>
</div>

@push('script')
    <script>
         $(document).ready(function() {
            $('#cart_dropdown_close').on('click', function(e) {
                e.preventDefault();
                $('.cart-dropdown').fadeOut(200).addClass('dismissed');
            });

            $('#cart_items').on('mouseenter', function() {
                $('.cart-dropdown').removeClass('dismissed').removeAttr('style');
            });
        });

        document.addEventListener('hexa-ai:cart-updated', function() {
            if (typeof updateNavCart === 'function') {
                updateNavCart();
            }
        });
    </script>
@endpush
