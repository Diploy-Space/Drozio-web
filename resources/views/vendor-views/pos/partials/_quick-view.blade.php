@php
    $variations = json_decode($product->retailDetail?->variation ?? '[]', true) ?? [];
    $hasVariations = is_array($variations) && count($variations) > 0;
    $allVariationsOutOfStock = false;

    if ($hasVariations) {
        $currentStock = $variations[0]['qty'] ?? 0;
        $currentPrice = $variations[0]['price'] ?? 0;
        $allVariationsOutOfStock = collect($variations)->every(fn ($variation) => empty($variation['qty']));
    } else {
        $currentStock = max(0, $product->current_stock);
        $currentPrice = $product->unit_price;
    }

    $isDigital = $product->product_type === 'digital';
    $isOutOfStock = ($hasVariations && $allVariationsOutOfStock) || $currentStock < 1;
    $imageSources = (!$isDigital && !empty($product->retailDetail?->color_image) && count($product->color_images_full_url) > 0)
        ? $product->color_images_full_url
        : $product->images_full_url;
@endphp

<div class="modal-body pos-qv">
    <button type="button" class="pos-qv__close close-quick-view-modal" data-dismiss="modal"
            aria-label="{{ translate('Close') }}">
        <i class="fi fi-rr-cross-small d-flex"></i>
    </button>

    <div class="pos-qv__grid">
        <div class="pos-qv__media">
            <div class="pd-img-wrap position-relative">
                <span class="pos-qv__type" data-toggle="tooltip" data-placement="right"
                      title="{{ $isDigital ? translate('Digital Product') : translate('Physical Product') }}">
                    <img height="16" class="aspect-1 svg" alt=""
                         src="{{ dynamicAsset(path: 'public/assets/front-end/img/icons/'.($isDigital ? 'digital' : 'physical').'-product.svg') }}">
                </span>

                <div class="swiper-container quickviewSlider2 pos-qv__stage">
                    <div class="swiper-wrapper">
                        @foreach ($imageSources as $photo)
                            @php
                                $imagePath = isset($photo['image_name'])
                                    ? getStorageImages(path: $photo['image_name'], type: 'backend-product')
                                    : getStorageImages(path: $photo, type: 'backend-product');
                            @endphp
                            <div class="swiper-slide position-relative" data-color="{{ $photo['color'] ?? '' }}">
                                <div class="easyzoom easyzoom--overlay is-ready">
                                    <a href="{{ $imagePath }}">
                                        <img class="aspect-1" alt="{{ $product->name }}" src="{{ $imagePath }}">
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="user-select-none">
                    <div class="quickviewSliderThumb2 swiper-container position-relative active-border pos-qv__thumbs">
                        <div class="swiper-wrapper auto-item-width justify-content-start">
                            @foreach ($imageSources as $photo)
                                @php
                                    $imagePath = isset($photo['image_name'])
                                        ? getStorageImages(path: $photo['image_name'], type: 'backend-product')
                                        : getStorageImages(path: $photo, type: 'backend-product');
                                @endphp
                                <div class="swiper-slide position-relative" role="group">
                                    <img class="aspect-1" alt="" src="{{ $imagePath }}">
                                </div>
                            @endforeach
                        </div>

                        <div class="swiper-button-next swiper-quickview-button-next"></div>
                        <div class="swiper-button-prev swiper-quickview-button-prev"></div>
                    </div>
                </div>
            </div>

            <dl class="pos-qv__meta">
                <div>
                    <dt>{{ translate('SKU') }}</dt>
                    <dd class="pos-num">{{ $product->code }}</dd>
                </div>
                <div>
                    <dt>{{ translate('Categories') }}</dt>
                    <dd>{{ $product->category->name ?? translate('Not found') }}</dd>
                </div>

                @if (!$isDigital && $product?->brand)
                    <div>
                        <dt>{{ translate('Brand') }}</dt>
                        <dd>{{ $product->brand->name }}</dd>
                    </div>
                @endif

                @if ($isDigital)
                    @php
                        $selectedAuthorNames = collect($digitalProductAuthors)->whereIn('id', $productAuthorIds)->pluck('name')->toArray();
                        $selectedPublisherNames = collect($publishingHouseRepo)->whereIn('id', $productPublishingHouseIds)->pluck('name')->toArray();
                    @endphp
                    @if (!empty($selectedAuthorNames))
                        <div>
                            <dt>{{ translate('Author') }}</dt>
                            <dd>{{ implode(', ', $selectedAuthorNames) }}</dd>
                        </div>
                    @endif
                    @if (!empty($selectedPublisherNames))
                        <div>
                            <dt>{{ translate('Publisher') }}</dt>
                            <dd>{{ implode(', ', $selectedPublisherNames) }}</dd>
                        </div>
                    @endif
                @endif

                @if (count($product->tags) > 0)
                    <div>
                        <dt>{{ translate('Tag') }}</dt>
                        <dd class="pos-qv__tags">
                            @foreach ($product->tags->take(3) as $tag)
                                <span><i class="fi fi-rr-tags"></i>{{ Str::limit($tag->tag, 15, '...') }}</span>
                            @endforeach
                            @if ($product->tags->count() > 3)
                                <span class="pos-qv__tags-more" title="{{ $product->tags->skip(3)->pluck('tag')->implode(', ') }}">+{{ $product->tags->count() - 3 }} {{ translate('More') }}</span>
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        <form id="add-to-cart-form" class="add-to-cart-details-form pos-qv__panel">
            @csrf
            <input type="hidden" name="id" value="{{ $product->id }}">

            <div class="pos-qv__scroll">
                <div class="details">
                    <div class="pos-qv__chips">
                        @if(!$isDigital)
                            <span class="pos-qv__chip">
                                {{ translate('Stock') }}
                                <b class="current-stock-qty pos-num">{{ $currentStock }}</b>
                            </span>
                            <span class="pos-qv__chip pos-qv__chip--state stock-status-in-quick-view {{ $currentStock > 0 ? 'text-success' : 'text-danger' }}">
                                {{ $currentStock > 0 ? translate('In Stock') : translate('Out of Stock') }}
                            </span>
                        @endif
                        @if ($product->discount > 0)
                            @php($hasServerRenderedDiscount = $product->discount_type === 'percent')
                            <span class="pos-qv__chip pos-qv__chip--offer set-discount-wrapper {{ $hasServerRenderedDiscount ? 'd-flex' : 'd-none' }}"
                                  @if(!$hasServerRenderedDiscount) style="display: none;" @endif>
                                <span class="set-discount-amount discounted_badge">{{ $hasServerRenderedDiscount ? $product->discount.'% '.translate('OFF') : '' }}</span>
                            </span>
                        @endif
                    </div>

                    <h2 class="product-title pos-qv__title">{{ $product->name }}</h2>

                    @if ($product->reviews_count > 0)
                        <div class="pos-qv__reviews">
                            <i class="fi fi-sr-star text-warning"></i>
                            <span>{{ $product->reviews_count.' '.translate('Ratings') }}</span>
                        </div>
                    @endif

                    <div class="pos-qv__price price-range-with-discount">
                        <span class="discounted-unit-price">
                            {{ getProductPriceByType(product: $product, type: 'discounted_unit_price', result: 'string') }}
                        </span>
                        @if (getProductPriceByType(product: $product, type: 'discount', result: 'value') > 0)
                            <del class="product-total-unit-price">{{ webCurrencyConverter(amount: $product->unit_price) }}</del>
                        @endif
                    </div>
                </div>

                <div class="variant-change pos-qv__variants">
                    @if (count(json_decode($product->colors)) > 0)
                        <div class="pos-qv__option">
                            <span class="pos-qv__option-label">{{ translate('Color') }}</span>
                            <div class="color-select d-flex gap-2 flex-wrap" id="option1">
                                @foreach (json_decode($product->colors) as $key => $color)
                                    <input class="btn-check action-color-change" type="radio"
                                           id="{{ $product->id }}-color-{{ $key }}" name="color"
                                           value="{{ $color }}" data-color="{{ $color }}"
                                           @if ($key == 0) checked @endif autocomplete="off">
                                    <label id="label-{{ $product->id }}-color-{{ $key }}"
                                           class="color-ball mb-0 {{ $key == 0 ? 'border-add' : '' }}"
                                           style="background: {{ $color }};"
                                           for="{{ $product->id }}-color-{{ $key }}" data-toggle="tooltip">
                                        <i class="fi fi-sr-check fs-10"></i>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @foreach (json_decode($product->choice_options) as $choice)
                        <div class="pos-qv__option">
                            <span class="pos-qv__option-label">{{ ucfirst($choice->title) }}</span>
                            <div class="d-flex gap-2 flex-wrap">
                                @foreach ($choice->options as $index => $option)
                                    <input class="btn-check" type="radio"
                                           id="{{ $choice->name }}-{{ $option }}" name="{{ $choice->name }}"
                                           value="{{ $option }}"
                                           @if ($index == 0) checked @endif autocomplete="off">
                                    <label class="check-label pos-check-label" for="{{ $choice->name }}-{{ $option }}">
                                        <span class="text-nowrap max-w-180 line-1">{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @php($extensionIndex = 0)
                    @if ($isDigital && $product->retailDetail?->digital_product_file_types && count($product->retailDetail?->digital_product_file_types) > 0 && $product->retailDetail?->digital_product_extensions)
                        @foreach ($product->retailDetail?->digital_product_extensions as $extensionKey => $extensionGroup)
                            @if (count($extensionGroup) > 0)
                                <div class="pos-qv__option">
                                    <span class="pos-qv__option-label">{{ translate($extensionKey) }}</span>
                                    <div class="d-flex gap-2 flex-wrap">
                                        @foreach ($extensionGroup as $extension)
                                            <input class="btn-check" type="radio"
                                                   id="extension_{{ str_replace(' ', '-', $extension) }}"
                                                   name="variant_key"
                                                   value="{{ $extensionKey.'-'.preg_replace('/\s+/', '-', $extension) }}"
                                                   {{ $extensionIndex == 0 ? 'checked' : '' }} autocomplete="off">
                                            <label class="check-label pos-check-label"
                                                   for="extension_{{ str_replace(' ', '-', $extension) }}">
                                                <span class="text-nowrap max-w-180 line-1">{{ $extension }}</span>
                                            </label>
                                            @php($extensionIndex++)
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>

                @if (filled(strip_tags($product->details ?? '')))
                    <div class="pos-qv__description">
                        <span class="pos-qv__option-label">{{ translate('Description') }}</span>
                        <div class="pos-qv__description-body rich-editor-html-content">{!! $product->details !!}</div>
                        <button type="button" class="pos-qv__description-toggle d-none"
                                data-more-label="{{ translate('See More') }}"
                                data-less-label="{{ translate('See Less') }}">{{ translate('See More') }}</button>
                    </div>
                @endif
            </div>

            <div class="pos-qv__footer price-section">
                <div class="alert alert--message flex-row alert-dismissible fade show pos-alert-message pos-bg-warning fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center justify-content-between d-none"
                     role="alert">
                    <div class="d-flex gap-2 align-items-center">
                        <img class="mb-1" src="{{ dynamicAsset(path: 'public/assets/back-end/img/warning-icon.png') }}"
                             alt="{{ translate('Warning') }}">
                        <div class="w-0">
                            <h6>{{ translate('Warning') }}</h6>
                            <div class="product-stock-message"></div>
                        </div>
                    </div>
                    <a href="javascript:" class="align-items-center close-alert-message">
                        <i class="fi fi-sr-cross-small"></i>
                    </a>
                </div>

                <div class="pos-qv__checkout">
                    <div class="default-quantity-system d-none">
                        <span class="pos-qv__option-label">{{ translate('Quantity') }}</span>
                        <span class="product-quantity-group pos-qv__stepper">
                            <button type="button" class="btn-number" data-type="minus" data-field="quantity" disabled>
                                <i class="fi fi-sr-minus fs-10"></i>
                            </button>
                            <input type="text" name="quantity" class="input-number cart-qty-field pos-num"
                                   placeholder="1" value="1" min="1" max="{{ $currentStock }}">
                            <button type="button" class="btn-number cart-qty-field-plus" data-type="plus" data-field="quantity">
                                <i class="fi fi-sr-plus fs-10"></i>
                            </button>
                        </span>
                    </div>

                    <div class="in-cart-quantity-system d--none">
                        <span class="pos-qv__option-label">{{ translate('Quantity') }}</span>
                        <span class="product-quantity-group pos-qv__stepper">
                            <button type="button" class="btn-number in-cart-quantity-minus action-get-variant-for-already-in-cart"
                                    data-action="minus">
                                <i class="fi fi-sr-minus fs-10"></i>
                            </button>
                            <input type="text" name="quantity_in_cart" class="in-cart-quantity-field pos-num"
                                   placeholder="1" value=""
                                   oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value < 1) this.value = 1;"
                                   maxlength="4">
                            <button type="button" class="btn-number in-cart-quantity-plus action-get-variant-for-already-in-cart"
                                    data-action="plus">
                                <i class="fi fi-sr-plus fs-10"></i>
                            </button>
                        </span>
                    </div>

                    <div class="pos-qv__total">
                        <span class="product-description-label">{{ translate('Total price') }}</span>
                        <strong class="product-details-chosen-price-amount pos-num"></strong>
                    </div>
                </div>

                <button class="quick-view-modal-add-cart-button action-add-to-cart pos-qv__submit" type="button"
                        {{ $isOutOfStock ? 'disabled' : '' }}>
                    <i class="fi fi-sr-shopping-cart d-flex"></i>
                    <span class="quick-view-add-cart-label">
                        {{ $isOutOfStock ? translate('Out of Stock') : translate('Add To Cart') }}
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
