@php
    $overallRating = $product?->overall_rating;
    $productShop = $product->added_by == 'admin' ? getInHouseShopConfig() : $product->seller?->shop;
    $productShopSlug = $product->added_by == 'admin' ? getInHouseShopConfig(key: 'slug') : $product->seller?->shop?->slug;
    $productUrl = route('product', $product->slug);
    $isPhysical = $product->product_type == 'physical';
    $stockLevel = $isPhysical ? (int) $firstVariationQuantity : null;
    $shopIsClosed = $product->added_by == 'admin'
        ? (checkVendorAbility(type: 'inhouse', status: 'temporary_close') || checkVendorAbility(type: 'inhouse', status: 'vacation_status'))
        : (checkVendorAbility(type: 'vendor', status: 'temporary_close', vendor: $product->seller?->shop) || checkVendorAbility(type: 'vendor', status: 'vacation_status', vendor: $product->seller?->shop));
    $summary = trim(strip_tags((string) $product->details));
    $imageSources = ($isPhysical && !empty($product->retailDetail?->color_image) && count($product->color_images_full_url) > 0)
        ? $product->color_images_full_url
        : $product->images_full_url;
@endphp

<div class="modal-body rtl">
    <button class="close close-quick-view-modal qv-close" type="button" data-dismiss="modal"
            aria-label="{{ translate('Close') }}">
        <span aria-hidden="true">&times;</span>
    </button>

    <div class="qv-grid">
        <div class="qv-media">
            <div class="pd-img-wrap">
                <div class="pd-stage">
                    <div class="swiper-container quickviewSlider2 aspect-1">
                        <div class="swiper-wrapper">
                            @foreach ($imageSources as $key => $photo)
                                @php
                                    $imagePath = isset($photo['image_name'])
                                        ? getStorageImages(path: $photo['image_name'], type: 'backend-product')
                                        : getStorageImages(path: $photo, type: 'backend-product');
                                @endphp
                                <div class="swiper-slide position-relative" data-color="{{ $photo['color'] ?? '' }}">
                                    <div class="easyzoom easyzoom--overlay is-ready">
                                        <a href="{{ $imagePath }}">
                                            <img class="h-100 aspect-1" alt="{{ $product->name }}" src="{{ $imagePath }}">
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="pd-badges">
                        <span class="pd-badge pd-badge--type">
                            <img class="svg"
                                 src="{{ theme_asset(path: 'public/assets/front-end/img/icons/'.($isPhysical ? 'physical' : 'digital').'-product.svg') }}"
                                 alt="">
                            {{ $isPhysical ? translate('Physical Product') : translate('Digital Product') }}
                        </span>
                        @if ($product->discount_value > 0)
                            <span class="pd-badge pd-badge--discount">
                                <span class="direction-ltr">-{{ $product->discount_label }}</span>
                            </span>
                        @endif
                    </div>

                    <div class="qv-stage-actions share_dropdown_wrapper">
                        <button type="button" class="pd-icon-btn share_btn" tabindex="0"
                                data-toggle="tooltip" data-placement="left" title="{{ translate('Share Product') }}">
                            <i class="fa fa-share-alt"></i>
                        </button>

                        <div class="share_dropdown bg-white d-flex gap-3 align-items-center flex-column">
                            <a href="#" class="flex-shrink-0 btn btn-circle p-0 bg-facebook text-white share-on-social-media share_btn facebook"
                               style="--size: 20px" data-action="{{ $productUrl }}"
                               data-social-media-name="facebook.com/sharer/sharer.php?u=">
                                <i class="czi-facebook lh-1 fs-10"></i>
                            </a>
                            <a href="#" class="flex-shrink-0 btn btn-circle p-0 bg-twitter text-white share-on-social-media share_btn twitter"
                               style="--size: 20px" data-action="{{ $productUrl }}"
                               data-social-media-name="twitter.com/intent/tweet?text=">
                                <i class="czi-twitter lh-1 fs-10"></i>
                            </a>
                            <a href="#" class="flex-shrink-0 btn btn-circle p-0 bg-linkedin text-white share-on-social-media share_btn linkedin"
                               style="--size: 20px" data-action="{{ $productUrl }}"
                               data-social-media-name="linkedin.com/shareArticle?mini=true&url=">
                                <i class="czi-linkedin lh-1 fs-10"></i>
                            </a>
                            <a href="#" class="flex-shrink-0 btn btn-circle p-0 bg-whatsapp text-white share-on-social-media share_btn whatsapp"
                               style="--size: 20px" data-action="{{ $productUrl }}"
                               data-social-media-name="api.whatsapp.com/send?text=">
                                <i class="fa fa-whatsapp lh-1 fs-10"></i>
                            </a>
                        </div>
                    </div>
                </div>

                @if (count($imageSources) > 1)
                    <div class="pd-thumbs user-select-none">
                        <div class="quickviewSliderThumb2 swiper-container position-relative active-border">
                            <div class="swiper-wrapper auto-item-width justify-content-start">
                                @foreach ($imageSources as $key => $photo)
                                    @php
                                        $thumbPath = isset($photo['image_name'])
                                            ? getStorageImages(path: $photo['image_name'], type: 'backend-product')
                                            : getStorageImages(path: $photo, type: 'backend-product');
                                    @endphp
                                    <div class="swiper-slide position-relative" role="group">
                                        <img class="aspect-1" alt="{{ $product->name }}" src="{{ $thumbPath }}">
                                    </div>
                                @endforeach
                            </div>

                            <div class="swiper-button-next swiper-quickview-button-next"></div>
                            <div class="swiper-button-prev swiper-quickview-button-prev"></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="qv-info web-direction">
            <div class="details product-cart-option-container border-0 p-0">
                @if (getWebConfig(name: 'business_mode') == 'multi' && $productShopSlug)
                    <a href="{{ route('vendor-shop', ['slug' => $productShopSlug]) }}" class="pd-shop">
                        <img class="pd-shop__avatar" alt=""
                             src="{{ getStorageImages(path: $productShop?->image_full_url, type: 'shop') }}">
                        <span>{{ translate('Sold by') }}</span>
                        <span class="pd-shop__name">{{ $productShop?->name ?? '' }}</span>
                        @include('web-views.partials._verified-badge', ['isVerified' => $product->hasVerifiedShopBadge()])
                    </a>
                @endif

                <a href="{{ $productUrl }}" class="pd-title">{{ $product->name }}</a>

                <div class="pd-meta">
                    @if ($overallRating[0] != 0)
                        <span class="pd-meta__item">
                            @include('web-views.partials._rating-display', [
                                'rating' => $overallRating[0],
                                'count' => 0,
                                'size' => 'md',
                                'showValue' => false,
                            ])
                            <strong>{{ number_format((float) $overallRating[0], 1) }}</strong>
                        </span>
                        <span class="pd-meta__sep"></span>
                        <span class="pd-meta__item"><strong>{{ $overallRating[1] }}</strong> {{ translate('Ratings') }}</span>
                        <span class="pd-meta__sep"></span>
                    @endif
                    <span class="pd-meta__item"><strong>{{ $countOrder }}</strong> {{ translate('Orders') }}</span>
                    <span class="pd-meta__sep"></span>
                    <span class="pd-meta__item text-capitalize">
                        <strong class="countWishlist-{{ $product->id }}">{{ $countWishlist }}</strong> {{ translate('Wish listed') }}
                    </span>
                </div>

                @if ($product['product_type'] == 'digital')
                    <div class="digital-product-authors mb-3">
                        @if (count($productPublishingHouseInfo['data']) > 0)
                            <div class="d-flex align-items-center g-2 me-2">
                                <span class="text-capitalize digital-product-author-title">{{ translate('Publishing House') }} :</span>
                                <div class="item-list">
                                    @foreach ($productPublishingHouseInfo['data'] as $publishingHouseName)
                                        <a href="{{ route('products', ['publishing_house_id' => $publishingHouseName['id'], 'product_type' => 'digital', 'page' => 1]) }}"
                                           class="text-base">
                                            {{ $publishingHouseName['name'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (count($productAuthorsInfo['data']) > 0)
                            <div class="d-flex align-items-center g-2 me-2">
                                <span class="text-capitalize digital-product-author-title">{{ translate('Author') }} :</span>
                                <div class="item-list">
                                    @foreach ($productAuthorsInfo['data'] as $productAuthor)
                                        <a href="{{ route('products', ['author_id' => $productAuthor['id'], 'product_type' => 'digital', 'page' => 1]) }}"
                                           class="text-base">
                                            {{ $productAuthor['name'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <form class="addToCartDynamicForm add-to-cart-details-form d-flex flex-column gap-3">
                    @csrf
                    <input type="hidden" name="id" value="{{ $product->id }}">

                    <div class="pd-price">
                        {!! getPriceRangeWithDiscount(product: $product) !!}
                    </div>

                    @if ($summary !== '')
                        <p class="pd-summary">{{ $summary }}</p>
                    @endif

                    <hr class="pd-divider">

                    @if (count(json_decode($product->colors)) > 0)
                        <div class="pd-option">
                            <div class="pd-option__label product-description-label">{{ translate('Color') }}</div>
                            <ul class="flex-start checkbox-color mb-0 p-0 list-inline gap-2">
                                @foreach (json_decode($product->colors) as $key => $color)
                                    <li>
                                        <input type="radio" id="{{ $product->id }}-color-{{ str_replace('#', '', $color) }}"
                                               name="color" value="{{ $color }}" @if($key == 0) checked @endif>
                                        <label style="background: {{ $color }};"
                                               class="quick-view-preview-image-by-color shadow-border"
                                               for="{{ $product->id }}-color-{{ str_replace('#', '', $color) }}"
                                               data-toggle="tooltip"
                                               data-key="{{ str_replace('#', '', $color) }}"
                                               data-title="{{ getColorNameByCode(code: $color) }}">
                                            <span class="outline"></span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @foreach (json_decode($product->choice_options) as $key => $choice)
                        <div class="pd-option">
                            <div class="pd-option__label product-description-label text-capitalize">{{ $choice->title }}</div>
                            <ul class="checkbox-alphanumeric checkbox-alphanumeric--style-1 p-0 mb-0">
                                @foreach ($choice->options as $index => $option)
                                    <span>
                                        <input type="radio" id="{{ $choice->name }}-{{ $option }}"
                                               name="{{ $choice->name }}" value="{{ $option }}"
                                               @if($index == 0) checked @endif>
                                        <label class="user-select-none" for="{{ $choice->name }}-{{ $option }}">
                                            <span class="text-nowrap max-w-180 line--limit-1">{{ $option }}</span>
                                        </label>
                                    </span>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach

                    @php($extensionIndex = 0)
                    @if ($product['product_type'] == 'digital' && $product->retailDetail?->digital_product_file_types && count($product->retailDetail?->digital_product_file_types) > 0 && $product->retailDetail?->digital_product_extensions)
                        @foreach ($product->retailDetail?->digital_product_extensions as $extensionKey => $extensionGroup)
                            @if (count($extensionGroup) > 0)
                                <div class="pd-option">
                                    <div class="pd-option__label product-description-label text-capitalize">{{ translate($extensionKey) }}</div>
                                    <div class="list-inline checkbox-alphanumeric checkbox-alphanumeric--style-1 p-0 mb-0 flex-start row ps-0">
                                        @foreach ($extensionGroup as $index => $extension)
                                            <div>
                                                <div class="for-mobile-capacity">
                                                    <input type="radio" hidden
                                                           id="extension_{{ str_replace(' ', '-', $extension) }}"
                                                           name="variant_key"
                                                           value="{{ $extensionKey.'-'.preg_replace('/\s+/', '-', $extension) }}"
                                                        {{ $extensionIndex == 0 ? 'checked' : '' }}>
                                                    <label for="extension_{{ str_replace(' ', '-', $extension) }}" class="__text-12px">
                                                        <span class="text-nowrap max-w-180 line--limit-1">{{ $extension }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                            @php($extensionIndex++)
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endif

                    <div class="pd-option">
                        <div class="pd-option__label product-description-label">{{ translate('Quantity') }}</div>
                        <div class="d-flex align-items-center justify-content-between pd-qty-box">
                            <button class="btn btn-number" type="button" data-type="minus" data-field="quantity" disabled="disabled">&minus;</button>
                            <input type="text" name="quantity"
                                   class="form-control input-number text-center product-details-cart-qty border-0 w-100"
                                   value="{{ $initialProductConfig['quantity'] ?? 1 }}"
                                   data-producttype="{{ $product->product_type }}"
                                   min="{{ $product->minimum_order_qty ?? 1 }}"
                                   max="{{ $isPhysical ? $product->current_stock : 100 }}">
                            <button class="btn btn-number" type="button" data-producttype="{{ $product->product_type }}"
                                    data-type="plus" data-field="quantity">+</button>
                        </div>

                        @if ($isPhysical)
                            <span class="pd-stock product-stock-status {{ $stockLevel <= 0 ? 'pd-stock--out' : ($stockLevel <= 10 ? 'pd-stock--low' : 'pd-stock--in') }}"
                                  data-low-stock-text="{{ translate('Limited Stock') }}"
                                  data-in-stock-text="{{ translate('In Stock') }}"
                                  data-out-of-stock-text="{{ translate('Out of Stock') }}">
                                {{ $stockLevel <= 0 ? translate('Out of Stock') : ($stockLevel <= 10 ? translate('Limited Stock') : translate('In Stock')) }}
                            </span>
                        @endif
                    </div>

                    <input type="hidden" class="product-generated-variation-code" name="product_variation_code"
                           data-product-id="{{ $product['id'] }}">
                    <input type="hidden" value="" class="product-exist-in-cart-list" name="key">

                    <div class="pd-total product-details-chosen-price-section">
                        <span class="pd-total__label">{{ translate('Total price') }}</span>
                        <span class="product-details-chosen-price-amount">
                            {{ webCurrencyConverter(amount: $initialProductConfig['total_quantity_price']) }}
                        </span>
                    </div>

                    <div class="pd-actions product-add-and-buy-section" {!! $firstVariationQuantity <= 0 ? 'style="display: none;"' : '' !!}>
                        @if ($shopIsClosed)
                            <button class="btn btn-secondary" type="button" disabled>{{ translate('Buy Now') }}</button>
                            <button class="btn btn--primary string-limit" type="button" disabled>{{ translate('Add To Cart') }}</button>
                        @else
                            <button class="btn btn-secondary product-buy-now-button" type="button"
                                    data-form=".add-to-cart-details-form"
                                    data-auth="{{ (getWebConfig(name: 'guest_checkout') == 1 || Auth::guard('customer')->check() ? 'true' : 'false') }}"
                                    data-route="{{ route('shop-cart') }}">
                                {{ translate('Buy Now') }}
                            </button>
                            <button class="btn btn--primary string-limit product-add-to-cart-button" type="button"
                                    data-form=".add-to-cart-details-form"
                                    data-update="{{ translate('Update Cart') }}"
                                    data-add="{{ translate('Add To Cart') }}">
                                {{ $initialProductConfig['first_variant_in_cart'] ? translate('Update Cart') : translate('Add To Cart') }}
                            </button>
                        @endif

                        <button type="button" data-product-id="{{ $product['id'] }}"
                                class="btn pd-wishlist product-action-add-wishlist"
                                aria-label="{{ translate('Add to wishlist') }}">
                            <i class="fa {{ $wishlist_status == 1 ? 'fa-heart' : 'fa-heart-o' }} wishlist_icon_{{ $product['id'] }} web-text-primary"
                               aria-hidden="true"></i>
                            <span class="countWishlist-{{ $product['id'] }}">{{ $countWishlist }}</span>
                            <div class="wishlist-tooltip" x-placement="top">
                                <div class="arrow"></div>
                                <div class="inner">
                                    <span class="add">{{ translate('Added to wishlist') }}</span>
                                    <span class="remove">{{ translate('Removed from wishlist') }}</span>
                                </div>
                            </div>
                        </button>
                    </div>

                    @if ($isPhysical)
                        <div class="pd-actions product-restock-request-section collapse" {!! $firstVariationQuantity <= 0 ? 'style="display: flex;"' : '' !!}>
                            <button type="button"
                                    class="btn request-restock-btn btn-outline-primary product-restock-request-button"
                                    data-auth="{{ auth('customer')->check() }}"
                                    data-form=".addToCartDynamicForm"
                                    data-default="{{ translate('Request Restock') }}"
                                    data-requested="{{ translate('Request Sent') }}">
                                {{ translate('Request Restock') }}
                            </button>
                            <button type="button" data-product-id="{{ $product['id'] }}"
                                    class="btn pd-wishlist product-action-add-wishlist"
                                    aria-label="{{ translate('Add to wishlist') }}">
                                <i class="fa {{ $wishlist_status == 1 ? 'fa-heart' : 'fa-heart-o' }} wishlist_icon_{{ $product['id'] }} web-text-primary"
                                   aria-hidden="true"></i>
                                <span class="countWishlist-{{ $product['id'] }}">{{ $countWishlist }}</span>
                                <div class="wishlist-tooltip" x-placement="top">
                                    <div class="arrow"></div>
                                    <div class="inner">
                                        <span class="add">{{ translate('Added to wishlist') }}</span>
                                        <span class="remove">{{ translate('Removed from wishlist') }}</span>
                                    </div>
                                </div>
                            </button>
                        </div>
                    @endif

                    @if ($shopIsClosed)
                        <div class="pd-alert pd-alert--warning" role="alert">
                            <span class="pd-alert__icon"><i class="fa fa-exclamation"></i></span>
                            <span class="pd-alert__body">
                                <strong class="pd-alert__title">{{ translate('Store Unavailable') }}</strong>
                                {{ translate('This shop is closed or on vacation. Products cannot be added.') }}
                            </span>
                        </div>
                    @endif
                </form>

                <hr class="pd-divider my-3">

                <div class="pd-facts mb-3">
                    @if ($product->code)
                        <span class="pd-facts__item">{{ translate('SKU') }}: <strong>{{ $product->code }}</strong></span>
                    @endif
                    @if ($isPhysical && $product->unit)
                        <span class="pd-facts__item">{{ translate('Unit') }}: <strong>{{ $product->unit }}</strong></span>
                    @endif
                    @if (($product->minimum_order_qty ?? 1) > 1)
                        <span class="pd-facts__item">{{ translate('Minimum Order Qty') }}: <strong>{{ $product->minimum_order_qty }}</strong></span>
                    @endif
                    @if ($isPhysical && $product->free_shipping)
                        <span class="pd-facts__item"><i class="fa fa-truck"></i> {{ translate('Free Delivery') }}</span>
                    @endif
                </div>

                <a href="{{ $productUrl }}" class="pd-details-link">
                    {{ translate('View Details') }} <i class="fa fa-angle-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    "use strict";
    productQuickViewFunctionalityInitialize();
</script>

<script>
    function initSliderWithZoom() {
        $(".easyzoom").each(function () {
            $(this).easyZoom();
        });

        const sliderOptions = {
            slidesPerView: 1,
            spaceBetween: 10,
            loop: false,
        };

        const thumbsElement = document.querySelector(".quickviewSliderThumb2");
        if (thumbsElement) {
            sliderOptions.thumbs = {
                swiper: new Swiper(thumbsElement, {
                    spaceBetween: 10,
                    slidesPerView: 'auto',
                    watchSlidesProgress: true,
                    navigation: {
                        nextEl: ".swiper-quickview-button-next",
                        prevEl: ".swiper-quickview-button-prev",
                    },
                }),
            };
        }

        new Swiper(".quickviewSlider2", sliderOptions);
    }

    initSliderWithZoom();

    $(document).on('change', 'input[name="color"]', function() {
        const selectedColor = $(this).val();
        const colors = @json(json_decode($product->colors));
        const colorIndex = colors.indexOf(selectedColor);
        if (colorIndex !== -1) {
            const mainSwiper = document.querySelector('.quickviewSlider2').swiper;
            const thumbSwiper = document.querySelector('.quickviewSliderThumb2')?.swiper;
            mainSwiper.slideTo(colorIndex);
            thumbSwiper?.slideTo(colorIndex);
        }
    });
</script>
