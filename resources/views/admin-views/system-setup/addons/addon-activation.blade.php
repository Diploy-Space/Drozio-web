@extends('layouts.admin.app')

@section('title', translate('Addon Activation Process'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-plug text-primary d-flex"></i>
                {{ translate('Addon Activation Process') }}
            </h2>
        </div>

        @php($isDeliverymanAppActivated = ($addonData['deliveryman_app']['activation_status'] ?? 0) == 1)

        <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
            <i class="fi fi-sr-lightbulb-on text-info"></i>
            <span>{{ translate('Enable an add-on and enter its purchase code to activate it.') }}</span>
        </div>

        @if(isset($addonData['deliveryman_app']))
        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('admin.settings.system-setup.addon-activation.activation') }}" method="post" class="form-advance-validation  form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
                    @csrf
                    <input type="hidden" name="addon_name" value="deliveryman_app">
                    <input type="hidden" name="software_type" value="addon">
                    <input type="hidden" name="software_id" value="MzYwODUwNDE=">
                    <div class="view-details-container">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h3 class="mb-0">{{ translate('Deliveryman App') }}</h3>
                                    @if($isDeliverymanAppActivated)
                                        <span class="badge bg-success bg-opacity-10 text-success fw-semibold text-capitalize">{{ translate('Activated') }}</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold text-capitalize">{{ translate('Not activated') }}</span>
                                    @endif
                                </div>
                                <p class="mb-0 fs-12">
                                    {{ translate('Enable the status and set up the required information to activate.') }}.
                                    {{ translate('You can purchase the deliveryman app from') }}
                                    <a href="https://store.6amtech.com/product/6valley-e-commerce-delivery-man-flutter-app/"
                                       target="_blank" rel="noopener" class="text-decoration-underline fw-semibold text-primary">
                                        {{ translate('Here') }}</a>.
                                </p>
                            </div>
                            <div class="d-flex gap-2">
                                <label class="switcher">
                                    <input
                                        class="switcher_input custom-modal-plugin"
                                        type="checkbox" value="1" name="status"
                                        {{ isset($addonData['deliveryman_app']['activation_status']) && $addonData['deliveryman_app']['activation_status'] == 1 ? 'checked' : '' }}
                                        data-modal-type="input-change-form"
                                        data-modal-form="#smtp-mail-config-form"
                                        data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/maintenance_mode-on.png') }}"
                                        data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/maintenance_mode-off.png') }}"
                                        data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Deliveryman App addon')]).'?' }}"
                                        data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Deliveryman App addon')]).'?' }}"
                                        data-on-message="<p>{{ translate('Enabling Deliveryman App addon status will allow the API for use') }}</p>"
                                        data-off-message="<p>{{ translate('Disabling Deliveryman App addon status will disable the API for use') }}</p>">
                                    <span class="switcher_control"></span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-3 mt-sm-4">
                            <div class="p-12 p-sm-20 bg-section rounded">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label text-capitalize" for="person_name">
                                                {{ translate('Name') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" value="{{ showDemoModeInputValue(value: $addonData['deliveryman_app']['name'] ?? "") }}"
                                                   placeholder="{{ translate('Ex') }}: {{ 'Jone Doe' }}"
                                                   name="name" class="form-control"  data-required-msg="{{translate('Name is field is required')}}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label text-capitalize" for="person_email">
                                                {{ translate('Email') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" value="{{ showDemoModeInputValue(value: $addonData['deliveryman_app']['email'] ?? "") }}"
                                                   placeholder="{{ translate('Ex') }}: {{ 'jone-doe@example.com' }}"
                                                   name="email" class="form-control"  data-required-msg="{{translate('Email is field is required')}}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label text-capitalize" for="username">
                                                {{ translate('Username') }}
                                                <span class="text-danger">*</span>
                                                <span class="tooltip-icon"
                                                      data-bs-toggle="tooltip"
                                                      data-bs-placement="right"
                                                      aria-label="{{ translate('Please use the username exactly as it is, without any spaces.') }} {{ translate('Make sure to enter the name correctly.') }}"
                                                      data-bs-title="{{ translate('Please use the username exactly as it is, without any spaces.') }} {{ translate('Make sure to enter the name correctly.') }}"
                                                >
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                            </label>
                                            <input type="text" value="{{ showDemoModeInputValue(value: $addonData['deliveryman_app']['username']) }}"
                                                   placeholder="{{ translate('Ex') }}: {{ 'jonedoe3050' }}"
                                                   name="username" class="form-control"  data-required-msg="{{translate('Username is field is required')}}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label text-capitalize" for="purchase_key">
                                                {{ translate('Purchase code') }}
                                                <span class="text-danger">*</span>
                                                <span class="tooltip-icon"
                                                      data-bs-toggle="tooltip"
                                                      data-bs-placement="right"
                                                      aria-label="{{ translate('Please check your purchase code before proceeding with the update.') }}"
                                                      data-bs-title="{{ translate('Please check your purchase code before proceeding with the update.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                            </label>
                                            <input type="text" value="{{ showDemoModeInputValue(value: $addonData['deliveryman_app']['purchase_key']) }}"
                                                   placeholder="{{ translate('Ex') }}: {{ 'CAWFRWRAAWRCAWRA' }}"
                                                   data-required-msg="{{translate('Purchase key field is required')}}"
                                                   name="purchase_key" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end flex-wrap gap-3 mt-4">
                                <button type="reset" class="btn btn-secondary w-120 px-4">
                                    {{ translate('Reset') }}
                                </button>
                                <button class="btn btn-primary w-120 px-4 {{ getDemoModeFormButton(type: 'class') }}"
                                        type="{{ getDemoModeFormButton(type: 'button') }}">
                                    <i class="fi fi-sr-disk"></i>
                                    {{ translate('Save') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </div>
    @include("layouts.admin.partials.offcanvas._addon-activation")
@endsection
