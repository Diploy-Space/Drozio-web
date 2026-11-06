<form action="{{ route('admin.people.customer.wallet.add-fund') }}" method="post"
    enctype="multipart/form-data" id="add-fund"
    class="form-advance-validation form-advance-inputs-validation" novalidate="novalidate">
    @csrf
    <div class="offcanvas offcanvas-end" tabindex="-1" id="add-fund-modal"
        aria-labelledby="add-fund-modal" style="--bs-offcanvas-width: 500px;">
        <div class="offcanvas-header bg-body">
            <h3 class="mb-0">{{ translate('Add Fund to Customer Wallet') }}</h3>
            <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;"
                    data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="fi fi-rr-cross"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20 overflow-wrap-anywhere">
                <div class="form-group mb-20">
                    <label class="form-label mb-2" for="add-fund-customer">
                        {{ translate('Select Customer') }} <span class="text-danger">*</span>
                    </label>
                    <select name="customer_id" id="add-fund-customer" class="custom-select"
                            data-required-msg="{{ translate('Select a customer to add fund') }}">
                        <option value="" disabled selected>{{ translate('Choose customer to add fund') }}</option>
                        @foreach($sidebarCustomers as $customer)
                            <option value="{{ $customer['id'] }}"
                                {{ (request('customer_id', 'all') === (string) $customer['id']) ? 'selected' : '' }}>
                                {{ $customer['text'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label mb-2" for="amount">
                        {{ translate('Amount') }}
                        ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                        <span class="text-danger">*</span>
                    </label>
                    <input type="number" class="form-control" name="amount" id="amount"
                        step=".01" placeholder="{{ translate('Ex') . ':' . '500' }}" data-required-msg="{{ translate('Amount field is required')}}" required>
                    <small id="amount_error" class="text-danger d--none">
                        {{ translate('Amount cannot be zero or negative') }}
                    </small>
                </div>
            </div>
            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20 overflow-wrap-anywhere">
                <div class="form-group mb-0">
                    <label class="form-label mb-2 d-flex align-items-center gap-1"
                        for="reference">{{ translate('Reference') }}
                        <small>({{ translate('Optional') }})</small></label>
                    <input type="text" class="form-control" name="reference"
                        placeholder="{{ translate('Ex') . ' : ' . translate('Amount for Reword') }}" id="reference" data-maxlength="30">
                    <div class="d-flex justify-content-end">
                        <span class="text-body-light">0/30</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center gap-3 bg-white px-3 py-2">
                <button type="button" class="btn btn-secondary w-100"
                        data-bs-dismiss="offcanvas" aria-label="Close">
                    {{ translate('Cancel') }}
                </button>

                <button type="submit" class="btn btn-primary w-100">
                    {{ translate('Add Fund') }}
                </button>
            </div>
        </div>
    </div>
</form>
