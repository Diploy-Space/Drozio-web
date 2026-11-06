@extends('layouts.admin.app')

@section('title', translate('General Settings'))

@section('content')
    <div class="content container-fluid">

        <div class="mb-3 mb-sm-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h2 class="h1 mb-0 text-capitalize d-flex flex-column flex-sm-row align-items-sm-center gap-sm-2">
                {{ translate('Business Setup') }}
            </h2>

            @include('admin-views.business-settings._business-module-switcher')
        </div>

        @include('admin-views.business-settings.business-setup-inline-menu')

        <div class="card mb-3 mb-sm-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8 col-xl-9">
                        @if ($businessSetting['maintenance_mode'])
                            <h3 class="fs-18">{{ translate('Maintenance Mode') }}</h3>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <p class="mb-0 fs-12">
                                    {{ translate('Your maintenance mode is activated') }}

                                    @if ($selectedMaintenanceDuration['maintenance_duration'] != 'until_change')
                                        {{ translate('From') }}<strong>{{ $maintenanceStartDate->format('m/d/Y, h:i A') }}</strong>
                                        to <strong>{{ $maintenanceEndDate->format('m/d/Y, h:i A') }}</strong>.
                                    @endif
                                </p>
                                <a class="btn btn-outline-primary icon-btn edit maintenance-mode-show" href="#">
                                    <i class="fi fi-sr-pencil"></i>
                                </a>
                            </div>
                        @else
                            <h3 class="fs-18">{{ translate('Maintenance Mode') }}</h3>
                            <p class="mb-0 fs-12">
                                {{ translate('Maintenance mode deactivates the selected systems from your chosen date and time.') }}
                            </p>
                        @endif

                        @if ($businessSetting['maintenance_mode'] && count($maintenanceSystemSetup) > 0)
                                <?php
                                $businessMode = getWebConfig(name: 'business_mode');
                                $totalSystemInMaintenance = 0;
                                if (array_key_exists('user_app', $maintenanceSystemSetup) && $maintenanceSystemSetup['user_app']) {
                                    $totalSystemInMaintenance++;
                                }
                                if (array_key_exists('user_website', $maintenanceSystemSetup) && $maintenanceSystemSetup['user_website']) {
                                    $totalSystemInMaintenance++;
                                }
                                if ($businessMode == 'multi' && array_key_exists('vendor_app', $maintenanceSystemSetup) && $maintenanceSystemSetup['vendor_app']) {
                                    $totalSystemInMaintenance++;
                                }
                                if ($businessMode == 'multi' && array_key_exists('vendor_panel', $maintenanceSystemSetup) && $maintenanceSystemSetup['vendor_panel']) {
                                    $totalSystemInMaintenance++;
                                }
                                if (array_key_exists('deliveryman_app', $maintenanceSystemSetup) && $maintenanceSystemSetup['deliveryman_app']) {
                                    $totalSystemInMaintenance++;
                                }
                                ?>

                            <div class="d-flex flex-wrap gap-3 mt-3 align-items-center">
                                <h5 class="mb-0">
                                    {{ translate('Maintenance mode is activated for the selected systems') }}
                                </h5>
                                <ul class="selected-systems d-flex gap-4 flex-wrap bg-soft-dark px-lg-5 px-3 py-1 mb-0 rounded">
                                    @if (
                                        ($businessMode == 'multi' && $totalSystemInMaintenance == 5) ||
                                            ($businessMode == 'single' && $totalSystemInMaintenance == 3))
                                        <li>{{ translate('All Systems') }}</li>
                                    @else
                                        @foreach ($maintenanceSystemSetup as $maintenanceSystemKey => $system)
                                            @if ($system)
                                                <li>{{ ucwords(str_replace('_', ' ', $maintenanceSystemKey)) }}</li>
                                            @endif
                                        @endforeach
                                    @endif
                                </ul>
                            </div>
                        @endif

                    </div>

                    <div class="col-md-4 col-xl-3">
                        <div class="mt-3 mt-md-0">
                            <label
                                class="d-flex justify-content-between align-items-center gap-3 border rounded px-20 py-3 user-select-none">
                                <span class="fw-medium text-dark">{{ translate('Maintenance Mode') }}</span>
                                <label class="switcher">
                                    @if(!$businessSetting['maintenance_mode'])
                                        <input type="checkbox"
                                               id="maintenanceModeSwitch"
                                               data-status="off"
                                               class="switcher_input maintenance-mode-show"
                                               data-warning="{{ translate('Do you want to turn off the maintenance mode') }}?"
                                               data-route="{{ route('admin.settings.business-settings.maintenance-mode') }}"
                                        >
                                    @else
                                        <input type="checkbox"
                                               data-status="on"
                                               class="switcher_input"
                                               data-warning="{{ translate('Do you want to turn off the maintenance mode') }}?"
                                               data-route="{{ route('admin.settings.business-settings.maintenance-mode') }}"
                                               id="maintenanceModeSwitch" checked>
                                    @endif

                                    <span class="switcher_control"></span>
                                </label>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.settings.business-settings.web-config.update') }}" method="POST" enctype="multipart/form-data" class="get-checked-required-field form-advance-inputs-validation form-advance-file-validation form-advance-validation non-ajax-form-validate" novalidate>
            @csrf

            <div class="card mb-3 mb-sm-4">
                <div class="card-header">
                    <h3 class="fs-18">{{ translate('Basic Information') }}</h3>
                    <p class="mb-0 fs-12">
                        {{ translate('Here you set up all your business information') }}.
                    </p>
                </div>
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="{{ getWebConfig('map_api_status') == 1 ? 'col-xxl-8' : 'col-xl-12' }}">
                            <div class="row g-4">
                                <div class="col-xl-6 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="">{{ translate('Company Name') }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="company_name" class="form-control" placeholder="{{ translate('Type your company name') }}" value="{{ $businessSetting['company_name'] }}" required="" data-required-msg="{{ translate('Company name is required') }}">
                                    </div>
                                </div>
                                <div class="col-xl-6 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="">{{ translate('Email') }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" name="company_email" class="form-control" placeholder="{{ translate('Type your email') }}" value="{{ $businessSetting['company_email'] }}" required="" data-required-msg="{{ translate('Company email is required') }}">
                                    </div>
                                </div>
                                <div class="col-xl-6 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="">{{ translate('Phone') }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input class="form-control" type="tel" name="company_phone"
                                            value="{{ $businessSetting['company_phone'] }}"
                                            placeholder="01xxxxxxxx">
                                    </div>
                                </div>

                                @php($countryCode = getWebConfig(name: 'country_code'))
                                <div class="col-xl-6 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="">{{ translate('Country') }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="custom-select" id="country" name="country_code"
                                            data-placeholder="{{ translate('Select from dropdown') }}">
                                            <option></option>
                                            @foreach (COUNTRIES as $country)
                                                <option value="{{ $country['code'] }}"
                                                    {{ $countryCode ? ($countryCode == $country['code'] ? 'selected' : '') : '' }}>
                                                    {{ $country['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xl-6 col-md-6">
                                    @php($timeZone = getWebConfig(name: 'timezone'))
                                    <div class="form-group">
                                        <label class="form-label" for="">{{ translate('Time Zone') }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="custom-select" name="timezone"
                                                data-placeholder="{{ translate('Select from dropdown') }}">
                                            <option></option>
                                            @foreach (App\Enums\GlobalConstant::TIMEZONE_ARRAY as $timeZoneArray)
                                                <option value="{{ $timeZoneArray['value'] }}"
                                                    {{ $timeZone ? ($timeZone == $timeZoneArray['value'] ? 'selected' : '') : '' }}>
                                                    {{ $timeZoneArray['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xl-6 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="pagination_limit">
                                            {{ translate('Pagination') }}
                                            <span class="text-danger">*</span>
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right"
                                                  aria-label="{{ translate('How many rows are shown in the list.') }}"
                                                  data-bs-title="{{ translate('How many rows are shown in the list.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                        </label>
                                        <input type="text" name="pagination_limit" class="form-control"
                                               placeholder="25" id="pagination_limit" data-required-msg="{{ translate('Pagination limit field is required') }}"
                                               value="{{ $businessSetting['pagination_limit'] }}" required step="1" min="1" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label" for="">{{ translate('Address') }}
                                            <span class="text-danger">*</span>
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right"
                                                aria-label="Enter your address" data-bs-title="{{ translate('Enter your business address, or tap the map to set the location.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                        </label>
                                        <textarea class="form-control" name="shop_address" id="shop-address" rows="1" data-required-msg="{{ translate('Shop address is required') }}"
                                            placeholder="{{ translate('Ex') }}: House#38, Road#04, Demo City" required>{{ $businessSetting['shop_address'] }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-4 {{ getWebConfig('map_api_status') == 1 ? '' : 'd-none' }}">
                            <div class="form-group position-relative h-100">
                                <input id="map-pac-input" class="form-control max-w-180 mt-1 position-absolute"
                                    title="{{ translate('Search your location here') }}" type="text"
                                    placeholder="{{ translate('Search here') }}" />
                                <div class="rounded w-100 h-100 min-h-260" id="location-map-canvas"></div>

                                <div class="latitude-longitude-container">
                                    <span class="latitude-longitude-item">
                                        <span>{{ 'Lat' }} :</span>
                                         <span id="get-default-latitude" data-latitude="{{ $businessSetting['default_location']['lat'] ?? '-33.8688' }}">{{ $businessSetting['default_location']['lat'] ?? '-33.8688' }}</span>
                                    </span>
                                    <span class="latitude-longitude-item">
                                        <span>{{ 'Long' }} : </span>
                                        <span id="get-default-longitude" data-longitude="{{ $businessSetting['default_location']['lng'] ?? '151.2195' }}">{{ $businessSetting['default_location']['lng'] ?? '151.2195' }}</span>
                                    </span>
                                    <input type="hidden" id="latitude" name="latitude" value="{{ $businessSetting['default_location']['lat'] ?? '-33.8688' }}">
                                    <input type="hidden" id="longitude" name="longitude" value="{{ $businessSetting['default_location']['lng'] ?? '151.2195' }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div
                                class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                <i class="fi fi-sr-lightbulb-on text-info"></i>
                                <span>{{ translate('For the address setup you can simply drag the map to pick for the perfect') }}
                                    <span class="fw-semibold">{{ translate('Lat') }}({{ translate('Latitude') }}) &
                                        {{ translate('Long') }}({{ translate('Longitude') }})
                                    </span> {{ translate('Value') }}.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3 mb-sm-4">
                <div class="card-header">
                    <h3 class="fs-18">{{ translate('General Setup') }}</h3>

                    <p class="mb-0 fs-12">
                        {{ translate('Here you can manage currency settings to match with your business criteria') }}
                    </p>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-sm-20 gap-3">
                        <div class="card card-sm shadow-1">
                            <div class="card-header">
                                <h3>{{ translate('Currency Setup') }}</h3>
                                <p class="mb-0 fs-12">
                                    {{ translate('Here you can manage currency settings to match with your business criteria') }}
                                </p>
                            </div>
                            <div class="card-body">
                                <div class="p-12 p-sm-20 bg-section rounded">
                                    @if(!$businessSetting['gateway_currency_support'])
                                        <div class="bg-danger bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
                                            <i class="fi fi-sr-triangle-warning text-danger"></i>
                                            <span>
                                                {{ translate('No payment gateway supports :currency currency', ['currency' => $systemCurrency->name]) }}
                                                {{ translate('Select at least one gateway supporting :currency for digital payment', ['currency' => $systemCurrency->name]) }}
                                            </span>
                                        </div>
                                    @endif

                                    <div class="row g-4 mb-3">
                                        <div class="col-xl-4 col-md-6">
                                            <div class="form-group">
                                                <label class="form-label"
                                                    for="">{{ translate('Select Currency') }}  <span class="text-danger">*</span></label>
                                                <select name="currency_id" class="custom-select"
                                                    data-placeholder="{{ translate('Select from dropdown') }}">
                                                    <option></option>
                                                    @foreach ($CurrencyList as $item)
                                                        <option value="{{ $item->id }}"
                                                            {{ $item->id == $businessSetting['system_default_currency'] ? 'selected' : '' }}>
                                                            {{ $item->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xl-4 col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="">
                                                    {{ translate('Currency Position') }}
                                                    <span class="text-danger">*</span>
                                                </label>

                                                <div class="border rounded px-3 py-1">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="form-check d-flex align-items-center gap-1">
                                                                <input class="form-check-input radio--input m-0" type="radio"
                                                                       name="currency_symbol_position" id="currency_position_left"
                                                                       value="left"
                                                                    {{ $businessSetting['currency_symbol_position'] == 'left' ? 'checked' : '' }}>

                                                                <label class="form-check-label" for="currency_position_left">
                                                                    ({{ getCurrencySymbol(currencyCode: getCurrencyCode(type: 'default')) }})
                                                                    {{ translate('Left') }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="form-check d-flex align-items-center gap-1">
                                                                <input class="form-check-input radio--input m-0" type="radio"
                                                                       name="currency_symbol_position" id="currency_position_right"
                                                                       value="right"
                                                                    {{ $businessSetting['currency_symbol_position'] == 'right' ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="currency_position_right">
                                                                    ({{ getCurrencySymbol(currencyCode: getCurrencyCode(type: 'default')) }})
                                                                    {{ translate('Right') }}
                                                                </label>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                        <div class="col-xl-4 col-md-6">
                                            <div class="form-group">
                                                <label class="form-label"
                                                    for="">{{ translate('Digit After Decimal Point') }} <span class="text-danger">*</span>
                                                    <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                        data-bs-placement="right"
                                                        aria-label="{{ translate('Enter digit after decimal point') }}"
                                                        data-bs-title="{{ translate('Enter digit after decimal point') }}">
                                                        <i class="fi fi-sr-info"></i>
                                                    </span>
                                                </label>
                                                <input type="number"
                                                    value="{{ $businessSetting['decimal_point_settings'] }}" required data-required-msg="{{ translate('Decimal point settings is required') }}"
                                                    name="decimal_point_settings" class="form-control" min="0"
                                                    placeholder="{{ translate('Ex') }}: 2">

                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                        <i class="fi fi-sr-lightbulb-on text-info"></i>
                                        <span>
                                           {{ translate('You can also set up your default and multiple currency from') }}
                                            <a href="{{ route('admin.settings.system-setup.currency.view') }}" target="_blank"
                                                class="text-decoration-underline fw-semibold">
                                                {{ translate('Currency Setup') }}
                                            </a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-sm shadow-1">
                            <div class="card-header">
                                <h3>{{ translate('Business Model Setup') }}</h3>
                                <p class="mb-0 fs-12">
                                    {{ translate('Choose one business model for selling your products.') }}
                                </p>
                            </div>
                            <div class="card-body">
                                <div class="p-12 p-sm-20 bg-section rounded">
                                    <div class="form-group mb-20">
                                        <label class="form-label mb-3"
                                            for="">{{ translate('Select Business Model') }} <span class="text-danger">*</span>
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right"
                                                aria-label="{{ translate('Select an option to activate the business model.') }}"
                                                data-bs-title="{{ translate('Select an option to activate the business model.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                        </label>
                                        <div class="bg-white p-3 rounded">
                                            <div class="row g-4">
                                                <div class="col-xl-6 col-md-6">
                                                    <div class="form-check d-flex gap-3">
                                                        <input class="form-check-input radio--input radio--input_lg"
                                                            type="radio" name="business_mode" id="business-mode-single-vendor"
                                                            value="single"
                                                            {{ $businessSetting['business_mode'] == 'single' ? 'checked' : '' }}>
                                                        <div class="flex-grow-1">
                                                            <label for="business-mode-single-vendor"
                                                                class="form-label text-dark fw-semibold mb-1">
                                                                {{ translate('Single Vendor') }}
                                                            </label>
                                                            <p class="fs-12 mb-3">
                                                                {{ translate('Sell products exclusively from your in-house shops only') }}
                                                            </p>
                                                            <div
                                                                class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                                                <i class="fi fi-sr-info text-warning fs-16"></i>
                                                                <span>

                                                                    {{ translate('You can change setup of your shop from here') }}
                                                                    <br>
                                                                    <a href="{{ route('admin.settings.business-settings.inhouse-shop') }}" target="_blank" class="text-decoration-underline fw-semibold">
                                                                        {{ translate('In house Shop Settings') }}
                                                                    </a>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xl-6 col-md-6">
                                                    <div class="form-check d-flex gap-3">
                                                        <input class="form-check-input radio--input radio--input_lg"
                                                            type="radio" value="multi" name="business_mode"
                                                            id="business-mode-multi-vendor"
                                                            {{ $businessSetting['business_mode'] == 'multi' ? 'checked' : '' }}>
                                                        <div class="flex-grow-1">
                                                            <label for="business-mode-multi-vendor"
                                                                class="form-label text-dark fw-semibold mb-1">
                                                                {{ translate('Multi Vendor') }}
                                                            </label>
                                                            <p class="fs-12 mb-3">
                                                                {{ translate('Alongside your store, multiple vendors can register and open their store') }}
                                                            </p>

                                                            <div
                                                                class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                                                <i class="fi fi-sr-lightbulb-on text-info fs-16"></i>
                                                                <span>
                                                                    {{ translate('Once on, configure more settings under Vendor Settings.') }}.
                                                                    <br>
                                                                    <a href="{{ route('admin.settings.business-settings.vendor-settings.index') }}" target="_blank"
                                                                        class="text-decoration-underline fw-semibold">
                                                                        {{ translate('Vendor Settings') }}
                                                                    </a>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-sm shadow-1">
                            <div class="card-header">
                                <h3>{{ translate('Payment Options') }}</h3>
                                <p class="mb-0 fs-12">
                                    {{ translate('Enable preferred payment methods to make payments from customer app and website') }}
                                </p>
                            </div>
                            <div class="card-body">
                                <div class="p-12 p-sm-20 bg-section rounded">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row g-4">
                                                <div class="col-xl-4 col-md-6">
                                                    <div class="form-check d-flex gap-3">
                                                        <input class="form-check-input checkbox--input" type="checkbox" name="cash_on_delivery"
                                                            id="cash_on_delivery" value="1" {{ $cashOnDelivery['status'] == 1 ? 'checked' : '' }}>
                                                        <div class="flex-grow-1">
                                                            <label for="cash_on_delivery"
                                                                class="form-label text-dark fw-semibold mb-1 user-select-none">
                                                                {{ translate('Cash On Delivery') }}
                                                            </label>
                                                            <p class="fs-12 mb-0">
                                                                {{ translate('Enabling cash on delivery') }} ({{ translate('COD') }}) {{ translate('Offers it as a payment option at checkout.') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xl-4 col-md-6">
                                                    <div class="form-check d-flex gap-3">
                                                        <input class="form-check-input checkbox--input" type="checkbox" name="digital_payment"
                                                            id="digital_payment" value="1"  {{ $digitalPayment['status'] ? 'checked' : '' }}>
                                                        <div class="flex-grow-1">
                                                            <label for="digital_payment"
                                                                class="form-label text-dark fw-semibold mb-1 user-select-none d-flex gap-1 align-items-center">
                                                                {{ translate('Digital Payment') }}
                                                            </label>
                                                            <p class="fs-12 mb-0">
                                                                {{ translate('Offers digital payment to customers at checkout.') }}.
                                                                <a href="{{ route('admin.settings.third-party.payment-method.index') }}" target="_blank"
                                                                    class="text-decoration-underline fw-semibold">{{ translate('Digital payment methods') }}</a>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xl-4 col-md-6">
                                                    <div class="form-check d-flex gap-3">
                                                        <input class="form-check-input checkbox--input" type="checkbox" name="offline_payment"
                                                            id="offline_payment" value="1" {{ $offlinePayment['status'] == 1 ? 'checked' : '' }}>
                                                        <div class="flex-grow-1">
                                                            <label for="offline_payment"
                                                                class="form-label text-dark fw-semibold mb-1 user-select-none">
                                                                {{ translate('Offline Payment') }}
                                                            </label>
                                                            <p class="fs-12 mb-0">
                                                                {{ translate('Offers offline payment to customers at checkout.') }}.
                                                                <a href="{{ route('admin.settings.third-party.offline-payment-method.index') }}" target="_blank"
                                                                    class="text-decoration-underline fw-semibold">{{ translate('Offline payment methods') }}</a>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-sm shadow-1">
                            <div class="card-header">
                                <h3>{{ translate('Copyright') }} & {{ translate('Cookies Text') }}</h3>
                                <p class="mb-0 fs-12">
                                    {{ translate('Add the necessary texts to display in required sections') }}
                                </p>
                            </div>
                            <div class="card-body">
                                <div class="p-12 p-sm-20 bg-section rounded">
                                    <div class="row gy-3">
                                        <div class="col-xl-6 col-md-6">
                                            <div class="form-group">
                                                <label class="form-label"
                                                    for="company_copyright_text">{{ translate('Copyright Text') }}
                                                    <span class="text-danger">*</span>
                                                    <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                        data-bs-placement="right" data-bs-html="true"
                                                        aria-label="Enter copyright text"
                                                        data-bs-title="
                                                        <div class='text-start'>{{ translate('Write the statement to inform that this is protected by copyright law') }}</div>
                                                        ">
                                                        <i class="fi fi-sr-info"></i>
                                                    </span>
                                                </label>
                                                <textarea class="form-control" name="company_copyright_text" rows="2" placeholder="{{ translate('Type about the description') }}" data-maxlength="100" required data-required-msg="{{ translate('Company copyright text is required') }}">{{ $businessSetting['company_copyright_text'] }}</textarea>
                                                <div class="d-flex justify-content-end">
                                                    <span class="text-body-light"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-md-6">
                                            <div class="form-group">
                                                <div class="float-end">
                                                    <label class="switcher" for="cookie-setting-status">
                                                        <input
                                                            class="switcher_input custom-modal-plugin"
                                                            type="checkbox" value="1" name="cookie_status"
                                                            id="cookie-setting-status"
                                                            {{ isset($cookieSetting) && $cookieSetting['status'] == 1 ? 'checked':'' }}
                                                            data-modal-type="input-change"
                                                            data-modal-form="#smtp-mail-config-form"
                                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/cookie-on.png') }}"
                                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/cookie-off.png') }}"
                                                            data-on-title="{{ translate('By Turning ON Cookie Settings')}}"
                                                            data-off-title="{{ translate('By Turning OFF Cookie Settings')}}"
                                                            data-on-message="<p>{{ translate('If you disable it customers cannot see Cookie Settings in frontend')}}</p>"
                                                            data-off-message="<p>{{ translate('If you enable it customers will see Cookie Settings in frontend')}}</p>">
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </div>
                                                <label class="form-label"
                                                    for="company_copyright_text">{{ translate('Cookies Text') }}
                                                    <span class="text-danger">*</span>
                                                    <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                        data-bs-placement="right" data-bs-html="true"
                                                        aria-label="Enter copyright text"
                                                        data-bs-title="
                                                        <div class='text-start'>{{ translate('Cookie text shown to customers in the app and on the website.') }}</div>
                                                        ">
                                                        <i class="fi fi-sr-info"></i>
                                                    </span>
                                                </label>
                                                <textarea class="form-control" name="cookie_text" rows="2" placeholder="{{ translate('Type about the description') }}" data-maxlength="100">{{ isset($cookieSetting) ? $cookieSetting['cookie_text'] : ''}}</textarea>
                                                <div class="d-flex justify-content-end">
                                                    <span class="text-body-light">0/100</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                <div class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="{{ getDemoModeFormButton(type: 'button') }}" class="btn btn-primary px-3 px-sm-4 {{ getDemoModeFormButton(type: 'class') }}">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>

        @include("admin-views.business-settings.partials._maintenance-mode-modal")

    </div>

    @include("layouts.admin.partials.offcanvas._general-setup")
@endsection

@push('script')
    @if(getWebConfig('map_api_status') == 1)
        <script src="https://maps.googleapis.com/maps/api/js?key={{ getWebConfig('map_api_key') }}&callback=googleMapInitialize&loading=async&libraries=places&v=3.56"
            defer>
        </script>
    @endif
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/business-settings/maintenance-mode-setting.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/business-settings/business-general-setting.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/business-settings/general-page.js') }}"></script>
@endpush
