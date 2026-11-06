@extends('layouts.admin.app')

@section('title', translate('Delivery Zone'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h2 class="h1 mb-0 text-capitalize d-flex flex-column flex-sm-row align-items-sm-center gap-sm-2">
                {{ translate('Business Setup') }}
            </h2>

            @include('admin-views.business-settings._business-module-switcher')
        </div>

        @include('admin-views.business-settings.business-setup-inline-menu')

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-column gap-20">
                            <div class="d-flex justify-content-between align-items-start gap-3 user-select-none">
                                <div>
                                    <h2 class="fw-medium mb-1">
                                        {{ translate('Delivery Available Country') }}
                                    </h2>
                                    <p class="mb-0 fs-12">
                                        {{ translate('Lets you select multiple countries for product delivery.') }}
                                    </p>
                                </div>
                                <form action="{{ route('admin.settings.business-settings.delivery-zone.country-restriction-status-change') }}" method="post" id="delivery-available-country-status-form" class="">
                                    @csrf
                                    <label class="switcher mx-auto" for="delivery-available-country-status">
                                        <input
                                            class="switcher_input custom-modal-plugin"
                                            type="checkbox" value="1" name="status"
                                            id="delivery-available-country-status"
                                            {{ $countryRestrictionStatus ? 'checked' : '' }}
                                            data-modal-type="input-change-form"
                                            data-modal-form="#delivery-available-country-status-form"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/delivery-available-country-on.png') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/delivery-available-country-off.png') }}"
                                            data-on-title = "{{ translate('Want to Turn ON :item', ['item' => translate('Delivery Available Country')]) . '?' }}"
                                            data-off-title = "{{ translate('Want to Turn OFF :item', ['item' => translate('Delivery Available Country')]) . '?' }}"
                                            data-on-message = "<p>{{ translate('Admin or vendor can deliver to the selected countries.') }}</p>"
                                            data-off-message = "<p>{{ translate('If disabled there will be no delivery restrictions for admin or vendors') }}</p>"
                                            data-on-button-text="{{ translate('Turn On') }}"
                                            data-off-button-text="{{ translate('Turn Off') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </form>
                            </div>
                            <div
                                class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                <i class="fi fi-sr-info text-warning"></i>
                                <span>
                                    <strong>{{ translate('Warning') }}:</strong>
                                    {{ translate('A country without a name will not appear as a shipping option.') }}
                                </span>
                            </div>
                            <div class="p-20 bg-section rounded">
                                <form action="{{ route('admin.settings.business-settings.delivery-zone.add-delivery-country') }}"
                                    method="post">
                                    @csrf
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('Country') }}</label>
                                        <select {{ $countryRestrictionStatus ? '' : 'disabled' }} class="custom-select multiple-select2" name="country_code[]"
                                            id="choice_attributes" multiple="multiple" data-placeholder="{{ translate('Select Country') }}">
                                            <option></option>
                                            @foreach ($countryOptions as $countryCode => $countryName)
                                                <option value="{{ $countryCode }}">
                                                    {{ $countryName }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="text-end mt-4">
                                        <button type="submit"
                                            class="btn btn-primary px-20 "><i class="fi fi-sr-disk"></i> {{ translate('Save') }}</button>
                                    </div>
                                </form>

                            </div>
                            <div class="card shadow-1">
                                <div class="card-body">
                                    <div class="d-flex flex-column gap-20">
                                        <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                                            <h4>{{ translate('Country List') }}
                                                <span class="badge text-dark bg-body-secondary fw-semibold rounded-50">{{ count($storedCountries) }}</span>
                                            </h4>
                                            <form action="{{ route('admin.settings.business-settings.delivery-zone.index') }}" method="get" class="flex-grow-1 max-w-300 min-w-100-mobile">
                                                <div class="input-group">
                                                    <input type="search" class="form-control" placeholder="{{ translate('Search Country') }}" name="search_country" value="{{ request('search_country') }}">
                                                    <div class="input-group-append search-submit">
                                                        <button type="submit">
                                                            <i class="fi fi-rr-search"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-borderless">
                                                <thead class="text-capitalize">
                                                    <tr>
                                                        <th>{{ translate('Serial') }}</th>
                                                        <th class="text-center">{{ translate('Country Name') }}</th>
                                                        <th class="text-center">{{ translate('Action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($storedCountries as $key => $storedCountry)
                                                        <tr>
                                                            <td>{{ $storedCountries->firstItem() + $key }}</td>
                                                            <td class="text-center">{{ $storedCountry->country_name }}</td>
                                                            <td>
                                                                <div class="d-flex justify-content-center gap-2">
                                                                    <a class="btn btn-outline-danger icon-btn delete-data"
                                                                        href="javascript:"
                                                                        title="{{ translate('Delete') }}"
                                                                        data-id="country-{{ $storedCountry->id }}">
                                                                        <i class="fi fi-rr-trash"></i>
                                                                    </a>
                                                                    <form action="{{ route('admin.settings.business-settings.delivery-zone.delivery-country-delete', ['id' => $storedCountry->id]) }}" method="post" id="country-{{ $storedCountry->id }}">
                                                                        @csrf @method('delete')
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="table-responsive">
                                            <div class="d-flex justify-content-lg-end">
                                                {{ $storedCountries->appends(request()->query())->links() }}
                                            </div>
                                        </div>
                                        @if (count($storedCountries) == 0)
                                            @include(
                                                'layouts.admin.partials._empty-state',
                                                ['text' => 'no_country_found'],
                                                ['image' => 'default']
                                            )
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-column gap-20">
                            <div class="d-flex justify-content-between align-items-start gap-3 user-select-none">
                                <div>
                                    <h2 class="fw-medium mb-1">
                                        {{ translate('Delivery Available ZIP Code Area') }}
                                    </h2>
                                    <p class="mb-0 fs-12">
                                        {{ translate('If enabled the zip code areas will be available for delivery.') }}
                                    </p>
                                </div>
                                <form
                                    action="{{ route('admin.settings.business-settings.delivery-zone.zipcode-restriction-status-change') }}"
                                    method="post" id="delivery-available-zip-code-status-form" class="no-reload-form">
                                    @csrf
                                    <label class="switcher mx-auto" for="delivery-available-zip-code-status">
                                        <input
                                            class="switcher_input custom-modal-plugin"
                                            type="checkbox" value="1" name="status"
                                            id="delivery-available-zip-code-status"
                                            {{ $zipCodeRestrictionStatus ? 'checked' : '' }}
                                            data-modal-type="input-change-form"
                                            data-modal-form="#delivery-available-zip-code-status-form"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/zip-code-on.png') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/zip-code-off.png') }}"
                                            data-on-title = "{{ translate('Want to Turn ON :item', ['item' => translate('Delivery Available ZIP Code Area')]) . '?' }}"
                                            data-off-title = "{{ translate('Want to Turn OFF :item', ['item' => translate('Delivery Available ZIP Code Area')]) . '?' }}"
                                            data-on-message = "<p>{{ translate('Deliveries are limited to the added zip code areas.') }}</p>"
                                            data-off-message = "<p>{{ translate('No delivery restrictions by zip code area.') }}</p>"
                                            data-on-button-text="{{ translate('Turn On') }}"
                                            data-off-button-text="{{ translate('Turn Off') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </form>
                            </div>
                            <div
                                class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                <i class="fi fi-sr-info text-warning"></i>
                                <span>
                                    {{ translate('If you do not enter a specific') }}
                                    <span class="fw-semibold">{{ translate('Zip Code') }}</span> {{ translate('From a country') }},{{ translate('That area would not be available for delivery.') }}
                                </span>
                            </div>
                            <div class="p-20 bg-section rounded">
                                <form action="{{ route('admin.settings.business-settings.delivery-zone.add-zip-code') }}"
                                    method="post" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
                                    @csrf
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('Zip Code') }}</label>
                                        <input type="text" class="form-control bootstrap-tags-input" name="zipcode" placeholder="{{ translate('Enter zip code') }}"   data-required-msg="{{ translate(':field is required', ['field' => translate('Zip Code')]) }}" required>

                                    </div>
                                    <div class="text-end mt-4">
                                        <button type="submit"
                                            class="btn btn-primary px-20 zip_code"><i class="fi fi-sr-disk"></i> {{ translate('Save') }}</button>
                                    </div>
                                </form>

                            </div>
                            <div class="card shadow-1">
                                <div class="card-body">
                                    <div class="d-flex flex-column gap-20">
                                        <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                                            <h4>{{ translate('Zip Code List') }}
                                                <span class="badge text-dark bg-body-secondary fw-semibold rounded-50">{{ count($storedZipCodes) }}</span>
                                            </h4>
                                            <form action="{{ route('admin.settings.business-settings.delivery-zone.index') }}" method="get" class="flex-grow-1 max-w-300 min-w-100-mobile">
                                                <div class="input-group">
                                                    <input type="search" class="form-control" placeholder="{{ translate('Search ZIP Code') }}" name="search_zip_code" value="{{ request('search_zip_code') }}">
                                                    <div class="input-group-append search-submit">
                                                        <button type="submit">
                                                            <i class="fi fi-rr-search"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-borderless">
                                                <thead class="text-capitalize">
                                                    <tr>
                                                        <th>{{ translate('Serial') }}</th>
                                                        <th class="text-center">{{ translate('Zip Code') }}</th>
                                                        <th class="text-center">{{ translate('Action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($storedZipCodes as $key => $zipCode)
                                                        <tr>
                                                            <td>{{ $storedZipCodes->firstItem() + $key }}</td>
                                                            <td class="text-center"><div class="max-w-180 mx-auto text-break">{{ $zipCode->zipcode }}</div></td>
                                                            <td>
                                                                <div class="d-flex justify-content-center gap-2">
                                                                    <a class="btn btn-outline-danger icon-btn delete-data"
                                                                        href="javascript:"
                                                                        title="{{ translate('Delete') }}"
                                                                        data-id="zip-{{ $zipCode->id }}">
                                                                        <i class="fi fi-rr-trash"></i>
                                                                    </a>
                                                                    <form
                                                                        action="{{ route('admin.settings.business-settings.delivery-zone.zip-code-delete', ['id' => $zipCode->id]) }}"
                                                                        method="post" id="zip-{{ $zipCode->id }}">
                                                                        @csrf @method('delete')
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="table-responsive">
                                            <div class="d-flex justify-content-lg-end">
                                                {{ $storedZipCodes->appends(request()->query())->links() }}
                                            </div>
                                        </div>
                                        @if (count($storedZipCodes) == 0)
                                            @include(
                                                'layouts.admin.partials._empty-state',
                                                ['text' => 'no_zip_code_found'],
                                                ['image' => 'default']
                                            )
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include("layouts.admin.partials.offcanvas._delivery-restriction")
@endsection
