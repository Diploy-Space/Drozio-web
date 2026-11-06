<form action="{{ url()->current() }}" method="GET">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="reviewFilterOffcanvas"
         aria-labelledby="reviewFilterOffcanvasLabel" style="--bs-offcanvas-width: 500px;">
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
                        <label for="" class="form-label mb-2">
                            {{ translate('Show Data') }}
                        </label>
                        <div class="select-wrapper">
                            <select name="" id="" class="form-select date-type-select">
                                <option value="">{{ translate('All Time') }}</option>
                                <option value="custom" selected>{{ translate('Custom Date') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-6 custom-date-div d--none">
                        <div>
                            <label for="start-date-time" class="form-label fs-12 mb-2">{{ translate('Start Date') }}</label>
                            <input type="date" name="from" id="start-date-time"
                                   value="{{ request('from') }}"
                                   class="form-control"
                                   title="{{ translate('From date') }}">
                        </div>
                    </div>
                    <div class="col-sm-6 custom-date-div d--none">
                        <div>
                            <label for="end-date-time" class="form-label fs-12 mb-2">{{ translate('End Date') }}</label>
                            <input type="date" name="to" id="end-date-time"
                                   value="{{ request('to') }}"
                                   class="form-control"
                                   title="{{ translate('To date') }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20 overflow-wrap-anywhere">
                <div class="mb-20">
                    <label for="name" class="form-label mb-2">{{ translate('Products')}}</label>
                    <div class="dropdown select-product-search w-100">
                        <input type="text" class="product_id" name="product_id" value="{{request('product_id')}}"
                               hidden>
                        <button
                            class="form-control d-flex justify-content-between align-items-center gap-2 text-start dropdown-toggle text-truncate pe-10px select-product-button"
                            data-bs-toggle="dropdown" type="button">
                            {{ $product?->name ?? translate('Select Product') }}
                        </button>
                        <div class="dropdown-menu w-100 px-2">
                            <div class="input-group">
                                <input type="search"
                                       data-route="{{ route('admin.people.reviews.search-product', ['business_module' => $activeBusinessModule]) }}"
                                       class="js-form-search form-control search-bar-input search-review-product"
                                       placeholder="{{translate('Search Product').'...'}}">
                                <div class="input-group-append search-submit">
                                    <button type="submit">
                                        <i class="fi fi-rr-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div
                                class="d-flex flex-column gap-3 max-h-40vh overflow-y-auto overflow-x-hidden search-result-box">
                                @include('admin-views.partials._search-product',['products' => $products])
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-20">
                    <label for="name" class="form-label mb-2">{{ translate('Vendor')}}</label>
                    <div class="dropdown select-vendor-search w-100">
                        <input type="hidden" class="vendor_id" name="vendor_id" value="{{ request('vendor_id') }}"
                               hidden>
                        <button
                            class="form-control d-flex justify-content-between align-items-center gap-2 text-start dropdown-toggle text-truncate pe-10px select-vendor-button"
                            data-bs-toggle="dropdown" type="button">
                            @if(request('vendor_id') != null)
                                {{ request('vendor_id') == 0 ? getInHouseShopConfig(key: 'name') : ($vendor?->name ?? translate('Select Vendor')) }}
                            @else
                                {{ translate('Select Vendor') }}
                            @endif
                        </button>
                        <div class="dropdown-menu w-100 px-2">
                            <div class="input-group">
                                <input type="search"
                                       data-route="{{ route('admin.people.reviews.search-vendor', ['business_module' => $activeBusinessModule]) }}"
                                       class="js-form-search form-control search-bar-input search-review-vendor"
                                       placeholder="{{translate('Search vendor').'...'}}">
                                <div class="input-group-append search-submit">
                                    <button type="submit">
                                        <i class="fi fi-rr-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div
                                class="d-flex flex-column max-h-200 overflow-y-auto overflow-x-hidden search-review-vendor-result-box">
                                @include('admin-views.reviews._review-vendors', ['shopList' => $shopList])
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-20">
                    <label class="form-label mb-2" for="customer">{{translate('Customer')}}</label>
                    <input type="hidden" id='customer-id' name="customer_id"
                           value="{{ request('customer_id') ?? 'all' }}">
                    <select name="customer_id" class="custom-select">
                        @foreach($customers as $customer)
                            <option value="{{ $customer['id'] }}"
                                {{ (request('customer_id', 'all') === (string) $customer['id']) ? 'selected' : '' }}>
                                {{ $customer['text'] }}
                            </option>
                        @endforeach
                    </select>

                </div>
                <div class="">
                    <div>
                        <label for="status" class="form-label mb-2">
                            {{ translate('Status') }}
                        </label>
                        <select class="custom-select" name="status">
                            <option value="" selected> {{ '---'.translate('Select status').'---' }} </option>
                            <option value="1" {{ !is_null($status) && $status == 1 ? 'selected' : '' }}>
                                {{ translate('Active') }}</option>
                            <option value="0" {{ !is_null($status) && $status == 0 ? 'selected' : '' }}>
                                {{ translate('Inactive') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center gap-3 bg-white px-3 py-2">
                <a href="{{ route('admin.people.reviews.list', ['business_module' => $activeBusinessModule]) }}"
                   class="btn btn-secondary w-100">
                    {{ translate('Clear Filter') }}
                </a>
                <button type="submit" class="btn btn-primary w-100">{{ translate('Apply') }}</button>
            </div>
        </div>
    </div>
</form>
