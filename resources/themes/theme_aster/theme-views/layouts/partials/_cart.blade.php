@php
    use App\Utils\CartManager;
@endphp
@php($cart = CartManager::getCartListQuery())
@php($cartCount = $cart->count())
<div class="dropdown module-cart">
    <a href="{{ route('shop-cart') }}" class="position-relative module-cart__toggle"
       aria-label="{{ translate('Shopping cart') }}">
        <i class="bi bi-bag fs-18"></i>
        <span class="count">{{ $cartCount }}</span>
    </a>
    <div class="dropdown-menu dropdown-menu-end module-cart__menu p-3">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
            <h6 class="d-flex align-items-center gap-2 mb-0 fs-16 fw-semibold">
                <i class="bi bi-cart-fill text-primary fs-18"></i>
                {{ translate('Shopping cart') }}
                @if($cartCount > 0)
                    <span class="badge bg-primary rounded-pill">{{ $cartCount }}</span>
                @endif
            </h6>
            <button type="button" class="border-0 bg-transparent p-0 text-muted d-flex align-items-center gap-2 fs-14"
                    data-module-cart-close>
                {{ translate('Close') }}
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        @if($cartCount > 0)
            @php($subTotal = 0)
            <div class="d-flex flex-column gap-3 scrollY-60">
                @foreach($cart as $cartItem)
                    @php($product = $cartItem->allProducts)
                    @php($isAvailable = $product && $product->status == 1)
                    @php($productUrl = $isAvailable ? route('product', $cartItem['slug']) : 'javascript:')
                    @php($currentStock = CartManager::getCartItemCurrentStock(cartItem: $cartItem))
                    @php($isAtMinimumQuantity = $cartItem['quantity'] <= ($product?->minimum_order_qty ?? 1) || $currentStock < $cartItem['quantity'])
                    <div class="d-flex align-items-start gap-2">
                        <a href="{{ $productUrl }}"
                           class="avatar avatar-xxl border rounded overflow-hidden flex-shrink-0 position-relative @unless($isAvailable) opacity-25 @endunless">
                            <img loading="lazy" class="img-fit dark-support" alt="{{ $cartItem['name'] }}"
                                 src="{{ getStorageImages(path: $cartItem?->product?->thumbnail_full_url, type: 'product') }}">
                        </a>

                        <div class="media-body d-flex flex-column gap-1 flex-grow-1 overflow-hidden">
                            <a href="{{ $productUrl }}"
                               class="fs-14 fw-semibold text-dark">{{ Str::limit($cartItem['name'], 30) }}</a>
                            @if(!empty($cartItem['variant']))
                                <span class="fs-12 text-muted">{{ translate('Variant') }} : <span
                                        class="text-dark">{{ $cartItem['variant'] }}</span></span>
                            @endif
                            @unless($isAvailable)
                                <span class="fs-12 text-danger">{{ translate('This product is no longer available') }}</span>
                            @endunless

                            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                <div class="product__price d-flex flex-wrap gap-2">
                                    @if($cartItem['discount'] > 0)
                                        <del class="product__old-price quantity_price_of_{{ $cartItem['id'] }}">{{ webCurrencyConverter($cartItem['price'] * $cartItem['quantity']) }}</del>
                                    @endif
                                    <ins class="product__new-price discount_price_of_{{ $cartItem['id'] }}">{{ webCurrencyConverter(($cartItem['price'] - $cartItem['discount']) * (int) $cartItem['quantity']) }}</ins>
                                </div>

                                <div class="quantity d-inline-flex align-items-center border rounded">
                                    <span class="quantity__minus cart-quantity-update cart_quantity__minus{{ $cartItem['id'] }}"
                                          data-cart-id="{{ $cartItem['id'] }}"
                                          data-product-id="{{ $cartItem['product_id'] }}"
                                          data-value="{{ $isAvailable ? -1 : -$cartItem['quantity'] }}"
                                          data-event="minus"
                                          data-prevent="true"
                                          aria-label="{{ $isAtMinimumQuantity || ! $isAvailable ? translate('Remove') : translate('Decrease quantity') }}">
                                        @if($isAtMinimumQuantity || ! $isAvailable)
                                            <i class="bi bi-trash3-fill text-danger fs-12"></i>
                                        @else
                                            <i class="bi bi-dash fs-14 text-primary"></i>
                                        @endif
                                    </span>
                                    <input type="text" inputmode="numeric"
                                           class="quantity__qty cart-quantity-update-input cart_quantity_of_{{ $cartItem['id'] }}"
                                           value="{{ $isAvailable ? $cartItem['quantity'] : 0 }}"
                                           aria-label="{{ translate('Quantity') }}"
                                           autocomplete="off"
                                           data-min="{{ $product?->minimum_order_qty ?? 1 }}"
                                           data-cart-id="{{ $cartItem['id'] }}"
                                           data-product-id="{{ $cartItem['product_id'] }}"
                                           data-value="0"
                                           data-current-stock="{{ $currentStock }}"
                                           data-prevent="true">
                                    @if($isAvailable)
                                        <span class="quantity__plus cart-quantity-update"
                                              data-cart-id="{{ $cartItem['id'] }}"
                                              data-product-id="{{ $cartItem['product_id'] }}"
                                              data-value="1"
                                              data-event="plus"
                                              data-prevent="true"
                                              aria-label="{{ translate('Increase quantity') }}">
                                            <i class="bi bi-plus fs-14 text-primary"></i>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @php($subTotal += ($cartItem['price'] - $cartItem['discount']) * (int) $cartItem['quantity'])
                @endforeach
            </div>

            <div class="flex-between-gap-3 pt-3 pb-3">
                <h6 class="mb-0">{{ translate('Total') }}</h6>
                <h5 class="text-primary mb-0 cart_total_amount">{{ webCurrencyConverter($subTotal) }}</h5>
            </div>
            <div class="d-flex gap-3">
                @if($web_config['guest_checkout_status'] || auth('customer')->check())
                    <a href="{{ route('shop-cart') }}"
                       class="btn btn-outline-primary btn-block">{{ translate('View cart') }}</a>
                    <a href="{{ route('checkout-details') }}"
                       class="btn btn-primary btn-block">{{ translate('Go to checkout') }}</a>
                @else
                    <button class="btn btn-outline-primary btn-block" data-bs-toggle="modal"
                            data-bs-target="#loginModal">{{ translate('View cart') }}</button>
                    <button class="btn btn-primary btn-block" data-bs-toggle="modal"
                            data-bs-target="#loginModal">{{ translate('Go to checkout') }}</button>
                @endif
            </div>
        @else
            <div class="d-flex flex-column align-items-center text-center py-2">
                <img width="80" class="img-fluid mb-3" alt="{{ translate('Empty cart') }}"
                     src="{{ theme_asset('assets/img/empty-state/empty-cart-new.svg') }}">
                <p class="fs-16 fw-semibold text-dark mb-2">{{ translate('Your cart is empty.') }}</p>
                <p class="fs-12 text-muted mb-3">{{ translate('Explore quality products, exclusive deals and fast delivery, tailored for you.') }}</p>
                <a href="{{ route('products', ['data_from' => 'latest']) }}"
                   class="btn btn-primary btn-sm px-4">{{ translate('Start shopping') }}</a>
            </div>
        @endif
    </div>
</div>

@push('script')
    <script>
        document.addEventListener('hexa-ai:cart-updated', function () {
            if (typeof updateNavCart === 'function') {
                updateNavCart();
            }
        });
    </script>
@endpush
