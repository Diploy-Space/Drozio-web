@extends('layouts.vendor.app')
@section('title', translate('Dashboard'))
@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header pb-0 border-0 mb-3">
            <div class="flex-between row align-items-center mx-1">
                <div>
                    <h1 class="page-header-title text-capitalize">{{translate('Welcome').' '.auth('seller')->user()->f_name.' '.auth('seller')->user()->l_name}}</h1>
                    <p class="mb-0">{{ translate('Monitor your business analytics and statistics').'.'}}</p>
                </div>

                <div>
                    <a class="btn btn--primary" href="{{route('vendor.retail.products.list',['type'=>'all'])}}">
                        <i class="tio-premium-outlined mr-1"></i> {{translate('Products')}}
                    </a>
                </div>
            </div>
        </div>

        @if($vendorBuilderVisible)
            <div class="card mb-3 border-0 overflow-hidden website-builder-banner">
                <div class="card-body website-builder-banner__body">
                    <div class="website-builder-banner__content">
                        <span class="website-builder-banner__icon">
                            <i class="fi fi-sr-palette"></i>
                        </span>
                        <div class="website-builder-banner__text">
                            <h4 class="website-builder-banner__title">
                                {{ translate('Build Your Custom Website') }}
                            </h4>
                            <p class="website-builder-banner__description">
                                {{ translate('Design and launch your store website without writing code.') }}
                            </p>
                            <ul class="website-builder-banner__features">
                                <li><i class="tio-checkmark-circle"></i>{{ translate('Drag & Drop') }}</li>
                                <li><i class="tio-checkmark-circle"></i>{{ translate('Live preview') }}</li>
                                <li><i class="tio-checkmark-circle"></i>{{ translate('Mobile friendly') }}</li>
                            </ul>
                        </div>
                    </div>
                    <div class="website-builder-banner__action">
                        <a href="{{ route('vendor.builder.index', ['page' => 'global-settings']) }}"
                           class="btn website-builder-banner__button" id="vendor-dashboard-builder-button">
                            {{ translate('Open Website Builder') }}
                            <i class="tio-arrow-forward website-builder-banner__button-arrow"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <div class="card mb-3 remove-card-shadow">
            <div class="card-body">
                <div class="row justify-content-between align-items-center g-2 mb-3">
                    <div class="col-sm-6 col-md-8">
                        <h4 class="d-flex align-items-center text-capitalize gap-10 mb-0">
                            <img src="{{dynamicAsset(path: 'public/assets/back-end/img/business_analytics.png')}}" alt="">
                            {{ translate('Business Analytics') }}
                        </h4>
                    </div>
                    <div class="col-sm-6 col-md-4 d-flex justify-content-sm-end">
                        <div class="min-w-200">
                            <select class="custom-select w-auto" id="statistics_type" name="statistics_type">
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
                    @include('vendor-views.partials._dashboard-order-status',['orderStatus'=>$dashboardData['orderStatus']])
                </div>
            </div>
        </div>
        <div class="row g-2">
            <div class="col-12" id="earn-statistics-div">
                @include('vendor-views.dashboard.partials.earning-statistics')
            </div>
            <div class="col-lg-6">
                <div class="card h-100 remove-card-shadow">
                    @include('vendor-views.partials._top-rated-products',['topRatedProducts'=>$dashboardData['topRatedProducts']])
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100 remove-card-shadow">
                    @include('vendor-views.partials._top-selling-products',['topSell'=>$dashboardData['topSell']])
                </div>
            </div>
            <div class="col-12">
                <div class="card h-100 remove-card-shadow">
                    @include('vendor-views.partials._top-rated-delivery-man',['topRatedDeliveryMan'=>$dashboardData['topRatedDeliveryMan']])
                </div>
            </div>
        </div>
    </div>
    <span id="order-status-url" data-url="{{ route('vendor.retail.dashboard.order-status', ['type' => ':type']) }}"></span>
    <span id="seller-text" data-text="{{ translate('Vendor')}}"></span>
    <span id="in-house-text" data-text="{{ translate('In-house')}}"></span>
    <span id="customer-text" data-text="{{ translate('Customer')}}"></span>
    <span id="store-text" data-text="{{ translate('Store')}}"></span>
    <span id="product-text" data-text="{{ translate('Product')}}"></span>
    <span id="order-text" data-text="{{ translate('Order')}}"></span>
    <span id="brand-text" data-text="{{ translate('Brand')}}"></span>
    <span id="business-text" data-text="{{ translate('Business')}}"></span>
    <span id="customers-text" data-text="{{ $dashboardData['customers'] }}"></span>
    <span id="products-text" data-text="{{ $dashboardData['products'] }}"></span>
    <span id="orders-text" data-text="{{ $dashboardData['orders'] }}"></span>
    <span id="brands-text" data-text="{{ $dashboardData['brands'] }}"></span>
@endsection

@push('script_2')
    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/apexcharts.js')}}"></script>
    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/vendor/dashboard.js')}}"></script>
@endpush
