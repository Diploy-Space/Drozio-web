@extends('layouts.admin.app')

@section('title', translate('Firebase Auth'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-bell text-primary d-flex"></i>
                {{ translate('Firebase') }}
            </h2>
        </div>
        @include('admin-views.third-party._third-party-firebase-menu')

        <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded mb-4">
            <div class="d-flex gap-2 align-items-center mb-2">
                <i class="fi fi-sr-lightbulb-on text-info"></i>
                <span>
                {{ translate('You need to configuration firebase') }}.
            </span>
            </div>
            <ul class="m-0 ps-20 d-flex flex-column gap-1 text-body">
                <li>{{ translate('To send notifications properly you can setup notification text from') }}
                    <a href="{{route('admin.settings.system-setup.push-notification.index')}}" target="_blank" class="fw-semibold text-decoration-underline">
                        {{ translate('Notification') }}
                    </a>{{ translate('Page') }}.
                </li>
            </ul>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex gap-3 justify-content-between align-items-center flex-wrap mb-3">
                    <div>
                        <h2>{{ translate('Firebase Configuration') }}</h2>
                        <p class="mb-0 fs-12">
                            {{ translate('Fill in this data to set up Firebase notifications.') }}.
                        </p>
                    </div>
                    <div>
                        <a data-bs-toggle="modal" href="#firebase-auth-modal" class="fs-12 text-decoration-underline">{{ translate('Where to get this information') }}</a>
                    </div>
                </div>
                <form action="{{ route('admin.settings.third-party.firebase-configuration.setup') }}" method="post"
                      style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="form-group masked-field">
                                    <label class="form-label d-flex align-items-center justify-content-between gap-2" for="push_notification_key">
                                        <span>{{ translate('Service account content') }}
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('Enter your service account content') }}" data-bs-title="{{ translate('Enter your service account content') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                        </span>
                                        <span class="changePassTarget cursor-pointer text-body-secondary fs-12 fw-medium d-flex align-items-center gap-1" role="button" aria-label="{{ translate('Show hide') }}">
                                            <i class="fi fi-sr-eye"></i>
                                        </span>
                                    </label>
                                    <textarea name="push_notification_key" id="push_notification_key" class="form-control masked-textarea" placeholder="{{ translate('Type about the description') }}"
                                              rows="10" required>{{ showDemoModeInputValue(value: $pushNotificationKey) }}</textarea>
                                </div>
                            </div>
                            <div class="col-lg-8 col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="apiKey">{{ translate('Api Key') }}
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('Enter your Api Key') }}" data-bs-title="{{ translate('Enter your Api Key') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <input type="text" class="form-control" name="apiKey" id="apiKey"
                                           placeholder="{{ translate('Ex') }}: AIzaSyD-xxxxxxxxxxxxxxxxxxxxxxxx" value="{{ showDemoModeInputValue(value: $configData?->apiKey) }}">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="authDomain">{{ translate('Auth Domain') }}
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('Enter your Auth Domain') }}" data-bs-title="{{ translate('Enter your Auth Domain') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <input type="text" class="form-control" name="authDomain" id="authDomain"
                                           placeholder="{{ translate('Ex') }}: your-app.firebaseapp.com"
                                           value="{{ showDemoModeInputValue(value: $configData?->authDomain) }}">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="projectId">{{ translate('Project ID') }}
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('Enter your Project ID') }}" data-bs-title="{{ translate('Enter your Project ID') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <input type="text" class="form-control" name="projectId" id="projectId"
                                           placeholder="{{ translate('Ex') }}: your-project-id"
                                           value="{{ showDemoModeInputValue(value: $configData?->projectId ?? '') }}">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="storageBucket">{{ translate('Storage Bucket') }}
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('Enter your Storage Bucket') }}" data-bs-title="{{ translate('Enter your Storage Bucket') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <input type="text" class="form-control" name="storageBucket" id="storageBucket"
                                           placeholder="{{ translate('Ex') }}: your-app.appspot.com"
                                           value="{{ showDemoModeInputValue(value: $configData?->storageBucket) }}">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="messagingSenderId">{{ translate('Messaging Sender ID') }}
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('Enter your Messaging Sender ID') }}" data-bs-title="{{ translate('Enter your Messaging Sender ID') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <input type="text" class="form-control" name="messagingSenderId" id="messagingSenderId"
                                           placeholder="{{ translate('Ex') }}: 1234567890"
                                           value="{{ showDemoModeInputValue(value: $configData?->messagingSenderId) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="appId">{{ translate('App ID') }}
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('Enter your App ID') }}" data-bs-title="{{ translate('Enter your App ID') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <input type="text" class="form-control" name="appId" id="appId"
                                           placeholder="{{ translate('Ex') }}: 1:1234567890:web:abc123def456"
                                           value="{{ showDemoModeInputValue(value: $configData?->appId) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="measurementId">{{ translate('Measurement ID') }}
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('Enter your Measurement ID') }}" data-bs-title="{{ translate('Enter your Measurement ID') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <input type="text" class="form-control" name="measurementId" id="measurementId"
                                           placeholder="{{ translate('Ex') }}: G-XXXXXXXXXX"
                                           value="{{ showDemoModeInputValue(value: $configData?->measurementId) }}">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end trans3 mt-3 action-btn-wrapper-container">
                            <div class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                                <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">
                                    {{ translate('Reset') }}
                                </button>
                                <button type="{{ getDemoModeFormButton(type: 'button') }}"
                                        class="btn btn-primary px-3 px-sm-4 {{ getDemoModeFormButton(type: 'class') }}">
                                    <i class="fi fi-sr-disk"></i>
                                    {{ translate('Save Information') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal fade" id="firebase-auth-modal" tabindex="-1" aria-labelledby="getInformationModal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                        <button type="button" class="btn-close border-0 shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 px-sm-5 pt-0">
                        <div class="swiper instruction-carousel pb-3">
                            <div class="swiper-wrapper">
                                <div class="swiper-slide">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <h3 class="modal-title my-3 text-center" id="instructionsModalLabel">{{ translate('Instructions') }}</h3>
                                        <p>
                                            {{ translate('Create a Firebase project first to configure OTP.') }}
                                            {{ translate('Create a project for your application first.') }}
                                        </p>
                                        <p>{{ translate('Now go the') }} <a href="https://console.firebase.google.com/" target="_blank">{{ translate('Firebase console') }}</a> {{ translate('And follow the instructions below') }} -</p>
                                        <ol class="d-flex flex-column __gap-1 __instructions">
                                            <li>{{ translate('Go to your firebase project.') }}</li>
                                            <li>{{ translate('Navigate to the build menu from the left sidebar and select authentication.') }}</li>
                                            <li>{{ translate('Get started the project and go to the sign-in method tab.') }}</li>
                                            <li>{{ translate('From the sign in providers section, select the phone option.') }}</li>
                                            <li>{{ translate('Ensure to enable the method phone and press save.') }}</li>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include("layouts.admin.partials.offcanvas._firebase-config-setup")
@endsection
