<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <h6 class="d-flex align-items-center gap-2 mb-0">
        <i class="bi bi-cart-fill text-primary fs-18"></i>
        {{ translate('Shopping cart') }}
    </h6>
    <button type="button" class="btn-link border-0 bg-transparent text-muted d-flex align-items-center gap-1 fs-14 p-0 close-cart-btn" data-module-cart-close>
        {{ translate('Close') }}
        <i class="bi bi-x-lg fs-12"></i>
    </button>
</div>
<div class="d-flex flex-column gap-3">
    @foreach($headerModuleCarts ?? [] as $moduleCart)
        <div class="bg-light rounded-3">
            <div class="d-flex justify-content-between align-items-center gap-3 px-3 py-2 border-bottom">
                <h6 class="mb-0">{{ $moduleCart['label'] }}</h6>
                <a href="{{ $moduleCart['view_url'] }}" class="btn btn-primary btn-sm text-nowrap">{{ translate('View cart') }}</a>
            </div>
            <div class="d-flex justify-content-between align-items-center gap-3 px-3 py-2">
                <div class="d-flex gap-5">
                    <div class="d-flex flex-column">
                        <span class="fs-12 text-muted">{{ translate('Total product') }}</span>
                        <span class="fw-bold">{{ $moduleCart['item_count'] }}</span>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="fs-12 text-muted">{{ translate('Total amount') }}</span>
                        <span class="fw-bold">{{ $moduleCart['total_amount_label'] }}</span>
                    </div>
                </div>
                @if($moduleCart['item_count'] > 0)
                    <form action="{{ $moduleCart['clear_url'] }}" method="{{ $moduleCart['is_clear_post'] ? 'post' : 'get' }}" class="m-0">
                        @if($moduleCart['is_clear_post'])
                            @csrf
                        @endif
                        <button type="submit" class="btn btn-link text-danger fw-semibold fs-14 p-0 text-nowrap text-decoration-none">{{ translate('Clear cart') }}</button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
</div>
