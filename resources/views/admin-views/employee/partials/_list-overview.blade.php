<div class="card card-body mb-20">
    <h3 class="mb-20">{{ translate('Employee Overview') }}</h3>
    <div class="row g-3">
        <div class="col-lg-6 col-xl-3">
            <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded">
                <div class="d-flex gap-3 align-items-center">
                    <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/employee.png') }}" alt="">
                    <h4 class="mb-0">{{ translate('Total Employees') }}</h4>
                </div>
                <span class="text-primary h3 mb-0 overflow-wrap-anywhere">{{ $totalEmployees }}</span>
            </div>
        </div>
        <div class="col-lg-6 col-xl-3">
            <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded">
                <div class="d-flex gap-3 align-items-center">
                    <img width="20"
                         src="{{ dynamicAsset(path: 'public/assets/back-end/img/report-vendor-active.png') }}" alt="">
                    <h4 class="mb-0">{{ translate('Active') }}</h4>
                </div>
                <span class="text-success h3 mb-0 overflow-wrap-anywhere">{{ $activeEmployees }}</span>
            </div>
        </div>
        <div class="col-lg-6 col-xl-3">
            <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded">
                <div class="d-flex gap-3 align-items-center">
                    <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/cancel-icon.png') }}"
                         alt="">
                    <h4 class="mb-0">{{ translate('Inactive') }}</h4>
                </div>
                <span class="text-danger h3 mb-0 overflow-wrap-anywhere">{{ $inactiveEmployees }}</span>
            </div>
        </div>
        <div class="col-lg-6 col-xl-3">
            <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded">
                <div class="d-flex gap-3 align-items-center">
                    <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/add-new-employee.png') }}"
                         alt="">
                    <h4 class="mb-0">{{ translate('Employee Roles') }}</h4>
                </div>
                <span class="text-info h3 mb-0 overflow-wrap-anywhere">{{ $totalRoles }}</span>
            </div>
        </div>
    </div>
</div>
