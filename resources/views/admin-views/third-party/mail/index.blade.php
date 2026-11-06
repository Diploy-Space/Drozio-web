@php
    use App\Enums\EmailTemplateKey;
@endphp

@extends('layouts.admin.app')

@section('title', translate('Mail configuration'))

@push('css_or_js')
@endpush

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
            <div class="card-body d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <h2>{{ translate('Mail configuration') }}</h2>
                    <p class="fs-12 mb-0">
                        {{ translate('Set up email configuration here to enable sending emails via') }} <a href="{{route('admin.settings.system-setup.email-templates.view',['admin',EmailTemplateKey::ADMIN_EMAIL_LIST[0]])}}" target="_blank" class="fw-semibold text-info-dark">{{ translate('Email Template') }}</a>.
                    </p>
                </div>
                <button class="btn btn-outline-primary text-capitalize" data-bs-toggle="modal"
                data-bs-target="#send-mail-confirmation-modal">
                    <i class="fi fi-sr-paper-plane"></i>
                    {{translate('Send test mail')}}
                </button>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                @php($data_smtp=getWebConfig(name: 'mail_config'))
                <form action="{{route('admin.settings.third-party.mail.update')}}" method="post" id="smtp-mail-config-form" class="form-advance-validation  form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate>
                    @csrf
                    @if(isset($data_smtp))

                    <div class="view-details-container">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="method-icon rounded-circle border bg-white d-inline-flex align-items-center justify-content-center flex-shrink-0 text-primary">
                                    <i class="fi fi-sr-envelope d-flex"></i>
                                </span>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                        <h3 class="mb-0">
                                            {{ translate('SMTP Mail Configuration') }}
                                        </h3>
                                        <span class="badge badge-soft-{{ $data_smtp['status'] == 1 ? 'success' : 'secondary' }} fs-10">
                                            {{ $data_smtp['status'] == 1 ? translate('Active') : translate('Inactive') }}
                                        </span>
                                    </div>
                                    <p class="mb-0 fs-12">
                                        {{ translate('Configure email sending using your own SMTP server') }}.
                                    </p>
                                </div>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <a href="javascript:" class="fs-12 fw-semibold d-flex align-items-end view-btn">{{ translate('View') }} <i class="fi fi-rr-arrow-small-down fs-16 trans3"></i></a>
                                <label class="switcher">
                                    <input
                                        class="switcher_input custom-modal-plugin"
                                        type="checkbox" value="1" name="status"
                                        {{ $data_smtp['status'] == 1 ? 'checked':'' }}
                                        data-modal-type="input-change-form"
                                        data-modal-form="#smtp-mail-config-form"
                                        data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/maintenance_mode-on.png') }}"
                                        data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/maintenance_mode-off.png') }}"
                                        data-on-title="{{translate('Want to Turn ON :item', ['item' => translate('Smtp mail config option')]).'?'}}"
                                        data-off-title="{{translate('Want to Turn OFF :item', ['item' => translate('Smtp mail config option')]).'?'}}"
                                        data-on-message="<p>{{translate('Enabling mail configuration services will allow the system to send emails').'.'.translate('Check the SMTP settings to avoid email delivery problems.')}}</p>"
                                        data-off-message="<p>{{translate('Disabling SMTP mail configuration services stops email sending')}}</p>">
                                    <span class="switcher_control"></span>
                                </label>
                            </div>
                        </div>
                        <div class="view-details mt-3 mt-sm-4">
                            <div class="p-12 p-sm-20 bg-section rounded">
                                <div class="row g-4">
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="">{{translate('Mailer name')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter mailer name')}}"
                                                    data-bs-title="{{translate('Enter mailer name')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   placeholder="{{ translate('Ex') }}: Alex"
                                                   class="form-control" name="name"
                                                   data-required-msg="{{translate('Mailer name field is required')}}"
                                                   value="{{config('app.mode')=='demo' ? '' :$data_smtp['name']}}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="">{{translate('Host')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter the name of the host of your mailing service')}}"
                                                    data-bs-title="{{translate('Enter the name of the host of your mailing service')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                                <span class="text-danger">*</span>
                                            </label>
                                           <input type="text" class="form-control" name="host"
                                                   placeholder="{{ translate('Ex') }}: smtp.mailtrap.io"
                                                  data-required-msg="{{translate('Host field is required')}}"
                                                   value="{{config('app.mode')=='demo'?'':$data_smtp['host']}}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="">{{translate('Driver')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter the driver for your mailing service')}}"
                                                    data-bs-title="{{translate('Enter the driver for your mailing service')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" name="driver"
                                                   placeholder="{{ translate('Ex') }}: smtp"
                                                    data-required-msg="{{translate('Driver field is required')}}"
                                                   value="{{config('app.mode')=='demo'?'':$data_smtp['driver']}}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="">{{translate('Port')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter the port number for your mailing service')}}"
                                                    data-bs-title="{{translate('Enter the port number for your mailing service')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                                <span class="text-danger">*</span>
                                            </label>
                                           <input type="text" class="form-control" name="port"
                                                   placeholder="{{translate('Ex')}}:587"
                                                    data-required-msg="{{translate('Port field is required')}}"
                                                   value="{{config('app.mode')=='demo'?'':$data_smtp['port']}}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="">{{translate('Username')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter the username of your account')}}"
                                                    data-bs-title="{{translate('Enter the username of your account')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" placeholder="{{ translate('Ex') }}: yahoo"
                                                   class="form-control"
                                                   name="username"
                                                   data-required-msg="{{translate('Username field is required')}}"
                                                   value="{{config('app.mode')=='demo'?'':$data_smtp['username']}}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="">{{translate('Email ID')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter your email ID')}}"
                                                    data-bs-title="{{translate('Enter your email ID')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   placeholder="{{translate('Ex')}}:Example@example.com"
                                                   class="form-control" name="email"
                                                   data-required-msg="{{translate('Email field is required')}}"
                                                   value="{{config('app.mode')=='demo'?'':$data_smtp['email_id']}}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="">{{translate('Encryption')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter the encryption type')}}"
                                                    data-bs-title="{{translate('Enter the encryption type')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                            </label>
                                           <input type="text"
                                                   placeholder="{{ translate('Ex') }}: TLS"
                                                   class="form-control" name="encryption"
                                                  data-required-msg="{{translate('Encryption field is required')}}"
                                                   value="{{config('app.mode')=='demo'?'':$data_smtp['encryption']}}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="smtpPassword">{{translate('Password')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter your password')}}"
                                                    data-bs-title="{{translate('Enter your password')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                            </label>
                                            <div class="input-group">
                                                <input type="password" class="form-control"
                                                    value="{{config('app.mode')=='demo'?'':$data_smtp['password']}}"
                                                    name="password" id="smtpPassword"
                                                       data-required-msg="{{translate('Password field is required')}}"
                                                    placeholder="{{translate('Ex')}}:123456"
                                                       required
                                                >
                                                <div class="input-group-append changePassTarget">
                                                    <a class="text-body-light" href="javascript:">
                                                        <i class="fi fi-sr-eye"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end flex-wrap gap-3 mt-4">
                                <button type="reset" class="btn btn-secondary w-120 px-4">
                                    {{ translate('Reset') }}
                                </button>
                                <button class="btn btn-primary w-120 px-4 {{ getDemoModeFormButton(type: 'class') }}"
                                        type="{{ getDemoModeFormButton(type: 'button') }}">
                                    <i class="fi fi-sr-disk"></i>
                                    {{ translate('Save') }}
                                </button>
                                @else
                                    <button type="submit" class="btn btn-primary px-5">
                                        {{ translate('Configure') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <form action="{{route('admin.settings.third-party.mail.update-sendgrid')}}" method="post" id="sendgrid-mail-config-form" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate>
                    @csrf
                    @php($data_sendgrid=getWebConfig(name: 'mail_config_sendgrid'))
                    @if(isset($data_sendgrid))

                    <div class="view-details-container">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="method-icon rounded-circle border bg-white d-inline-flex align-items-center justify-content-center flex-shrink-0 text-primary">
                                    <i class="fi fi-sr-cloud d-flex"></i>
                                </span>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                        <h3 class="mb-0">
                                            {{ translate('Sendgrid mail configuration') }}
                                        </h3>
                                        <span class="badge badge-soft-{{ $data_sendgrid['status'] == 1 ? 'success' : 'secondary' }} fs-10">
                                            {{ $data_sendgrid['status'] == 1 ? translate('Active') : translate('Inactive') }}
                                        </span>
                                    </div>
                                    <p class="mb-0 fs-12">
                                        {{ translate('Send emails via SendGrid\'s secure cloud email service') }}.
                                    </p>
                                </div>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <a href="javascript:" class="fs-12 fw-semibold d-flex align-items-end view-btn {{ $data_sendgrid['status'] == 1 ? 'active' : '' }}">{{ translate('View') }} <i class="fi fi-rr-arrow-small-down fs-16 trans3"></i></a>
                                <label class="switcher">
                                    <input
                                        class="switcher_input custom-modal-plugin"
                                        type="checkbox" value="1" name="status"
                                        {{ $data_sendgrid['status'] == 1 ? 'checked':'' }}
                                        data-modal-type="input-change-form"
                                        data-modal-form="#sendgrid-mail-config-form"
                                        data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/maintenance_mode-on.png') }}"
                                        data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/maintenance_mode-off.png') }}"
                                        data-on-title="{{translate('Want to Turn ON :item', ['item' => translate('Sendgrid mail config option')]).'?'}}"
                                        data-off-title="{{translate('Want to Turn OFF :item', ['item' => translate('Sendgrid mail config option')]).'?'}}"
                                        data-on-message="<p>{{translate('Enabling mail configuration services will allow the system to send emails').'.'.translate('Check the SendGrid settings to avoid email delivery problems.')}}</p>"
                                        data-off-message="<p>{{translate('Disabling sendgrid mail configuration services stops email sending')}}</p>">
                                    <span class="switcher_control"></span>
                                </label>
                            </div>
                        </div>
                        <div class="view-details mt-3 mt-sm-4">
                            <div class="p-12 p-sm-20 bg-section rounded">
                                <div class="row g-4">
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="">{{translate('Mailer name')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter mailer name')}}"
                                                    data-bs-title="{{translate('Enter mailer name')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                           placeholder="{{ translate('Ex') }}: Alex"
                                                           class="form-control" name="name"
                                                            data-required-msg="{{translate('Mailer name field is required')}}"
                                                           value="{{config('app.mode')=='demo' ? '' :$data_sendgrid['name']}}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="">{{translate('Host')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter the name of the host of your mailing service')}}"
                                                    data-bs-title="{{translate('Enter the name of the host of your mailing service')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" name="host"
                                                   placeholder="{{ translate('Ex') }}: smtp.mailtrap.io"
                                                   data-required-msg="{{translate('Host field is required')}}"
                                                   value="{{config('app.mode')=='demo' ? '' : $data_sendgrid['host']}}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="">{{translate('Driver')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter the driver for your mailing service')}}"
                                                    data-bs-title="{{translate('Enter the driver for your mailing service')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" name="driver"
                                                           placeholder="{{ translate('Ex') }}: smtp"
                                                   data-required-msg="{{translate('Smtp field is required')}}"
                                                           value="{{config('app.mode')=='demo'?'':$data_sendgrid['driver']}}" required>

                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="">{{translate('Port')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter the port number for your mailing service')}}"
                                                    data-bs-title="{{translate('Enter the port number for your mailing service')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" name="port"
                                            placeholder="{{translate('Ex').':'.'587'}}"
                                                   data-required-msg="{{translate('Port field is required')}}"
                                            value="{{config('app.mode')=='demo'?'':$data_sendgrid['port']}}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="">{{translate('Username')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter the username of your account')}}"
                                                    data-bs-title="{{translate('Enter the username of your account')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" placeholder="{{translate('Ex').':'.'yahoo'}}"
                                                           class="form-control" name="username"
                                                   data-required-msg="{{translate('Username field is required')}}"
                                                           value="{{config('app.mode')=='demo'?'':$data_sendgrid['username']}}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="">{{translate('Email ID')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter your email ID')}}"
                                                    data-bs-title="{{translate('Enter your email ID')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                            placeholder="{{translate('Ex').':'}}Example@example.com"
                                            class="form-control" name="email"
                                                   data-required-msg="{{translate('Email field is required')}}"
                                            value="{{config('app.mode')=='demo'?'':$data_sendgrid['email_id']}}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="">{{translate('Encryption')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter the encryption type')}}"
                                                    data-bs-title="{{translate('Enter the encryption type')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                            </label>
                                            <input type="text"
                                            placeholder="{{ translate('Ex') }}: TLS"
                                            class="form-control" name="encryption"
                                                   data-required-msg="{{translate('Encryption field is required')}}"
                                            value="{{config('app.mode')=='demo'?'':$data_sendgrid['encryption']}}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label" for="smtpPassword">{{translate('Password')}}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    aria-label="{{translate('Enter your password')}}"
                                                    data-bs-title="{{translate('Enter your password')}}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                            </label>
                                            <div class="input-group">
                                                <input type="password" class="form-control"
                                                       name="password" id="sendGridPassword"
                                                       placeholder="{{translate('Ex')}}:123456"
                                                       data-required-msg="{{translate('Password field is required')}}"
                                                       value="{{config('app.mode')=='demo'?'':$data_sendgrid['password']}}" required>

                                                <div class="input-group-append changePassTarget">
                                                    <a class="text-body-light" href="javascript:">
                                                        <i class="fi fi-sr-eye"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end flex-wrap gap-3 mt-4">
                                <button type="reset" class="btn btn-secondary w-120 px-4">
                                    {{ translate('Reset') }}
                                </button>
                                <button class="btn btn-primary w-120 px-4 {{ getDemoModeFormButton(type: 'class') }}"
                                        type="{{ getDemoModeFormButton(type: 'button') }}">
                                    <i class="fi fi-sr-disk"></i>
                                    {{ translate('Save') }}
                                </button>
                                @else
                                    <button type="submit" class="btn btn-primary px-5">
                                        {{ translate('Configure') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="getInformationModal" tabindex="-1" aria-labelledby="getInformationModal"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close border-0" data-bs-dismiss="modal" aria-label="Close"><i
                            class="tio-clear"></i></button>
                </div>
                <div class="modal-body px-4 px-sm-5 pt-0">
                    <div class="swiper instruction-carousel pb-3">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <img width="80" class="mb-3"
                                         src="{{dynamicAsset(path: 'public/assets/back-end/img/smtp-server.png')}}" loading="lazy"
                                         alt="">
                                    <h4 class="lh-md mb-3 text-capitalize">{{translate('Find SMTP server details')}}</h4>
                                    <ul class="d-flex flex-column px-4 gap-2 mb-4 w-100">
                                        <li>
                                            {{translate('Ask your email provider for the SMTP host, port, username and password.').'.'}}
                                        </li>
                                        <li>{{translate('Note').':'}}
                                             {{translate('Check your email provider documentation if you cannot find these details.').'.'}}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <img width="80" class="mb-3"
                                         src="{{dynamicAsset(path: 'public/assets/back-end/img/config-smtp.png')}}" loading="lazy"
                                         alt="">
                                    <h4 class="lh-md mb-3 text-capitalize">{{translate('Configure SMTP settings')}}</h4>
                                    <ul class="d-flex flex-column px-4 gap-2 mb-4 w-100">
                                        <li>{{translate('Go to the SMTP mail setup page in the admin panel').'.'}}</li>
                                        <li>{{translate('Enter the SMTP hostname, port, username and password.').'.'}}</li>
                                        <li>{{translate('Choose the appropriate encryption method').' '.'(e.g., SSL,TLS)'.' '.translate('If required').'.'}}</li>
                                        <li>{{translate('Save the settings').'.'}}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <img width="80" class="mb-3"
                                         src="{{dynamicAsset(path: 'public/assets/back-end/img/test-smtp.png')}}" loading="lazy"
                                         alt="">
                                    <h4 class="lh-md mb-3 text-capitalize">{{translate('Test SMTP connection')}}</h4>
                                    <ul class="d-flex flex-column px-4 gap-2 mb-4 w-100">
                                        <li>{{translate('Click on the').'"'.translate('Send test mail').'"'.translate('Button to verify the SMTP connection')}}
                                        </li>
                                        <li>{{translate('A confirmation message means the connection is working.').'.'}} </li>
                                        <li>{{translate('If not, double-check your SMTP settings and try again').'.'}}</li>
                                        <li>{{translate('Note').':'.translate('Ask your email provider or IT administrator for the SMTP settings.').'.'}}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="d-flex flex-column align-items-center gap-2 mb-4">
                                    <img width="80" class="mb-3"
                                         src="{{dynamicAsset(path: 'public/assets/back-end/img/enable-mail-config.png')}}"
                                         loading="lazy" alt="">
                                    <h4 class="lh-md mb-3 text-capitalize">{{translate('Enable mail configuration')}}</h4>
                                    <ul class="d-flex flex-column px-4 gap-2 mb-4 w-100">
                                        <li>{{ translate('If the SMTP connection test is successful') }} {{ translate('Toggle the switch on to enable mail configuration services.')}}</li>
                                        <li>{{ translate('Lets the system send email using the configured SMTP settings.').'.' }}</li>
                                    </ul>
                                    <button class="btn btn-primary px-10 mt-3 text-capitalize"
                                            data-bs-dismiss="modal">{{ translate('Got it') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="instruction-pagination-custom my-2"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="send-mail-confirmation-modal" tabindex="-1"
         aria-labelledby="send-mail-confirmation-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered max-w-655">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                        data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body px-20 py-0 mb-30">
                    <div class="d-flex flex-column gap-sm-20 gap-3">
                        <div>
                            <h3 class="text-capitalize">{{ translate('Send test mail') }}</h3>
                            <p class="fs-12 mb-0">{{ translate('Insert a valid email address to get mail') }}</p>
                        </div>
                        <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                            <i class="fi fi-sr-lightbulb-on text-info"></i>
                            <span>
                                {{ translate('SMTP is configured. Send a test to confirm mail arrives.') }}.
                            </span>
                        </div>
                        <div class="p-12 p-sm-20 bg-section rounded d-flex flex-wrap gap-sm-20 gap-3 justify-content-end align-items-end text-capitalize">
                            <div class="flex-grow-1">
                                <label class="form-label" for="">
                                    {{ translate('Type mail address') }}
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="email" id="test-email" class="form-control"
                                    placeholder="{{ translate('Ex') }}: john@email.com">
                            </div>
                            <button type="button" id="test-mail-send"
                            class="btn btn-primary px-4 min-w-120 h-40 text-capitalize">{{translate('Send mail')}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <span id="get-send-mail-route-text" data-action="{{route('admin.settings.third-party.mail.send')}}"
          data-error-text="{{translate('Email configuration error').'!!'}}"
          data-success-text="{{translate('Email configured perfectly')}}"
          data-info-text="{{translate('Email status is not active').'!'}}"
          data-invalid-text="{{translate('Invalid email address').'!'}}">
    </span>
    @include("layouts.admin.partials.offcanvas._3rd-party-mail-setup")
@endsection

@push('script')
     <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/mail.js')}}"></script>
@endpush
