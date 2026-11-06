@extends('layouts.admin.app')

@section('title', translate('Add Offline Payment Method'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
            <div class="">
                <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                    <i class="fi fi-sr-credit-card text-primary d-flex"></i>
                    {{ translate('Add Offline Payment Method') }}
                </h2>
                <a href="{{ route('admin.settings.third-party.offline-payment-method.index') }}" class="d-flex mt-2 gap-1 align-items-center text-decoration-none">
                    <i class="fi fi-rr-arrow-small-left mt-1 fs-5"></i>
                    <span class="text text-capitalize">{{translate('Back to offline payment methods')}}</span>
                </a>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#getInformationModal">
                <i class="fi fi-sr-eye"></i>
                {{translate('Section view')}}
            </button>
        </div>

        <form action="{{ route('admin.settings.third-party.offline-payment-method.add') }}" method="POST" id="payment-method-offline" class="" novalidate>
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
                        <label for="method_name" class="form-label text-capitalize">{{ translate('Payment method name') }} <span class="text-danger">*</span></label>
                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('Add payment method name') }}" data-bs-title="{{ translate('Add payment method name') }}">
                            <i class="fi fi-sr-info"></i>
                        </span>
                        <input type="text" class="form-control" id="method_name" placeholder="{{ translate('Ex') }}: bKash"
                               data-required-msg="{{ translate(':field is required', ['field' => translate('Method name')]) }}"
                               name="method_name" required>
                    </div>

                    <div class="input-fields-section offline-field-section d-flex flex-column gap-3" id="input-fields-section">
                        @include('admin-views.third-party.offline-payment-method.partials._payment-field-card', ['paymentField' => null])
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
                        @include('admin-views.third-party.offline-payment-method.partials._customer-field-card', ['customerField' => null, 'customerKey' => 0])
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
                <button type="submit" class="btn btn-primary">
                    <i class="fi fi-sr-disk"></i> {{ translate('Save Information') }}
                </button>
            </div>
        </form>
    </div>
    <div class="modal fade" id="getInformationModal" tabindex="-1" aria-labelledby="getInformationModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="--bs-modal-width: 560px;">
            <div class="modal-content">
                <div class="modal-header d-flex align-items-center justify-content-between">
                    <h3 class="modal-title mb-0 fs-16">{{ translate('Instructions') }}</h3>
                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body p-4 p-sm-5">
                    <div class="swiper instruction-carousel">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <img loading="lazy" decoding="async" width="80" class="mb-3"
                                         src="{{dynamicAsset(path: 'public/assets/back-end/img/delivery2.png')}}" alt="">
                                    <h4 class="lh-md mb-3 text-capitalize text-center">{{translate('Create your custom offline payment method')}}</h4>
                                    <ul class="d-flex flex-column gap-2 mb-0"><li>{{translate('For a personalised payment experience').'!'}}</li></ul>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="d-flex flex-column gap-2">
                                    <h4 class="lh-md mb-3 text-capitalize text-center">{{translate('How does offline payment method work').'?'}}</h4>
                                    <ul class="d-flex flex-column gap-2 mb-0 ps-3">
                                        <li>{{translate('Step').' '.'1'.' :'.translate('Add').' ‘'.translate('Payment Information').'’'}}</li>
                                        <li>{{translate('Step').' '.'2'.' :'.translate('Click').' ‘ +'.translate('Add New Field').'’'.translate('For more information').'['.translate('According to your payment method').']'}}</li>
                                        <li>{{translate('Step').' '.'3'.' :'.translate('Add').' ‘'.translate('Information required from customer').'’ '.'['.translate('That you need to verify according to your payment method').']'}}</li>
                                        <li>{{translate('Step').' '.'4'.' :'.translate('Click').' ‘ +'.translate('Add New Field').'’'.translate('For more information').'['.translate('According to your payment method').']'}}</li>
                                        <li>{{translate('Step').' '.'5'.' :'.translate('Mark the check box if the field is required')}}</li>
                                        <li>{{translate('Step').' '.'6'.' :'.translate('Click').' ‘'.translate('Submit').'’ '.translate('To save the changes')}}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="d-flex flex-column gap-2">
                                    <h4 class="lh-md mb-3 text-capitalize text-center">{{translate('Important note')}}</h4>
                                    <ul class="d-flex flex-column gap-2 mb-0 ps-3">
                                        <li>{{translate('You can add one or more offline payment methods for your customers')}}</li>
                                        <li>{{translate('The customer picks an offline method and fills in the required details.').'.'}}</li>
                                        <li>{{translate('Admin reviews the payment and updates the order and payment status.').'.'}}</li>
                                        <li>{{translate('Open Order Details and match the payment information.').'.'}}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <img loading="lazy" decoding="async" width="80" class="mb-3"
                                         src="{{dynamicAsset(path: 'public/assets/back-end/img/confirmed.png')}}" alt="">
                                    <h4 class="lh-md mb-3 text-capitalize text-center">{{translate('The two-in-one benefits of ‘Offline Payment Method’ Feature').':'}}</h4>
                                    <ul class="d-flex flex-column gap-2 mb-0 ps-3 align-self-start">
                                        <li>{{translate('Get paid from customers')}}</li>
                                        <li>{{translate('Introduce more convenient payment methods for customers').'!'}}</li>
                                    </ul>
                                    <button type="button" class="btn btn-primary px-10 mt-3 text-capitalize" data-bs-dismiss="modal">{{ translate('Got it') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="instruction-nav d-flex align-items-center justify-content-between gap-2 mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 swiper-button-prev">
                            <i class="fi fi-rr-angle-left"></i> {{ translate('Back') }}
                        </button>
                        <div class="instruction-pagination-custom mb-0"></div>
                        <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 swiper-button-next">
                            {{ translate('Next') }} <i class="fi fi-rr-angle-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
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

