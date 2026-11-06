<div class="col-lg-6 px-max-md-0">
    <div class="card card __shadow h-100">
        <div class="card-body p-xl-35">
            <div class="row d-flex justify-content-between align-items-center mx-1 mb-3">
                <div class="d-flex gap-1 align-items-center">
                    <img loading="lazy"  class="size-30" src="{{theme_asset(path: "public/assets/front-end/png/best-sellings.png")}}"
                         alt="{{ translate('Best sellings')}}">
                    <h2 class="font-bold pl-1 mb-0 fs-16">{{ translate('Best sellings')}}</h2>
                </div>
                <div>
                    <a class="text-capitalize view-all-btn-text" href="{{ route('best-selling-products') }}">
                        <span class="view-btn-text">{{ translate('View All') }}</span>
                        <i class="czi-view-list"></i>
                    </a>
                </div>
            </div>
            <div class="row g-3">
                @foreach($bestSellProduct as $key=> $bestSellItem)
                    @if($bestSellItem && $key<6)
                        <div class="col-sm-6">
                            <a class="__best-selling" href="{{route('product',$bestSellItem->slug)}}">
                                <div class="d-flex flex-wrap">
                                    <div class="best-selleing-image">
                                        <img loading="lazy" class="rounded"
                                             src="{{ getStorageImages(path: $bestSellItem?->thumbnail_full_url, type: 'product') }}"
                                             alt="{{ translate('Product') }}"/>
                                    </div>
                                    <div class="best-selling-details">
                                        <h3 class="widget-product-title h6">
                                        <span class="ptr fs-14 fw-semibold">
                                            {{ Str::limit($bestSellItem['name'],100) }}
                                        </span>
                                        </h3>
                                        @if(getProductPriceByType(product: $bestSellItem, type: 'discount', result: 'value') > 0)
                                            <div class="d-flex">
                                            <span class="for-discount-value p-1 pl-2 pr-2 font-bold fs-13">
                                                <span class="direction-ltr d-block">
                                                    -{{ getProductPriceByType(product: $bestSellItem, type: 'discount', result: 'string') }}
                                                </span>
                                            </span>
                                            </div>
                                        @endif
                                        <h4 class="widget-product-meta d-flex flex-wrap gap-8 align-items-center row-gap-0 mb-0 letter-spacing-0">
                                            <span>
                                                @if(getProductPriceByType(product: $bestSellItem, type: 'discount', result: 'value') > 0)
                                                <del class="__color-9B9B9B __text-12px">
                                                    {{ webCurrencyConverter(amount: $bestSellItem->unit_price) }}
                                                </del>
                                                @endif
                                            </span>
                                            <span class="text-accent text-dark fs-15">
                                                {{ getProductPriceByType(product: $bestSellItem, type: 'discounted_unit_price', result: 'string') }}
                                            </span>
                                        </h4>
                                        @php($overallRating = getOverallRating($bestSellItem['reviews']))
                                        @include('web-views.partials._rating-display', [
                                            'rating' => $overallRating[0],
                                            'count' => $overallRating[1],
                                            'size' => 'sm',
                                        ])
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
