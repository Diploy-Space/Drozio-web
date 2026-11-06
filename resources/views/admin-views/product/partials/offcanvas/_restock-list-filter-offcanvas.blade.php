<form action="{{ url()->current() }}" method="GET">
    <input type="hidden" name="searchValue" value="{{ request('searchValue') }}">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRestockFilter"
        aria-labelledby="offcanvasRestockFilterLabel" style="--bs-offcanvas-width: 500px;">
        <div class="offcanvas-header bg-body">
            <h3 class="mb-0">{{ translate('Filter') }}</h3>
            <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;"
                data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="fi fi-rr-cross"></i>
            </button>
        </div>
        <div class="offcanvas-body">
           <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20 overflow-wrap-anywhere">
                <div class="row g-3 date-filter-wrapper">
                    <div class="col-12">
                        <label class="form-label mb-2">
                            {{ translate('Show Data') }}
                        </label>
                        <div class="select-wrapper">
                            <select name="date_type" class="form-select date-type-select">
                                    <option value="">{{ translate('All Time') }}</option>
                                    <option value="custom" {{ request('date_type') === 'custom' ? 'selected' : '' }}>{{ translate('Custom Date') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-6 custom-date-div d--none">
                        <div>
                            <label for="start-date-time" class="form-label fs-12 mb-2">{{ translate('Start Date') }}</label>
                            <input type="date" name="from" id="start-date-time" value="{{ request('from') }}" class="form-control"
                                   title="{{translate('From date')}}">
                        </div>
                    </div>
                    <div class="col-sm-6 custom-date-div d--none">
                        <div>
                            <label for="end-date-time" class="form-label fs-12 mb-2">{{ translate('End Date') }}</label>
                            <input type="date" name="to" id="end-date-time" value="{{ request('to') }}" class="form-control"
                                   title="{{translate('To date')}}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center gap-3 bg-white px-3 py-2">
                <a class="btn btn-secondary w-100" href="{{ url()->current() }}">
                    {{ translate('Clear Filter') }}
                </a>
                <button type="submit" class="btn btn-primary w-100">
                    {{ translate('Apply') }}
                </button>
            </div>
        </div>
    </div>
</form>
