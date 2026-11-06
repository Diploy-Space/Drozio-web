<div class="hm-cart__head">
    <h6 class="hm-cart__title">
        <i class="fi fi-sr-shopping-cart text-primary"></i>
        <span class="text-capitalize">{{ translate('Shopping cart') }}</span>
    </h6>
    <a href="javascript:" class="hm-module-cart__close" data-module-cart-close>
        <span>{{ translate('Close') }}</span>
        <i class="czi-close"></i>
    </a>
</div>

<div class="hm-cart__body hm-module-cart__list">
    @foreach($headerModuleCarts ?? [] as $moduleCart)
        <div class="hm-module-cart">
            <div class="hm-module-cart__head">
                <h6 class="hm-module-cart__title">{{ $moduleCart['label'] }}</h6>
                <a href="{{ $moduleCart['view_url'] }}" class="btn btn--primary btn-sm">{{ translate('View cart') }}</a>
            </div>
            <div class="hm-module-cart__foot">
                <div class="hm-module-cart__figures">
                    <div class="hm-module-cart__figure">
                        <span class="hm-module-cart__label">{{ translate('Total product') }}</span>
                        <span class="hm-module-cart__value">{{ $moduleCart['item_count'] }}</span>
                    </div>
                    <div class="hm-module-cart__figure">
                        <span class="hm-module-cart__label">{{ translate('Total amount') }}</span>
                        <span class="hm-module-cart__value">{{ $moduleCart['total_amount_label'] }}</span>
                    </div>
                </div>
                @if($moduleCart['item_count'] > 0)
                    <form action="{{ $moduleCart['clear_url'] }}" method="{{ $moduleCart['is_clear_post'] ? 'post' : 'get' }}" class="hm-module-cart__clear-form">
                        @if($moduleCart['is_clear_post'])
                            @csrf
                        @endif
                        <button type="submit" class="hm-module-cart__clear">{{ translate('Clear cart') }}</button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
</div>
