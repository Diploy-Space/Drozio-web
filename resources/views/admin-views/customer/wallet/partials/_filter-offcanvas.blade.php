<form action="{{ route('admin.people.customer.wallet.report') }}" method="get" id="filter-form">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasWalletFilter" aria-labelledby="offcanvasWalletFilterLabel" style="--bs-offcanvas-width: 500px;">
        <div class="offcanvas-header bg-body">
            <h3 class="mb-0">{{ translate('Filter') }}</h3>
            <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;" data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}">
                <i class="fi fi-rr-cross"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20 overflow-wrap-anywhere">
                <label class="form-label" for="wallet-filter-date">
                    {{ translate('Date Range') }}
                    <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                        aria-label="{{ translate('Select a date range to view wallet transactions') }}"
                        data-bs-title="{{ translate('Select a date range to view wallet transactions') }}">
                        <i class="fi fi-sr-info"></i>
                    </span>
                </label>
                <div class="position-relative">
                    <span class="fi fi-sr-calendar icon-absolute-on-right"></span>
                    <input
                        type="text"
                        id="wallet-filter-date"
                        class="js-daterangepicker form-control previous-date-true placeholder-mode-true"
                        name="date"
                        value="{{ request('date') }}"
                        placeholder="{{ translate('Start Date') . ' - ' . translate('End Date') }}"
                    >
                </div>
            </div>
            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20 overflow-wrap-anywhere">
                <label class="form-label" for="transaction_type">
                    {{ translate('Transaction Type') }}
                </label>
                <div class="select-wrapper">
                    <select name="transaction_type" id="transaction_type" class="form-select"
                        title="{{ translate('Select transaction type') }}">
                        <option value="">{{ translate('All') }}</option>
                        @foreach($transactionTypes as $value => $label)
                            <option value="{{ $value }}" {{ request('transaction_type') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20 overflow-wrap-anywhere">
                <label class="form-label" for="customer-id">
                    {{ translate('Customer') }}
                </label>
                <select name="customer_id" id="customer-id" class="custom-select">
                    @foreach($customers as $customer)
                        <option value="{{ $customer['id'] }}"
                            {{ (request('customer_id', 'all') === (string) $customer['id']) ? 'selected' : '' }}>
                            {{ $customer['text'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center gap-3 bg-white px-4 py-3">
                <a class="btn btn-secondary w-100" href="{{ route('admin.people.customer.wallet.report') }}">
                    {{ translate('Clear Filter') }}
                </a>
                <button type="submit" class="btn btn-primary w-100">
                    {{ translate('Apply') }}
                </button>
            </div>
        </div>
    </div>
</form>
