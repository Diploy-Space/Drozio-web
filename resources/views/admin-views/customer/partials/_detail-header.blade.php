<div class="d-print-none mb-3">
    <h2 class="h1 mb-0 text-capitalize d-flex gap-2">
        <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/add-new-seller.png') }}" alt="">
        {{ translate('Customer details') }}
    </h2>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card card-body h-100">
            <div class="d-flex gap-3 align-items-center justify-content-between flex-wrap mb-2">
                <h3 class="mb-0">{{ translate('Customer').' # '.$customer['id'] }}</h3>
                <p class="mb-0 fs-12">
                    {{ translate('Join Date') }} :
                    <span class="fw-bold">{{ date('d F, Y', strtotime($customer['created_at'])) }}</span>
                </p>
            </div>
            <div class="row g-2">
                <div class="col-xl-6">
                    <div class="bg-section rounded-10 p-2 h-100">
                        <div class="d-flex gap-2 justify-content-between align-items-start">
                            <div class="flex-grow-1 min-w-0 d-flex gap-2 align-items-center flex-wrap">
                                <img src="{{ getStorageImages(path: $customer->image_full_url, type: 'backend-profile') }}"
                                     alt="{{ translate('Image') }}" class="rounded-circle aspect-1 w-60">
                                <div class="flex-grow-1 min-w-0 text-break">
                                    @php($customerFullName = $customer['f_name'].' '.$customer['l_name'])
                                    <h3 class="mb-1 max-w-200" @if (mb_strlen($customerFullName) > 60) data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ $customerFullName }}" @endif>{{ Str::limit($customerFullName, 60) }}</h3>
                                    <p class="fs-12 mb-0">
                                        @if (!empty($customer['phone']))
                                            <a href="tel:{{ $customer['phone'] }}" class="text-decoration-none text-dark">{{ $customer['phone'] }}</a>
                                        @else
                                            {{ translate('No Data Found') }}
                                        @endif
                                    </p>
                                    <p class="fs-12 mb-0">
                                        @if (!empty($customer['email']))
                                            <a href="mailto:{{ $customer['email'] }}" class="text-decoration-none text-dark">{{ $customer['email'] }}</a>
                                        @else
                                            {{ translate('No Data Found') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <a href="#profileUpdateOffcanvas" data-bs-toggle="offcanvas" title="{{ translate('Edit') }}">
                                <i class="fi fi-sr-pencil text-primary fs-16"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="bg-FFF9F0 p-3 rounded-10 h-100 d-flex flex-column justify-content-center">
                        <h2 class="text-warning mb-2 fw-bold">
                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $customer['wallet_balance'])) }}
                        </h2>
                        <p class="fs-12 mb-0">{{ translate('Wallet Balance') }}</p>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="bg-F0FFF3 p-3 rounded-10 h-100 d-flex flex-column justify-content-center">
                        <h2 class="text-warning mb-2 fw-bold">{{ $customer['loyalty_point'] }}</h2>
                        <p class="fs-12 mb-0">{{ translate('Loyalty point') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-body h-100">
            <h3 class="mb-2">{{ translate('Saved Address') }}</h3>
            <div class="bg-section p-3 rounded-10 d-flex gap-2 align-items-center justify-content-between flex-wrap h-100">
                <div class="flex-grow-1">
                    <h2 class="text-info mb-2 fw-bold">{{ count($customer->addresses) }}</h2>
                    <p class="fs-12 mb-0">{{ translate('Address Available') }}</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas"
                        data-bs-target="#savedAddressOffcanvas">{{ translate('View Address') }}</button>
            </div>
        </div>
    </div>
</div>

@if (count($customerModuleTabs) > 0)
    <ul class="nav nav-pills gap-2 mb-3">
        @foreach ($customerModuleTabs as $customerModuleTab)
            <li class="nav-item">
                <a href="{{ $customerModuleTab['url'] }}"
                   class="nav-link rounded-pill px-4 fs-16 {{ $customerModuleTab['is_active'] ? 'active' : 'text-body' }}">
                    {{ $customerModuleTab['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
@endif

@include('admin-views.customer.partials._saved-address-offcanvas')
@include('admin-views.customer.customer-update-partials')
