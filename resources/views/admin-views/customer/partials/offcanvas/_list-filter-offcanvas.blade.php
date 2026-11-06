<form action="{{ route('admin.people.customer.list') }}" method="GET">
    <input type="hidden" name="searchValue" value="{{ request('searchValue') }}">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCustomerListFilter"
         aria-labelledby="offcanvasCustomerListFilterLabel" style="--bs-offcanvas-width: 500px;">
        <div class="offcanvas-header bg-body">
            <h3 class="mb-0">{{ translate('Filter') }}</h3>
            <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;"
                    data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="fi fi-rr-cross"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20 overflow-wrap-anywhere">
                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label" for="order_date">
                            {{ translate('Order Date') }}
                            <span class="tooltip-icon" data-bs-toggle="tooltip"
                                  data-bs-title="{{ translate('Shows customers who ordered within this date range') }}">
                                <i class="fi fi-sr-info text-9B9B9B fs-12"></i>
                            </span>
                        </label>
                        <div class="position-relative">
                            <span class="fi fi-sr-calendar icon-absolute-on-right"></span>
                            <input type="text" name="order_date" id="order_date"
                                   class="js-daterangepicker form-control previous-date-true placeholder-mode-true"
                                   value="{{ request('order_date') }}"
                                   placeholder="{{ translate('Start Date') . ' - ' . translate('End Date') }}" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="customer_joining_date">
                            {{ translate('Customer Joining Date') }}
                            <span class="tooltip-icon" data-bs-toggle="tooltip"
                                  data-bs-title="{{ translate('Shows customers who joined within this date range') }}">
                                <i class="fi fi-sr-info text-9B9B9B fs-12"></i>
                            </span>
                        </label>
                        <div class="position-relative">
                            <span class="fi fi-sr-calendar icon-absolute-on-right"></span>
                            <input type="text" name="customer_joining_date" id="customer_joining_date"
                                   class="js-daterangepicker form-control previous-date-true placeholder-mode-true"
                                   value="{{ request('customer_joining_date') }}"
                                   placeholder="{{ translate('Start Date') . ' - ' . translate('End Date') }}" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="order_business_module">
                            {{ translate('Business Module') }}
                            <span class="tooltip-icon" data-bs-toggle="tooltip"
                                  data-bs-title="{{ translate('Shows customers who placed orders in this business module') }}">
                                <i class="fi fi-sr-info text-9B9B9B fs-12"></i>
                            </span>
                        </label>
                        <div class="select-wrapper">
                            <select class="form-select" name="order_business_module" id="order_business_module">
                                <option value="" {{ request('order_business_module') ? '' : 'selected' }}>
                                    {{ translate('All') }}
                                </option>
                                @foreach ($orderBusinessModules as $businessModuleKey => $businessModuleLabel)
                                    <option value="{{ $businessModuleKey }}" {{ request('order_business_module') === $businessModuleKey ? 'selected' : '' }}>
                                        {{ $businessModuleLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20 overflow-wrap-anywhere">
                <label class="form-label">{{ translate('Customer Status') }}</label>
                <div class="bg-white rounded p-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="d-flex gap-2 flex-grow-1">
                            <input class="form-check-input radio--input m-0" type="radio" name="is_active"
                                   id="customerStatusAll" value=""
                                {{ in_array(request('is_active'), ['0', '1'], true) ? '' : 'checked' }}>
                            <label class="form-check-label fs-12 cursor-pointer" for="customerStatusAll">
                                {{ translate('All') }}
                            </label>
                        </div>
                        <div class="d-flex gap-2 flex-grow-1">
                            <input class="form-check-input radio--input m-0" type="radio" name="is_active"
                                   id="customerStatusActive" value="1"
                                {{ request('is_active') === '1' ? 'checked' : '' }}>
                            <label class="form-check-label fs-12 cursor-pointer" for="customerStatusActive">
                                {{ translate('Active') }}
                            </label>
                        </div>
                        <div class="d-flex gap-2 flex-grow-1">
                            <input class="form-check-input radio--input m-0" type="radio" name="is_active"
                                   id="customerStatusBlocked" value="0"
                                {{ request('is_active') === '0' ? 'checked' : '' }}>
                            <label class="form-check-label fs-12 cursor-pointer" for="customerStatusBlocked">
                                {{ translate('Blocked') }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20 overflow-wrap-anywhere">
                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label" for="sort_by">{{ translate('Sort By') }}</label>
                        <div class="select-wrapper">
                            <select class="form-select" name="sort_by" id="sort_by">
                                <option value="" {{ request('sort_by') ? '' : 'selected' }}>
                                    {{ translate('Default') }}
                                </option>
                                <option value="desc" {{ request('sort_by') === 'desc' ? 'selected' : '' }}>
                                    {{ translate('Newest first') }}
                                </option>
                                <option value="asc" {{ request('sort_by') === 'asc' ? 'selected' : '' }}>
                                    {{ translate('Oldest first') }}
                                </option>
                                <option value="order_amount" {{ request('sort_by') === 'order_amount' ? 'selected' : '' }}>
                                    {{ translate('Highest order amount') }}
                                </option>
                                <option value="order_count" {{ request('sort_by') === 'order_count' ? 'selected' : '' }}>
                                    {{ translate('Most orders') }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="choose_first">
                            {{ translate('Choose First') }}
                            <span class="tooltip-icon" data-bs-toggle="tooltip"
                                  data-bs-title="{{ translate('Limits the result to this many customers') }}">
                                <i class="fi fi-sr-info text-9B9B9B fs-12"></i>
                            </span>
                        </label>
                        <input type="number" class="form-control" min="1" id="choose_first" name="choose_first"
                               value="{{ request('choose_first') }}" placeholder="{{ translate('Ex') }} : 100">
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center gap-3 bg-white px-4 py-3">
                <a class="btn btn-secondary w-100" href="{{ route('admin.people.customer.list') }}">
                    {{ translate('Clear Filter') }}
                </a>
                <button type="submit" class="btn btn-primary w-100">
                    {{ translate('Apply') }}
                </button>
            </div>
        </div>
    </div>
</form>
