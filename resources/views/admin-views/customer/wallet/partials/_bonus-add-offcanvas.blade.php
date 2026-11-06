<form action="{{ route('admin.people.customer.wallet.bonus-setup') }}" id="add-wallet-bonus-setup" method="post"
      class="bonus-setup-form form-advance-validation form-advance-inputs-validation form-advance-file-validation"
      novalidate="novalidate"
      data-currency-symbol="{{ getCurrencySymbol(currencyCode: getCurrencyCode(type: 'default')) }}"
      data-date-range-message="{{ translate('End date can not be earlier than start date') }}">
    @csrf
    <div class="offcanvas offcanvas-end" tabindex="-1" id="bonusAddOffcanvas" aria-labelledby="bonusAddOffcanvasLabel"
         style="--bs-offcanvas-width: 500px;">
        <div class="offcanvas-header gap-3 justify-content-between bg-body">
            <h3 class="mb-0 flex-grow-1">{{ translate('Add New Bonus') }}</h3>
            <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;"
                    data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}">
                <i class="fi fi-rr-cross"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            @include('admin-views.customer.wallet.partials._bonus-form-fields', ['bonus' => null, 'fieldId' => 'add'])
        </div>
        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center gap-3 bg-white px-3 py-2">
                <button type="reset" class="btn btn-secondary w-100">{{ translate('Reset') }}</button>
                <button type="button" class="btn btn-primary w-100 form-submit"
                        data-form-id="add-wallet-bonus-setup">{{ translate('Submit') }}</button>
            </div>
        </div>
    </div>
</form>
