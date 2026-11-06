@extends('layouts.admin.app')

@section('title', translate('Dashboard'))

@section('content')
    @if ($canViewDashboard)
        <div class="content container-fluid">
            <div class="mb-3">
                <h1 class="page-header-title">{{ translate('Welcome') . ' ' . auth('admin')->user()->name }}</h1>
                <p>{{ translate('Monitor your business analytics and statistics') . '.' }}</p>
            </div>

            <div class="card mb-2 remove-card-shadow">
                <div class="card-body">
                    <div class="row flex-between align-items-center g-2 mb-3">
                        <div class="col-sm-6">
                            <h4 class="d-flex align-items-center text-capitalize gap-10 mb-0">
                                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/business_analytics.png') }}"
                                    alt="">
                                <span class="fw-bold fs-16">{{ translate('Business Analytics') }}</span>
                            </h4>
                        </div>
                        <div class="col-sm-6 d-flex justify-content-sm-end">
                            <div class="min-w-200">
                                <select class="custom-select w-auto" name="statistics_type" id="statistics_type">
                                    <option value="overall"
                                        {{ $statisticsType == 'overall' ? 'selected' : '' }}>
                                        {{ translate('Overall Statistics') }}
                                    </option>
                                    <option value="today"
                                        {{ $statisticsType == 'today' ? 'selected' : '' }}>
                                        {{ translate('Today Statistics') }}
                                    </option>
                                    <option value="this_week"
                                        {{ $statisticsType == 'this_week' ? 'selected' : '' }}>
                                        {{ translate('This Week Statistics') }}
                                    </option>
                                    <option value="this_month"
                                        {{ $statisticsType == 'this_month' ? 'selected' : '' }}>
                                        {{ translate('This Month Statistics') }}
                                    </option>
                                    <option value="this_year"
                                        {{ $statisticsType == 'this_year' ? 'selected' : '' }}>
                                        {{ translate('This Year Statistics') }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2" id="order_stats">
                        @include('admin-views.partials._dashboard-order-status', ['data' => $data])
                    </div>
                </div>
            </div>

            <div class="card mb-3 remove-card-shadow">
                <div class="card-body">
                    <h4 class="d-flex align-items-center text-capitalize gap-10 mb-3">
                        <img width="20" class="mb-1"
                            src="{{ dynamicAsset(path: 'public/assets/back-end/img/admin-wallet.png') }}" alt="">
                        <span class="fw-bold fs-16">{{ translate('Admin Wallet') }}</span>
                    </h4>

                    <div class="row g-2" id="order_stats">
                        @include('admin-views.partials._dashboard-wallet-stats', ['data' => $data])
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-8" id="order-statistics-div">
                    @include('admin-views.system.partials.order-statistics')
                </div>
                <div class="col-lg-4">
                    <div class="card remove-card-shadow h-100">
                        <div class="card-header">
                            <h4 class="d-flex align-items-center text-capitalize gap-10 mb-0 fw-bold fs-16">
                                {{ translate('User Overview') }}
                            </h4>
                        </div>
                        <div class="card-body justify-content-center d-flex flex-column">
                            <div>
                                <div class="position-relative">
                                    <div id="chart" class="apex-pie-chart d-flex justify-content-center"></div>
                                    <div class="total--orders">
                                        <h3 class="fw-bold">{{ $data['getTotalCustomerCount'] + $data['getTotalVendorCount'] + $data['getTotalDeliveryManCount'] }}
                                        </h3>
                                        <span class="text-capitalize">{{ translate('Total User') }}</span>
                                    </div>
                                </div>
                                <div class="apex-legends flex-column">
                                    <div data-color="#7bc4ff">
                                        <span class="text-capitalize">{{ translate('Total Customers') . ' ' . '(' . $data['getTotalCustomerCount'] . ')' }}
                                        </span>
                                    </div>
                                    <div data-color="#f9b530">
                                        <span
                                            class="text-capitalize">{{ translate('Total Vendor') . ' ' . '(' . $data['getTotalVendorCount'] . ')' }}</span>
                                    </div>
                                    <div data-color="#1c1a93">
                                        <span
                                            class="text-capitalize">{{ translate('Total Delivery Man') . ' ' . '(' . $data['getTotalDeliveryManCount'] . ')' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12" id="earn-statistics-div">
                    @include('admin-views.system.partials.earning-statistics')
                </div>
                <div class="col-12">
                    @include('admin-views.system.partials._users', [
                            'top_customer' => $data['top_customer'],
                            'topRatedDeliveryMan' => $data['topRatedDeliveryMan'],
                        ])
                </div>
                <div class="col-12">
                    @include('admin-views.system.partials._stores', [
                        'topVendorListByWishlist' => $data['topVendorListByWishlist'],
                        'topVendorByEarning' => $data['topVendorByEarning'],
                    ])
                </div>
                <div class="col-12">
                    @include('admin-views.system.partials._products', [
                        'mostRatedProducts' => $data['mostRatedProducts'],
                        'topSellProduct' => $data['topSellProduct'],

                    ])
                </div>
                <div class="col-12">
                    @include('admin-views.system.partials._vendor-products', [
                        'vendorMostRatedProducts' => $data['vendorMostRatedProducts'],
                        'vendorTopSellProduct' => $data['vendorTopSellProduct'],
                    ])
                </div>
            </div>
        </div>
    @else
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-12 mb-2 mb-sm-0">
                        <h3 class="text-center">{{ translate('Hi') }} {{ auth('admin')->user()->name }}
                            {{ ' , ' . translate('Welcome to your dashboard') }}.</h3>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <span id="earning-statistics-url" data-url="{{ route('admin.retail.dashboard.earning-statistics') }}"></span>
    <span id="order-status-url" data-url="{{ route('admin.retail.dashboard.order-status') }}"></span>
    <span id="seller-text" data-text="{{ translate('Vendor') }}"></span>
    <span id="message-commission-text" data-text="{{ translate('Commission') }}"></span>
    <span id="in-house-text" data-text="{{ translate('In-house') }}"></span>
    <span id="customer-text" data-text="{{ translate('Customer') }}"></span>
    <span id="store-text" data-text="{{ translate('Store') }}"></span>
    <span id="product-text" data-text="{{ translate('Product') }}"></span>
    <span id="order-text" data-text="{{ translate('Order') }}"></span>
    <span id="brand-text" data-text="{{ translate('Brand') }}"></span>
    <span id="business-text" data-text="{{ translate('Business') }}"></span>
    @if ($canViewDashboard)
    <span id="orders-text" data-text="{{ $data['order'] }}"></span>
    <span id="user-overview-data" style="background-color: #000;" data-customer="{{ $data['getTotalCustomerCount'] }}"
        data-customer-title="{{ translate('Total Customers') }}" data-vendor="{{ $data['getTotalVendorCount'] }}"
        data-vendor-title="{{ translate('Total Vendor') }}" data-delivery-man="{{ $data['getTotalDeliveryManCount'] }}"
        data-delivery-man-title="{{ translate('Total Delivery Man') }}"></span>
    @endif
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/apexcharts.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/admin/dashboard.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const guideModal = document.getElementById('guideModal');
            const arrowIcon = document.querySelector('.setup-guide__button .fi');

            if (guideModal && arrowIcon) {
                guideModal.addEventListener('shown.bs.modal', () => {
                    arrowIcon.classList.remove('fi-sr-angle-right');
                    arrowIcon.classList.add('fi-sr-angle-down');
                });

                guideModal.addEventListener('hidden.bs.modal', () => {
                    arrowIcon.classList.remove('fi-sr-angle-down');
                    arrowIcon.classList.add('fi-sr-angle-right');
                });
            }
        });
    </script>


@endpush
