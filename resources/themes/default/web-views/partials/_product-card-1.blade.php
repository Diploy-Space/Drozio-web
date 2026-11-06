@if(isset($product))
    @php($overallRating = $product?->overall_rating)
    <div class="flash_deal_product get-view-by-onclick"
         data-link="{{ route('product', $product->slug) }}">


        @if($product->discount_value > 0)
            <span class="for-discount-value p-1 pl-2 pr-2 font-bold fs-13">
               <span class="direction-ltr d-block">
                   -{{ $product->discount_label }}
               </span>
           </span>
        @else
            <span class="for-discount-value-null"></span>
        @endif


        <div class="d-flex">
            <div class="d-flex align-items-center justify-content-center p-12px">
                <div class="flash-deals-background-image">
                    <img loading="lazy" class="__img-125px" alt="{{ $product['name'] }}"
                         src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}">
                </div>
            </div>

            <div class="flash_deal_product_details pl-0 pr-3 pr-1 d-flex mt-3">
                <div>
                    <div>
                        <a href="{{ route('product', $product->slug) }}"
                           class="flash-product-title text-capitalize fw-semibold">
                            {{ $product['name'] }}
                        </a>
                    </div>

                    <div class="d-flex flex-wrap gap-8 align-items-center row-gap-0">
                        @if($product->discount_value > 0)
                            <del class="category-single-product-price">
                                {{ webCurrencyConverter(amount: $product->unit_price) }}
                            </del>
                        @endif
                        <span class="flash-product-price text-dark fw-semibold">
                           {{ $product->discounted_unit_price_label }}
                       </span>
                    </div>


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
