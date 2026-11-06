@php use App\Utils\Helpers;use App\Utils\ProductManager;use Illuminate\Support\Str; @endphp
@php($overallRating = $product?->reviews ? $product?->overall_rating : 0)
<div class="product border shadow-none rounded text-center d-flex flex-column gap-3 ov-hidden cursor-pointer h-100 min-h-inherit get-view-by-onclick"
     data-link="{{route('product',$product->slug)}}">
     @if($product->discount_value > 0)
         <span class="discount-badge">
             <span>
                  -{{ $product->discount_label }}
             </span>
         </span>
     @endif
    <div class="product__top width-100-16px mt-2 mx-auto aspect-1 border rounded-10">
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
               class="btn-compare stopPropagation add-to-compare compare_list-{{$product['id']}} {{ isProductInCompareList($product->id) ?'compare_list_icon_active':'' }}"
               data-action="{{route('product-compare.store')}}"
               data-product-id="{{$product['id']}}"
               id="compare_list-{{$product['id']}}" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="{{translate('Add to compare list')}}">
                <i class="bi bi-repeat"></i>
            </a>
            <a href="javascript:" class="btn-quickview stopPropagation get-quick-view"
               data-action="{{route('quick-view')}}"
               data-product-id="{{$product['id']}}" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="{{ translate('Quick view') }}">
                <i class="bi bi-eye"></i>
            </a>
        </div>

        <div class="product__thumbnail align-items-center d-flex h-100 justify-content-center">
            <img class="dark-support rounded-10 h-100 object-fit-cover" alt="{{ Str::limit($product['name'], 25) }}"
                 src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}">
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
    <div class="product__summary d-flex flex-column align-items-start text-start gap-1 pb-2 px-2 cursor-pointer">
        <h6 class="product__title text-truncate fs-16">
            {{ Str::limit($product['name'], 25) }}
        </h6>
        <div class="product__price d-flex justify-content-start align-items-baseline flex-wrap column-gap-2">
            @if($product->discount_value > 0)
                <del class="product__old-price fs-14">{{webCurrencyConverter($product->unit_price)}}</del>
            @endif
            <ins class="product__new-price fs-16 fw-extra-bold">
                {{ $product->discounted_unit_price_label }}
            </ins>
        </div>
        @include('theme-views.partials._rating-display', [
            'rating' => $overallRating[0],
            'count' => $product->total_reviews,
            'size' => 'sm',
        ])
        <div class="text-muted fs-14">
            @if($product->added_by=='seller')
                {{ isset($product->seller->shop->name) ? Str::limit($product->seller->shop->name, 20) : '' }}
                @include('theme-views.partials._verified-badge', ['isVerified' => $product->hasVerifiedShopBadge(), 'size' => 14])
            @elseif($product->added_by=='admin')
                {{getInHouseShopConfig(key:'name')}}
                @include('theme-views.partials._verified-badge', ['isVerified' => isInHouseShopVerified(), 'size' => 14])
            @endif
        </div>
    </div>
</div>
