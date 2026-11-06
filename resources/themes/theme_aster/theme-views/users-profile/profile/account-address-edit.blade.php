@extends('theme-views.layouts.profile')

@section('title', translate('Edit Address').' | '.$web_config['company_name'].' '.translate('eCommerce'))

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-5">
        <div class="container">
            <div class="row g-3">
                @include('theme-views.partials._profile-aside')
                <div class="col-lg-9">
                    <div class="card h-100">
                        <div class="card-body p-lg-4">
                            <div class="d-flex d-md-none justify-content-end">
                                <a href="{{ route('user-profile') }}"
                                   class="btn-link text-secondary d-flex align-items-baseline">
                                    <i class="bi bi-chevron-left fs-12"></i> {{translate('Go Back')}}
                                </a>
                            </div>
                            <div class="mt-4">
                                <form action="{{route('address-update')}}" method="post">
                                    @csrf
                                    <div class="row gy-4">
                                        <div class="col-md-6">
                                            <input type="hidden" name="id" value="{{ $shippingAddress->id}}">
                                            <div class="">
                                                <h6 class="fw-semibold text-muted mb-3 text-capitalize">{{translate('Choose label')}}</h6>
                                                <ul class="option-select-btn flex-wrap style--two gap-4 mb-4">
                                                    <li>
                                                        <label>
                                                            <input type="radio" name="address_type" hidden
                                                                   value="home" {{ Str::lower($shippingAddress->address_type) == 'home' ? 'checked':''}}>
                                                            <span><i class="bi bi-house"></i></span>
                                                        </label>
                                                        {{translate('Home')}}
                                                    </li>
                                                    <li>
                                                        <label>
                                                            <input type="radio" name="address_type" hidden
                                                                   value="permanent" {{ Str::lower($shippingAddress->address_type) == 'permanent' ? 'checked':''}}>
                                                            <span><i class="bi bi-paperclip"></i></span>
                                                        </label>
                                                        {{translate('Permanent')}}
                                                    </li>
                                                    <li>
                                                        <label>
                                                            <input type="radio" name="address_type" hidden=""
                                                                   value="office" {{ Str::lower($shippingAddress->address_type) == 'office' ? 'checked':''}}>
                                                            <span><i class="bi bi-briefcase"></i></span>
                                                        </label>
                                                        {{ translate('Office') }}
                                                    </li>
                                                </ul>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="name"
                                                       class="text-capitalize">{{translate('Contact person')}}</label>
                                                <input type="text" id="name" name="name" class="form-control"
                                                       value="{{ $shippingAddress['contact_person_name']}}"
                                                       placeholder="{{ translate('Ex') }}: John Doe">
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="phone2">{{translate('Phone')}}</label>
                                                <div
                                                    class="select-wrap focus-border form-control rounded d-flex align-items-center px-0">
                                                    <input type="tel" id="phone"
                                                           class="form-control bg-transparent focus-input phone-input-with-country-picker"
                                                           name="phone" value="{{ $shippingAddress['phone'] }}"
                                                           placeholder="{{translate('Ex').':'.('01xxxxxxxxx')}}">
                                                </div>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label for="country">{{translate('Country')}}</label>
                                                <select name="country" id="country"
                                                        class="form-select select2 select_picker">
                                                    <option value="" disabled selected>
                                                        {{ translate('Select Country') }}
                                                    </option>
                                                    @if($country_restrict_status)
                                                        @foreach($delivery_countries as $country)
                                                            <option
                                                                value="{{ $country['name']}}" {{ $country['name'] == $shippingAddress['country']?'selected' : ''}}>{{ $country['name']}}</option>
                                                        @endforeach
                                                    @else
                                                        @foreach(COUNTRIES as $country)
                                                            <option
                                                                value="{{ $country['name'] }}" {{ $shippingAddress['country'] == $country['name']? 'selected' : '' }}>{{ $country['name'] }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="city">{{translate('City')}}</label>
                                                <input class="form-control" type="text" id="address-city" name="city"
                                                       value="{{ $shippingAddress->city }}" required
                                                       placeholder="{{ translate('Ex') }}: Dhaka">
                                            </div>

                                            <div class="form-group">
                                                <label class="text-capitalize"
                                                       for="zip-code">{{translate('Zip Code')}}</label>
                                                @if($zip_restrict_status)
                                                    <select name="zip" class="form-control select2 select-picker"
                                                            data-live-search="true" id="" required>
                                                        @foreach($delivery_zipcodes as $zip)
                                                            <option
                                                                value="{{ $zip->zipcode }}" {{ $zip->zipcode == $shippingAddress->zip? 'selected' : ''}}>{{ $zip->zipcode }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input class="form-control" type="text" id="zip_code" name="zip"
                                                           value="{{ $shippingAddress->zip }}" required  placeholder="{{ translate('Ex') }}: {{ '1216' }}">
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6 mt-5 mt-md-0">
                                            <div class="d-none d-md-flex justify-content-end mb-5">
                                                <a href="{{ route('user-profile') }}"
                                                   class="btn-link text-secondary d-flex align-items-baseline">
                                                    <i class="bi bi-chevron-left fs-12"></i> {{translate('Go Back')}}
                                                </a>
                                            </div>
                                            <h6 class="fw-semibold text-muted mb-3 text-capitalize">{{translate('Choose address type')}}</h6>
                                            <div class="d-flex flex-wrap style--two gap-4 mb-3">
                                                <div>
                                                    <label class="d-flex align-items-center gap-2 cursor-pointer">
                                                        <input type="radio" name="is_billing"
                                                               value="1" {{ $shippingAddress->is_billing == 1 ? 'checked' : ''}} >
                                                        {{translate('Billing address')}}
                                                    </label>
                                                </div>
                                                <div>
                                                    <label class="d-flex align-items-center gap-2 cursor-pointer">
                                                        <input type="radio" name="is_billing"
                                                               value="0" {{ $shippingAddress->is_billing == 0 ? 'checked' : ''}} >
                                                        {{translate('Shipping address')}}
                                                    </label>
                                                </div>
                                            </div>
                                            @if(getWebConfig('map_api_status') ==1 )
                                                <div class="mb-3 position-relative">
                                                    <input id="pac-input" class="controls rounded __inline-46"
                                                           title="{{translate('Search your location here')}}"
                                                           type="text"
                                                           placeholder="{{translate('Search here')}}"/>
                                                    <div class="dark-support rounded w-100 __h-14rem"
                                                         id="location_map_canvas"></div>
                                                </div>
                                            @endif
                                            <div class="form-group">
                                                <label for="address">{{translate('Address')}}</label>
                                                <textarea name="address" id="address" rows="5" class="form-control"
                                                          placeholder="{{ translate('Ex') }}: 1216 Dhaka">{{ $shippingAddress['address']}}</textarea>
                                            </div>
                                        </div>
                                        <input type="hidden" id="latitude"
                                               name="latitude" class="form-control d-inline"
                                               placeholder="{{ translate('Ex').':'.'-94.22213' }}"
                                               value="{{ $shippingAddress->latitude??0}}" required readonly>
                                        <input type="hidden"
                                               name="longitude" class="form-control"
                                               placeholder="{{ translate('Ex').':'.'103.344322' }}" id="longitude"
                                               value="{{ $shippingAddress->longitude??0}}" required readonly>
                                        <div class="col-12">
                                            <div
                                                class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                                                <label class="custom-checkbox"></label>

                                                <div class="d-flex justify-content-end gap-3">
                                                    <button type="reset"
                                                            class="btn btn-secondary">{{translate('Reset')}}</button>
                                                    <button type="submit"
                                                            class="btn btn-primary text-capitalize">{{translate('Update address')}}</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <span id="address-latitude" data-latitude="{{ $shippingAddress['latitude']??'-33.8688'}}" hidden></span>
    <span id="address-longitude" data-longitude="{{ $shippingAddress['longitude']??'151.2195'}}" hidden></span>
@endsection
@push('script')
    <script src="{{ theme_asset('assets/plugins/select2/js/select2.min.js') }}" defer></script>
    <script src="{{ theme_asset('assets/js/address.js') }}" defer></script>
    @if(getWebConfig('map_api_status') ==1 )
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{getWebConfig('map_api_key')}}&callback=callBackFunction&loading=async&libraries=places&v=3.56"
            defer>
        </script>
    @endif
@endpush
