@extends('theme-views.layouts.profile')
@section('title', translate('My addresses').' | '.$web_config['company_name'].' '.translate('eCommerce'))
@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-5">
        <div class="container">
            <div class="row g-3">
                @include('theme-views.partials._profile-aside')
                <div class="col-lg-9">
                    <div class="card h-100">
                        <div class="card-body p-lg-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <h5 class="text-capitalize">{{ translate('My addresses') }}</h5>
                                <a href="{{ route('account-address-add') }}"
                                   class="btn btn-outline-secondary rounded-pill px-3 px-sm-4">
                                    <span class="d-none d-sm-inline-block text-capitalize">{{ translate('Add address') }}</span>
                                    <i class="bi bi-plus-circle-fill"></i>
                                </a>
                            </div>

                            <div class="mt-3">
                                <div class="row gy-3 text-dark">
                                    @foreach($shippingAddresses as $shippingAddress)
                                        <div class="col-md-6">
                                            <div class="card border-0">
                                                <div class="card-header gap-2 align-items-center d-flex justify-content-between">
                                                    <h6>{{ translate($shippingAddress['address_type']) }}
                                                        ({{ $shippingAddress['is_billing'] == 1 ? translate('Billing address') : translate('Shipping address') }})</h6>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <a href="{{ route('address-edit', $shippingAddress->id) }}"
                                                           class="p-0 bg-transparent border-0">
                                                            <img src="{{ theme_asset('assets/img/svg/location-edit.svg') }}"
                                                                 alt="" class="svg">
                                                        </a>

                                                        <a href="javascript:"
                                                           data-action="{{ route('address-delete', ['id' => $shippingAddress->id]) }}"
                                                           data-message="{{ translate('Want to delete this address').'?' }}"
                                                           class="p-0 bg-transparent border-0 delete-action">
                                                            <img src="{{ theme_asset('assets/img/svg/delete.svg') }}"
                                                                 alt="" class="svg">
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <address>
                                                        <dl class="mb-0 flexible-grid width--5rem">
                                                            <dt>{{ translate('Name') }}</dt>
                                                            <dd>{{ $shippingAddress['contact_person_name'] }}</dd>

                                                            <dt>{{ translate('Phone') }}</dt>
                                                            <dd><a href="tel:{{ $shippingAddress['phone'] }}"
                                                                   class="text-dark">{{ $shippingAddress['phone'] }}</a></dd>

                                                            <dt>{{ translate('City') }}</dt>
                                                            <dd>{{ $shippingAddress['city'] }}</dd>

                                                            <dt>{{ translate('Zip code') }}</dt>
                                                            <dd>{{ $shippingAddress['zip'] }}</dd>

                                                            <dt>{{ translate('Address') }}</dt>
                                                            <dd>{{ $shippingAddress['address'] }}</dd>

                                                            <dt>{{ translate('Country') }}</dt>
                                                            <dd>{{ $shippingAddress['country'] }}</dd>
                                                        </dl>
                                                    </address>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if(count($shippingAddresses) == 0)
                                    <div class="d-flex justify-content-center align-items-center">
                                        <div class="d-flex flex-column justify-content-center align-items-center gap-2 py-5 w-100">
                                            <img width="60" class="mb-3"
                                                 src="{{ theme_asset('assets/img/empty-state/empty-address.svg') }}" alt="">
                                            <h5 class="text-center text-muted">
                                                {{ translate('No address found') }}!
                                            </h5>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
