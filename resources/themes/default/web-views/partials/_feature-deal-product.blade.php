@if(isset($product))
    @php($overallRating = $product?->overall_rating)
    <div class="flash_deal_product get-view-by-onclick" data-link="{{ route('product',$product->slug) }}">
        @if($product->discount > 0)
            <div class="d-flex">
            <span class="for-discount-value p-1 pl-2 pr-2 font-bold fs-13">
                <span class="direction-ltr d-block">
                    @if ($product->discount_type == 'percent')
                        -{{round($product->discount,(!empty($decimal_point_settings) ? $decimal_point_settings: 0))}}%
                    @elseif($product->discount_type =='flat')
                        -{{ webCurrencyConverter(amount: $product->discount) }}
                    @endif
                </span>
            </span>
            </div>
        @endif
        <div class=" d-flex">
            <div class="d-flex align-items-center justify-content-center p-12px">
                <div class="flash-deals-background-image">
                    <img class="__img-125px" alt=""
                         src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}">
                </div>
            </div>
            <div class="flash_deal_product_details pl-3 pr-3 pr-1 d-flex mt-3">
                <div>
                    <a href="{{route('product',$product->slug)}}" class="text-capitalize fw-semibold">
                        {{ Str::limit($product['name'], 23) }}
                    </a>
                    @include('web-views.partials._rating-display', [
                        'rating' => $overallRating[0],
                        'count' => $product->total_reviews,
                        'size' => 'sm',
                        'reserve' => true,
                    ])
                    <div class="d-flex flex-wrap gap-8 align-items-center row-gap-0">
                        @if($product->discount > 0)
                            <del class="category-single-product-price">
                                {{ webCurrencyConverter(amount: $product->unit_price) }}
                            </del>
                        @endif
                        <span class="flash-product-price text-dark fw-semibold">
                            {{ webCurrencyConverter(amount: $product->unit_price - getProductDiscount(product: $product, price: $product->unit_price)) }}
                        </span>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endif
