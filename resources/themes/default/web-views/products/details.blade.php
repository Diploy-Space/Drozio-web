@extends('layouts.front-end.app')

@section('title', $product['name'])

@push('css_or_js')
    @include(VIEW_FILE_NAMES['product_seo_meta_content_partials'], ['metaContentData' => $product?->seoInfo, 'productDetails' => $product])
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/product-details.css') }}"/>
@endpush

@section('content')
    @php
        $isPhysical = $product->product_type == 'physical';
        $stockLevel = $isPhysical ? (int) $firstVariationQuantity : null;
        $productShop = $product->added_by == 'admin' ? getInHouseShopConfig() : $product->seller?->shop;
        $productShopSlug = $product->added_by == 'admin' ? getInHouseShopConfig(key: 'slug') : $product->seller?->shop?->slug;
        $productCategory = $product->category;
    @endphp
    <div class="__inline-23 pd-page">
        <div class="container my-4 rtl text-align-direction">
            <nav class="pd-breadcrumb web-direction" aria-label="{{ translate('Breadcrumb') }}">
                <a href="{{ route('home') }}">{{ translate('Home') }}</a>
                <i class="czi-arrow-right"></i>
                @if ($productCategory)
                    <a href="{{ route('category-products', ['slug' => $productCategory->slug]) }}">{{ $productCategory->name }}</a>
                    <i class="czi-arrow-right"></i>
                @endif
                <span class="pd-breadcrumb__current">{{ $product->name }}</span>
            </nav>
            <div class="row g-3 {{Session::get('direction') === "rtl" ? '__dir-rtl' : ''}}">
                <div class="col-lg-9">
                    <div class="h-100 d-flex flex-column gap-4 pb-2">
                        <div class="card card-body flex-grow-0">
                            <div class="row g-3">
                                <div class="col-lg-5 col-md-4">
                                    <div class="pd-img-wrap position-relative">
                                        <div class="pd-stage">
                                        <div class="swiper-container quickviewSlider2 aspect-1">
                                            <div class="swiper-wrapper">
                                                @php
                                                    $imageSources = ($product->product_type === 'physical' && !empty($product->retailDetail?->color_image) && count($product->color_images_full_url) > 0)
                                                        ? $product->color_images_full_url
                                                        : $product->images_full_url;
                                                @endphp

                                                @foreach ($imageSources as $key => $photo)
                                                    @php
                                                        $imagePath = isset($photo['image_name'])
                                                            ? getStorageImages(path: $photo['image_name'], type: 'backend-product')
                                                            : getStorageImages(path: $photo, type: 'backend-product');

                                                        $colorCode = $photo['color'] ?? '';
                                                    @endphp
                                                    <div class="swiper-slide position-relative" data-color="{{ $colorCode }}">
                                                        <div class="easyzoom easyzoom--overlay is-ready">
                                                            <a href="{{ $imagePath }}">
                                                                <img class="rounded h-100 aspect-1" alt="" src="{{ $imagePath }}">
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endforeach

                                                @if($product?->preview_file_full_url['path'])
                                                    <div class="product-preview-modal-text"
                                                         data-toggle="modal"
                                                         data-target="#product-preview-modal">
                                                        <span class="text-primary fw-bold py-2 user-select-none fs-14">
                                                            {{ translate('See Preview') }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="pd-badges">
                                            @if ($product->discount_value > 0)
                                                <span class="pd-badge pd-badge--discount">
                                                    <span class="direction-ltr">-{{ $product->discount_label }}</span>
                                                </span>
                                            @endif
                                            <span class="pd-badge pd-badge--type">
                                                <img class="svg"
                                                     src="{{ theme_asset(path: 'public/assets/front-end/img/icons/'.($isPhysical ? 'physical' : 'digital').'-product.svg') }}"
                                                     alt="">
                                                {{ $isPhysical ? translate('Physical Product') : translate('Digital Product') }}
                                            </span>
                                        </div>

                                        <div class="pd-stage-actions">
                                                <div class="share_dropdown_wrapper">
                                                    <button type="button" class="pd-icon-btn share_btn" tabindex="0"
                                                            data-toggle="tooltip" data-placement="left" title="{{ translate('Share Product') }}">
                                                        <i class="fa fa-share-alt"></i>
                                                    </button>
                                                    <div class="share_dropdown bg-white d-flex gap-3 align-items-center flex-column">
                                                        <a href="#"  class="flex-shrink-0 btn btn-circle p-0 bg-facebook text-white share-on-social-media share_btn facebook" style="--size: 20px"   data-action="{{route('product',$product->slug)}}"    data-social-media-name="facebook.com/sharer/sharer.php?u=">
                                                            <i class="czi-facebook lh-1 fs-10"></i>
                                                        </a>
                                                        <a href="#" class="flex-shrink-0 btn btn-circle p-0 bg-twitter text-white share-on-social-media share_btn twitter" style="--size: 20px"    data-action="{{route('product',$product->slug)}}" data-social-media-name="twitter.com/intent/tweet?text=">
                                                            <i class="czi-twitter lh-1 fs-10"></i>
                                                        </a>
                                                        <a href="#" class="flex-shrink-0 btn btn-circle p-0 bg-linkedin text-white share-on-social-media share_btn linkedin" style="--size: 20px"  data-action="{{route('product',$product->slug)}}" data-social-media-name="linkedin.com/shareArticle?mini=true&url=">
                                                            <i class="czi-linkedin lh-1 fs-10"></i>
                                                        </a>
                                                        <a href="#" class="flex-shrink-0 btn btn-circle p-0 bg-whatsapp text-white share-on-social-media share_btn whatsapp" style="--size: 20px"  data-action="{{route('product',$product->slug)}}" data-social-media-name="api.whatsapp.com/send?text=">
                                                            <i class="fa fa-whatsapp lh-1 fs-10"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="pd-thumbs user-select-none">
                                            <div class="quickviewSliderThumb2 swiper-container position-relative active-border">
                                                <div class="swiper-wrapper auto-item-width justify-content-start">
                                                    @foreach ($imageSources as $key => $photo)
                                                        @php
                                                            $imagePath = isset($photo['image_name'])
                                                                ? getStorageImages(path: $photo['image_name'], type: 'backend-product')
                                                                : getStorageImages(path: $photo, type: 'backend-product');
                                                        @endphp
                                                        <div class="swiper-slide position-relative" role="group">
                                                            <img class="aspect-1" alt="{{ $product->name }}" src="{{ $imagePath }}">
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <div class="swiper-button-next swiper-quickview-button-next"></div>
                                                <div class="swiper-button-prev swiper-quickview-button-prev"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-7 col-md-8 mt-md-0 mt-sm-3 web-direction">
                                    <div class="details __h-100 product-cart-option-container p-0 pd-buy-column">
                                        <div class="d-flex flex-column gap-2">
                                            @if(getWebConfig(name: 'business_mode') == 'multi' && $productShopSlug)
                                                <a href="{{ route('vendor-shop', ['slug' => $productShopSlug]) }}" class="pd-shop">
                                                    <img class="pd-shop__avatar" alt=""
                                                         src="{{ getStorageImages(path: $productShop?->image_full_url, type: 'shop') }}">
                                                    <span>{{ translate('Sold by') }}</span>
                                                    <span class="pd-shop__name">{{ $productShop?->name ?? '' }}</span>
                                                    @include('web-views.partials._verified-badge', ['isVerified' => $product->hasVerifiedShopBadge()])
                                                </a>
                                            @endif

                                            <h1 class="pd-title">{{ $product->name }}</h1>

                                            <div class="pd-meta pro">
                                                @if($overallRating[0] != 0)
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
                                                    <span class="pd-meta__item">
                                                        <a href="#reviews" data-toggle="tab" role="tab">
                                                            <strong>{{ $overallRating[1] }}</strong> {{ translate('Ratings') }}
                                                        </a>
                                                    </span>
                                                    <span class="pd-meta__sep"></span>
                                                @endif
                                                <span class="pd-meta__item"><strong>{{ $countOrder }}</strong> {{ translate('Orders') }}</span>
                                                <span class="pd-meta__sep"></span>
                                                <span class="pd-meta__item text-capitalize">
                                                    <strong class="countWishlist-{{ $product->id }}">{{ $countWishlist }}</strong> {{ translate('Wish listed') }}
                                                </span>
                                            </div>
                                        </div>

                                        @if($product['product_type'] == 'digital')
                                            <div class="digital-product-authors mb-2">
                                                @if(count($productPublishingHouseInfo['data']) > 0)
                                                    <div class="d-flex align-items-center g-2 me-2">
                                                        <span class="text-capitalize digital-product-author-title">{{ translate('Publishing House') }} :</span>
                                                        <div class="item-list">
                                                            @foreach($productPublishingHouseInfo['data'] as $publishingHouseName)
                                                                <a href="{{ route('products', ['publishing_house_id' => $publishingHouseName['id'], 'product_type' => 'digital', 'page'=>1]) }}"
                                                                   class="text-base">
                                                                    {{ $publishingHouseName['name'] }}
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                @if(count($productAuthorsInfo['data']) > 0)
                                                    <div class="d-flex align-items-center g-2 me-2">
                                                        <span class="text-capitalize digital-product-author-title">{{ translate('Author') }} :</span>
                                                        <div class="item-list">
                                                            @foreach($productAuthorsInfo['data'] as $productAuthor)
                                                                <a href="{{ route('products',['author_id' => $productAuthor['id'], 'product_type' => 'digital', 'page' => 1]) }}"
                                                                   class="text-base">
                                                                    {{ $productAuthor['name'] }}
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        <form class="addToCartDynamicForm add-to-cart-details-form d-flex flex-column gap-4">

                                            <div class="pd-price">
                                                @if($product->discount_value > 0)
                                                    <del class="product-total-unit-price">
                                                        {{ webCurrencyConverter(amount: $product->unit_price) }}
                                                    </del>
                                                @endif
                                                <span class="discounted-unit-price">
                                                    {{ $product->discounted_unit_price_label }}
                                                </span>
                                            </div>

                                            <hr class="pd-divider">

                                            @csrf
                                            <input type="hidden" name="id" value="{{ $product->id }}">
                                            <div
                                                class="position-relative">
                                                @if (count(json_decode($product->colors)) > 0)
                                                    <div class="pd-option">
                                                        <div class="pd-option__label product-description-label">
                                                            {{ translate('Color') }}
                                                        </div>
                                                        <div>
                                                            <ul class="list-inline checkbox-color mb-0 p-0 flex-start gap-2">
                                                                @foreach (json_decode($product->colors) as $key => $color)
                                                                    <li>
                                                                        <input type="radio"
                                                                               id="{{ str_replace(' ', '', ($product->id. '-color-'. str_replace('#','',$color))) }}"
                                                                               name="color" value="{{ $color }}"
                                                                               @if($key == 0) checked @endif>
                                                                        <label style="background: {{ $color }};"
                                                                               class="focus-preview-image-by-color shadow-border"
                                                                               for="{{ str_replace(' ', '', ($product->id. '-color-'. str_replace('#','',$color))) }}"
                                                                               data-toggle="tooltip"
                                                                               data-key="{{ str_replace('#','',$color) }}"
                                                                               data-colorid="preview-box-{{ str_replace('#','',$color) }}"
                                                                               data-title="{{ getColorNameByCode(code: $color) }}">
                                                                            <span class="outline"></span></label>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </div>
                                                @endif
                                                @php
                                                    $qty = 0;
                                                    if(!empty($product->retailDetail?->variation)){
                                                    foreach (json_decode($product->retailDetail?->variation ?? '[]') as $key => $variation) {
                                                            $qty += $variation->qty;
                                                        }
                                                    }
                                                @endphp
                                            </div>

                                            @php($extensionIndex=0)
                                            @if($product['product_type'] == 'digital' && $product->retailDetail?->digital_product_file_types && count($product->retailDetail?->digital_product_file_types) > 0 && $product->retailDetail?->digital_product_extensions)
                                                @foreach($product->retailDetail?->digital_product_extensions as $extensionKey => $extensionGroup)
                                                    <div class="pd-option">
                                                        <div
                                                            class="pd-option__label product-description-label text-capitalize">
                                                            {{ translate($extensionKey) }}
                                                        </div>
                                                        <div>
                                                            @if(count($extensionGroup) > 0)
                                                                <div
                                                                    class="list-inline checkbox-alphanumeric checkbox-alphanumeric--style-1 mb-0 mx-1 flex-start ps-0">
                                                                    @foreach($extensionGroup as $index => $extension)
                                                                        <div class="user-select-none">
                                                                            <div class="for-mobile-capacity">
                                                                                <input type="radio" hidden
                                                                                       id="extension_{{ str_replace(' ', '-', $extension) }}"
                                                                                       name="variant_key"
                                                                                       value="{{ $extensionKey.'-'.preg_replace('/\s+/', '-', $extension) }}"
                                                                                    {{ $extensionIndex == 0 ? 'checked' : ''}}>
                                                                                <label
                                                                                    for="extension_{{ str_replace(' ', '-', $extension) }}"
                                                                                    class="__text-12px">
                                                                                    <span class="text-nowrap max-w-180 line--limit-1">{{ $extension }}</span>
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        @php($extensionIndex++)
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif

                                            @foreach (json_decode($product->choice_options) as $key => $choice)
                                                <div class="pd-option">
                                                    <div
                                                        class="pd-option__label product-description-label text-capitalize">{{ $choice->title }}
                                                    </div>
                                                    <div>
                                                        <div
                                                            class="list-inline checkbox-alphanumeric checkbox-alphanumeric--style-1 mb-0 mx-1 flex-start row ps-0">
                                                            @foreach ($choice->options as $index => $option)
                                                                <div class="user-select-none">
                                                                    <div class="for-mobile-capacity">
                                                                        <input type="radio"
                                                                               id="{{ str_replace(' ', '', ($choice->name. '-'. $option)) }}"
                                                                               name="{{ $choice->name }}" value="{{ $option }}"
                                                                               @if($index == 0) checked @endif >
                                                                        <label class="__text-12px"
                                                                               for="{{ str_replace(' ', '', ($choice->name. '-'. $option)) }}">
                                                                                <span class="text-nowrap max-w-180 line--limit-1">{{ $option }}</span>
                                                                            </label>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach

                                            <div class="product-quantity d-flex flex-column gap-3">
                                                <div class="pd-option">
                                                    <div class="pd-option__label product-description-label">
                                                        {{ translate('Quantity') }}
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center pd-qty-box">
                                                        <button class="btn btn-number" type="button"
                                                                data-type="minus" data-field="quantity" disabled="disabled">&minus;</button>
                                                        <input type="text" name="quantity"
                                                               class="form-control input-number text-center product-details-cart-qty border-0 w-100"
                                                               value="{{ $initialProductConfig['quantity'] ?? 1 }}"
                                                               data-producttype="{{ $product->product_type }}"
                                                               min="{{ $product->minimum_order_qty ?? 1 }}"
                                                               max="{{ $isPhysical ? $product->current_stock : 100 }}">
                                                        <button class="btn btn-number" type="button"
                                                                data-producttype="{{ $product->product_type }}"
                                                                data-type="plus" data-field="quantity">+</button>
                                                    </div>

                                                    @if($isPhysical)
                                                        <span class="pd-stock product-stock-status {{ $stockLevel <= 0 ? 'pd-stock--out' : ($stockLevel <= 10 ? 'pd-stock--low' : 'pd-stock--in') }}"
                                                              data-low-stock-text="{{ translate('Limited Stock') }}"
                                                              data-in-stock-text="{{ translate('In Stock') }}"
                                                              data-out-of-stock-text="{{ translate('Out of Stock') }}">
                                                            {{ $stockLevel <= 0 ? translate('Out of Stock') : ($stockLevel <= 10 ? translate('Limited Stock') : translate('In Stock')) }}
                                                        </span>
                                                    @endif

                                                    <input type="hidden" class="product-generated-variation-code"
                                                           name="product_variation_code"
                                                           data-product-id="{{ $product['id'] }}">
                                                    <input type="hidden" value=""
                                                           class="product-exist-in-cart-list" name="key">
                                                </div>

                                                <div class="pd-total product-details-chosen-price-section">
                                                    <span class="pd-total__label">{{ translate('Total price') }}</span>
                                                    <span class="product-details-chosen-price-amount">
                                                        {{ webCurrencyConverter(amount: $initialProductConfig['total_quantity_price']) }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="__btn-grp product-add-and-buy-section-parent">

                                                <?php


                                                $isTemporaryClose = false;
                                                $isVacationMode = false;
                                                $authorType = $product->added_by == 'admin' ? 'inhouse' : ($product->added_by == 'seller' ? 'vendor' : '');

                                                if ($authorType === 'vendor') {
                                                    $isTemporaryClose = checkVendorAbility(type: $authorType, status: 'temporary_close', vendor: $product->seller->shop);
                                                    $isVacationMode = !$isTemporaryClose ? checkVendorAbility(type: $authorType, status: 'vacation_status', vendor: $product->seller->shop) : false;
                                                } else {
                                                    $isTemporaryClose = checkVendorAbility(type: $authorType, status: 'temporary_close');
                                                    $isVacationMode = !$isTemporaryClose ? checkVendorAbility(type: $authorType, status: 'vacation_status') : false;
                                                }
                                                ?>

                                                <div
                                                    class="pd-actions product-add-and-buy-section {!! $firstVariationQuantity <= 0 ? '' : 'd-flex' !!}"
                                                    {!! $firstVariationQuantity <= 0 ? 'style="display: none;"' : '' !!}>

                                                    @if($isTemporaryClose || $isVacationMode)
                                                        <button class="btn btn-secondary" type="button" disabled>
                                                            {{ translate('Buy Now') }}
                                                        </button>
                                                        <button class="btn btn--primary string-limit" type="button" disabled>
                                                            {{ translate('Add To Cart') }}
                                                        </button>
                                                    @else
                                                        <button type="button"
                                                                class="btn btn-secondary product-buy-now-button"
                                                                data-form=".add-to-cart-details-form"
                                                                data-auth="{{ (getWebConfig(name: 'guest_checkout') == 1 || Auth::guard('customer')->check()) ? 'true' : 'false' }}"
                                                                data-route="{{ route('shop-cart') }}"
                                                        >
                                                            <span class="string-limit">{{ translate('Buy Now') }}</span>
                                                        </button>
                                                        <button
                                                            class="btn btn--primary product-add-to-cart-button"
                                                            type="button"
                                                            data-form=".add-to-cart-details-form"
                                                            data-update="{{ translate('Update Cart') }}"
                                                            data-add="{{ translate('Add To Cart') }}"
                                                        >
                                                            <span class="string-limit">
                                                                {{ $initialProductConfig['first_variant_in_cart'] ? translate('Update Cart') : translate('Add To Cart') }}
                                                            </span>
                                                        </button>
                                                    @endif

                                                    <button type="button" data-product-id="{{ $product['id'] }}"
                                                            class="btn pd-wishlist product-action-add-wishlist"
                                                            aria-label="{{ translate('Add to wishlist') }}">
                                                        <i class="fa {{($wishlistStatus == 1?'fa-heart':'fa-heart-o') }} wishlist_icon_{{ $product['id'] }} web-text-primary"
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

                                                @if($isPhysical)
                                                    <div
                                                        class="pd-actions product-restock-request-section collapse" {!! $firstVariationQuantity <= 0 ? 'style="display: flex;"' : '' !!}>
                                                        <button type="button"
                                                                class="btn request-restock-btn btn-outline-primary product-restock-request-button"
                                                                data-auth="{{ auth('customer')->check() }}"
                                                                data-form=".addToCartDynamicForm"
                                                                data-default="{{ translate('Request Restock') }}"
                                                                data-requested="{{ translate('Request Sent') }}"
                                                        >
                                                            {{ translate('Request Restock') }}
                                                        </button>
                                                        <button type="button" data-product-id="{{ $product['id'] }}"
                                                                class="btn pd-wishlist product-action-add-wishlist"
                                                                aria-label="{{ translate('Add to wishlist') }}">
                                                            <i class="fa {{($wishlistStatus == 1?'fa-heart':'fa-heart-o') }} wishlist_icon_{{ $product['id'] }} web-text-primary"
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

                                                @if($isTemporaryClose || $isVacationMode)
                                                    <div class="pd-alert pd-alert--warning mt-3" role="alert">
                                                        <span class="pd-alert__icon"><i class="fa fa-exclamation"></i></span>
                                                        <span class="pd-alert__body">
                                                            <strong class="pd-alert__title">{{ translate('Store Unavailable') }}</strong>
                                                            {{ translate('This shop is closed or on vacation. Products cannot be added.') }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>

                                        </form>

                                        <hr class="pd-divider">

                                        <div class="pd-facts">
                                            @if($product->code)
                                                <span class="pd-facts__item">{{ translate('SKU') }}: <strong>{{ $product->code }}</strong></span>
                                            @endif
                                            @if($isPhysical && $product->unit)
                                                <span class="pd-facts__item">{{ translate('Unit') }}: <strong>{{ $product->unit }}</strong></span>
                                            @endif
                                            @if(($product->minimum_order_qty ?? 1) > 1)
                                                <span class="pd-facts__item">{{ translate('Minimum Order Qty') }}: <strong>{{ $product->minimum_order_qty }}</strong></span>
                                            @endif
                                            @if($isPhysical && $product->free_shipping)
                                                <span class="pd-facts__item"><i class="fa fa-truck"></i> {{ translate('Free Delivery') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="rtl text-align-direction flex-grow-1">
                            <div class="__review-overview card card-body h-100">
                               <ul class="nav nav-tabs pd-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link mb-0 tab_link active"
                                        data-toggle="tab"
                                        href="#overview"
                                        role="tab">
                                            {{ translate('Overview') }}
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mb-0 tab_link"
                                        data-toggle="tab"
                                        href="#reviews"
                                        role="tab">
                                            {{ translate('Reviews') }}
                                        </a>
                                    </li>
                                </ul>


                                <div class="tab-content px-lg-3">
                                    <div class="tab-pane fade show active text-justify" id="overview" role="tabpanel">
                                        <div class="details-content-wrap ov-hidden show-more--content">
                                            <div class="row pt-2 specification">

                                                @if($product->video_url != null && (str_contains($product->video_url, "youtube.com/embed/")))
                                                    <div class="col-12 mb-4">
                                                        <iframe width="420" height="315" src="{{ $product->video_url }}"
                                                                title="YouTube video player" frameborder="0"
                                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                                referrerpolicy="strict-origin-when-cross-origin"
                                                                allowfullscreen></iframe>
                                                    </div>
                                                @endif
                                                @if ($product['details'])
                                                    <div
                                                        class="p-details-description text-body col-lg-12 col-md-12 details-text-justify rich-editor-html-content" style="max-height: 525px">
                                                        {!! $product['details'] !!}
                                                    </div>
                                                @endif
                                            </div>
                                            @if (!$product['details'] && ($product->video_url == null || !(str_contains($product->video_url, "youtube.com/embed/"))))
                                                <div>
                                                    <div class="text-center text-capitalize py-5">
                                                        <img class="mw-90"
                                                             src="{{ theme_asset(path: 'public/assets/front-end/img/icons/nodata.svg') }}"
                                                             alt="">
                                                        <p class="text-capitalize mt-2">
                                                            <small>
                                                                {{ translate('Not found').'!' }}
                                                            </small>
                                                        </p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="d-flex justify-content-center mt-2">
                                            <button class="btn btn-outline-primary text-capitalize px-4 py-2 see-more-details">{{ translate('See More') }}</button>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="reviews" role="tabpanel">
                                        <div class="">
                                            @if($product->total_reviews==0 && $productReviews->total() == 0)
                                                <div>
                                                    <div class="text-center text-capitalize">
                                                        <img class="mw-100"
                                                             src="{{ theme_asset(path: 'public/assets/front-end/img/icons/empty-review.svg') }}"
                                                             alt="">
                                                        <p class="text-capitalize">
                                                            <small>{{ translate('No review given yet') }}!</small>
                                                        </p>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="row pt-2 pb-3">
                                                    <div class="col-lg-4 col-md-5 ">
                                                        <div
                                                            class=" row d-flex justify-content-center align-items-center">
                                                            <div
                                                                class="col-12 d-flex justify-content-center align-items-center">
                                                                <h2 class="overall_review mb-2 __inline-28">
                                                                    {{ number_format((float) $overallRating[0], 1) }}
                                                                </h2>
                                                            </div>
                                                            <div class="d-flex justify-content-center align-items-center">
                                                                @include('web-views.partials._rating-display', [
                                                                    'rating' => $overallRating[0],
                                                                    'count' => 0,
                                                                    'size' => 'lg',
                                                                    'align' => 'center',
                                                                    'showValue' => false,
                                                                    'showEmpty' => true,
                                                                ])
                                                            </div>
                                                            <div
                                                                class="col-12 d-flex justify-content-center align-items-center mt-2">
                                                                <span class="text-center">
                                                                    {{ $productReviews->total() }} {{ translate('Ratings') }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-8 col-md-7 pt-sm-3 pt-md-0">
                                                        <div
                                                            class="d-flex align-items-center mb-2 font-size-sm">
                                                            <div
                                                                class="__rev-txt"><span
                                                                    class="d-inline-block align-middle text-body">{{ translate('Excellent') }}</span>
                                                            </div>
                                                            <div class="w-0 flex-grow">
                                                                <div class="progress text-body __h-5px">
                                                                    <div class="progress-bar web--bg-primary"
                                                                         role="progressbar"
                                                                         style="width: <?php echo $widthRating = ($rating[0] != 0) ? ($rating[0] / $overallRating[1]) * 100 : (0); ?>%;"
                                                                         aria-valuenow="60" aria-valuemin="0"
                                                                         aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-1 text-body">
                                                                <span
                                                                    class=" {{Session::get('direction') === "rtl" ? 'me-3 float-left' : 'ml-3 float-right'}} ">
                                                                    {{ $rating[0] }}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="d-flex align-items-center mb-2 text-body font-size-sm">
                                                            <div
                                                                class="__rev-txt"><span
                                                                    class="d-inline-block align-middle ">{{ translate('Good') }}</span>
                                                            </div>
                                                            <div class="w-0 flex-grow">
                                                                <div class="progress __h-5px">
                                                                    <div class="progress-bar web--bg-primary" role="progressbar"
                                                                         style="width: <?php echo $widthRating = ($rating[1] != 0) ? ($rating[1] / $overallRating[1]) * 100 : (0); ?>%; background-color: #a7e453;"
                                                                         aria-valuenow="27" aria-valuemin="0"
                                                                         aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <span
                                                                    class="{{Session::get('direction') === "rtl" ? 'me-3 float-left' : 'ml-3 float-right'}}">
                                                                        {{ $rating[1] }}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="d-flex align-items-center mb-2 text-body font-size-sm">
                                                            <div
                                                                class="__rev-txt"><span
                                                                    class="d-inline-block align-middle ">{{ translate('Average') }}</span>
                                                            </div>
                                                            <div class="w-0 flex-grow">
                                                                <div class="progress __h-5px">
                                                                    <div class="progress-bar web--bg-primary" role="progressbar"
                                                                         style="width: <?php echo $widthRating = ($rating[2] != 0) ? ($rating[2] / $overallRating[1]) * 100 : (0); ?>%; background-color: #ffda75;"
                                                                         aria-valuenow="17" aria-valuemin="0"
                                                                         aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <span
                                                                    class="{{Session::get('direction') === "rtl" ? 'me-3 float-left' : 'ml-3 float-right'}}">
                                                                    {{ $rating[2] }}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="d-flex align-items-center mb-2 text-body font-size-sm">
                                                            <div
                                                                class="__rev-txt "><span
                                                                    class="d-inline-block align-middle">{{ translate('Below Average') }}</span>
                                                            </div>
                                                            <div class="w-0 flex-grow">
                                                                <div class="progress __h-5px">
                                                                    <div class="progress-bar web--bg-primary" role="progressbar"
                                                                         style="width: <?php echo $widthRating = ($rating[3] != 0) ? ($rating[3] / $overallRating[1]) * 100 : (0); ?>%; background-color: #fea569;"
                                                                         aria-valuenow="9" aria-valuemin="0"
                                                                         aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <span
                                                                    class="{{Session::get('direction') === "rtl" ? 'me-3 float-left' : 'ml-3 float-right'}}">
                                                                    {{ $rating[3] }}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="d-flex align-items-center text-body font-size-sm">
                                                            <div
                                                                class="__rev-txt"><span
                                                                    class="d-inline-block align-middle ">{{ translate('Poor') }}</span>
                                                            </div>
                                                            <div class="w-0 flex-grow">
                                                                <div class="progress __h-5px">
                                                                    <div class="progress-bar web--bg-primary" role="progressbar"
                                                                         style="width: <?php echo $widthRating = ($rating[4] != 0) ? ($rating[4] / $overallRating[1]) * 100 : (0); ?>%;"
                                                                         aria-valuenow="4" aria-valuemin="0"
                                                                         aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <span
                                                                    class="{{Session::get('direction') === "rtl" ? 'me-3 float-left' : 'ml-3 float-right'}}">
                                                                        {{ $rating[4] }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row pb-4 mb-3">
                                                    <div class="__inline-30">
                                                        <span
                                                            class="text-capitalize">{{ translate('Product review') }}</span>
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="row pb-4">
                                                <div class="col-12" id="product-review-list">
                                                    @include('web-views.partials._product-reviews')
                                                </div>

                                                @if($product->total_reviews > 2)
                                                    <div class="col-12">
                                                        <div
                                                            class="card-footer d-flex justify-content-center align-items-center">
                                                            <button class="btn text-white view_more_button web--bg-primary">
                                                                {{ translate('View More') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    @php($companyReliability = getWebConfig('company_reliability'))
                    @if($companyReliability != null)
                        <div class="product-details-shipping-details">
                            @foreach ($companyReliability as $key=>$value)
                                @if ($value['status'] == 1 && !empty($value['title']))
                                    <div class="shipping-details-bottom-border">
                                        <div class="px-3 py-3">
                                            <img
                                                class="{{Session::get('direction') === "rtl" ? 'float-right ml-2' : 'mr-2'}} __img-20"
                                                src="{{ getStorageImages(path: imagePathProcessing(imageData: $value['image'],path: 'company-reliability'), type: 'source', source: 'public/assets/front-end/img'.'/'.$value['item'].'.png') }}"
                                                alt="">
                                            <span>{{ translate($value['title']) }}</span>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @if(getWebConfig(name: 'business_mode')=='multi')
                        <div class="__inline-31">

                            @if($product->added_by=='seller')
                                @if(isset($product->seller->shop))
                                    <div class="row position-relative">
                                        <div class="col-12 position-relative">
                                            <a href="{{route('vendor-shop',['slug'=> $product?->seller?->shop->slug]) }}"
                                               class="d-block">
                                                <div class="d-flex __seller-author align-items-center">
                                                    <div>
                                                        <img class="__img-60 img-circle" alt=""
                                                             src="{{ getStorageImages(path: $product?->seller?->shop->image_full_url, type: 'shop') }}">
                                                    </div>
                                                    <div
                                                        class="ms-2 w-0 flex-grow">
                                                        <h2 class="fs-15 mb-2">
                                                            {{ $product->seller->shop->name }}
                                                            @include('web-views.partials._verified-badge', ['isVerified' => $product->hasVerifiedShopBadge()])
                                                        </h2>
                                                        @include('web-views.partials._store-status-chip', [
                                                            'type' => 'vendor',
                                                            'vendor' => $product->seller->shop,
                                                        ])
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <div class="row d-flex justify-content-between">
                                                <div class="col-6 ">
                                                    <div
                                                        class="d-flex justify-content-center align-items-center rounded __h-79px hr-right-before">
                                                        <div class="text-center">
                                                            <img
                                                                src="{{ theme_asset(path: 'public/assets/front-end/img/rating.svg') }}"
                                                                class="mb-2" alt="">
                                                            <div class="__text-12px text-base">
                                                                <strong>{{ $totalReviews }}</strong>
                                                                {{ translate('Ratings') }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div
                                                        class="d-flex justify-content-center align-items-center rounded __h-79px">
                                                        <div class="text-center">
                                                            <img
                                                                src="{{ theme_asset(path: 'public/assets/front-end/img/products.svg') }}"
                                                                class="mb-2" alt="">
                                                            <div class="__text-12px text-base">
                                                                <strong>{{ $productsForReview->total() }}</strong>
                                                                {{ translate('Products') }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 position-static mt-3">
                                            <div class="chat_with_seller-buttons">
                                                @if (auth('customer')->id())
                                                    <button
                                                        class="btn w-100 d-block text-center web--bg-primary text-white"
                                                        data-toggle="modal"
                                                        data-target="#chatting_modal"
                                                        @if(checkVendorAbility(type: 'vendor', status: 'temporary_close', vendor: $product->seller->shop))
                                                            disabled
                                                        @endif
                                                    >
                                                        <img class="mb-1" alt=""
                                                             src="{{ theme_asset(path: 'public/assets/front-end/img/chat-16-filled-icon.png') }}">
                                                        <span class="d-none d-sm-inline-block text-capitalize">
                                                        {{ translate('Chat with vendor') }}
                                                    </span>
                                                    </button>
                                                @else
                                                    <a href="{{ route('customer.auth.login') }}"
                                                       class="btn w-100 d-block text-center web--bg-primary text-white">
                                                        <img
                                                            src="{{ theme_asset(path: 'public/assets/front-end/img/chat-16-filled-icon.png') }}"
                                                            class="mb-1" alt="">
                                                        <span class="d-none d-sm-inline-block text-capitalize">
                                                        {{ translate('Chat with vendor') }}
                                                    </span>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="row position-relative d-flex justify-content-between">
                                    <div class="col-9">
                                        <a href="{{route('vendor-shop',['slug' => getInHouseShopConfig(key:'slug')]) }}" class="row d-flex align-items-center">
                                            <div>
                                                <img class="__inline-32" alt=""
                                                     src="{{ getStorageImages(path: getInHouseShopConfig(key:'image_full_url'), type: 'shop') }}">
                                            </div>
                                            <div>
                                                <div
                                                    class="{{ session('direction') === "rtl" ? 'right' : 'ml-2' }} get-view-by-onclick"
                                                    data-link="{{ route('vendor-shop',['slug' => getInHouseShopConfig(key:'slug')]) }}">
                                                    <h2 class="font-bold __text-16px mb-1">
                                                        {{ getInHouseShopConfig(key: 'name') }}
                                                        @include('web-views.partials._verified-badge', ['isVerified' => isInHouseShopVerified()])
                                                    </h2>
                                                    @include('web-views.partials._store-status-chip', [
                                                        'type' => 'inhouse',
                                                    ])
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <div class="row d-flex justify-content-between">
                                            <div class="col-6 ">
                                                <div
                                                    class="d-flex justify-content-center align-items-center rounded __h-79px hr-right-before">
                                                    <div class="text-center">
                                                        <img
                                                            src="{{ theme_asset(path: 'public/assets/front-end/img/rating.svg') }}"
                                                            class="mb-2" alt="">
                                                        <div class="__text-12px text-base">
                                                            <strong>{{ $totalReviews}}</strong>
                                                            {{ translate('Ratings') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div
                                                    class="d-flex justify-content-center align-items-center rounded __h-79px">
                                                    <div class="text-center">
                                                        <img
                                                            src="{{ theme_asset(path: 'public/assets/front-end/img/products.svg') }}"
                                                            class="mb-2" alt="">
                                                        <div class="__text-12px text-base">
                                                            <strong>{{ $productsForReview->total() }}</strong>
                                                            {{ translate('Products') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 position-static mt-3">
                                        <div class="chat_with_seller-buttons">
                                            @if (auth('customer')->id())
                                                <button class="btn w-100 d-block text-center web--bg-primary text-white"
                                                        data-toggle="modal" data-target="#chatting_modal"
                                                    {{ checkVendorAbility(type: 'inhouse', status: 'temporary_close') ? 'disabled' : '' }}
                                                >
                                                    <img class="mb-1" alt=""
                                                         src="{{ theme_asset(path: 'public/assets/front-end/img/chat-16-filled-icon.png') }}">
                                                    <span class="d-none d-sm-inline-block text-capitalize">
                                                        {{ translate('Chat with vendor') }}
                                                </span>
                                                </button>
                                            @else
                                                <a href="{{ route('vendor-shop', ['slug' => getInHouseShopConfig(key:'slug')]) }}"
                                                   class="btn w-100 d-block text-center web--bg-primary text-white">
                                                    <img class="mb-1" alt=""
                                                         src="{{ theme_asset(path: 'public/assets/front-end/img/chat-16-filled-icon.png') }}">
                                                    <span class="d-none d-sm-inline-block text-capitalize">
                                                        {{ translate('Chat with vendor') }}
                                                    </span>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h2 class="__text-16px font-bold text-capitalize mb-0">
                            @if(getWebConfig(name: 'business_mode') == 'multi')
                                {{ translate('More from the store') }}
                            @else
                                {{ translate('You may also like') }}
                            @endif
                        </h2>
                        @if($product->added_by == 'seller' && isset($product->seller->shop))
                        <a class="text-capitalize view-all-btn-text" href="{{ route('vendor-shop',['slug'=> $product->seller->shop->slug]) }}">
                            <span class="view-btn-text">{{ translate('View All') }}</span>
                            <i class="czi-view-list"></i>
                        </a>
                        @else
                            <a class="text-capitalize view-all-btn-text" href="{{route('vendor-shop', ['slug'=> getInHouseShopConfig(key: 'slug')]) }}">
                                <span class="view-btn-text">{{ translate('View All') }}</span>
                                <i class="czi-view-list"></i>
                            </a>
                        @endif
                    </div>
                    <div>
                        @foreach($moreProductFromSeller as $item)
                            @include('web-views.partials._seller-products-product-details',['product' => $item, 'decimal_point_settings' => $decimalPointSettings])
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @if (count($relatedProducts)>0)
            <div class="container rtl text-align-direction">
                <div class="card __card border-0 mb-4">
                    <div class="card-body">
                        <div class="row flex-between align-items-center mb-2">
                            <div class="ms-1">
                                <h2 class="text-capitalize font-bold fs-16 mb-0">{{ translate('Similar products') }}</h2>
                            </div>
                            <div class="view_all d-flex justify-content-center align-items-center">
                                <div>
                                    @if(!empty($product?->category?->slug))
                                        <a class="text-capitalize view-all-btn-text me-1"
                                           href="{{ route('category-products', ['slug' => $product?->category?->slug]) }}">
                                            <span class="view-btn-text">{{ translate('View All') }}</span>
                                            <i class="czi-view-list"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            @foreach($relatedProducts as $key => $relatedProduct)
                                <div class="col-xl-2 col-sm-3 col-6">
                                    @include('web-views.partials._inline-single-product-without-eye',['product'=>$relatedProduct,'decimal_point_settings'=>$decimalPointSettings])
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="modal fade rtl text-align-direction" id="show-modal-view" tabindex="-1" role="dialog"
             aria-labelledby="show-modal-image"
             aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body flex justify-content-center">
                        <button class="btn btn-default __inline-33 dir-end-minus-7px"
                                data-dismiss="modal">
                            <i class="fa fa-close"></i>
                        </button>
                        <img class="element-center" id="attachment-view" src="" alt="">
                    </div>
                </div>
            </div>
        </div>

    </div>

    @include("web-views.products._product-details-sticky", ['productDetails' => $product])

    @if($product?->preview_file_full_url['path'])
        @include('web-views.partials._product-preview-modal', ['previewFileInfo' => $previewFileInfo])
    @endif

    @include('layouts.front-end.partials.modal._chatting',['seller'=>$product->seller, 'user_type'=>$product->added_by])

    <span id="route-review-list-product" data-url="{{ route('review.product-list') }}"></span>
    <span id="products-details-page-data" data-id="{{ $product['id'] }}"></span>
    <span id="all-msg-container" data-afterextend="{{translate('See Less')}}" data-seemore="{{translate('See More')}}"></span>
@endsection

@push('script')
    <script>
        $(document).on('change', 'input[name="color"]', function() {
            const selectedColor = $(this).val();
            const colors = @json(json_decode($product->colors));
            const colorIndex = colors.indexOf(selectedColor);
            if (colorIndex !== -1) {
                const mainSwiper = document.querySelector('.quickviewSlider2')?.swiper;
                const thumbSwiper = document.querySelector('.quickviewSliderThumb2')?.swiper;
                mainSwiper?.slideTo(colorIndex);
                thumbSwiper?.slideTo(colorIndex);
            }
        });
    </script>
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/product-details.js') }}" defer></script>
    <script type="text/javascript" async="async"
            src="https://platform-api.sharethis.com/js/sharethis.js#property=5f55f75bde227f0012147049&product=sticky-share-buttons"></script>

    <script>
        $(document).ready(function () {

            function applyShowMoreLogic() {
                $(".tab-pane.active").each(function () {
                    let content = $(this).find(".details-content-wrap");
                    let button  = $(this).find(".see-more-details");

                    if (!content.length || !button.length) return;

                    if (content[0].scrollHeight > 280) {
                        content.addClass(" active");
                        button.show();
                    } else {
                        content.removeClass(" active");
                        button.hide();
                    }
                });
            }

            applyShowMoreLogic();

            $(document).on("shown.bs.tab", ".tab_link", function () {
                applyShowMoreLogic();
            });

            $(document).on("click", ".see-more-details", function () {
                let tabPane = $(this).closest(".tab-pane");
                let content = tabPane.find(".details-content-wrap");
                let productDetailsDescription = tabPane.find(".p-details-description");

                content.toggleClass(" active");

                if (content.hasClass("active")) {
                    $(this).html($("#all-msg-container").data("seemore"));
                    productDetailsDescription.removeClass('overflow-y-auto');
                } else {
                    $(this).html($("#all-msg-container").data("afterextend"));
                    productDetailsDescription.addClass('overflow-y-auto');
                }
            });

        });
    </script>
@endpush
