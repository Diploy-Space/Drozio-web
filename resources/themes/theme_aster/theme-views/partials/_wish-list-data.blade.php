@php use App\Utils\Helpers;use App\Utils\ProductManager; @endphp
<div class="table-responsive">
    <table class="table align-middle table-spacing table-borderless">
        <tbody>
        @if($wishlists->count()>0)
            @foreach($wishlists as $key=>$wishlist)
                @php($product = $wishlist->productFullInfo)
                @if( $wishlist->productFullInfo)
                    @php($wholesaleCard = ($wholesaleWishlistCards ?? [])[$product->id] ?? null)
                    <tr class="bg-light">
                        <td>{{ $wishlists->firstItem() + $key}}</td>
                        <td>
                            <div class="media gap-3 align-items-center mn-w200">
                                <div class="avatar border rounded size-3-437rem">
                                    <img class="img-fit dark-support rounded border aspect-1" alt=""
                                        src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}">
                                </div>
                                <div class="media-body">
                                    <a href="{{ $wholesaleCard['url'] ?? route('product', $product['slug']) }}">
                                        <h6 class="text-truncate text-capitalize width--20ch link-hover-base">
                                            {{ $product['name'] }}
                                        </h6>
                                    </a>
                                    <a href="{{ route('vendor-shop', ['slug' => $wishlistShops[$product->id]['slug'] ?? '']) }}"
                                       class="text-truncate width--10 d-block mt-1 text-secondary-body-color">
                                        {{ $wishlistShops[$product->id]['name'] ?? '' }}
                                        @include('theme-views.partials._verified-badge', ['isVerified' => $wishlistShops[$product->id]['is_verified'] ?? false])
                                    </a>
                                </div>

                            </div>
                        </td>
                        <td>
                            <div class="product__price d-flex flex-wrap align-items-end gap-2">
                                <div class="text-dark fs-12 d-flex gap-2 align-items-baseline wishlist-price">
                                    @if($wholesaleCard)
                                        <div class="d-flex flex-column flex-md-row gap-1 gap-md-2 align-items-start align-items-md-baseline">
                                            <span class="text-nowrap">{{ $wholesaleCard['price_label'] }}</span>
                                            @if($wholesaleCard['original_price_label'])
                                                <del class="text-muted text-nowrap">{{ $wholesaleCard['original_price_label'] }}</del>
                                            @endif
                                        </div>
                                    @else
                                        {!! getPriceRangeWithDiscount(product: $product) !!}
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex justify-content-center gap-2 align-items-center">
                            @if($isRetailWishlist ?? true)
                                <a href="javascript:"
                                    style="--size: 36px"
                                   class="btn btn-outline-primary btn-shadow rounded-circle btn-action add-to-compare compare_list-{{$product['id']}} {{ isProductInCompareList($product->id) ?'compare_list_icon_active':'' }}"
                                   data-product-id ="{{$product['id']}}" data-action="{{route('product-compare.store')}}"
                                   id="compare_list-{{$product['id']}}">
                                    <img src="{{ theme_asset('assets/img/icons/compare.svg') }}" class="svg" alt="">
                                </a>
                                <a href="#" data-action="{{ route('cart.add') }}" data-id="{{$product['id']}}" data-quantity="{{$product['minimum_order_qty'] ?? 1}}" data-key="" data-product_variation_code="{{$wishlist['product_id']}}"
                                   style="--size: 36px"
                                   class="btn btn-outline-primary btn-shadow rounded-circle btn-action add-to-cart-from-wishlist"
                                   data-product-id ="{{$product['id']}}"
                                   id="">
                                    <img src="{{ theme_asset('assets/img/icons/cart.svg') }}" class="svg" alt="">
                                </a>
                            @elseif($wholesaleCard && $wholesaleCard['add_to_cart']['requires_selection'])
                                <a href="{{ $wholesaleCard['url'] }}" style="--size: 36px"
                                   class="btn {{ $wholesaleCard['is_in_cart'] ? 'btn-primary' : 'btn-outline-primary' }} btn-shadow rounded-circle btn-action"
                                   title="{{ translate('Add To Cart') }}">
                                    <img src="{{ theme_asset('assets/img/icons/cart.svg') }}" class="svg" alt="{{ translate('Add To Cart') }}">
                                </a>
                            @elseif($wholesaleCard)
                                <button type="button" style="--size: 36px"
                                        class="btn {{ $wholesaleCard['is_in_cart'] ? 'btn-primary' : 'btn-outline-primary' }} btn-shadow rounded-circle btn-action"
                                        data-wholesale-wishlist-add-to-cart
                                        data-in-cart-class="btn-primary"
                                        data-not-in-cart-class="btn-outline-primary"
                                        data-action="{{ $wholesaleCard['add_to_cart']['action'] }}"
                                        data-product-id="{{ $wholesaleCard['add_to_cart']['product_id'] }}"
                                        data-quantity="{{ $wholesaleCard['add_to_cart']['quantity'] }}"
                                        data-variation="{{ $wholesaleCard['add_to_cart']['variation'] }}"
                                        data-failure-message="{{ translate('Something went wrong') }}"
                                        title="{{ translate('Add To Cart') }}">
                                    <img src="{{ theme_asset('assets/img/icons/cart.svg') }}" class="svg" alt="{{ translate('Add To Cart') }}">
                                </button>
                            @endif
                                <button type="button" data-confirm-text="{{ translate('Okay') }}"
                                        data-wishlist="{{ translate('Wishlist') }}"
                                        data-product-id = "{{$product['id']}}"
                                        data-action="{{ route('wishlists.destroy', ['productId' => $product['id']]) }}"
                                        data-bs-toggle="modal" data-bs-target="#wishlist-modal-{{ $product['id'] }}"
                                        style="--size: 36px"
                                        class="btn btn-outline-danger btn-shadow rounded-circle btn-action">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endif
            @endforeach
        @endif
        </tbody>
    </table>
</div>

@if($wishlists->count()==0)
    <div class="d-flex flex-column justify-content-center align-items-center gap-2 py-3 w-100">
        <img width="80" class="mb-3" src="{{ theme_asset('assets/img/empty-state/empty-wishlist.svg') }}" alt="">
        <h5 class="text-center text-muted">
            {{ translate('You have not added any product to wishlist yet') }}!
        </h5>
    </div>
@endif

<div class="d-flex flex-column gap-2 d-none">
    @if($wishlists->count()>0)
        @foreach($wishlists as $key=>$wishlist)
            @php($product = $wishlist->productFullInfo)
            @if( $wishlist->productFullInfo)
                @php($wholesaleCard = ($wholesaleWishlistCards ?? [])[$product->id] ?? null)
                <div class="media gap-3 bg-light p-3 rounded">
                    <div class="avatar border rounded size-3-437rem">
                        <img
                            src="{{ getStorageImages(path:$product->thumbnail_full_url, type: 'product') }}"
                            class="img-fit dark-support rounded" alt="">
                    </div>
                    <div class="media-body d-flex flex-column gap-1">
                        <a href="{{ $wholesaleCard['url'] ?? route('product', $product['slug']) }}">
                            <h6 class="text-truncate text-capitalize width--10 link-hover-base">
                                {{ $product['name'] }}
                            </h6>
                        </a>
                        <a href="{{ route('vendor-shop', ['slug' => $wishlistShops[$product->id]['slug'] ?? '']) }}"
                           class="text-truncate width--10 d-block mt-1 text-secondary-body-color">
                            {{ $wishlistShops[$product->id]['name'] ?? '' }}
                            @include('theme-views.partials._verified-badge', ['isVerified' => $wishlistShops[$product->id]['is_verified'] ?? false])
                        </a>
                        <div>
                            {{ translate('Price') }} :
                            <div class="product__price d-flex flex-wrap align-items-end gap-2 mb-4 ">
                                <div class="text-primary d-flex gap-2 align-items-center">
                                    @if($wholesaleCard)
                                        <span>{{ $wholesaleCard['price_label'] }}</span>
                                        @if($wholesaleCard['original_price_label'])
                                            <del class="text-muted">{{ $wholesaleCard['original_price_label'] }}</del>
                                        @endif
                                    @else
                                        {!! getPriceRangeWithDiscount(product: $product) !!}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 align-items-center mt-1">
                            @if($isRetailWishlist ?? true)
                            <a href="javascript:"
                               class="btn btn-outline-success rounded-circle btn-action add-to-compare compare_list-{{$product['id']}} {{ isProductInCompareList($product->id) ?'compare_list_icon_active':'' }}"
                               data-product-id ="{{$product['id']}}" data-action="{{route('product-compare.store')}}">
                                <i class="bi bi-repeat"></i>
                            </a>
                            @elseif($wholesaleCard && $wholesaleCard['add_to_cart']['requires_selection'])
                            <a href="{{ $wholesaleCard['url'] }}"
                               class="btn {{ $wholesaleCard['is_in_cart'] ? 'btn-primary' : 'btn-outline-primary' }} rounded-circle btn-action"
                               title="{{ translate('Add To Cart') }}">
                                <i class="bi bi-cart"></i>
                            </a>
                            @elseif($wholesaleCard)
                            <button type="button"
                                    class="btn {{ $wholesaleCard['is_in_cart'] ? 'btn-primary' : 'btn-outline-primary' }} rounded-circle btn-action"
                                    data-wholesale-wishlist-add-to-cart
                                    data-in-cart-class="btn-primary"
                                    data-not-in-cart-class="btn-outline-primary"
                                    data-action="{{ $wholesaleCard['add_to_cart']['action'] }}"
                                    data-product-id="{{ $wholesaleCard['add_to_cart']['product_id'] }}"
                                    data-quantity="{{ $wholesaleCard['add_to_cart']['quantity'] }}"
                                    data-variation="{{ $wholesaleCard['add_to_cart']['variation'] }}"
                                    data-failure-message="{{ translate('Something went wrong') }}"
                                    title="{{ translate('Add To Cart') }}">
                                <i class="bi bi-cart"></i>
                            </button>
                            @endif
                            <button type="button"
                                    data-bs-toggle="modal" data-bs-target="#wishlist-modal-{{ $product['id'] }}"
                                    class="btn btn-outline-danger rounded-circle btn-action" >
                                <i class="bi bi-trash3-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @endif
</div>

<div class="border-0">
    {{ $wishlists->links() }}
</div>

@if($wishlists->count()>0)
    @foreach($wishlists as $key=>$wishlist)
        @php($product = $wishlist->productFullInfo)
        @if( $wishlist->productFullInfo)
            <div class="modal fade" style="--bs-modal-border-radius: 20px;" id="wishlist-modal-{{ $product['id'] }}" tabindex="-1"
                 aria-labelledby="wishlist-modal-{{ $product['id'] }}Label"
                 aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <button type="button" class="btn-close bg-F2F2F2 rounded-circle" style="background-size: 12px;" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body px-20 py-0 mb-30">
                            <form action="{{ route('wishlists.destroy', ['productId' => $product['id'], 'business_module' => $wishlistBusinessModule ?? null]) }}" method="post" class="remove-wishlist-form">
                                @csrf
                                @method('DELETE')
                                <div class="d-flex flex-column align-items-center text-center mb-30">
                                    <img src="{{ theme_asset('assets/img/modal/wishlist-clear.svg') }}" width="70" class="aspect-1 h-70px mb-20" id="custom-modal-1753511225620-image" alt="">
                                    <h4 class="modal-title my-3">
                                        {{ translate('Do you want to clear your wishlist').' ?' }}
                                    </h4>
                                    <div class="text-center px-4">
                                        {{ translate('Clear this product from your wishlist permanently?').' ?' }}
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center gap-3 mt-3">
                                    <button type="button" class="btn btn-light h-45px px-5" style="--bs-btn-bg: #f2f2f2;" data-bs-dismiss="modal">
                                        {{ translate('No') }}
                                    </button>
                                    <button type="submit" class="btn btn-primary h-45px px-5" data-bs-dismiss="modal">
                                        {{ translate('Yes') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endif

@if($wholesaleWishlistCartScript ?? null)
    @push('script')
        <script src="{{ $wholesaleWishlistCartScript }}"></script>
    @endpush
@endif
