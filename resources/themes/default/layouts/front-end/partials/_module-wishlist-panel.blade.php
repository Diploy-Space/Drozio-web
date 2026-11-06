<div class="hm-cart__head">
    <h6 class="hm-cart__title">
        <i class="fi fi-sr-heart text-primary"></i>
        <span class="text-capitalize">{{ translate('Wishlist') }}</span>
    </h6>
    <a href="javascript:" class="hm-module-cart__close" data-module-wishlist-close>
        <span>{{ translate('Close') }}</span>
        <i class="czi-close"></i>
    </a>
</div>

<div class="hm-cart__body hm-module-cart__list">
    @foreach($headerModuleWishlists as $moduleWishlist)
        <div class="hm-module-cart">
            <div class="hm-module-cart__head">
                <h6 class="hm-module-cart__title">{{ $moduleWishlist['label'] }}</h6>
                <a href="{{ $moduleWishlist['view_url'] }}" class="btn btn--primary btn-sm">{{ translate('View wishlist') }}</a>
            </div>
            <div class="hm-module-cart__foot">
                <div class="hm-module-cart__figures">
                    <div class="hm-module-cart__figure">
                        <span class="hm-module-cart__label">{{ translate('Total product') }}</span>
                        <span class="hm-module-cart__value">{{ $moduleWishlist['item_count'] }}</span>
                    </div>
                </div>
                @if($moduleWishlist['item_count'] > 0)
                    <form action="{{ $moduleWishlist['clear_url'] }}" method="post" class="hm-module-cart__clear-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="hm-module-cart__clear">{{ translate('Clear wishlist') }}</button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
</div>
