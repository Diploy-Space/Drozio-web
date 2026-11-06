@extends('layouts.admin.app')

@section('title', translate('Setup Vat/Tax Calculation'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-20">
            <h2 class="fs-20 mb-0">
                {{ translate('Setup Vat/Tax Calculation') }}
            </h2>
        </div>

        @if (count($setupTabs))
            <ul class="nav nav--tab gap-1 nav-pills mb-20">
                @foreach ($setupTabs as $setupTab)
                    <li class="nav-item">
                        <a class="nav-link px-3 {{ $setupTab['active'] ? 'active' : '' }}" href="{{ $setupTab['url'] }}">
                            {{ $setupTab['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="card card-body mb-3 mb-sm-4">
            <div class="row align-items-center">
                <div class="col-md-8 col-xl-9">
                    <h3 class="fs-18 mb-1 text-capitalize">
                        {{ translate('Allow Vat/Tax calculation for vendor').' ?' }}
                    </h3>
                    <p class="mb-0 fs-12 text-capitalize">
                        {{ translate('To activate Vat/Tax calculation, turn on the status.') }}
                    </p>
                </div>

                <div class="col-md-4 col-xl-3">
                    <div class="mt-3 mt-md-0">
                        <label class="d-flex justify-content-between align-items-center gap-3 border rounded px-20 py-3 user-select-none">
                            <span class="fw-medium text-dark">{{ translate('Status') }}</span>

                            <form action="{{ route('admin.settings.vat-tax.system-tax-vat-vendor-status', $systemTaxVat?->id) }}"
                                  method="post"
                                  id="vat-tax-{{ $systemTaxVat?->id }}-status-form">
                                @csrf
                                <input type="hidden" name="id" value="{{ $systemTaxVat?->id }}">
                                <input type="hidden" name="business_module" value="{{ $businessModule }}">
                                <label class="switcher">
                                    <input
                                        class="switcher_input custom-modal-plugin"
                                        type="checkbox" value="1" name="is_active"
                                        id="vat-tax-{{ $systemTaxVat?->id }}-status"
                                        {{ $systemTaxVat?->is_active == 1 ? 'checked' : '' }}
                                        data-modal-type="input-change-form"
                                        data-modal-form="#vat-tax-{{ $systemTaxVat?->id }}-status-form"
                                        data-on-image="{{ dynamicAsset(path: 'public/assets/backend/media/toggle-modal-icons/turn-on.svg') }}"
                                        data-off-image="{{ dynamicAsset(path: 'public/assets/backend/media/toggle-modal-icons/turn-off.svg') }}"
                                        data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Status')]) }} ?"
                                        data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Status')]) }} ?"
                                        data-on-message="<p>{{ translate('Want to Turn ON :item', ['item' => translate('Vat status from your system')]).' '.translate('It will affect tax calculation & reports') }}</p>"
                                        data-off-message="<p>{{ translate('Want to Turn OFF :item', ['item' => translate('Vat status from your system')]).' '.translate('It will affect tax calculation & reports') }}</p>">
                                    <span class="switcher_control"></span>
                                </label>
                            </form>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-body d-flex flex-column gap-4 mt-4 {{ $systemTaxVat?->is_active == 1 ? '' : 'disabled' }}">
            <form action="{{ route('admin.settings.vat-tax.system-tax-vat-store') }}" method="post" class="setup-vat-tax-config-form js-vat-tax-setup">
                @csrf
                <input type="hidden" name="country_code" value="{{ $country_code ?? ($systemTaxVat?->country_code ?? null) }}">
                <input type="hidden" name="id" value="{{ $systemTaxVat?->id }}">
                <input type="hidden" name="business_module" value="{{ $businessModule }}">

                <div class="bg-section rounded p-12 p-sm-20">
                    <h3 class="mb-20">{{ translate('Vat calculation based on product price') }}</h3>
                    <div class="bg-white p-3 rounded border">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-check d-flex gap-3">
                                    <input class="form-check-input radio--input radio--input_lg vat-tax-include-price" type="radio"
                                           name="tax_status" id="include_product_price" value="include"
                                        {{ !$systemTaxVat || $systemTaxVat?->is_included == 1 ? 'checked' : '' }}>

                                    <label for="include_product_price">
                                        <div class="flex-grow-1">
                                            <div class="form-label text-dark fw-semibold mb-1 text-capitalize">
                                                {{ translate('Calculate VAT include product price') }}
                                            </div>
                                            <p class="fs-12 mb-2">
                                                {{ translate('By selecting this option VAT is included in the product price.') }}
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check d-flex gap-3">
                                    <input class="form-check-input radio--input radio--input_lg vat-tax-exclude-price" type="radio"
                                           value="exclude" name="tax_status" id="exclude_product_price"
                                        {{ $systemTaxVat && $systemTaxVat?->is_included == 0 ? 'checked' : '' }}
                                    >
                                    <label for="exclude_product_price">
                                        <div class="flex-grow-1">
                                            <div class="form-label text-dark fw-semibold mb-1 text-capitalize">
                                                {{ translate('Calculate VAT exclude product price') }}
                                            </div>
                                            <p class="fs-12 mb-2">
                                                {{ translate('VAT is added on top of the item price.') }}
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 js-vat-include-notice {{ $systemTaxVat && $systemTaxVat?->is_included == 0 ? 'd-none' : '' }}">
                        <div class="bg-danger bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                            <i class="fi fi-sr-triangle-warning text-danger"></i>
                            <span>
                                {{ translate('VAT is already inside the product price in this mode, so no VAT type or rate is applied and vendors cannot select a VAT rate on their products.') }}
                                {{ translate('Select :option to set a VAT type and let vendors choose rates.', ['option' => translate('Calculate VAT exclude product price')]) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="bg-section rounded p-12 p-sm-20 exclude_product_price_details {{ !$systemTaxVat || $systemTaxVat?->is_included == 1 ? 'disabled' : '' }}">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div>
                                <h3 class="mb-1">{{ translate('Basic Setup') }}</h3>
                                <p class="fs-12 mb-0">{{ translate('Set your VAT type and rate.') }}</p>
                            </div>
                            <div class="product_wise_content mt-2 {{ $systemTaxVat?->tax_type == 'product_wise' ? 'd-block' : 'd-none' }}">
                                <div class="bg-danger bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                    <i class="fi fi-sr-triangle-warning text-danger"></i>
                                    <span>
                                        {{ translate('When you change') }}
                                        <span class="fw-semibold">{{ translate('VAT Type') }}</span>
                                        {{ translate('To product wise.') }}
                                        {{ translate('Vendors will have control to setup the taxes of their products.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="">
                                <label class="form-label" for="select_vat_type">
                                    {{ translate('Select Vat Type') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <div class="select-wrapper">
                                    <select class="form-select js-select-vat-type" id="select_vat_type" name="tax_type">
                                        @foreach ($taxTypes as $item)
                                            <option {{ $systemTaxVat?->tax_type == $item ? 'selected' : '' }} value="{{ $item }}" data-target=".{{ $item }}_content">
                                                {{ ucwords(translate($item)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="order_wise_content mt-4 {{ !$systemTaxVat || in_array($systemTaxVat?->tax_type, ['order_wise']) ? '' : 'd-none' }}">
                                <label class="form-label" for="">
                                    {{ translate('Select Vat Rate') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <select class="custom-select multiple-select2" name="tax_ids[]" multiple="multiple"
                                        data-placeholder="{{ translate('Type & select vat rate') }}"
                                    {{ in_array($systemTaxVat?->tax_type, ['order_wise']) ? 'selected' : '' }}
                                >
                                    <option></option>
                                    @foreach ($taxVats as $taxVat)
                                        <option
                                            {{ in_array($taxVat->id, $systemTaxVat?->tax_ids ?? []) ? 'selected' : '' }}
                                            value="{{ $taxVat->id }}"> {{ $taxVat->name }}
                                            ({{ $taxVat->tax_rate }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="category_wise_content mt-2 {{ $systemTaxVat?->tax_type == 'category_wise' ? '' : 'd-none' }}">
                                <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                    <i class="fi fi-sr-lightbulb-on text-info"></i>
                                    <span>
                                        {{ translate('Please specify the vat rate while creating a category from') }}
                                        <a href="{{ $categoryListUrl }}" target="_blank"
                                           class="text-decoration-underline fw-semibold">
                                            {{ translate('Category List') }}
                                        </a>
                                        {{ translate('For categories created without VAT, edit the category and update it.') }}
                                    </span>
                                </div>
                            </div>
                            <div class="product_wise_content mt-2 {{ $systemTaxVat?->tax_type == 'product_wise' ? '' : 'd-none' }}">
                                <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                    <i class="fi fi-sr-lightbulb-on text-info"></i>
                                    <span>
                                        {{ translate('Please specify the vat/tax rate while creating an item from') }}
                                        <a href="{{ $productListUrl }}"
                                           class="text-decoration-underline fw-semibold" target="_blank">
                                            {{ $productListLabel }}
                                        </a>
                                        {{ translate('For products created without tax, edit the product and update it.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if (count($additionalTaxItems))
                    <div class="bg-section rounded p-12 p-sm-20 exclude_product_price_details">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div>
                                    <h3 class="mb-1">{{ translate('Additional Setup') }}</h3>
                                    <p class="fs-12 mb-0">{{ translate('Here you can setup vat for additional charges.') }}</p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                @foreach ($additionalTaxItems as $item)
                                    @php($additionalData = $systemTaxVat?->additionalData?->where('name', $item)->first())
                                    <div class="">
                                        <div class="form-label d-flex gap-2 justify-content-between align-items-center">
                                            <span class="text-capitalize">{{ translate($item) }}</span>
                                            <label class="switcher" for="services__charge_{{ $item }}">
                                                <input type="checkbox" class="switcher_input" value="1"
                                                       name="additional_status[{{ $item }}]"
                                                       id="services__charge_{{ $item }}"
                                                    {{ $additionalData?->is_active ? 'checked' : '' }}>
                                                <span class="switcher_control"></span>
                                            </label>
                                        </div>
                                        <select class="custom-select multiple-select2" multiple="multiple"
                                                data-placeholder="{{ translate('Type & select vat rate') }}"
                                                id="additional_charge_{{ $item }}"
                                                name="additional[{{ $item }}][]">
                                            <option></option>
                                            @foreach ($taxVats as $taxVat)
                                                <option {{ in_array($taxVat->id, $additionalData?->tax_ids ?? []) ? 'selected' : '' }}
                                                    value="{{ $taxVat->id }}">
                                                    {{ $taxVat->name }} ({{ $taxVat->tax_rate }}%)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <div class="d-flex flex-wrap justify-content-end gap-3 mt-3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">
                        {{ translate('Reset') }}
                    </button>
                    <button type="submit" class="btn btn-primary px-3 px-sm-4">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include("taxmodule::6valley.offcanvas._setup-vat-tax-calculation")
@endsection

@push('script')
    <script>
        'use strict';
        $(document).ready(function () {
            function toggleVatCalcOption($form) {
                const isExcluded = $form.find('.vat-tax-exclude-price').prop('checked');

                $form.find('.exclude_product_price_details').toggleClass('disabled', !isExcluded);
                $form.find('.js-vat-include-notice').toggleClass('d-none', isExcluded);
            }

            function selectVatType($form) {
                $form.find('.order_wise_content, .category_wise_content, .product_wise_content').addClass('d-none');

                let $selectedOption = $form.find('.js-select-vat-type option:selected');
                let targetSelector = $selectedOption.data('target');

                if (targetSelector) {
                    $form.find(targetSelector).removeClass('d-none');
                }
            }

            $('.js-vat-tax-setup').each(function () {
                const $form = $(this);
                toggleVatCalcOption($form);
                selectVatType($form);

                $form.on('change', '.vat-tax-exclude-price, .vat-tax-include-price', function () {
                    toggleVatCalcOption($form);
                });
                $form.on('change', '.js-select-vat-type', function () {
                    selectVatType($form);
                });
                $form.on('reset', function () {
                    requestAnimationFrame(() => {
                        toggleVatCalcOption($form);
                        selectVatType($form);
                    });
                });
            });
        });
    </script>
@endpush
