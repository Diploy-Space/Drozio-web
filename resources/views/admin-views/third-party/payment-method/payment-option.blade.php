@php use Illuminate\Support\Facades\Session; @endphp
@extends('layouts.admin.app')

@section('title', translate('Payment options'))

@section('content')
    @php($direction = Session::get('direction') === 'rtl' ? 'right' : 'left')
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/business-setup.png') }}" alt="">
                {{ translate('Business Setup') }}
            </h2>
        </div>

        @include('admin-views.third-party._third-party-payment-method-menu')

        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('admin.settings.business-settings.payment-method.payment-option') }}" method="post" id="payment-methods-settings-form">
                    @csrf
                    <h5 class="mb-4 text-uppercase d-flex text-capitalize">{{ translate('Payment methods') }}</h5>
                    <div class="row">
                        @isset($cashOnDelivery)
                            <div class="col-xl-4 col-sm-6">
                                <div class="form-group">
                                    <div class="d-flex justify-content-between align-items-center gap-10 form-control">
                                        <span class="title-color">
                                            {{ translate('Cash On Delivery') }}
                                            <span class="input-label-secondary cursor-pointer" data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                title="{{ translate('Makes cash on delivery available as a payment option.') }}">
                                                <img width="16"
                                                    src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}"
                                                    alt="">
                                            </span>
                                        </span>
                                        <label class="switcher" for="cash-on-delivery">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="cash_on_delivery"
                                                id="cash-on-delivery"
                                                {{ $cashOnDelivery['status'] == 1 ? 'checked' : '' }}
                                                data-modal-type="input-change"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/cod-on.png') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/warning.png') }}"
                                                data-on-title="{{ translate('Want to Turn ON the Cash On Delivery option') }}"
                                                data-off-title="{{ translate('Want to Turn OFF the Cash On Delivery option') }}"
                                                data-on-message="<p>{{ translate('Customers can select cash on delivery at checkout.') }}</p>"
                                                data-off-message="<p>{{ translate('Hides cash on delivery from the checkout page.') }}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endisset
                        @isset($digitalPayment)
                            <div class="col-xl-4 col-sm-6">
                                <div class="form-group">
                                    <div class="d-flex justify-content-between align-items-center gap-10 form-control">
                                        <span class="title-color">
                                            {{ translate('Digital Payment') }}
                                            <span class="input-label-secondary cursor-pointer" data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                title="{{ translate('If enabled, customers can choose digital payment options during the checkout process') }}">
                                                <img width="16"
                                                    src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}"
                                                    alt="">
                                            </span>
                                        </span>
                                        <label class="switcher" for="digital-payment">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="digital_payment"
                                                id="digital-payment"
                                                {{ $digitalPayment['status'] == 1 ? 'checked' : '' }}
                                                data-modal-type="input-change"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/digital-payment-on.png') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/digital-payment-off.png') }}"
                                                data-on-title="{{ translate('Want to Turn ON the Digital Payment option') }}"
                                                data-off-title="{{ translate('Want to Turn OFF the Digital Payment option') }}"
                                                data-on-message="<p>{{ translate('If enabled customers can select Digital Payment during checkout') }}</p>"
                                                data-off-message="<p>{{ translate('If disabled Digital Payment options will be hidden from the checkout page') }}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endisset
                        @isset($offlinePayment)
                            <div class="col-xl-4 col-sm-6">
                                <div class="form-group">
                                    <div class="d-flex justify-content-between align-items-center gap-10 form-control">
                                        <span class="title-color">
                                            {{ translate('Offline Payment') }}
                                            <span class="input-label-secondary cursor-pointer" data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                title="{{ translate('Offline Payment allows customers to use external payment methods.') }}
                                                {{ translate('They must share payment details with the vendor afterward.') }}
                                                {{ translate('Sets whether customers can pay offline.') }}">
                                                <img width="16"
                                                    src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}"
                                                    alt="">
                                            </span>
                                        </span>
                                        <label class="switcher" for="offline-payment">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="offline_payment"
                                                id="offline-payment"
                                                {{ $offlinePayment['status'] == 1 ? 'checked' : '' }}
                                                data-modal-type="input-change"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/digital-payment-on.png') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/digital-payment-off.png') }}"
                                                data-on-title="{{ translate('Want to Turn ON the Offline Payment option') }}"
                                                data-off-title="{{ translate('Want to Turn OFF the Offline Payment option') }}"
                                                data-on-message="<p>{{ translate('If enabled customers can pay through external payment methods') }}</p>"
                                                data-off-message="<p>{{ translate('If disabled customers have to use the system added payment gateways') }}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endisset
                        <div class="col-12">
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn--primary px-5 text-uppercase">
                                    {{ translate('Save') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade warning-modal" id="active-one-method-modal" tabindex="-1"
        aria-labelledby="active-one-method-modal" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close btn-close-danger" data-bs-dismiss="modal" aria-label="Close"><i
                            class="tio-clear"></i></button>
                </div>
                <div class="modal-body px-4 px-sm-5 pt-0">
                    <div class="d-flex flex-column align-items-center text-center gap-20 mb-5">
                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/modal/warning.png') }}"
                            width="80" alt="">
                        <h5 class="modal-title">
                            {{ translate('You must activate at least one method') }}
                        </h5>
                        <div class="text-center">
                            <p>
                                {{ translate('You cannot turn off all payment methods at a time.') }}
                                {{ translate('Must activate at least 1 payment method for a smooth order payment system.') }}
                            </p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <button type="button" class="btn btn--primary min-w-120 font-weight-bold" data-bs-dismiss="modal">
                            {{ translate('Okay') }}, {{ translate('Got it') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade warning-modal" id="minimum-one-digital-payment" tabindex="-1" aria-labelledby="minimum-one-digital-payment"
        aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close btn-close-danger" data-bs-dismiss="modal" aria-label="Close"><i
                            class="tio-clear"></i></button>
                </div>
                <div class="modal-body px-4 px-sm-5 pt-0">
                    <div class="d-flex flex-column align-items-center text-center gap-20 mb-5">
                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/modal/warning.png') }}"
                            width="80" alt="">
                        <h5 class="modal-title">
                            {{ translate('You must activate one of the digital payment methods.') }}
                        </h5>
                        <div class="text-center">
                            <p class="modal-message">
                            </p>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <a class="btn btn--primary min-w-120 font-weight-bold minimum-one-digital-payment"
                           href="{{ route('admin.settings.third-party.payment-method.index') }}" target="_blank">
                            {{ translate('Go to 3rd party payment methods') }}
                        </a>
                        <a class="btn btn--primary min-w-120 font-weight-bold minimum-one-offline-payment-method" href="{{ route('admin.settings.third-party.offline-payment-method.index') }}" target="_blank">
                            {{ translate('Go to Offline Payment Methods') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
