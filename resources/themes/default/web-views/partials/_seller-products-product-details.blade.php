@if(isset($product))
@php($overallRating = $product?->overall_rating)
<div class="flash_deal_product rtl cursor-pointer mb-2 get-view-by-onclick"
    data-link="{{ route('product',$product->slug) }}">
    @if($product->discount > 0)
    <div class="d-flex position-absolute z-2">
        <span class="for-discount-value p-1 pl-2 pr-2 font-bold fs-13">
            <span class="direction-ltr d-block">
                @if ($product->discount_type == 'percent')
                    -{{ round($product->discount,(!empty($decimalPointSettings) ? $decimalPointSettings: 0))}}%
                @elseif($product->discount_type =='flat')
                    -{{ webCurrencyConverter(amount: $product->discount) }}
                @endif
            </span>
        </span>
    </div>
    @endif
    <div class="d-flex">
        <div class="d-flex align-items-center justify-content-center p-2">
            <div class="image-default-bg-color">
                <img class="__img-100" alt="" src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}">
            </div>
        </div>
        <div class=" flash_deal_product_details pl-2 pr-3 pr-1 d-flex align-items-center">
            <div class="w-100">
                <div>
                    <h3 class="flash-product-title">
                        {{$product['name']}}
                    </h3>
                </div>
                <h4 class="d-flex flex-wrap gap-8 align-items-center row-gap-0 mb-0 fs-14 line-height-base">
                    @if($product->discount > 0)
                        <del class="category-single-product-price">
                            {{ webCurrencyConverter(amount: $product->unit_price) }}
                        </del>
                    @endif
                    <span class="flash-product-price fw-semibold text-dark">
                        {{ webCurrencyConverter(amount: $product->unit_price-getProductDiscount(product: $product, price: $product->unit_price)) }}
                    </span>
                </h4>
                @include('web-views.partials._rating-display', [
                    'rating' => $overallRating[0],
                    'count' => $product->total_reviews,
                    'size' => 'sm',
                ])
            </div>
        </div>
    </div>
</div>
@endif
