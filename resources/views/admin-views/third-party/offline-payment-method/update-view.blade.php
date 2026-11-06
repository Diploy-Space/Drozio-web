@extends('layouts.admin.app')

@section('title', translate('Edit Offline Payment Method'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
            <div class="">
                <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                    <i class="fi fi-sr-credit-card text-primary d-flex"></i>
                    {{ translate('Edit Offline Payment Method') }}
                </h2>
                <a href="{{ route('admin.settings.third-party.offline-payment-method.index') }}" class="d-flex mt-2 gap-1 align-items-center text-decoration-none">
                    <i class="fi fi-rr-arrow-small-left mt-1 fs-5"></i>
                    <span class="text text-capitalize">{{translate('Back to offline payment methods')}}</span>
                </a>
            </div>
        </div>

        @include('admin-views.third-party._third-party-payment-method-menu')

        <form action="{{ route('admin.settings.third-party.offline-payment-method.update',[$method['id']]) }}" method="POST" id="payment-method-offline" class="form-advance-validation form-advance-inputs-validation">
            @csrf
            <div class="row g-3">
                <div class="col-xxl-6">
                    <div class="card h-100">
                        <div class="card-body offline-method-card-body">
                    <div class="mb-3">
                        <h3 class="mb-1">{{translate('Payment Information')}}</h3>
                        <p class="fs-12 mb-0">{{translate('Choose a payment method such as bank, mobile wallet or card.').' . '.translate('Customers choose from these, so add the relevant input fields.').'.'}}</p>
                    </div>

                    <div class="form-group p-12 p-sm-20 bg-section rounded mb-3">
                        <label for="method_name" class="form-label">{{ translate('Payment method name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="method_name" placeholder="{{ translate('Ex') }}: bKash" name="method_name"  data-required-msg="{{ translate(':field is required', ['field' => translate('Method name')]) }}"  value="{{ $method['method_name'] }}" required>
                    </div>

                    <input type="hidden" name="id" value="{{ $method['id'] }}">
                    <div class="input-fields-section offline-field-section d-flex flex-column gap-3" id="input-fields-section">
                        @foreach ($method['method_fields'] as $item)
                            @include('admin-views.third-party.offline-payment-method.partials._payment-field-card', ['paymentField' => $item])
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-outline-primary w-100 mt-auto wm-add-field-btn add-input-fields-group">
                        <i class="fi fi-sr-add"></i> {{ translate('Add New Field') }}
                    </button>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="card h-100">
                        <div class="card-body offline-method-card-body">
                    <div class="mb-3">
                        <h3 class="mb-1">{{translate('Information required from customer')}}</h3>
                        <p class="fs-12 mb-0">{{translate('Fields customers fill in after completing the offline payment.').' . '. translate('Add fields and mark them required so customers must fill them in.').'.'}}</p>
                    </div>

                    <div class="customer-input-fields-section offline-field-section d-flex flex-column gap-3" id="customer-input-fields-section">
                        @foreach ($method['method_informations'] as $key=>$item)
                            @include('admin-views.third-party.offline-payment-method.partials._customer-field-card', ['customerField' => $item, 'customerKey' => $key])
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-outline-primary w-100 mt-auto wm-add-field-btn add-customer-input-fields-group">
                        <i class="fi fi-sr-add"></i> {{ translate('Add New Field') }}
                    </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-3 mt-3">
                <button type="reset" class="btn btn-secondary">{{ translate('Reset') }}</button>
                <button type="submit" class="btn btn-primary"><i class="fi fi-sr-disk"></i> {{ translate('Update Information') }}</button>
            </div>
        </form>
    </div>
    <span id="get-add-input-field-text"
          data-field-label = "{{translate('Field')}}"
          data-required-suffix = "{{translate('Is required')}}"
          data-input-field-name = "{{translate('Input field Name')}}"
          data-input-field-name-placeholder = "{{translate('Ex').':'.translate('Bank Name')}}"
          data-input-data = "{{translate('Input data')}}"
          data-input-data-placeholder = "{{ translate('Ex') }}: AVC bank"
          data-delete-text = "{{translate('Delete')}}"
    ></span>
    <span id="get-add-customer-input-field-text"
          data-field-label = "{{translate('Field')}}"
          data-required-suffix = "{{translate('Is required')}}"
          data-input-field-name = "{{translate('Input field Name')}}"
          data-input-field-name-placeholder = "{{translate('Ex').':'.translate('Payment By')}}"
          data-input-placeholder = "{{translate('Placeholder')}}"
          data-input-placeholder-placeholder = "{{translate('Ex').':'.translate('Enter name')}}"
          data-delete-text = "{{translate('Delete')}}"
          data-require-text = "{{translate('Is required')}}"
    ></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/third-party/offline-payment.js') }}"></script>
@endpush
