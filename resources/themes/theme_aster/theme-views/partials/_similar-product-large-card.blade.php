@php use App\Utils\Helpers;use App\Utils\ProductManager;use Illuminate\Support\Str; @endphp
@php($overallRating = $product->reviews ? $product?->overall_rating : 0)
<div class="product border rounded text-center d-flex flex-column gap-10"
     onclick="location.href='{{route('product',$product->slug)}}'">
    <div class="product__top width--100 height-12-5-rem aspect-1">
        @if($product->discount > 0)
            <span class="product__discount-badge">
                -@if ($product->discount_type == 'percent')
                    {{round($product->discount, $web_config['decimal_point_settings'])}}%
                @elseif($product->discount_type =='flat')
                    {{webCurrencyConverter($product->discount)}}
                @endif
            </span>
        @endif
        @if(isset($product->flash_deal_status) && $product->flash_deal_status)
            <div class="product__power-badge">
                <img src="{{ theme_asset('assets/img/svg/power.svg') }}" alt=""
                     class="svg text-white">
            </div>
        @endif

        <div class="product__actions d-flex flex-column gap-2">
            <a href="javascript:"
               data-action="{{route('wishlists.store')}}"
               data-product-id="{{$product['id']}}"
               id="wishlist-{{$product['id']}}"
               class="btn-wishlist stopPropagation add-to-wishlist wishlist-{{$product['id']}} {{ isProductInWishList($product->id) ?'wishlist_icon_active':'' }}"
               data-bs-toggle="tooltip"
               data-bs-placement="left"
               data-bs-title="{{ translate('Add to wishlist') }}">
                <i class="bi bi-heart"></i>
            </a>
            <a href="javascript:"
               data-action="{{route('product-compare.store')}}"
               data-product-id="{{$product['id']}}"
               class="btn-compare stopPropagation add-to-compare compare_list-{{$product['id']}} {{ isProductInCompareList($product->id) ?'compare_list_icon_active':'' }}"
               data-bs-toggle="tooltip"
               data-bs-placement="left"
               data-bs-title="{{ translate('Add to compare') }}"
               id="compare_list-{{$product['id']}}">
                <i class="bi bi-repeat"></i>
            </a>
        </div>

        <div class="product__thumbnail align-items-center d-flex h-100 justify-content-center">
            <img src="{{ getStorageImages(path:$product->thumbnail_full_url, type: 'product') }}"
                 loading="lazy" class="dark-support rounded-10 h-100 object-fit-cover" alt="{{ Str::limit($product['name'], 25) }}">
        </div>
        @if(($product['product_type'] == 'physical') && ($product['current_stock'] < 1))
            <div class="product__notify">
                {{ translate('Sorry this item is currently sold out') }}
            </div>
        @endif

        @if(isset($product->flash_deal_status) && $product->flash_deal_status)
            <div class="product__countdown d-flex gap-2 gap-sm-3 justify-content-center"
                 data-date="{{ $product->flash_deal_end_at ?? $product->flash_deal_end_date }}">
                <div class="days d-flex flex-column gap-2"></div>
                <div class="hours d-flex flex-column gap-2"></div>
                <div class="minutes d-flex flex-column gap-2"></div>
                <div class="seconds d-flex flex-column gap-2"></div>
            </div>
        @endif
    </div>
    <div class="product__summary d-flex flex-column align-items-center gap-1 pb-3  cursor-pointer">
        @include('theme-views.partials._rating-display', [
            'rating' => $overallRating[0],
            'count' => $product->total_reviews,
            'size' => 'sm',
        ])
        <h3 class="text-muted fs-12">
            @if($product->added_by=='seller')
                {{ isset($product->seller->shop->name) ? Str::limit($product->seller->shop->name, 20) : '' }}
                @include('theme-views.partials._verified-badge', ['isVerified' => $product->hasVerifiedShopBadge(), 'size' => 14])
            @elseif($product->added_by=='admin')
                {{getInHouseShopConfig(key:'name')}}
                @include('theme-views.partials._verified-badge', ['isVerified' => isInHouseShopVerified(), 'size' => 14])
            @endif
        </h3>

        <h4 class="product__title text-truncate h6">
            {{ Str::limit($product['name'], 25) }}
        </h4>

        <h5 class="product__price d-flex flex-wrap column-gap-2">
            @if($product->discount > 0)
                <del class="product__old-price">{{webCurrencyConverter($product->unit_price)}}</del>
            @endif
            <ins class="product__new-price">
                {{webCurrencyConverter($product->unit_price-Helpers::getProductDiscount($product,$product->unit_price))}}
            </ins>
        </h5>
    </div>
</div>
