@extends('layouts.admin.app')

@section('title', translate('Other Setup'))

@section('content')
    <div class="content container-fluid">
        <div class="row g-3 align-items-center mb-3">
            <div class="col-md-12">
                <h1 class="mb-3 sm-sm-20">
                    {{ translate('Shop Setup') }}
                </h1>
                @include("admin-views.inhouse-shop._inhouse-shop-menu")
            </div>
        </div>

        <form action="{{ route('admin.settings.business-settings.inhouse-shop-setup') }}" method="POST"
              class="form-advance-validation form-advance-inputs-validation non-ajax-form-validate" novalidate="novalidate">
            @csrf

            @include('admin-views.inhouse-shop._verified-badge-card')

            @if ($freeDeliveryModules)
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="mb-sm-20 mb-3">
                            <h2>{{ translate('Order Setup') }}</h2>
                            <p class="fs-12 mb-0">
                                {{ translate('Here you can configure the settings related to orders') }}
                            </p>
                        </div>

                        @foreach ($freeDeliveryModules as $freeDeliveryModule)
                            <div class="p-12 p-sm-20 bg-section rounded {{ $loop->last ? '' : 'mb-3 mb-sm-20' }}">
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        @include('admin-views.inhouse-shop._free-delivery-field')
                                    </div>
                                    @if (isset($minimumOrderAmountModules[$freeDeliveryModule['key']]))
                                        <div class="col-lg-6">
                                            @include('admin-views.inhouse-shop._minimum-order-amount-field', ['minimumOrderAmountModule' => $minimumOrderAmountModules[$freeDeliveryModule['key']]])
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                <div
                    class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset"
                            class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="submit" class="btn btn-primary px-3 px-sm-4">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>

    </div>
    @include("layouts.admin.partials.offcanvas._inhouse-shop-others-setup")
@endsection
