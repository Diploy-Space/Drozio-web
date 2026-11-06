<form action="{{ route('admin.settings.third-party.payment-method.addon-payment-set') }}" method="POST"
      id="{{$gateway->key_name}}-form" enctype="multipart/form-data" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate>
    @csrf @method('PUT')
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas-{{ $gateway->key_name }}"
         aria-labelledby="offcanvas-{{ str_replace('_',' ',$gateway->key_name) }}-Label">
        <div class="offcanvas-header bg-body">
            <h3 class="mb-0 text-capitalize">
                {{ translate('Setup') }} - {{ str_replace('_',' ',$gateway->key_name) }}
            </h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="bg-danger bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3 mb-sm-20">
                <i class="fi fi-sr-triangle-warning text-danger"></i>
                <span>
                    {{ translate('Please configure your payment methods before enabling the status.') }}
                </span>
            </div>

            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <h4 class="text-capitalize">{{ str_replace('_',' ', $gateway->key_name) }}</h4>
                <p class="fs-12">
                    {{ translate('If you turn off customer cannot pay through this payment gateway') }}
                </p>

                <div class="border rounded px-3 py-2 d-flex justify-content-between align-items-center bg-white">
                    <h4 class="text-capitalize mb-0">{{ translate('Status') }}</h4>

                    @php($additionalData = $gateway['additional_data'] != null ? json_decode($gateway['additional_data']) : [])
                    <?php
                        $gatewayImgPath = dynamicAsset(path: 'public/assets/back-end/img/modal/payment-methods/' . $gateway->key_name . '.png');
                        if (!empty($additionalData) && $additionalData?->gateway_image && file_exists(base_path('storage/app/public/payment_modules/gateway_image/' . $additionalData->gateway_image))) {
                            $gatewayImgPath = $additionalData->gateway_image ? dynamicStorage(path: 'storage/app/public/payment_modules/gateway_image/' . $additionalData->gateway_image) : $gatewayImgPath;
                        }
                    ?>

                    @if(($gateway->is_active == 0 && $gateway->is_enabled_to_use) || ($gateway->is_active == 1))
                        <label class="switcher show-status-text">
                            <input
                                class="switcher_input custom-modal-plugin"
                                type="checkbox" value="1" name="status"
                                id="{{ $gateway->key_name }}-canvas"
                                {{ $gateway['is_active'] ? 'checked' : '' }}
                                data-modal-type="input-change"
                                data-on-image="{{ $gatewayImgPath }}"
                                data-off-image="{{ $gatewayImgPath }}"
                                data-on-title="{{ translate('Want to Turn ON') }} {{ str_replace('_', ' ', strtoupper($gateway->key_name)) }} {{ translate('As the Digital Payment method').'?' }}"
                                data-off-title="{{ translate('Want to Turn OFF') }} {{ str_replace('_', ' ', strtoupper($gateway->key_name)) }} {{ translate('As the Digital Payment method').'?' }}"
                                data-on-message="<p>{{ translate('If enabled customers can use this payment method') }}</p>"
                                data-off-message="<p>{{ translate('If disabled this payment method will be hidden from the checkout page') }}</p>">
                            <span class="switcher_control"></span>
                        </label>
                    @else
                        <label class="switcher" data-bs-toggle="modal" data-bs-target="#gateway-modal-{{ $gateway['key_name'] }}">
                            <input disabled
                                   class="switcher_input custom-modal-plugin"
                                   type="checkbox" value="1" name="status"
                                   id="{{ $gateway->key_name }}-canvas"
                                   {{ $gateway['is_active'] ? 'checked' : '' }}
                                   data-modal-type="input-change"
                                   data-on-image="{{ $gatewayImgPath }}"
                                   data-off-image="{{ $gatewayImgPath }}"
                                   data-on-title="{{ translate('Want to Turn ON') }} {{ str_replace('_', ' ', strtoupper($gateway->key_name)) }} {{ translate('As the Digital Payment method').'?'}}"
                                   data-off-title="{{ translate('Want to Turn OFF') }} {{ str_replace('_', ' ', strtoupper($gateway->key_name)) }} {{ translate('As the Digital Payment method').'?'}}"
                                   data-on-message="<p>{{ translate('If enabled customers can use this payment method') }}</p>"
                                   data-off-message="<p>{{ translate('If disabled this payment method will be hidden from the checkout page') }}</p>">
                            <span class="switcher_control"></span>
                        </label>
                    @endif
                </div>
            </div>

            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <h4>{{ translate('Choose Logo') }} <span class="text-danger">*</span></h4>
                <p class="fs-12">{{ translate('It will show in website & app.') }}</p>

                <div class="upload-file">
                    <input type="file" name="gateway_image" class="upload-file__input single_file_input"
                           data-required-msg="{{ translate(':field is required', ['field' => translate('Logo')]) }}"
                           data-max-size="{{ getFileUploadMaxSize() }}"
                           accept="{{ getFileUploadFormats(skip:['.svg']) }}">
                    <label
                        class="upload-file__wrapper w-325">
                        <div class="upload-file-textbox text-center">
                            <img width="34" height="34" class="svg"
                                 src="{{dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg')}}"
                                 alt="{{ translate('Gateway') }}">
                            <h6 class="mt-1 fw-medium lh-base text-center">
                                <span class="text-info">{{ translate('Click to upload') }}</span>
                                <br>
                                {{ translate('Or drag and drop') }}
                            </h6>
                        </div>
                        <img class="upload-file-img" loading="lazy" src="{{ $gatewayImgPath }}"
                            data-default-src="{{ $gatewayImgPath }}" alt="">
                    </label>
                    <div class="overlay">
                        <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                            <button type="button" class="btn btn-outline-info icon-btn view_btn">
                                <i class="fi fi-sr-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                                <i class="fi fi-rr-camera"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <p class="fs-10 mt-3 text-center">
                    {{ getFileUploadFormats(skip: '.svg,', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                    <span class="fw-medium">({{ translate('Ratio') }} {{ '3 : 1' }})</span>
                </p>
            </div>

            <div class="mb-3 mb-sm-20">
                <div class="p-12 p-sm-20 bg-section rounded">
                    <div class="card-body">
                        <input name="gateway" value="{{ $gateway->key_name }}" hidden>
                        <div class="form-group">
                            <label class="form-label" for="">
                                {{ translate('Choose Use Type') }}
                                <span class="text-danger">*</span>
                                <i class="fi fi-sr-info text-body-light" data-bs-custom-class="text-start"
                                   data-bs-toggle="tooltip" data-bs-html="true"
                                   data-bs-title="<div>{{ translate('When select live option') }}: {{ translate('During use this from website or app need real required data.') }} {{ translate('Otherwise this gateway cannot work.') }}</div>
                                        <div class='p-2'></div>
                                        <div>{{ translate('When select live option') }} : {{ translate('Use fake data to test that the payment gateway works.') }}</div>"></i>
                            </label>
                            @php($mode = $gateway->live_values['mode'])
                            <div class="min-h-40 d-flex align-items-center gap-4 border rounded mb-2 px-3 py-1">
                                <div class="form-check d-flex gap-1">
                                    <input class="form-check-input radio--input" type="radio" name="mode"
                                           id="live-{{ $gateway->key_name }}" value="live" {{ $mode == 'live' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="live-{{ $gateway->key_name }}">
                                        {{ translate('Live') }}
                                    </label>
                                </div>
                                <div class="form-check d-flex gap-1">
                                    <input class="form-check-input radio--input" type="radio" name="mode"
                                           data-required-msg="{{ translate($gateway->key_name ).  translate('Field is required')}}"
                                           id="test-{{ $gateway->key_name }}" value="test" {{ $mode == 'test' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="test-{{ $gateway->key_name }}">
                                        {{ translate('Test') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        @if($gateway->key_name === 'paystack')
                            @php($skip=['gateway', 'mode', 'status', 'supported_country', 'callback_url'])
                        @else
                            @php($skip=['gateway','mode','status', 'supported_country'])
                        @endif
                        @foreach($gateway->live_values as $gatewayKey => $value)
                            @if(!in_array($gatewayKey , $skip))
                                @php($isSecret = \Illuminate\Support\Str::contains(strtolower($gatewayKey), ['secret','password','pass','token','key','pin','signature','hmac','mac','private','hash']))
                                <div class="mb-4">
                                    <label for="gateway-key-{{ $gateway->key_name }}-{{ $gatewayKey }}" class="form-label">
                                        {{ucwords(str_replace('_',' ',$gatewayKey))}}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="position-relative">
                                        <input type="{{ $isSecret ? 'password' : 'text' }}" class="form-control {{ $isSecret ? 'pe-5' : '' }}"
                                               name="{{$gatewayKey}}"
                                               data-required-msg="{{ translate('The :field field is required', ['field' => ucwords(str_replace('_', ' ', $gatewayKey))]) }}"
                                               id="gateway-key-{{ $gateway->key_name }}-{{ $gatewayKey }}"
                                               placeholder="{{ ucwords(str_replace('_',' ',$gatewayKey)) }} *"
                                               autocomplete="off"
                                               value="{{ showDemoModeInputValue(value: $value) }}" required>
                                        @if($isSecret)
                                            <span class="changePassTarget position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-body-light" role="button" aria-label="{{ translate('Show hide') }}">
                                                <i class="fi fi-sr-eye"></i>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        @php($supportedCountry = $gateway->live_values)
                        @if (isset($supportedCountry['supported_country']))
                            @php($supportedCountry = $supportedCountry['supported_country'])
                            <label for="{{ $gateway->key_name }}-title" class="form-label">
                                {{ translate('Supported Country') }} *
                            </label>
                            <div class="mb-4">
                                <select class="js-select form-select w-100" name="supported_country">
                                    <option value="egypt" {{$supportedCountry == 'egypt'?'selected':''}}>
                                        Egypt
                                    </option>
                                    <option value="PAK" {{$supportedCountry == 'PAK'?'selected':''}}>
                                        Pakistan
                                    </option>
                                    <option value="KSA" {{$supportedCountry == 'KSA'?'selected':''}}>
                                        Saudi Arabia
                                    </option>
                                    <option value="oman" {{$supportedCountry == 'oman'?'selected':''}}>
                                        Oman
                                    </option>
                                    <option value="UAE" {{$supportedCountry == 'UAE'?'selected':''}}>
                                        UAE
                                    </option>

                                    <option value="argentina" {{$supportedCountry == 'argentina'?'selected':''}}>
                                        Argentina
                                    </option>
                                    <option value="brasil" {{$supportedCountry == 'brasil'?'selected':''}}>
                                        Brasil
                                    </option>
                                    <option value="mexico" {{$supportedCountry == 'mexico'?'selected':''}}>
                                        México
                                    </option>
                                    <option value="uruguay" {{$supportedCountry == 'uruguay'?'selected':''}}>
                                        Uruguay
                                    </option>
                                    <option value="colombia" {{$supportedCountry == 'colombia'?'selected':''}}>
                                        Colombia
                                    </option>
                                    <option value="chile" {{$supportedCountry == 'chile'?'selected':''}}>
                                        Chile
                                    </option>
                                    <option value="peru" {{$supportedCountry == 'peru'?'selected':''}}>
                                        Perú
                                    </option>
                                </select>
                            </div>
                        @endif

                        <div class="mb-4">
                            <label for="gateway-title-{{ $gateway->key_name }}" class="form-label">
                                {{ translate('Payment Gateway Title') }}
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="gateway_title"
                                   id="gateway-title-{{ $gateway->key_name }}"
                                   placeholder="{{ translate('Payment Gateway Title') }}"
                                   value="{{ $additionalData != null ? $additionalData->gateway_title : ''}}" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer shadow-lg">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="reset" class="btn btn-secondary px-3 px-sm-4 flex-grow-1">
                    {{ translate('Reset') }}
                </button>
                <button type="submit" class="btn btn-primary px-3 px-sm-4 flex-grow-1">
                    {{ translate('Submit') }}
                </button>
            </div>
        </div>
    </div>
</form>
