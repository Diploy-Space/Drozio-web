<div class="modal fade pos-modal" id="clearCartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none" data-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center px-4 px-sm-5 pb-5">
                <div class="mb-30">
                    <img width="64" height="64" src="{{ dynamicAsset(path: 'public/assets/back-end/img/modal/clear-cart.png') }}" alt="">
                </div>

                <h3>{{ translate('Are you sure you want to clear your cart') }} ?</h3>
                <p class="mb-30">{{ translate('This action will permanently remove all items from your current cart') }}. </p>

                <form action="{{ route('vendor.retail.pos.clear-cart-ids') }}" method="GET">
                    @csrf
                    <div class="d-flex justify-content-center gap-3 pb-0">
                        <button type="button" class="btn btn-secondary min-w-120" data-dismiss="modal">
                            {{ translate('No') }}
                        </button>
                        <button type="submit" class="btn btn-danger min-w-120">
                            {{ translate('Yes clear') }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
