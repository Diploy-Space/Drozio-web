<div class="modal fade" id="holdOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center px-4 px-sm-5 pb-30">
                <div class="mb-30">
                    <img width="64" height="64" src="{{ dynamicAsset(path: 'public/assets/back-end/img/modal/hold-order.png') }}" alt="">
                </div>

                <h3>{{ translate('Want to hold this order') }}?</h3>
                <p class="mb-30">{{ translate('Save this order on hold for future checkout') }}</p>

                <form action="{{ route('admin.retail.pos.new-cart-id') }}" method="POST">
                    @csrf
                    <div class="d-flex justify-content-center gap-3 pb-2">
                        <button type="button" class="btn btn-secondary min-w-120 h-40" data-bs-dismiss="modal">
                            {{ translate('No') }}
                        </button>
                        <button type="submit" class="btn btn-danger min-w-120 h-40">
                            {{ translate('Yes hold') }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

