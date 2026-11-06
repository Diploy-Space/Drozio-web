@extends('layouts.admin.app')

@section('title', translate('Google Map APIs'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-settings-sliders text-primary d-flex"></i>
                {{ translate('3rd Party') }} - {{ translate('Other Configurations') }}
            </h2>
        </div>
        @include('admin-views.third-party._third-party-others-menu')

        <form action="{{ config('app.mode') != 'demo' ? route('admin.settings.third-party.map-api') : 'javascript:' }}"
            method="POST" enctype="multipart/form-data" id="google-map-api-status-form" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate>
            @csrf

            <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-start mb-2">
                <i class="fi fi-sr-lightbulb-on text-info mt-1"></i>
                <span>
                    <span class="fw-semibold">{{ translate('Client Key') }}:</span>
                    {{ translate('Enable the Maps JavaScript API, optionally restricted by HTTP referrer.') }}.
                    <span class="fw-semibold">{{ translate('Server Key') }}:</span>
                    {{ translate('Enable the Places API and you can restrict it by IP address') }}.
                    {{ translate('The same API key works for both fields.') }}.
                </span>
            </div>
            <div class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-start mb-3">
                <i class="fi fi-sr-info text-warning mt-1"></i>
                <span>
                    {{ translate('Map features will not work until this section is configured.') }}.
                </span>
            </div>
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex gap-3 justify-content-between align-items-center flex-wrap mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="method-icon rounded-circle border bg-white d-inline-flex align-items-center justify-content-center flex-shrink-0 text-primary">
                                <i class="fi fi-sr-map-marker d-flex"></i>
                            </span>
                            <div>
                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                    <h2 class="mb-0">{{ translate('Google Map API') }}</h2>
                                    <span class="badge badge-soft-{{ $mapAPIStatus ? 'success' : 'secondary' }} fs-10">
                                        {{ $mapAPIStatus ? translate('Active') : translate('Inactive') }}
                                    </span>
                                </div>
                                <p class="mb-0 fs-12">
                                    {{ translate('Add Google API credentials to enable Google Maps.') }}.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex gap-3 align-items-center">
                            <label class="switcher mx-auto" for="map-api-status-id">
                                <input
                                    class="switcher_input custom-modal-plugin"
                                    type="checkbox" value="1" name="status"
                                    id="map-api-status-id"
                                    {{ $mapAPIStatus ? 'checked' : '' }}
                                    data-modal-type="input-change-form"
                                    data-modal-form="#google-map-api-status-form"
                                    data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/delivery-available-country-on.png') }}"
                                    data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/delivery-available-country-off.png') }}"
                                    data-on-title = "{{translate('Want to Turn ON :item', ['item' => translate('Map')]).'?'}}"
                                    data-off-title = "{{translate('Want to Turn OFF :item', ['item' => translate('Map')]).'?'}}"
                                    data-on-message = "<p>{{translate('If enabled, map will be available in the system')}}</p>"
                                    data-off-message = "<p>{{translate('If enabled, map will be hidden from the system')}}</p>"
                                    data-on-button-text="{{ translate('Turn On') }}"
                                    data-off-button-text="{{ translate('Turn Off') }}">
                                <span class="switcher_control"></span>
                            </label>
                            @if(getWebConfig('map_api_status') == 1)
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mapViewModal">
                                <i class="fi fi-sr-region-pin"></i>
                                {{ translate('Test Map View') }}
                            </button>
                            @endif
                        </div>
                    </div>
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label" for="">{{translate('Map api key').'('.translate('Client').')'}}
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('Enter your client map api key') }}" data-bs-title="{{ translate('Enter your client map api key') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <div class="position-relative">
                                        <input type="password" placeholder="{{translate('Map api key').' ('.translate('Client').')'}}"
                                           class="form-control pe-5" name="map_api_key" autocomplete="off"
                                               data-required-msg="{{translate('Map api key field is required')}}"
                                           value="{{ showDemoModeInputValue(value: $mapAPIKey) }}" required>
                                        <span class="changePassTarget position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-body-secondary" role="button" aria-label="{{ translate('Show hide') }}">
                                            <i class="fi fi-sr-eye"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label" for="">{{translate('Map api key')}} ({{translate('Server')}})
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('Enter your server map api key') }}" data-bs-title="{{ translate('Enter your server map api key') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <div class="position-relative">
                                        <input type="password" placeholder="{{translate('Map api key')}} ({{translate('Server')}})"
                                            class="form-control pe-5" name="map_api_key_server" autocomplete="off"
                                               data-required-msg="{{translate('Map api key field is required')}}"
                                            value="{{ showDemoModeInputValue(value: $mapAPIKeyServer) }}" required>
                                        <span class="changePassTarget position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-body-secondary" role="button" aria-label="{{ translate('Show hide') }}">
                                            <i class="fi fi-sr-eye"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                <div class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="{{ getDemoModeFormButton(type: 'button') }}"
                            class="btn btn-primary px-3 px-sm-4 {{ getDemoModeFormButton(type: 'class') }}">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>

        </form>
    </div>

    @if(getWebConfig('map_api_status') == 1)
        <div class="modal fade" id="mapViewModal" tabindex="-1" aria-labelledby="mapViewModal" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 px-4 py-3 d-flex justify-content-between">
                    <h2 class="m-0">{{ translate('Map View') }}</h2>
                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                        data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body px-4 py-0 mb-4 overflow-y-scroll">
                    <div class="form-group position-relative h-100 aspect-ratio-4-2">
                        <input id="map-pac-input" class="form-control max-w-180 mt-1 position-absolute"
                               title="{{ translate('Search your location here') }}" type="text"
                               placeholder="{{ translate('Search here') }}" />
                        <div class="rounded w-100 h-100 min-h-260" id="location-map-canvas"></div>

                        <div class="latitude-longitude-container">
                            <span class="latitude-longitude-item">
                                {{ 'Lat' }} : <span id="get-default-latitude" data-latitude="{{ $default_location['lat'] ?? '-33.8688' }}">{{ $default_location['lat'] ?? '-33.8688' }}</span>
                            </span>
                            <span class="latitude-longitude-item">
                                {{ 'Long' }} : <span id="get-default-longitude" data-longitude="{{ $default_location['lng'] ?? '151.2195' }}">{{ $default_location['lng'] ?? '151.2195' }}</span>
                            </span>
                            <input type="hidden" id="latitude" name="latitude" value="{{ $default_location['lat'] ?? '-33.8688' }}">
                            <input type="hidden" id="longitude" name="longitude" value="{{ $default_location['lng'] ?? '151.2195' }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @include("layouts.admin.partials.offcanvas._google-map-api")
@endsection

@push('script')
    @if(getWebConfig('map_api_status') == 1)
        <script src="https://maps.googleapis.com/maps/api/js?key={{ getWebConfig('map_api_key') }}&callback=googleMapInitialize&loading=async&libraries=places&v=3.56"
                defer>
        </script>
    @endif
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/business-settings/general-page.js') }}"></script>
@endpush
