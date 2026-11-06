@php($shippingMethod = getWebConfig(name: 'shipping_method'))
@php($cart = \App\Utils\CartManager::getCartListGroupQuery())
@php($adminShipping = $cartAdminShipping)
@php($cartItemCount = $cart->sum(fn ($group) => $group->count()))

<div class="sc-head">
    <div>
        <h1 class="sc-head__title">{{ translate('Shopping cart') }}</h1>
        @if($cartItemCount > 0)
            <div class="sc-head__count">
                <span>{{ $cartItemCount }} {{ $cartItemCount === 1 ? translate('Item') : translate('Items') }}</span>
                @if($cart->count() > 1)
                    <span>{{ $cart->count() }} {{ translate('Shops') }}</span>
                @endif
            </div>
        @endif
    </div>
    <a href="{{ route('home') }}" class="sc-head__back">
        <i class="tio-back-ui"></i> {{ translate('Continue Shopping') }}
    </a>
</div>

<div class="row g-3">
    <section class="col-lg-8">
        @if($cartItemCount > 0)
            <div class="sc-cols">
                <label class="sc-cols__all">
                    <input type="checkbox" class="sc-check" id="sc-select-all">
                    <span>{{ translate('Select all') }}</span>
                </label>
                <span>{{ translate('Unit Price') }}</span>
                <span>{{ translate('Quantity') }}</span>
                <span class="text-end">{{ translate('Total') }}</span>
            </div>
        @endif

        @foreach($cart as $groupKey => $group)
            @php($isPhysicalProductExist = $group->contains(fn ($row) => $row->product_type == 'physical' && $row->is_checked))
            @php($nonOrderWiseShippingCost = $group->where('product_type', 'physical')->where('is_checked', 1)->where('shipping_type', '!=', 'order_wise')->sum('shipping_cost'))
            @php($firstItem = $group->first())

            @if($shippingMethod == 'inhouse_shipping' || $firstItem->seller_is == 'admin')
                @php($shippingType = $adminShipping->shipping_type ?? 'order_wise')
            @else
                @php($shippingType = ($cartSellerShippingTypes[$firstItem->seller_id] ?? null)?->shipping_type ?? 'order_wise')
            @endif

            @php($shop = $firstItem->seller_is == 'admin' ? null : ($cartShops[$firstItem['seller_id']] ?? null))
            @php($chosenShipping = $cartChosenShippings[$firstItem['cart_group_id']] ?? null)
            @php($shippings = $cartShippingMethods[$firstItem['seller_id'].'|'.$firstItem['seller_is']] ?? collect())
            @php($sellerWiseChoiceNeeded = $isPhysicalProductExist && $shippingMethod == 'sellerwise_shipping' && $shippingType == 'order_wise')
            @php($minimumOrder = \App\Utils\OrderManager::verifyCartListMinimumOrderAmount($request, $groupKey))
            @php($minimumOrderShortfall = $minimumOrder['minimum_order_amount'] - $minimumOrder['amount'])

            <article class="sc-shop cart_information">
                <header class="sc-shop__head">
                    <div class="sc-shop__brand">
                        @if($firstItem->seller_is == 'admin' || $shop)
                            <input type="checkbox" class="sc-check shop-head-check shop-head-check-desktop"
                                   aria-label="{{ translate('Select every item from this shop') }}">
                            <a class="sc-shop__name"
                               href="{{ route('vendor-shop', ['slug' => $firstItem->seller_is == 'admin' ? getInHouseShopConfig(key: 'slug') : $shop->slug]) }}">
                                <img class="svg" src="{{ theme_asset(path: 'public/assets/front-end/img/cart-store-new.svg') }}" alt="">
                                <span>{{ $firstItem->seller_is == 'admin' ? getInHouseShopConfig(key: 'name') : $shop->name }}</span>
                                @include('web-views.partials._verified-badge', ['isVerified' => $firstItem->hasVerifiedShopBadge()])
                            </a>
                        @else
                            <span class="sc-shop__name sc-shop__name--off">
                                <img class="svg" src="{{ theme_asset(path: 'public/assets/front-end/img/cart-store-new.svg') }}" alt="">
                                <span>{{ translate('Vendor not available') }}</span>
                            </span>
                        @endif
                    </div>

                    @if($sellerWiseChoiceNeeded && count($shippings) > 0)
                        <div class="sc-ship {{ $chosenShipping ? '' : 'sc-ship--pending' }}">
                            <label class="sc-ship__label" for="shipping-{{ $groupKey }}">{{ translate('Shipping Method') }}</label>
                            <select class="form-control sc-ship__select action-set-shipping-id"
                                    id="shipping-{{ $groupKey }}"
                                    data-product-id="{{ $firstItem['cart_group_id'] }}">
                                <option value="">{{ translate('Choose shipping method') }}</option>
                                @foreach($shippings as $shipping)
                                    <option value="{{ $shipping['id'] }}" @selected($chosenShipping?->shipping_method_id == $shipping['id'])>
                                        {{ ucfirst($shipping['title']).' ('.$shipping['duration'].') '.webCurrencyConverter(amount: $shipping['cost']) }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="sc-ship__hint">{{ translate('Required to continue') }}</span>
                        </div>
                    @elseif($isPhysicalProductExist && $shippingType != 'order_wise')
                        <div class="sc-ship">
                            <span class="sc-ship__label">{{ translate('Total shipping cost') }}</span>
                            <span class="sc-ship__value">{{ webCurrencyConverter(amount: $nonOrderWiseShippingCost) }}</span>
                        </div>
                    @elseif($isPhysicalProductExist && $chosenShipping)
                        <div class="sc-ship">
                            <span class="sc-ship__label">{{ translate('Total shipping cost') }}</span>
                            <span class="sc-ship__value">{{ webCurrencyConverter(amount: $chosenShipping->shipping_cost) }}</span>
                        </div>
                    @endif
                </header>

                @if($sellerWiseChoiceNeeded && count($shippings) == 0)
                    <p class="sc-note sc-note--danger mb-0">
                        <i class="czi-security-announcement"></i>
                        <span>{{ translate('No shipping options available at this shop') }}. {{ translate('Please remove all items from this shop') }}.</span>
                    </p>
                @endif

                @if($minimumOrderShortfall > 0)
                    <p class="sc-note sc-note--attention mb-0">
                        <i class="czi-security-announcement"></i>
                        <span>{!! translate('The minimum order amount for :shop is :amount', [
                            'shop' => e($firstItem->seller_is == 'admin' ? getInHouseShopConfig(key: 'name') : $shop?->name),
                            'amount' => '<strong>'.e(webCurrencyConverter(amount: $minimumOrder['minimum_order_amount'])).'</strong>',
                        ]) !!}</span>
                    </p>
                @endif

                <ul class="sc-items">
                    @foreach($group as $cartItem)
                        @php($product = $cartItem->allProducts)
                        @php($currentStock = \App\Utils\CartManager::getCartItemCurrentStock($cartItem))
                        @php($minimumOrderQty = $product?->minimum_order_qty ?? 1)
                        @php($isPurchasable = \App\Utils\CartManager::isCartItemPurchasable($cartItem))
                        @php($isOutOfStock = $cartItem->product_type == 'physical' && $currentStock < $cartItem['quantity'])

                        <li class="sc-item {{ $isPurchasable ? '' : 'sc-item--off' }}">
                            <label class="sc-item__pick">
                                <input type="checkbox" class="sc-check shop-item-check shop-item-check-desktop"
                                       value="{{ $cartItem['id'] }}" @checked($cartItem['is_checked'])
                                       aria-label="{{ $cartItem['name'] }}">
                            </label>

                            <a class="sc-item__thumb" href="{{ $isPurchasable ? route('product', $cartItem['slug']) : 'javascript:' }}">
                                <img src="{{ getStorageImages(path: $cartItem?->product?->thumbnail_full_url, type: 'product') }}"
                                     alt="{{ translate('Product') }}">
                                @unless($isPurchasable)
                                    <span class="sc-item__flag">{{ translate('N/A') }}</span>
                                @endunless
                            </a>

                            <div class="sc-item__info">
                                <a class="sc-item__name" href="{{ $isPurchasable ? route('product', $cartItem['slug']) : 'javascript:' }}">
                                    {{ $cartItem['name'] }}
                                </a>
                                @if(!empty($cartItem['variant']))
                                    <span class="sc-item__variant">{{ translate('Variant') }} : {{ $cartItem['variant'] }}</span>
                                @endif
                                @if($cartItem->product_type == 'physical' && $shippingType != 'order_wise')
                                    <span class="sc-item__variant">
                                        {{ translate('Shipping Cost') }} : {{ webCurrencyConverter(amount: $cartItem['shipping_cost']) }}
                                    </span>
                                @endif
                                @if($isOutOfStock)
                                    <span class="sc-item__alert">
                                        <i class="czi-security-announcement"></i> {{ translate('Out of Stock') }}
                                    </span>
                                @elseif(!$isPurchasable)
                                    <span class="sc-item__alert">
                                        <i class="czi-security-announcement"></i> {{ translate('Product not available right now') }}
                                    </span>
                                @endif
                            </div>

                            <div class="sc-item__meta">
                                <div class="sc-item__price">
                                    <span class="sc-item__label">{{ translate('Unit Price') }}</span>
                                    <span>{{ webCurrencyConverter(amount: $cartItem['price'] - $cartItem['discount']) }}</span>
                                </div>

                                <div class="sc-item__qty">
                                    <div class="sc-qty qty">
                                        <button type="button"
                                                class="sc-qty__btn qty_minus action-update-cart-quantity-list"
                                                aria-label="{{ translate('Decrease quantity') }}"
                                                data-minimum-order="{{ $minimumOrderQty }}"
                                                data-cart-id="{{ $cartItem['id'] }}"
                                                data-increment="-1"
                                                data-event="minus"
                                                @disabled(!$isPurchasable || $cartItem['quantity'] <= $minimumOrderQty)>
                                            <i class="tio-remove"></i>
                                        </button>
                                        <input type="text" inputmode="numeric"
                                               class="sc-qty__input qty_input cartQuantity{{ $cartItem['id'] }} action-change-update-cart-quantity-list"
                                               value="{{ $cartItem['quantity'] }}"
                                               name="quantity[{{ $cartItem['id'] }}]"
                                               id="cart_quantity_web{{ $cartItem['id'] }}"
                                               aria-label="{{ translate('Quantity') }}"
                                               data-current-stock="{{ $currentStock }}"
                                               data-minimum-order="{{ $minimumOrderQty }}"
                                               data-cart-id="{{ $cartItem['id'] }}"
                                               data-increment="0"
                                               data-min="{{ $minimumOrderQty }}"
                                               @disabled(!$isPurchasable)
                                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                        <button type="button"
                                                class="sc-qty__btn qty_plus action-update-cart-quantity-list"
                                                aria-label="{{ translate('Increase quantity') }}"
                                                data-minimum-order="{{ $minimumOrderQty }}"
                                                data-cart-id="{{ $cartItem['id'] }}"
                                                data-increment="1"
                                                @disabled(!$isPurchasable)>
                                            <i class="tio-add"></i>
                                        </button>
                                    </div>
                                    <div class="sc-qty-hint" data-minimum-order-message="{{ translate('Minimum order quantity is :quantity', ['quantity' => $minimumOrderQty]) }}"></div>
                                </div>

                                <div class="sc-item__total">
                                    <span class="sc-item__label">{{ translate('Total') }}</span>
                                    <span>{{ webCurrencyConverter(amount: ($cartItem['price'] - $cartItem['discount']) * $cartItem['quantity']) }}</span>
                                </div>
                            </div>

                            <button type="button" class="sc-item__remove action-remove-cart-item"
                                    data-cart-id="{{ $cartItem['id'] }}"
                                    data-toggle="tooltip" data-placement="top"
                                    title="{{ translate('Remove') }}" aria-label="{{ translate('Remove') }}">
                                <i class="tio-delete-outlined"></i>
                            </button>
                        </li>
                    @endforeach
                </ul>

                @php($freeDelivery = \App\Utils\OrderManager::getFreeDeliveryOrderAmountArray($firstItem->cart_group_id))
                @if($freeDelivery['status'] && (session()->missing('coupon_type') || session('coupon_type') != 'free_delivery'))
                    <div class="sc-free">
                        <div class="sc-free__text">
                            <img class="svg" src="{{ theme_asset(path: 'public/assets/front-end/img/icons/free-shipping-dynamic.svg') }}" alt="">
                            @if($freeDelivery['amount_need'] <= 0)
                                <span class="sc-free__earned">{{ translate('You Get Free Delivery Bonus') }}</span>
                            @else
                                <span>{!! translate('Add :amount more for free delivery', [
                                    'amount' => '<span class="sc-free__amount need-for-free-delivery">'.e(webCurrencyConverter(amount: $freeDelivery['amount_need'])).'</span>',
                                ]) !!}</span>
                            @endif
                        </div>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar"
                                 style="width: {{ $freeDelivery['percentage'] }}%"
                                 aria-valuenow="{{ $freeDelivery['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                @endif
            </article>
        @endforeach

        @if($shippingMethod == 'inhouse_shipping')
            @php($anyPhysicalProductExist = $cart->contains(fn ($group) => $group->contains(fn ($row) => $row->product_type == 'physical' && $row->is_checked)))
            @php($inhouseShippingType = $adminShipping->shipping_type ?? 'order_wise')

            @if($anyPhysicalProductExist && $inhouseShippingType == 'order_wise')
                @php($inhouseShippings = $cartShippingMethods['1|admin'] ?? collect())
                @php($chosenShippingIds = $cart->keys()->map(fn ($groupId) => ($cartChosenShippings[$groupId] ?? null)?->shipping_method_id))
                @php($inhouseChosenShippingId = $chosenShippingIds->filter()->unique()->count() === 1 && !$chosenShippingIds->contains(null) ? $chosenShippingIds->first() : null)

                <div class="sc-panel">
                    <span class="sc-panel__title">{{ translate('Shipping Method') }}</span>
                    <div class="sc-ship {{ $inhouseChosenShippingId ? '' : 'sc-ship--pending' }}">
                        <select class="form-control sc-ship__select action-set-shipping-id" data-product-id="all_cart_group"
                                aria-label="{{ translate('Shipping Method') }}">
                            <option value="">{{ translate('Choose shipping method') }}</option>
                            @foreach($inhouseShippings as $shipping)
                                <option value="{{ $shipping['id'] }}" @selected($inhouseChosenShippingId == $shipping['id'])>
                                    {{ ucfirst($shipping['title']).' ('.$shipping['duration'].') '.webCurrencyConverter(amount: $shipping['cost']) }}
                                </option>
                            @endforeach
                        </select>
                        <span class="sc-ship__hint">{{ translate('Required to continue') }}</span>
                    </div>
                    <span class="sc-panel__hint">{{ translate('This delivery option applies to every shop in your cart') }}.</span>
                </div>
            @endif
        @endif

        @if($cartItemCount == 0)
            <div class="sc-empty">
                <img src="{{ theme_asset(path: 'public/assets/front-end/img/icons/empty-cart.svg') }}" alt="">
                <div class="sc-empty__title">{{ translate('Your cart is empty.') }}</div>
                <p class="sc-empty__text">{{ translate('Browse the store and add the items you want to buy') }}.</p>
                <a href="{{ route('home') }}" class="btn btn--primary">{{ translate('Continue Shopping') }}</a>
            </div>
        @endif

        @if($cartItemCount > 0)
            <div class="mt-3">
                <label class="sc-note-label" for="order_note">
                    {{ translate('Order Note') }} <span>({{ translate('Optional') }})</span>
                </label>
                <textarea class="sc-note-field" id="order_note" name="order_note"
                          placeholder="{{ translate('Anything the seller should know about this order') }}">{{ session('order_note') }}</textarea>
            </div>
        @endif
    </section>

    @include('web-views.partials._order-summary')

    <span id="route-customer-set-shipping-method" data-url="{{ route('customer.set-shipping-method') }}"></span>
    <span id="route-action-checkout-function" data-route="shop-cart"></span>
</div>

@push('script')
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/cart-details.js') }}" defer></script>
@endpush
