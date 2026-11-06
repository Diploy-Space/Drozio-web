@php
    use App\Utils\CartManager;
    $isCustomerLoggedIn = auth('customer')->check();
@endphp
<div class="container">
    <ul class="app-bar__list list-unstyled d-flex align-items-center justify-content-between mb-0">
        <li>
            <a href="{{ route('home') }}"
               class="app-bar__link {{ (Request::is('/') || Request::is('home')) ? 'active' : '' }}"
               aria-label="{{ translate('Home') }}">
                @include('theme-views.partials.icons._app-bar-home')
            </a>
        </li>
        <li>
            @if($isCustomerLoggedIn)
                <a href="{{ route('wishlists.index') }}"
                   class="app-bar__link {{ Request::is('customer/wishlist') ? 'active' : '' }}"
                   aria-label="{{ translate('Wishlist') }}">
                    <span class="position-relative">
                        @include('theme-views.partials.icons._app-bar-wishlist')
                        <span class="count wishlist_count_status">{{ $headerWishlistCount ?? 0 }}</span>
                    </span>
                </a>
            @else
                <a href="javascript:" class="app-bar__link" data-bs-toggle="modal" data-bs-target="#loginModal"
                   aria-label="{{ translate('Wishlist') }}">
                    @include('theme-views.partials.icons._app-bar-wishlist')
                </a>
            @endif
        </li>
        <li>
            <div class="dropup position-static">
                <a href="javascript:"
                   class="app-bar__link {{ Request::is('shop-cart') ? 'active' : '' }}"
                   data-toggle="collapse" data-target="cart_dropdown"
                   aria-label="{{ translate('Shopping cart') }}">
                    <span class="position-relative">
                        @include('theme-views.partials.icons._app-bar-cart')
                        @if($usesModuleCartPanel ?? storefrontUsesModuleHeaderPanel())
                            <span class="count">{{ $headerModuleCartCount ?? 0 }}</span>
                        @else
                            @php($cart_mobile=CartManager::getCartListQuery())
                            <span class="count">{{ $cart_mobile->count() }}</span>
                        @endif
                    </span>
                </a>

                <ul class="dropdown-menu scrollY-60 bs-dropdown-min-width--100 shadow-sm cart-dropdown-mobile-view"
                    id="cart_dropdown">
                    @if($usesModuleCartPanel ?? storefrontUsesModuleHeaderPanel())
                        <li class="p-3">
                            @include('theme-views.layouts.partials._module-cart-panel')
                        </li>
                    @elseif($cart_mobile->count() > 0)
                        @php($sub_total=0)
                        @php($total_tax=0)
                        @foreach($cart_mobile as $cartItem)
                            @php($product=$cartItem->product)
                            <li>
                                <div class="media gap-3">
                                    <div class="avatar avatar-xxl overflow-hidden position-relative rounded">
                                        <img loading="lazy" alt="Product"
                                            src="{{ getStorageImages(path: $cartItem?->product?->thumbnail_full_url, type: 'product') }}"
                                            class="img-fit dark-support rounded img-fit overflow-hidden {{ $product && $product->status == 0?'blur-section':'' }}"/>

                                        @if ($product && $product->status == 0)
                                            <span
                                                class="temporary-closed position-absolute text-center p-2 text-capitalize">
                                        <span>{{ translate('Not available') }}</span>
                                    </span>
                                        @endif
                                    </div>
                                    <div class="media-body">
                                        <h6 class="mb-1 {{ $product && $product->status == 0?'blur-section':'' }}">
                                            <a class="line-clamp-2" href="{{ $product && $product->status == 1? route('product',$cartItem['slug']) :'javascript:'}}">{{Str::limit($cartItem['name'],30)}}</a>
                                        </h6>
                                        @if(!empty($cartItem['variant']))
                                            <div>
                                                <span class="fs-12">{{translate('Variant')}} : {{ $cartItem['variant'] }}</span>
                                            </div>
                                        @endif
                                        <div class="d-flex gap-3 justify-content-between align-items-end">
                                            <div
                                                class="d-flex flex-column gap-1 {{ $product && $product->status == 0?'blur-section':'' }}">
                                                <div class="fs-12"><span
                                                        class="cart_quantity_{{ $cartItem['id'] }}">{{$cartItem['quantity']}}</span>
                                                     {{'×'.webCurrencyConverter(($cartItem['price']-$cartItem['discount']))}}
                                                </div>
                                                <div class="product__price d-flex flex-wrap gap-2">
                                                    <del
                                                        class="product__old-price quantity_price_of_{{ $cartItem['id'] }}">{{webCurrencyConverter($cartItem['price']*$cartItem['quantity'])}}</del>
                                                    <ins
                                                        class="product__new-price discount_price_of_{{ $cartItem['id'] }}">{{webCurrencyConverter(($cartItem['price']-$cartItem['discount'])*(int)$cartItem['quantity'])}}</ins>
                                                </div>
                                            </div>

                                            <div class="quantity">
                                                @if ($product && $product->status == 1)
                                                    <?php
                                                        $getProductCurrentStock = $product->current_stock;
                                                        if(!empty($product->retailDetail?->variation)) {
                                                            foreach(json_decode($product->retailDetail?->variation ?? '[]', true) as $productVariantSingle) {
                                                                if($productVariantSingle['type'] == $cartItem->variant) {
                                                                    $getProductCurrentStock = $productVariantSingle['qty'];
                                                                }
                                                            }
                                                        }
                                                    ?>

                                                    <span
                                                        class="quantity__minus cart-quantity-update cart_quantity__minus{{ $cartItem['id'] }}"
                                                        data-cart-id="{{ $cartItem['id'] }}"
                                                        data-product-id="{{ $cartItem['product_id'] }}"
                                                        data-value=-1
                                                        data-event="minus"
                                                        data-prevent="true">

                                                        @if($getProductCurrentStock < $cartItem['quantity'] || $cartItem['quantity'] == ($product ? $product->minimum_order_qty : 1))
                                                            <i class="bi bi-trash3-fill text-danger fs-10"></i>
                                                        @else
                                                            <i class="bi bi-dash"></i>
                                                        @endif

                                                    </span>
                                                    <input type="text"
                                                           class="quantity__qty cart-quantity-update-input cart_quantity_of_{{ $cartItem['id'] }}"
                                                           data-min="{{ $product?->minimum_order_qty ?? 1 }}"
                                                           value="{{$cartItem['quantity']}}"
                                                           data-cart-id="{{ $cartItem['id'] }}"
                                                           data-product-id="{{ $cartItem['product_id'] }}"
                                                           data-current-stock="{{ $getProductCurrentStock }}"
                                                           data-value=0
                                                           data-prevent="true">
                                                    <span class="quantity__plus cart-quantity-update"
                                                          data-cart-id="{{ $cartItem['id'] }}"
                                                          data-product-id="{{ $cartItem['product_id'] }}"
                                                          data-value=1
                                                          data-event="plus"
                                                          data-prevent="true">
                                                        <i class="bi bi-plus"></i>
                                                    </span>
                                                @else
                                                    <span
                                                        class="quantity__minus cart-quantity-update cart_quantity__minus{{ $cartItem['id'] }}"
                                                        data-cart-id="{{ $cartItem['id'] }}"
                                                        data-product-id="{{ $cartItem['product_id'] }}"
                                                        data-value="-{{$cartItem['quantity']}}"
                                                        data-event="minus" data-prevent="true">
                                                        <i class="bi bi-trash3-fill text-danger fs-10"></i>
                                                    </span>
                                                    <input type="text"
                                                           class="quantity__qty cart-quantity-update-input cart_quantity_of_{{ $cartItem['id'] }}"
                                                           data-min="{{ $product?->minimum_order_qty ?? 1 }}" value="0"
                                                           data-cart-id="{{ $cartItem['id'] }}"
                                                           data-product-id="{{ $cartItem['product_id'] }}"
                                                           data-value=0
                                                           data-prevent="true">
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            @php($sub_total+=($cartItem['price']-$cartItem['discount'])*(int)$cartItem['quantity'])
                            @php($total_tax+=$cartItem['tax']*(int)$cartItem['quantity'])
                        @endforeach

                        <li>
                            <div class="flex-between-gap-3 pt-2 pb-4">
                                <h6>{{translate('Total')}}</h6>
                                <h3 class="text-primary cart_total_amount">{{webCurrencyConverter($sub_total)}}</h3>
                            </div>
                            <div class="d-flex gap-3">
                                @if($web_config['guest_checkout_status'] || auth('customer')->check())
                                    <a href="{{route('shop-cart')}}"
                                       class="btn btn-outline-primary btn-block">{{translate('View cart')}}</a>
                                    <a href="{{route('checkout-details')}}"
                                       class="btn btn-primary btn-block">{{translate('Go to checkout')}}</a>
                                @else
                                    <button class="btn btn-outline-primary btn-block" data-bs-toggle="modal"
                                            data-bs-target="#loginModal">{{translate('View cart')}}</button>
                                    <button class="btn btn-primary btn-block" data-bs-toggle="modal"
                                            data-bs-target="#loginModal">{{translate('Go to checkout')}}</button>
                                @endif
                            </div>
                        </li>
                    @else
                        <div class="widget-cart-item">
                            <div class="p-2 pt-0">
                                <div class="d-flex justify-content-end w-100">
                                    <button type="button" class="btn-link text-dark opacity-25 fs-18 close-cart-btn">
                                        <i class="bi bi-x-circle-fill"></i>
                                    </button>
                                </div>
                                <div class="d-flex flex-column align-items-center text-center">
                                    <img width="60" class="svg mb-3" src="{{ theme_asset('assets/img/empty-state/empty-cart-new.svg') }}" alt="">
                                    <h5 class="text-center mb-2">
                                        {{ translate('Your cart is empty.') }}
                                    </h5>
                                    <p class="fs-12 mb-30">{{ translate('Explore quality products, exclusive deals and fast delivery, tailored for you.') }}</p>
                                    <button type="button" class="btn btn-primary fw-semibold text-capitalize mb-2 close-cart-btn">{{ translate('Ok, got it') }}</button>
                                </div>
                            </div>
                        </div>
                    @endif
                </ul>
            </div>
        </li>
        <li>
            @if($isCustomerLoggedIn)
                <a href="{{ route('product-compare.index') }}"
                   class="app-bar__link {{ Request::is('compare-list') ? 'active' : '' }}"
                   aria-label="{{ translate('Compare') }}">
                    <span class="position-relative">
                        @include('theme-views.partials.icons._app-bar-compare')
                        <span class="count compare_list_count_status">{{ session()->has('compare_list') ? count(session('compare_list')) : 0 }}</span>
                    </span>
                </a>
            @else
                <a href="javascript:" class="app-bar__link" data-bs-toggle="modal" data-bs-target="#loginModal"
                   aria-label="{{ translate('Compare') }}">
                    @include('theme-views.partials.icons._app-bar-compare')
                </a>
            @endif
        </li>
    </ul>
</div>
