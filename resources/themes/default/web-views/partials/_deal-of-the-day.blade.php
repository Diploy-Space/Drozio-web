@php
    $hasDealOfTheDay = isset($dealOfTheDay->product);
    $hasRecommendedProduct = isset($recommendedProduct->discount_type);
    $showcaseProduct = $hasDealOfTheDay ? $dealOfTheDay->product : ($hasRecommendedProduct ? $recommendedProduct : null);

    if ($showcaseProduct) {
        $showcaseSavings = getProductPriceByType(product: $showcaseProduct, type: 'discounted_amount', result: 'value', price: $showcaseProduct->unit_price);
        $showcaseIsInStock = $showcaseProduct->product_type === 'digital' || $showcaseProduct->current_stock > 0;
        $showcaseIsLowStock = $showcaseIsInStock && $showcaseProduct->product_type !== 'digital'
            && $showcaseProduct->current_stock <= (int)getWebConfig(name: 'stock_limit');
    }
@endphp

@if($showcaseProduct || count($latestProductsList ?? []) > 0)
<div class="container rtl">
    <div class="row g-4 pt-0 pb-0 mt-0 __deal-of">
        @if($showcaseProduct)
            @php($overallRating = $showcaseProduct->overall_rating)
            <div class="col-xl-3 col-md-4 pt-0">
                <div class="deal_of_the_day h-100">
                    <div class="deal-showcase-header">
                        <span class="deal-showcase-icon">
                            <i class="tio-flash"></i>
                        </span>
                        <div class="deal-showcase-heading">
                            <h2 class="h6 m-0">
                                {{ $hasDealOfTheDay ? translate('Deal of the day') : translate('Recommended product') }}
                            </h2>
                            @if($hasDealOfTheDay && $dealOfTheDay->title)
                                <p class="m-0">{{ $dealOfTheDay->title }}</p>
                            @endif
                        </div>
                    </div>

                    <a class="deal-showcase-media" href="{{ route('product', $showcaseProduct->slug) }}">
                        <img loading="lazy" alt="{{ $showcaseProduct['name'] }}"
                             src="{{ getStorageImages(path: $showcaseProduct->thumbnail_full_url, type: 'product') }}">
                        @if($showcaseProduct->discount_value > 0)
                            <span class="deal-showcase-discount">
                                <span class="direction-ltr d-block">-{{ $showcaseProduct->discount_label }}</span>
                            </span>
                        @endif
                    </a>

                    <div class="deal-showcase-body">
                        @include('web-views.partials._rating-display', [
                            'rating' => $overallRating[0],
                            'count' => $showcaseProduct->total_reviews,
                            'size' => 'sm',
                            'reserve' => true,
                            'align' => 'center',
                        ])

                        <h3 class="deal-showcase-title">
                            <a href="{{ route('product', $showcaseProduct->slug) }}">{{ $showcaseProduct['name'] }}</a>
                        </h3>

                        <div class="deal-showcase-price">
                            <span class="current">{{ $showcaseProduct->discounted_unit_price_label }}</span>
                            @if($showcaseProduct->discount_value > 0)
                                <del>{{ webCurrencyConverter(amount: $showcaseProduct->unit_price) }}</del>
                            @endif
                        </div>

                        <ul class="deal-showcase-facts">
                            @if($showcaseSavings > 0)
                                <li>
                                    <span>{{ translate('You save') }}</span>
                                    <strong class="text-success">{{ webCurrencyConverter(amount: $showcaseSavings) }}</strong>
                                </li>
                            @endif
                            <li>
                                <span>{{ translate('Availability') }}</span>
                                @if(!$showcaseIsInStock)
                                    <strong class="text-danger">{{ translate('Out of Stock') }}</strong>
                                @elseif($showcaseIsLowStock)
                                    <strong class="text-warning">{{ translate('Limited Stock') }}</strong>
                                @else
                                    <strong class="text-success">{{ translate('In Stock') }}</strong>
                                @endif
                            </li>
                        </ul>

                        <a class="btn btn--primary font-bold rounded-10 deal-showcase-cta"
                           href="{{ route('product', $showcaseProduct->slug) }}">
                            {{ translate('Grab This Deal') }}
                        </a>
                    </div>
                </div>
            </div>
        @endif

        @if(count($latestProductsList ?? []) > 0)
        <div class="{{ $showcaseProduct ? 'col-xl-9 col-md-8' : 'col-12' }}">
            <div class="latest-product-margin">
                <div class="d-flex justify-content-between align-items-baseline mb-14px">
                    <h2 class="text-center mb-0">
                    <span class="for-feature-title __text-22px font-bold text-center">
                        {{ translate('Latest Products')}}
                    </span>
                    </h2>
                    <div class="mr-1">
                        <a class="text-capitalize view-all-btn-text" href="{{ route('latest-products') }}">
                            <span class="view-btn-text">{{ translate('View All') }}</span>
                            <i class="czi-view-list"></i>
                        </a>
                    </div>
                </div>

                <div class="row mt-0 g-2">
                    @php($latestProductsListIndex=0)
                    @foreach($latestProductsList as $product)
                        @if($latestProductsListIndex < 8)
                            @php($latestProductsListIndex++)
                            <div class="col-xl-3 col-sm-4 col-md-6 col-lg-4 col-6 product-with-bg">
                                <div class="h-100">
                                    @include('web-views.partials._inline-single-product',['product'=>$product,'decimal_point_settings'=>$decimal_point_settings])
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endif
