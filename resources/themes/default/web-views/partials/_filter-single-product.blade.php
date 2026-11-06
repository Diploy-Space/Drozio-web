@php($overallRating = $product?->overall_rating)

<div class="product-single-hover style--card h-100 {{ $cardModifier ?? '' }}">
    <div class="overflow-hidden position-relative">
        <div class=" inline_product clickable d-flex justify-content-center">
            @if($product->discount_value > 0)
                <span class="for-discount-value p-1 pl-2 pr-2 font-bold fs-13">
                    <span class="direction-ltr d-block">
                        -{{ $product->discount_label }}
                    </span>
                </span>
            @else
                <div class="d-flex justify-content-end">
                    <span class="for-discount-value-null"></span>
                </div>
            @endif
            <div class="d-flex pb-0">
                <a href="{{route('product',$product->slug)}}" class="w-100 rounded">
                    <img loading="lazy" decoding="async" alt="{{ $product['name'] }}" class="border border-black-50" src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}">
                </a>
            </div>

            <div class="quick-view">
                <div class="d-none d-md-flex gap-2 align-items-center quick-view-tag">
                    @if($product->product_type == 'digital')
                        <div class="bg-white btn-circle" style="--size: 26px" data-toggle="tooltip" title="{{ translate('Digital Product') }}" data-placement="left">
                            <img loading="lazy" class="h-16px aspect-1 svg" src="{{theme_asset(path: "public/assets/front-end/img/icons/digital-product.svg")}}" alt="{{ translate('Digital Product') }}">
                        </div>
                    @else
                        <div class="bg-white btn-circle" style="--size: 26px" data-toggle="tooltip" title="{{ translate('Physical Product') }}" data-placement="left">
                            <img loading="lazy" class="h-16px aspect-1 svg" src="{{theme_asset(path: "public/assets/front-end/img/icons/physical-product.svg")}}" alt="{{ translate('Physical Product') }}">
                        </div>
                    @endif
                </div>
                <a class="btn-circle stopPropagation action-product-quick-view" href="javascript:" data-product-id="{{ $product->id }}">
                    <i class="czi-eye align-middle"></i>
                </a>
            </div>
            @if($product->product_type == 'physical' && $product->current_stock <= 0)
                <span class="out_fo_stock">{{translate('Out of Stock')}}</span>
            @endif
        </div>
        <div class="single-product-details">
            <h4 class="text-center mb-1 lh-1 letter-spacing-0 fs-14 fw-semibold">
                <a href="{{route('product',$product->slug)}}">
                    {{ $product['name'] }}
                </a>
            </h4>
            <div class="text-center mb-1">
                <h5 class="product-price text-center d-flex flex-wrap justify-content-center align-items-baseline gap-1 mb-0 lh-1 letter-spacing-0">
                    @if($product->discount_value > 0)
                        <del class="category-single-product-price font-weight-normal fs-14">
                            {{ webCurrencyConverter(amount: $product->unit_price) }}
                        </del>
                    @endif
                    <span class="text-accent text-dark fs-15">
                        {{ $product->discounted_unit_price_label }}
                    </span>
                </h5>
            </div>
            @include('web-views.partials._rating-display', [
                'rating' => $overallRating[0],
                'count' => $product->total_reviews,
                'size' => 'sm',
                'align' => 'center',
            ])
            @if(($showSavings ?? false) && $product->discount_value > 0)
                @php($savingsAmount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $product->unit_price))
                @if($savingsAmount > 0)
                    <div class="product-savings text-center mb-1">
                        {{ translate('You save') }} <strong>{{ webCurrencyConverter(amount: $savingsAmount) }}</strong>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
