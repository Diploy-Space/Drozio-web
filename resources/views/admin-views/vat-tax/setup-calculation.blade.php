@extends('layouts.admin.app')

@section('title', translate('Setup Calculation'))

@push('css_or_js')
    <style>
        /*css Code*/
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="fs-20 mb-0">
                 {{ translate('Setup Calculation') }}
            </h2>
        </div>
        <div class="card card-body mb-3 mb-sm-4">
            <div class="row align-items-center">
                <div class="col-md-8 col-xl-9">
                    <h3 class="fs-18 mb-1">{{ translate('Allow vat calculation for vendor') }} ?</h3>
                    <p class="mb-0 fs-12">
                        {{ translate('To activate vat calculation, turn on the status.') }}
                    </p>
                </div>

                <div class="col-md-4 col-xl-3">
                    <div class="mt-3 mt-md-0">
                        <label
                            class="d-flex justify-content-between align-items-center gap-3 border rounded px-20 py-3 user-select-none">
                            <span class="fw-medium text-dark">{{ translate('Status') }}</span>
                            <label class="switcher">
                            <input type="checkbox" class="switcher_input"
                                    id="vat_calc_status">

                                <span class="switcher_control"></span>
                            </label>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <form action="" method="post">
            <div class="card card-body d-flex flex-column gap-4 mt-4 vat_calc_details">
                <div class="bg-section rounded p-12 p-sm-20">
                    <h3 class="mb-20">{{ translate('Vat calculation based on product price') }}</h3>
                    <div class="bg-white p-3 rounded border">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-check d-flex gap-3">
                                    <input class="form-check-input radio--input radio--input_lg" type="radio"
                                        name="vat_calc_product_price" id="include_product_ptice" value="" checked>
                                    <div class="flex-grow-1">
                                        <label for="" class="form-label text-dark fw-semibold mb-1">
                                            {{ translate('Calculate vat include product price') }}
                                        </label>
                                        <p class="fs-12 mb-2">
                                            {{ translate('Sets the same tax rate for every income source.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check d-flex gap-3">
                                    <input class="form-check-input radio--input radio--input_lg" type="radio" value=""
                                        name="vat_calc_product_price" id="exclude_product_ptice">
                                    <div class="flex-grow-1">
                                        <label for="" class="form-label text-dark fw-semibold mb-1">
                                            {{ translate('Calculate vat exclude product price') }}
                                        </label>
                                        <p class="fs-12 mb-2">
                                            {{ translate('Sets an individual VAT rate for each income source.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-section rounded p-12 p-sm-20 d-none exclude_product_price_details">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div>
                                <h3 class="mb-1">{{ translate('Basic Setup') }}</h3>
                                <p class="fs-12 mb-0">{{ translate('Set your VAT type and rate.') }}</p>
                            </div>
                            <div class="product_wise_content mt-2">
                                <div class="bg-danger bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                    <i class="fi fi-sr-triangle-warning text-danger"></i>
                                    <span>
                                       {{ translate('When you change') }}
                                        <span class="fw-semibold">{{ translate('Vat type') }}</span>
                                        {{ translate('To product wise.') }}
                                        {{ translate('Vendors will have control to setup the taxes of their products.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="">
                                <label class="form-label" for="">{{ translate('Select vat type') }}</label>
                                <div class="select-wrapper">
                                    <select class="form-select" id="select_vat_type" name="">
                                        <option value="order" data-target=".order_wise_content" selected>{{ translate('Order wise') }}</option>
                                        <option value="category" data-target=".category_wise_content">{{ translate('Category wise') }}</option>
                                        <option value="product" data-target=".product_wise_content">{{ translate('Product') }}</option>
                                    </select>

                                </div>
                            </div>
                            <div class="order_wise_content mt-4">
                                <label class="form-label" for="">{{ translate('Select vat rate') }}</label>
                                <select class="custom-select multiple-select2" name="options[]" multiple="multiple" data-max-length="5" data-placeholder="{{ translate('Type & select vat rate') }}">
                                    <option value="1">Option 01</option>
                                    <option value="2">Option 02</option>
                                    <option value="3">Option 03</option>
                                    <option value="4">Option 04</option>
                                    <option value="5">Option 05</option>
                                    <option value="6">Option 06</option>
                                    <option value="7">Option 07</option>
                                </select>
                            </div>
                            <div class="category_wise_content mt-2">
                                <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                    <i class="fi fi-sr-lightbulb-on text-info"></i>
                                    <span>
                                        {{ translate('Please specify the vat rate while creating a category from') }}
                                        <a href="#" class="text-decoration-underline fw-semibold">{{ translate('Category List') }}</a>.
                                        {{ translate('For categories created without VAT, edit the category and update it.') }}
                                    </span>
                                </div>
                            </div>
                            <div class="product_wise_content mt-2">
                                <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                    <i class="fi fi-sr-lightbulb-on text-info"></i>
                                    <span>
                                        {{ translate('Please specify the tax rate while creating an item from') }}
                                        <a href="#" class="text-decoration-underline fw-semibold">{{ translate('Item list') }}</a>.
                                        {{ translate('Edit existing items to add tax if they were created without it.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-section rounded p-12 p-sm-20 d-none exclude_product_price_details">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div>
                                <h3 class="mb-1">{{ translate('Additional Setup') }}</h3>
                                <p class="fs-12 mb-0">{{ translate('Here you can setup vat for additional charges.') }}</p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="">
                                <label class="form-label d-flex gap-2 justify-content-between align-items-center" for="">
                                    <span>{{ translate('Vat on delivery charge') }}</span>
                                    <label class="switcher">
                                        <input type="checkbox" class="switcher_input" value="" checked="" id="" name="">
                                        <span class="switcher_control"></span>
                                    </label>
                                </label>
                                <select class="custom-select multiple-select2" name="options[]" multiple="multiple" data-max-length="5" data-placeholder="{{ translate('Type & select vat rate') }}">
                                    <option value="1">VAT (5%)</option>
                                    <option value="2">GST (7%)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap justify-content-end gap-3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="button" class="btn btn-primary px-3 px-sm-4">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('script')
    <script>
        'use strict';
        $(document).ready(function () {

            function toggleVatCalcStatus() {
                if ($('#vat_calc_status').prop('checked')) {
                    $('.vat_calc_details').removeClass('disabled');
                } else {
                    $('.vat_calc_details').addClass('disabled');
                }
            }

            toggleVatCalcStatus();
            $('#vat_calc_status').on('change', toggleVatCalcStatus);

            function toggleVatCalcOption() {
                if ($('#exclude_product_ptice').prop('checked')) {
                    $('.exclude_product_price_details').removeClass('d-none');
                } else {
                    $('.exclude_product_price_details').addClass('d-none');
                }
            }

            toggleVatCalcOption();
            $('#exclude_product_ptice, #include_product_ptice').on('change', toggleVatCalcOption);

            function selectVatType() {
                $('.order_wise_content, .category_wise_content, .product_wise_content').addClass('d-none');

                let $selectedOption = $('#select_vat_type option:selected');
                let targetSelector = $selectedOption.data('target');

                if (targetSelector) {
                    $(targetSelector).removeClass('d-none');
                }
            }
            selectVatType();
            $('#select_vat_type').on('change', selectVatType);

        });
    </script>


@endpush
