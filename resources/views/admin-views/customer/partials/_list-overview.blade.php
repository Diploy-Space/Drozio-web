<div class="card card-body mb-20">
    <h3 class="mb-20">{{ translate('Customer Overview') }}</h3>
    <div class="row g-3">
        <div class="col-lg-6 col-xl-3">
            <a class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded"
               href="{{ route('admin.people.customer.list', $overviewQuery) }}">
                <div class="d-flex gap-3 align-items-center">
                    <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/total-customer.png') }}"
                         alt="">
                    <h4 class="mb-0">{{ translate('Total Customers') }}</h4>
                </div>
                <span class="text-primary h3 mb-0 overflow-wrap-anywhere">{{ $totalCustomers }}</span>
            </a>
        </div>
        <div class="col-lg-6 col-xl-3">
            <a class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded"
               href="{{ route('admin.people.customer.list', ['is_active' => 1, ...$overviewQuery]) }}">
                <div class="d-flex gap-3 align-items-center">
                    <img width="20"
                         src="{{ dynamicAsset(path: 'public/assets/back-end/img/report-vendor-active.png') }}" alt="">
                    <h4 class="mb-0">{{ translate('Active') }}</h4>
                </div>
                <span class="text-success h3 mb-0 overflow-wrap-anywhere">{{ $activeCustomers }}</span>
            </a>
        </div>
        <div class="col-lg-6 col-xl-3">
            <a class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded"
               href="{{ route('admin.people.customer.list', ['is_active' => 0, ...$overviewQuery]) }}">
                <div class="d-flex gap-3 align-items-center">
                    <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/cancel-icon.png') }}"
                         alt="">
                    <h4 class="mb-0">{{ translate('Blocked') }}</h4>
                </div>
                <span class="text-danger h3 mb-0 overflow-wrap-anywhere">{{ $blockedCustomers }}</span>
            </a>
        </div>
        <div class="col-lg-6 col-xl-3">
            <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded">
                <div class="d-flex gap-3 align-items-center">
                    <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/add-new-employee.png') }}"
                         alt="">
                    <h4 class="mb-0">{{ translate('New This Month') }}</h4>
                </div>
                <span class="text-info h3 mb-0 overflow-wrap-anywhere">{{ $newCustomersThisMonth }}</span>
            </div>
        </div>
    </div>
</div>
