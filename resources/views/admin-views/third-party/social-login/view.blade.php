@extends('layouts.admin.app')

@section('title', translate('Social Media'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-settings-sliders text-primary d-flex"></i>
                {{ translate('3rd Party') }} - {{ translate('Other Configurations') }}
            </h2>
        </div>
        @include('admin-views.third-party._third-party-others-menu')

        <div class="card mb-3">
            <form action="{{ route('admin.settings.third-party.social-media-chat.update',['whatsapp']) }}" method="post">
                @csrf
                <div class="card-header px-20 py-3">
                    <h2>{{ translate('Social Media Chat') }}</h2>
                    <p class="mb-0 fs-12">
                        {{ translate('Setup social media chatting feature for customer easy to talk to you') }}.
                    </p>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-20">
                        <div class="card card-sm shadow-1">
                            <div class="card-body">
                                <div class="view-details-container">
                                    <div class="d-flex justify-content-between align-items-center gap-3">
                                        @php($whatsappConfigured = !empty($whatsapp['phone']))
                                        <div class="d-flex align-items-center gap-3">
                                            <img class="rounded-circle border bg-white p-1 flex-shrink-0"
                                                 width="44" height="44" loading="lazy" decoding="async"
                                                 src="{{ dynamicAsset(path: 'public/assets/back-end/img/whatsapp.png') }}"
                                                 alt="{{ translate('WhatsApp') }}">
                                            <div>
                                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                    <h3 class="mb-0">
                                                        {{ translate('WhatsApp') }}
                                                    </h3>
                                                    <span class="badge badge-soft-{{ $whatsappConfigured ? 'success' : 'secondary' }} fs-10">
                                                        {{ $whatsappConfigured ? translate('Configured') : translate('Not Configured') }}
                                                    </span>
                                                </div>
                                                <p class="mb-0 fs-12">
                                                    {{ translate('Provide a whatsapp number where customers can chat with you') }}
                                                    .
                                                </p>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="javascript:"
                                               class="fs-12 fw-semibold d-flex align-items-end view-btn">
                                                {{ translate('View') }}
                                                <i class="fi fi-rr-arrow-small-down fs-16 trans3"></i>
                                            </a>
                                            <label class="switcher" for="whatsapp-id">
                                                <input
                                                    class="switcher_input custom-modal-plugin"
                                                    type="checkbox" value="1" name="status"
                                                    id="whatsapp-id"
                                                    {{ isset($whatsapp['status']) && $whatsapp['status'] == 1 ? 'checked' : '' }}
                                                    data-modal-type="input-change"
                                                    data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/social/whatsapp-on.png') }}"
                                                    data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/social/whatsapp-off.png') }}"
                                                    data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('WhatsApp as social media chat option')]).'?'}}"
                                                    data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('WhatsApp as social media chat option')]).'?'}}"
                                                    data-on-message="<p>{{ translate('If enabled, WhatsApp chatting option will be available in the system') }}</p>"
                                                    data-off-message="<p>{{ translate('If enabled, WhatsApp chatting option will be hidden from the system') }}</p>"
                                                    data-on-button-text="{{ translate('Turn On') }}"
                                                    data-off-button-text="{{ translate('Turn Off') }}">
                                                <span class="switcher_control"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="d--none view-details mt-3 mt-sm-4">
                                        <div
                                            class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
                                            <i class="fi fi-sr-lightbulb-on text-info"></i>
                                            <span>
                                            {{ translate('Your country code is set from your selected business country.') }} <a
                                                    href="{{ route('admin.settings.business-settings.web-config.index') }}"
                                                    target="_blank"
                                                    class="fw-semibold text-decoration-underline">{{ translate('Business Information') }}</a> {{ translate('Page') }}.
                                        </span>
                                        </div>
                                        <div class="p-12 p-sm-20 bg-section rounded">
                                            @if($whatsapp)
                                                <div class="form-group">
                                                    <label class="form-label" for="">{{ translate('Whatsapp number') }}
                                                        <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                              data-bs-placement="top"
                                                              aria-label="{{ translate('Provide a WhatsApp number without country code') }}"
                                                              data-bs-title="{{ translate('Provide a WhatsApp number without country code') }}">
                                                        <i class="fi fi-sr-info"></i>
                                                    </span>
                                                    </label>
                                                    <input type="tel" id="phone" name="phone" class="form-control"
                                                           value="{{ $whatsapp['phone'] }}"
                                                           placeholder="{{ translate('Type your number') }}">
                                                </div>
                                                <div class="d-flex justify-content-end flex-wrap gap-3">
                                                    <button type="reset"
                                                            class="btn btn-secondary w-120 px-4">{{ translate('Reset') }}</button>
                                                    <button type="{{config('app.mode')!='demo'?'submit':'button'}}"
                                                            class="btn btn-primary w-120 px-4 {{config('app.mode')!= 'demo'? '' : 'call-demo-alert'}}"
                                                    >{{ translate('Save') }}</button>
                                                </div>
                                            @else
                                                <div class="mt-3 d-flex flex-wrap justify-content-center gap-10">
                                                    <button type="submit" class="btn btn-primary w-120 px-4 text-uppercase">
                                                        {{ translate('Configure') }}
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card">
            <div class="card-header px-20 py-3">
                <h2 class="text-capitalize">{{ translate('Social Media Login') }}</h2>
                <p class="mb-0 fs-12">
                    {{ translate('Let customers sign in with their social media accounts.') }}
                </p>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-20">
                    @if (isset($socialLoginServices))
                        @foreach ($socialLoginServices as $socialLoginService)
                            <div class="card card-sm shadow-1">
                                <div class="card-body">
                                    <form
                                        action="{{ route('admin.settings.third-party.social-login.update',[$socialLoginService['login_medium']]) }}"
                                        method="post">
                                        @csrf
                                        <div class="view-details-container">
                                            <div class="d-flex justify-content-between align-items-center gap-3">
                                                @php($isConfigured = !empty($socialLoginService['client_id']) && !empty($socialLoginService['client_secret']))
                                                <div class="d-flex align-items-center gap-3">
                                                    @if(isset($socialLoginLogos[$socialLoginService['login_medium']]))
                                                        <img class="rounded-circle border bg-white p-1 flex-shrink-0"
                                                             width="44" height="44" loading="lazy" decoding="async"
                                                             src="{{ dynamicAsset(path: $socialLoginLogos[$socialLoginService['login_medium']]) }}"
                                                             alt="{{ translate($socialLoginService['login_medium']) }}">
                                                    @endif
                                                    <div>
                                                        <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                            <h3 class="mb-0 text-capitalize">
                                                                {{ translate($socialLoginService['login_medium']) }} {{ translate('Login') }}
                                                            </h3>
                                                            <span class="badge badge-soft-{{ $isConfigured ? 'success' : 'secondary' }} fs-10">
                                                                {{ $isConfigured ? translate('Configured') : translate('Not Configured') }}
                                                            </span>
                                                        </div>
                                                        <p class="mb-1 fs-12">
                                                            {{ translate('Use') }} {{ translate($socialLoginService['login_medium']) }}  {{ translate('Turn the switch on and set up the social login files.') }}
                                                        </p>
                                                        <a data-bs-toggle="modal"
                                                           href="#{{ $socialLoginService['login_medium'] }}-modal"
                                                           class="fs-12 text-decoration-underline">{{ translate('Get Credential Setup') }}</a>
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <a href="javascript:"
                                                       class="fs-12 fw-semibold d-flex align-items-end view-btn">
                                                        {{ translate('View') }}
                                                        <i class="fi fi-rr-arrow-small-down fs-16 trans3"></i></a>
                                                </div>
                                            </div>
                                            <div class="d--none view-details mt-3 mt-sm-4">
                                                <div class="p-12 p-sm-20 bg-section rounded">
                                                    <div class="form-group">
                                                        <label class="form-label" for="">{{ translate('Callback URI') }}
                                                            <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                                  data-bs-placement="top"
                                                                  aria-label="{{ translate('Add the OAuth authorization URL') }}"
                                                                  data-bs-title="{{ translate('Add the OAuth authorization URL') }}">
                                                            <i class="fi fi-sr-info"></i>
                                                        </span>
                                                        </label>
                                                        <div
                                                            class="form-control d-flex align-items-center justify-content-between gap-2 px-3 py-2">
                                                            <span class="form-ellipsis d-flex"
                                                                  id="id_{{ $socialLoginService['login_medium'] }}">{{ route('home') }}/customer/auth/login/{{ $socialLoginService['login_medium'] }}/callback</span>
                                                            <a href="javascript:" class="copy-to-clipboard"
                                                               data-id="#id_{{ $socialLoginService['login_medium'] }}">
                                                                <i class="fi fi-rr-duplicate"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label"
                                                               for="">{{ translate('Store Client ID') }}
                                                            <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                                  data-bs-placement="top"
                                                                  aria-label="{{ translate('Add the unique client ID') }}"
                                                                  data-bs-title="{{ translate('Add the unique client ID') }}">
                                                            <i class="fi fi-sr-info"></i>
                                                        </span>
                                                        </label>
                                                        <div class="position-relative">
                                                            <input type="password" class="form-control pe-5" name="client_id"
                                                                   autocomplete="off"
                                                                   placeholder="{{ translate('Ex') }}:{{ translate('Client ID') }}"
                                                                   value="{{config('app.mode')!='demo'? $socialLoginService['client_id']??"":''}}">
                                                            <span class="changePassTarget position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-body-secondary" role="button" aria-label="{{ translate('Show hide') }}">
                                                                <i class="fi fi-sr-eye"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label"
                                                               for="">{{ translate('Store Client Secret Key') }}
                                                            <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                                  data-bs-placement="top"
                                                                  aria-label="{{ translate('Store Client Secret Key') }}"
                                                                  data-bs-title="{{ translate('Store Client Secret Key') }}">
                                                            <i class="fi fi-sr-info"></i>
                                                        </span>
                                                        </label>
                                                        <div class="position-relative">
                                                            <input type="password" class="form-control pe-5"
                                                                   name="client_secret" autocomplete="off"
                                                                   placeholder="{{ translate('Ex') }}:{{ translate('Client secret key') }}"
                                                                   value="{{config('app.mode')!='demo'?$socialLoginService['client_secret']??"":''}}">
                                                            <span class="changePassTarget position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-body-secondary" role="button" aria-label="{{ translate('Show hide') }}">
                                                                <i class="fi fi-sr-eye"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end flex-wrap gap-3 mt-4">
                                                    <button type="reset"
                                                            class="btn btn-secondary w-120 px-4">{{ translate('Reset') }}</button>
                                                    <button type="{{config('app.mode')!='demo'?'submit':'button'}}"
                                                            class="btn btn-primary w-120 px-4 {{config('app.mode')!= 'demo'? '' : 'call-demo-alert'}}"
                                                    >{{ translate('Save') }}</button>
                                                </div>
                                            </div>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    @if(isset($appleLoginServices))
                        @foreach ($appleLoginServices as $appleLoginService)
                            <div class="card card-sm shadow-1">
                                <div class="card-body">
                                    <form
                                        action="{{ route('admin.settings.third-party.social-login.update-apple', [$appleLoginService['login_medium']]) }}"
                                        method="post" enctype="multipart/form-data" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
                                        @csrf

                                        <div class="view-details-container">
                                            <div class="d-flex justify-content-between align-items-center gap-3">
                                                @php($isConfigured = !empty($appleLoginService['client_id']) && !empty($appleLoginService['client_secret']) && !empty($appleLoginService['team_id']) && !empty($appleLoginService['key_id']))
                                                <div class="d-flex align-items-center gap-3">
                                                    @if(isset($socialLoginLogos[$appleLoginService['login_medium']]))
                                                        <img class="rounded-circle border bg-white p-1 flex-shrink-0"
                                                             width="44" height="44" loading="lazy" decoding="async"
                                                             src="{{ dynamicAsset(path: $socialLoginLogos[$appleLoginService['login_medium']]) }}"
                                                             alt="{{ translate($appleLoginService['login_medium']) }}">
                                                    @endif
                                                    <div>
                                                        <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                            <h3 class="mb-0 text-capitalize">
                                                                {{ translate($appleLoginService['login_medium']) }} {{ translate('Login') }}
                                                            </h3>
                                                            <span class="badge badge-soft-{{ $isConfigured ? 'success' : 'secondary' }} fs-10">
                                                                {{ $isConfigured ? translate('Configured') : translate('Not Configured') }}
                                                            </span>
                                                        </div>
                                                        <p class="mb-1 fs-12">
                                                            {{ translate('Use') }} {{ translate($appleLoginService['login_medium']) }}  {{ translate('Turn the switch on and set up the social login files.') }}
                                                        </p>
                                                        <a data-bs-toggle="modal"
                                                           href="#{{ $appleLoginService['login_medium'] }}-modal"
                                                           class="fs-12 text-decoration-underline">{{ translate('Get Credential Setup') }}</a>
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <a href="javascript:"
                                                       class="fs-12 fw-semibold d-flex align-items-end view-btn">{{ translate('View') }}
                                                        <i class="fi fi-rr-arrow-small-down fs-16 trans3"></i></a>
                                                </div>
                                            </div>
                                            <div class="d--none view-details mt-3 mt-sm-4">
                                                <div class="p-12 p-sm-20 bg-section rounded">
                                                    <div class="form-group">
                                                        <label class="form-label" for="">{{ translate('Callback URI') }}
                                                            <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                                  data-bs-placement="top"
                                                                  aria-label="{{ translate('Add the OAuth authorization URL') }}"
                                                                  data-bs-title="{{ translate('Add the OAuth authorization URL') }}">
                                                            <i class="fi fi-sr-info"></i>
                                                        </span>
                                                        </label>
                                                        <div
                                                            class="form-control d-flex align-items-center justify-content-between gap-2 px-3 py-2">
                                                            <span class="form-ellipsis d-flex"
                                                                  id="id_{{ $appleLoginService['login_medium'] }}">{{ url('/') }}/customer/auth/login/{{ $appleLoginService['login_medium'] }}/callback</span>
                                                            <a href="javascript:" class="copy-to-clipboard"
                                                               data-id="#id_{{ $appleLoginService['login_medium'] }}">
                                                                <i class="fi fi-rr-duplicate"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label"
                                                               for="">{{ translate('Store Client ID') }}
                                                            <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                                  data-bs-placement="top"
                                                                  aria-label="{{ translate('Add the unique client ID') }}"
                                                                  data-bs-title="{{ translate('Add the unique client ID') }}">
                                                            <i class="fi fi-sr-info"></i>
                                                        </span>
                                                        </label>
                                                        <div class="position-relative">
                                                            <input type="password" class="form-control pe-5" name="client_id"
                                                                   autocomplete="off"
                                                                   placeholder="{{ translate('Ex') }}:{{ translate('Client ID') }}"
                                                                   value="{{ showDemoModeInputValue(value: $appleLoginService['client_id'] ?? '') }}">
                                                            <span class="changePassTarget position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-body-secondary" role="button" aria-label="{{ translate('Show hide') }}">
                                                                <i class="fi fi-sr-eye"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label" for="">{{ translate('Team ID') }}
                                                            <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                                  data-bs-placement="top"
                                                                  aria-label="{{ translate('Team ID') }}"
                                                                  data-bs-title="{{ translate('Team ID') }}">
                                                            <i class="fi fi-sr-info"></i>
                                                        </span>
                                                        </label>
                                                        <div class="position-relative">
                                                            <input type="password" class="form-control pe-5" name="team_id"
                                                                   autocomplete="off"
                                                                   placeholder="{{ translate('Ex').':'.translate('Team ID') }}"
                                                                   value="{{ showDemoModeInputValue(value: $appleLoginService['team_id'] ?? '') }}">
                                                            <span class="changePassTarget position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-body-secondary" role="button" aria-label="{{ translate('Show hide') }}">
                                                                <i class="fi fi-sr-eye"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label"
                                                               for="">{{ translate('Store Client Secret Key') }}
                                                            <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                                  data-bs-placement="top"
                                                                  aria-label="{{ translate('Store Client Secret Key') }}"
                                                                  data-bs-title="{{ translate('Store Client Secret Key') }}">
                                                            <i class="fi fi-sr-info"></i>
                                                        </span>
                                                        </label>
                                                        <div class="position-relative">
                                                            <input type="password" class="form-control pe-5" name="key_id"
                                                                   autocomplete="off"
                                                                   placeholder="{{ translate('Ex').':'.translate('Key ID') }}"
                                                                   value="{{ showDemoModeInputValue(value: $appleLoginService['key_id'] ?? '') }}">
                                                            <span class="changePassTarget position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-body-secondary" role="button" aria-label="{{ translate('Show hide') }}">
                                                                <i class="fi fi-sr-eye"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label" for="customFileUpload">
                                                            {{ translate('Choose updated file') }}
                                                        </label>
                                                        <input type="file" class="form-control" name="service_file" accept=".p8"
                                                               id="customFileUpload">
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end flex-wrap gap-3 mt-4">
                                                    <button type="reset"
                                                            class="btn btn-secondary w-120 px-4">{{ translate('Reset') }}</button>
                                                    <button type="{{config('app.mode')!='demo'?'submit':'button'}}"
                                                            class="btn btn-primary w-120 px-4 {{config('app.mode')!= 'demo'? '' : 'call-demo-alert'}}"
                                                    >{{ translate('Save') }}</button>
                                                </div>
                                            </div>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        <div class="modal fade" id="google-modal" data-backdrop="static" data-keyboard="false" tabindex="-1"
             aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                        <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body px-20 py-0 mb-30">
                        <div class="d-flex gap-3 flex-column align-items-center text-center mb-4">
                            <img width="80" src="{{ dynamicAsset(path: 'public/assets/back-end/img/google-logo.png') }}"
                                 loading="lazy" decoding="async" alt="{{ translate('Google') }}">
                            <h4 class="modal-title"
                                id="staticBackdropLabel">{{ translate('Google API Set up Instructions') }}</h4>
                        </div>
                        <ol class="d-flex flex-column gap-2 max-h-40vh overflow-y-auto">
                            <li>{{ translate('Go to the Google Developers Console') }}.</li>
                            <li>{{ translate('Create a new project or select an existing project').'.'}}</li>
                            <li>{{ translate('Click on Credentials in the left-hand menu').'.'}}</li>
                            <li>{{ translate('Create an OAuth client ID for a web application').'.'}}</li>
                            <li>{{ translate('Enter a name for your client ID and click Create').'.'}}</li>
                            <li>{{ translate('Enter the URL of your website as an authorized JavaScript origin').'.'}}</li>
                            <li>{{ translate('Enter the callback URL as an authorized redirect URL').'.'}}</li>
                            <li>{{ translate('Paste the client ID and client secret into your application settings.').'.'}}</li>
                            <li>{{ translate('Enable Google login and test it before going live.').'.'}}</li>
                        </ol>
                        <div class="d-flex justify-content-center mt-4">
                            <button type="button" class="btn btn-primary px-5"
                                    data-bs-dismiss="modal">{{ translate('Got it') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="facebook-modal" data-backdrop="static" data-keyboard="false" tabindex="-1"
             aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                        <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body px-20 py-0 mb-30">
                        <div class="d-flex gap-3 flex-column align-items-center text-center mb-4">
                            <img width="80" src="{{ dynamicAsset(path: 'public/assets/back-end/img/facebook.png') }}"
                                 loading="lazy" decoding="async" alt="{{ translate('Facebook') }}">
                            <h5 class="modal-title text-capitalize"
                                id="staticBackdropLabel">{{ translate('Facebook API set up instructions') }}</h5>
                        </div>

                        <ol class="d-flex flex-column gap-2">
                            <li>{{ translate('Go to the Facebook Developer website').'.'}}</li>
                            <li>{{ translate('Create a new app or select an existing app').'.'}}</li>
                            <li>{{ translate('Click Add a New App, or select an existing one.').'.'}}</li>
                            <li>{{ translate('Fill in Display Name, Contact Email and App Purpose.').'.'}}</li>
                            <li>{{ translate('Click Create App to create your app').'.'}}</li>
                            <li>{{ translate('In the left menu, click Settings then Basic.').'.'}}</li>
                            <li>{{ translate('Scroll to Facebook Login and click Set Up.').'.'}}</li>
                            <li>{{ translate('Choose the login behavior, permissions, and other settings as per your requirements').'.'}}</li>
                            <li>{{ translate('Paste the App ID and App Secret into your application settings.').'.'}}</li>
                            <li>{{ translate('Enable Facebook login and test it before going live.').'.'}}</li>
                        </ol>
                        <div class="d-flex justify-content-center mt-4">
                            <button type="button" class="btn btn-primary px-5" data-bs-dismiss="modal">
                                {{ translate('Got it') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="apple-modal" data-backdrop="static" data-keyboard="false" tabindex="-1"
             aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content"
                     style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
                    <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                        <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body px-20 py-0 mb-30">
                        <div class="d-flex gap-3 flex-column align-items-center text-center mb-4">
                            <img width="80" src="{{ dynamicAsset(path: 'public/assets/back-end/img/apple.png') }}"
                                 loading="lazy" decoding="async" alt="{{ translate('Apple') }}">
                            <h4 class="modal-title"
                                id="staticBackdropLabel">{{ translate('Apple API Set up Instructions') }}</h4>
                        </div>

                        <ol class="d-flex flex-column gap-2">
                            <li>{{ translate('Go to apple developer page') }} (<a
                                    href="https://developer.apple.com/account/resources/identifiers/list"
                                    target="_blank">{{ translate('Click Here') }}</a>)
                            </li>
                            <li>{{ translate('Here in the top left corner you can see the') }}
                                <b>{{ translate('Team ID') }}</b> [{{ translate('Apple developer account name') }}
                                ]{{'-'.' '. translate('Team ID') }}</li>
                            <li>{{ translate('Click plus icon') }} -> {{ translate('Select app IDs') }}
                                -> {{ translate('Click on continue') }}</li>
                            <li>{{ translate('Add a description and the identifier used for the app.') }}
                                <b>{{ translate('Client ID') }}</b></li>
                            <li>{{ translate('Download the AuthKey file and store it safely. It signs push notifications.') }} </li>
                            <li>{{ translate('Again click plus icon') }}
                                -> {{ translate('Select service IDs').' '.'->'.' '.translate('Click on continue') }}</li>
                            <li>{{ translate('Push a description and also identifier and continue') }} </li>
                            <li>{{ translate('Download the file in device named') }}
                                <b>AuthKey KeyID.p8</b>
                                [{{ translate('The service key ID, shown after AuthKey in the filename.') }}
                                ]
                            </li>
                        </ol>
                        <div class="d-flex justify-content-center mt-4">
                            <button type="button" class="btn btn-primary px-5"
                                    data-bs-dismiss="modal">{{ translate('Got it') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include("layouts.admin.partials.offcanvas._3rd-party-login-setup")
@endsection
