@extends('layouts.front-end.app')

@section('title', translate('Shipping address'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/bootstrap-select.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/plugin/intl-tel-input/css/intlTelInput.css') }}">
@endpush

@push('page_css')
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/checkout.css') }}">
@endpush

@section('content')
    @php($mapApiStatus = getWebConfig(name: 'map_api_status'))
    @php($isGuestCheckout = !auth('customer')->check())
    @php($showCreateAccount = $isGuestCheckout && $web_config['guest_checkout_status'])
    @php($sectionNumber = 0)

    <div class="container co-page rtl text-align-direction">
        <div class="co-steps-bar">
            @include('web-views.partials._checkout-steps', ['step' => 2])
        </div>

        <div class="row">
            <section class="col-lg-8 co-main">
                @if($isGuestCheckout)
                    <div class="co-signin">
                        <i class="tio-user"></i>
                        <span>{{ translate('Already have an account?') }}</span>
                        <a class="co-signin__link" href="{{ route('customer.auth.login') }}">{{ translate('Sign In') }}</a>
                    </div>
                @endif

                @if($physical_product_view)
                    @php($sectionNumber++)
                    <input type="hidden" id="physical_product" name="physical_product" value="yes">

                    <form method="post" class="co-card" id="address-form">
                        <div class="co-card__head">
                            <div class="co-card__lead">
                                <span class="co-card__badge">{{ $sectionNumber }}</span>
                                <div>
                                    <h2 class="co-card__title">{{ translate('Shipping address') }}</h2>
                                    <p class="co-card__note">{{ translate('Where should we deliver your order?') }}</p>
                                </div>
                            </div>

                            @if($shipping_addresses->count() > 0)
                                @include('web-views.checkout.partials._saved-addresses', [
                                    'addresses' => $shipping_addresses,
                                    'itemClass' => 'select_shipping_address',
                                    'itemIdPrefix' => 'shippingAddress',
                                    'methodField' => 'shipping_method_id',
                                ])
                            @endif
                        </div>

                        <div class="co-card__body">
                            <div class="co-grid">
                                <div class="co-field">
                                    <label class="co-label" for="name">{{ translate('Contact Person Name') }}<span>*</span></label>
                                    <input type="text" class="form-control" name="contact_person_name" id="name" {{ $shipping_addresses->count() == 0 ? 'required' : '' }}>
                                </div>

                                <div class="co-field">
                                    <label class="co-label" for="phone">{{ translate('Phone') }}<span>*</span></label>
                                    <input type="tel" class="form-control" name="phone" id="phone" {{ $shipping_addresses->count() == 0 ? 'required' : '' }}>
                                </div>

                                @if($isGuestCheckout)
                                    <div class="co-field co-field--full">
                                        <label class="co-label" for="email">{{ translate('Email') }}<span>*</span></label>
                                        <input type="email" class="form-control" name="email" id="email" {{ $shipping_addresses->count() == 0 ? 'required' : '' }}>
                                    </div>
                                @endif

                                <div class="co-field">
                                    <label class="co-label" for="address_type">{{ translate('Address Type') }}</label>
                                    <select class="form-control" name="address_type" id="address_type">
                                        <option value="permanent">{{ translate('Permanent') }}</option>
                                        <option value="home">{{ translate('Home') }}</option>
                                        <option value="office">{{ translate('Office') }}</option>
                                    </select>
                                </div>

                                <div class="co-field">
                                    <label class="co-label" for="country">{{ translate('Country') }}<span>*</span></label>
                                    <select name="country" id="country" class="form-control selectpicker" data-live-search="true" required>
                                        <option value="" readonly>{{ translate('Select Country') }}</option>
                                        @forelse($countries as $country)
                                            <option value="{{ $country['name'] }}">{{ $country['name'] }}</option>
                                        @empty
                                            <option value="">{{ translate('No country to deliver') }}</option>
                                        @endforelse
                                    </select>
                                </div>

                                <div class="co-field">
                                    <label class="co-label" for="city">{{ translate('City') }}<span>*</span></label>
                                    <input type="text" class="form-control" name="city" id="city" {{ $shipping_addresses->count() == 0 ? 'required' : '' }}>
                                </div>

                                <div class="co-field">
                                    <label class="co-label" for="{{ $zip_restrict_status ? 'select2-zip-container' : 'zip' }}">{{ translate('Zip Code') }}<span>*</span></label>
                                    @if($zip_restrict_status)
                                        <select name="zip" class="form-control selectpicker" data-live-search="true" id="select2-zip-container" required>
                                            @forelse($zip_codes as $code)
                                                <option value="{{ $code->zipcode }}">{{ $code->zipcode }}</option>
                                            @empty
                                                <option value="">{{ translate('No zip to deliver') }}</option>
                                            @endforelse
                                        </select>
                                    @else
                                        <input type="text" class="form-control" name="zip" id="zip" {{ $shipping_addresses->count() == 0 ? 'required' : '' }}>
                                    @endif
                                </div>

                                <div class="co-field co-field--full">
                                    <label class="co-label" for="address">{{ translate('Address') }}<span>*</span></label>
                                    <textarea class="form-control" id="address" name="address" {{ $shipping_addresses->count() == 0 ? 'required' : '' }}></textarea>
                                    <span class="co-hint opacity-0 map-address-alert">
                                        {{ translate('Note') }}: {{ translate('You need to select address from your selected country') }}
                                    </span>
                                </div>
                            </div>

                            @if($mapApiStatus == 1)
                                <div class="co-map">
                                    <label class="co-label">{{ translate('Search your location here') }}</label>
                                    <div class="co-map__frame location-map-canvas-area map-area-alert-border">
                                        <input id="pac-input" class="controls rounded __inline-46 location-search-input-field" title="{{ translate('Search your location here') }}" type="text" placeholder="{{ translate('Search here') }}">
                                        <div class="__h-200px" id="location_map_canvas"></div>
                                    </div>
                                </div>
                            @endif

                            <input type="hidden" id="latitude" name="latitude" value="{{ $default_location ? $default_location['lat'] : 0 }}" readonly>
                            <input type="hidden" id="longitude" name="longitude" value="{{ $default_location ? $default_location['lng'] : 0 }}" readonly>
                        </div>

                        <div class="co-card__foot">
                            <label class="co-check" id="save_address_label">
                                <input type="hidden" name="shipping_method_id" id="shipping_method_id" value="0">
                                @if(!$isGuestCheckout)
                                    <input type="checkbox" name="save_address" id="save_address">
                                    {{ translate('Save this Address') }}
                                @endif
                            </label>
                        </div>
                    </form>

                    @if($showCreateAccount)
                        @include('web-views.checkout.partials._create-account')
                    @endif
                @endif

                @if($billing_input_by_customer)
                    @php($sectionNumber++)

                    <form method="post" class="co-card" id="billing-address-form">
                        <div class="co-card__head">
                            <div class="co-card__lead">
                                <span class="co-card__badge">{{ $sectionNumber }}</span>
                                <div>
                                    <h2 class="co-card__title">{{ translate('Billing address') }}</h2>
                                    <p class="co-card__note">{{ translate('Used for your invoice and payment verification') }}</p>
                                </div>
                            </div>

                            @if($physical_product_view)
                                <label class="co-same" for="same_as_shipping_address">
                                    <input type="checkbox" id="same_as_shipping_address" name="same_as_shipping_address"
                                           class="action-hide-billing-address" {{ $billing_input_by_customer == 1 ? '' : 'checked' }}>
                                    {{ translate('Same as shipping address') }}
                                </label>
                            @endif
                        </div>

                        <div id="hide_billing_address">
                            @if($billing_addresses->count() > 0)
                                <div class="co-card__tools">
                                    @include('web-views.checkout.partials._saved-addresses', [
                                        'addresses' => $billing_addresses,
                                        'itemClass' => 'select_billing_address',
                                        'itemIdPrefix' => 'billingAddress',
                                        'methodField' => 'billing_method_id',
                                    ])
                                </div>
                            @endif

                            <div class="co-card__body">
                                <div class="co-grid">
                                    <div class="co-field">
                                        <label class="co-label" for="billing_contact_person_name">{{ translate('Contact Person Name') }}<span>*</span></label>
                                        <input type="text" class="form-control" name="billing_contact_person_name" id="billing_contact_person_name" {{ $billing_addresses->count() == 0 ? 'required' : '' }}>
                                    </div>

                                    <div class="co-field">
                                        <label class="co-label" for="billing_phone">{{ translate('Phone') }}<span>*</span></label>
                                        <input type="tel" class="form-control phone-input-with-country-picker-2" name="billing_phone" id="billing_phone" {{ $billing_addresses->count() == 0 ? 'required' : '' }}>
                                    </div>

                                    @if($isGuestCheckout)
                                        <div class="co-field co-field--full">
                                            <label class="co-label" for="billing_contact_email">{{ translate('Email') }}<span>*</span></label>
                                            <input type="email" class="form-control" name="billing_contact_email" id="billing_contact_email" {{ $billing_addresses->count() == 0 ? 'required' : '' }}>
                                        </div>
                                    @endif

                                    <div class="co-field">
                                        <label class="co-label" for="billing_address_type">{{ translate('Address Type') }}</label>
                                        <select class="form-control" name="billing_address_type" id="billing_address_type">
                                            <option value="permanent">{{ translate('Permanent') }}</option>
                                            <option value="home">{{ translate('Home') }}</option>
                                            <option value="office">{{ translate('Office') }}</option>
                                        </select>
                                    </div>

                                    <div class="co-field">
                                        <label class="co-label" for="billing_country">{{ translate('Country') }}<span>*</span></label>
                                        <select name="billing_country" class="form-control selectpicker" data-live-search="true" id="billing_country">
                                            <option value="" readonly>{{ translate('Select Country') }}</option>
                                            @foreach($countries as $country)
                                                <option value="{{ $country['name'] }}">{{ $country['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="co-field">
                                        <label class="co-label" for="billing_city">{{ translate('City') }}<span>*</span></label>
                                        <input type="text" class="form-control" name="billing_city" id="billing_city" {{ $billing_addresses->count() == 0 ? 'required' : '' }}>
                                    </div>

                                    <div class="co-field">
                                        <label class="co-label" for="billing_zip">{{ translate('Zip Code') }}<span>*</span></label>
                                        @if($zip_restrict_status)
                                            <select name="billing_zip" class="form-control selectpicker" data-live-search="true" id="billing_zip">
                                                @foreach($zip_codes as $code)
                                                    <option value="{{ $code->zipcode }}">{{ $code->zipcode }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="text" class="form-control" name="billing_zip" id="billing_zip" {{ $billing_addresses->count() == 0 ? 'required' : '' }}>
                                        @endif
                                    </div>

                                    <div class="co-field co-field--full">
                                        <label class="co-label" for="billing_address">{{ translate('Address') }}<span>*</span></label>
                                        <textarea class="form-control" id="billing_address" name="billing_address" {{ $billing_addresses->count() == 0 ? 'required' : '' }}></textarea>
                                        <span class="co-hint opacity-0 map-address-alert">
                                            {{ translate('Note') }}: {{ translate('You need to select address from your selected country') }}
                                        </span>
                                    </div>
                                </div>

                                @if($mapApiStatus == 1)
                                    <div class="co-map">
                                        <label class="co-label">{{ translate('Search your location here') }}</label>
                                        <div class="co-map__frame location-map-billing-canvas-area map-area-alert-border">
                                            <input id="pac-input-billing" class="controls rounded __inline-46 location-search-input-field" title="{{ translate('Search your location here') }}" type="text" placeholder="{{ translate('Search here') }}">
                                            <div class="__h-200px" id="location_map_canvas_billing"></div>
                                        </div>
                                    </div>
                                @endif

                                <input type="hidden" name="billing_method_id" id="billing_method_id" value="0">
                                <input type="hidden" id="billing_latitude" name="billing_latitude" value="{{ $default_location ? $default_location['lat'] : 0 }}" readonly>
                                <input type="hidden" id="billing_longitude" name="billing_longitude" value="{{ $default_location ? $default_location['lng'] : 0 }}" readonly>
                            </div>

                            @if(!$isGuestCheckout)
                                <div class="co-card__foot">
                                    <label class="co-check" id="save-billing-address-label">
                                        <input type="checkbox" name="save_address_billing" id="save_address_billing">
                                        {{ translate('Save this Address') }}
                                    </label>
                                </div>
                            @endif
                        </div>
                    </form>

                    @if($showCreateAccount && !$physical_product_view)
                        @include('web-views.checkout.partials._create-account')
                    @endif
                @endif
            </section>

            @include('web-views.partials._order-summary')
        </div>
    </div>

    <span id="message-update-this-address" data-text="{{ translate('Update this Address') }}"></span>
    <span id="route-customer-choose-shipping-address-other" data-url="{{ route('customer.choose-shipping-address-other') }}"></span>
    <span id="default-latitude-address" data-value="{{ $default_location ? $default_location['lat'] : '-33.8688' }}"></span>
    <span id="default-longitude-address" data-value="{{ $default_location ? $default_location['lng'] : '151.2195' }}"></span>
    <span id="route-action-checkout-function" data-route="checkout-details"></span>
    <span id="system-country-restrict-status" data-value="{{ $country_restrict_status }}"></span>
@endsection

@push('script')
    <script>
        "use strict";
        const deliveryRestrictedCountries = @json($countriesName);

        function deliveryRestrictedCountriesCheck(countryOrCode, elementSelector, inputElement) {
            const foundIndex = deliveryRestrictedCountries.findIndex(country => country.toLowerCase() === countryOrCode.toLowerCase());
            if (foundIndex !== -1) {
                $(elementSelector).removeClass('map-area-alert-danger');
                $(inputElement).parent().find('.map-address-alert').removeClass('opacity-100').addClass('opacity-0')
            } else {
                $(elementSelector).addClass('map-area-alert-danger');
                $(inputElement).val('')
                $(inputElement).parent().find('.map-address-alert').removeClass('opacity-0').addClass('opacity-100')
            }
        }

        $('#is_check_create_account').on('change', function () {
            if ($(this).is(':checked')) {
                $('.is_check_create_account_password_group').fadeIn();
            } else {
                $('.is_check_create_account_password_group').fadeOut();
            }
        });

        $(document).ready(function () {
            if (typeof hideBillingAddressFunction === 'function') {
                hideBillingAddressFunction();
            }
        });
    </script>

    <script src="{{ theme_asset(path: 'public/assets/front-end/js/bootstrap-select.min.js') }}" defer></script>
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/shipping.js') }}" defer></script>

    @if($mapApiStatus == 1)
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{ getWebConfig(name: 'map_api_key') }}&callback=mapsShopping&loading=async&libraries=places&v=3.56"
            defer>
        </script>
    @endif
@endpush
