<div class="modal fade" id="deleteModal-{{$brand['id']}}" tabindex="-1" aria-labelledby="deleteModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                <button type="button" class="btn btn-circle border-0 fs-12 text-body bg-section2 shadow-none" style="--size: 2rem;" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fi fi-sr-cross"></i>
                </button>
            </div>
            <div class="modal-body px-20 py-0 mb-30">
                <div class="d-flex flex-column align-items-center text-center mb-30">
                    <img src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/delete.png') }}" width="80" class="mb-20" id="" alt="">
                    <h2 class="modal-title mb-3" id="">{{ translate('Are you sure you want to delete this :item', ['item' => translate('Brand')]) }}?</h2>
                    <div class="text-center" id="">{{ translate('Deleting this brand removes its data permanently.') }} </div>
                </div>
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-secondary max-w-120 flex-grow-1" data-bs-dismiss="modal">
                        {{ translate('No') }}
                    </button>
                    <button type="button" class="btn btn-danger max-w-120 flex-grow-1 delete-brand"
                            title="{{ translate('Delete') }}"
                            data-product-count = "{{ $brand['brand_all_products_count'] }}"
                            data-text="{{ translate('There were :count products under this brand. Please update their brand from the below list before deleting this one.', ['count' => $brand['brand_all_products_count']]) }}"
                            id="{{ $brand['id'] }}">
                        {{ translate('Yes Delete') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
