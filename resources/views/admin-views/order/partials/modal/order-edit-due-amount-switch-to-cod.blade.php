<form action="{{ route('admin.retail.orders.customer-due-amount') }}" method="POST">
    @csrf
    <input type="hidden" name="order_id" value="{{ $order['id'] }}">
    <input type="hidden" name="order_due_amount" value="{{ $order['edit_due_amount'] ?? 0 }}">
    <div class="modal fade" id="switchToCODModal" tabindex="-1" aria-labelledby="switchToCODModal"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn btn-circle border-0 fs-12 text-body bg-section2 shadow-none"
                            style="--size: 2rem;" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fi fi-sr-cross d-flex"></i>
                    </button>
                </div>
                <div class="modal-body px-20 py-0 mb-30">
                    <div class="d-flex flex-column align-items-center text-center mb-30">
                        <img src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/icons/cod.png') }}"
                             width="64" class="aspect-1 mb-20" alt="">
                        <h2 class="modal-title mb-3">{{ translate('Switch to Cash on Delivery') }}?</h2>
                        <div>
                            {{ translate('Before switching this order to') }}
                            <strong>{{ translate('Cash On Delivery') }} (COD),</strong>
                            {{ translate('Please confirm the payment issue with the customer to avoid any misunderstandings') }}
                        </div>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-secondary py-3 w-100"
                                data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary py-3 w-100"
                                data-bs-dismiss="modal">{{ translate('Confirm') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
