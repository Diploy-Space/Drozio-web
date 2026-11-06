@php use App\Utils\Helpers;use App\Utils\ProductManager;use Illuminate\Support\Str; @endphp
@php($overallRating = $product?->reviews ? $product?->overall_rating : 0)
<div class="product d-flex flex-column gap-3 get-view-by-onclick" data-link="{{route('product',$product->slug)}}">
    @if($product->discount_value > 0)
        <span class="discount-badge">
            <span>
                -{{ $product->discount_label }}
            </span>
        </span>
    @endif
    <div class="product__top border rounded">

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
               data-bs-toggle="tooltip"
               data-bs-placement="left"
               data-bs-title="{{ translate('Add to compare list') }}"
               data-action="{{route('product-compare.store')}}"
               data-product-id="{{$product['id']}}"
               id="compare_list-{{$product['id']}}">
                <i class="bi bi-repeat"></i>
            </a>
            <a href="javascript:"
               data-action="{{route('quick-view')}}"
               data-product-id="{{$product['id']}}"
               class="btn-quickview stopPropagation get-quick-view" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="{{ translate('Quick view') }}">
                <i class="bi bi-eye"></i>
            </a>
        </div>

        <div>
            <img src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}"
                 loading="lazy" class="img-fit dark-support rounded aspect-1" alt="{{ Str::limit($product['name'], 50) }}">
        </div>
    </div>
    <div class="product__summary d-flex flex-column gap-1 cursor-pointer">
        <h6 class="product__title text-truncate width--80">
            {{ Str::limit($product['name'], 18) }}
        </h6>
        <div class="product__price d-flex flex-wrap align-items-baseline column-gap-2">
            @if($product->discount_value > 0)
                <del class="product__old-price">{{webCurrencyConverter($product->unit_price)}}</del>
            @endif
            <ins class="product__new-price">
                {{ $product->discounted_unit_price_label }}
            </ins>
        </div>
        @include('theme-views.partials._rating-display', [
            'rating' => $overallRating[0],
            'count' => $product->total_reviews,
            'size' => 'sm',
        ])
        <div class="text-muted fs-12">
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
