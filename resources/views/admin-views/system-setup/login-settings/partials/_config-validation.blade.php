<div class="d-flex flex-column align-items-center text-center gap-2 mb-2">
    <div class="d-flex flex-column justify-content-center align-items-center mb-3 position-relative">
        @if($data['type'] == 'google')
            <img src="{{ getStorageImages(path: null, type: 'banner', source: dynamicAsset('public/assets/back-end/img/google-logo.png')) }}"
                class="status-icon" alt="" width="80"/>
        @elseif($data['type'] == 'facebook')
            <img src="{{ getStorageImages(path: null, type: 'banner', source: dynamicAsset('public/assets/back-end/img/facebook-circle.svg')) }}"
                class="status-icon" alt="" width="80"/>
        @elseif($data['type'] == 'apple')
            <img src="{{ getStorageImages(path: null, type: 'banner', source: dynamicAsset('public/assets/back-end/img/apple.png')) }}"
                 class="status-icon" alt="" width="80"/>
        @elseif($data['type'] == 'otp' || $data['type'] == 'otp-login')
            <img src="{{ getStorageImages(path: null, type: 'banner', source: dynamicAsset('public/assets/back-end/img/sms-config-icon.svg')) }}"
                 class="status-icon" alt="" width="80"/>
        @elseif($data['type'] == 'email')
            <img src="{{ getStorageImages(path: null, type: 'banner', source: dynamicAsset('public/assets/back-end/img/mail-config.png')) }}"
                 class="status-icon" alt="" width="80"/>
        @endif
    </div>
    <h3 class="modal-title">
        @if($data['type'] == 'google')
            {{ translate('Set up Google configuration first') }}
        @elseif($data['type'] == 'facebook')
            {{ translate('Set up Facebook configuration first') }}
        @elseif($data['type'] == 'apple')
            {{ translate('Set up Apple configuration first') }}
        @elseif($data['type'] == 'otp' || $data['type'] == 'otp-login')
            {{ translate('Set up otp configuration first') }}
        @elseif($data['type'] == 'email')
            {{ translate('Set up Email configuration first') }}
        @endif
    </h3>
    <div class="text-center">
        @if($data['type'] == 'google' || $data['type'] == 'facebook' || $data['type'] == 'apple')
            {{ translate('It looks like your :type login configuration is not set up yet', ['type' => $data['type']]) }}
            {{ translate('To enable :type login, set up the :type configuration first', ['type' => $data['type']]) }}
        @elseif($data['type'] == 'otp' || $data['type'] == 'otp-login')
            {{ translate('It looks like your sms configuration is not set up yet.') }} {{ translate('To enable the otp system please set up the sms configuration first') }}
        @elseif($data['type'] == 'email')
            {{ translate('It looks like your mail configuration is not set up yet.') }} {{ translate('To enable the mail service please set up the mail configuration first') }}
        @endif
    </div>
    <div class="text-center py-3">
        @if($data['type'] == 'google')
            <a class="btn btn-primary max-w-250 flex-grow-1" href="{{ route('admin.settings.third-party.social-login.view') }}" target="_blank">
                {{ translate('Go to Google configuration') }}
            </a>
        @elseif($data['type'] == 'facebook')
            <a class="btn btn-primary max-w-250 flex-grow-1" href="{{ route('admin.settings.third-party.social-login.view') }}" target="_blank">
                {{ translate('Go to Facebook configuration') }}
            </a>
        @elseif($data['type'] == 'apple')
            <a class="btn btn-primary max-w-250 flex-grow-1" href="{{ route('admin.settings.third-party.social-login.view') }}" target="_blank">
                {{ translate('Go to Apple configuration') }}
            </a>
        @elseif($data['type'] == 'otp' || $data['type'] == 'otp-login')
            <a class="btn btn-primary max-w-250 flex-grow-1" href="{{ route('admin.settings.third-party.sms-module') }}" target="_blank">
                {{ translate('Go to SMS configuration') }}
            </a>
        @elseif($data['type'] == 'email')
            <a class="btn btn-primary max-w-250 flex-grow-1" href="{{ route('admin.settings.third-party.mail.index') }}" target="_blank">
                {{ translate('Go to Email configuration') }}
            </a>
        @endif
    </div>
</div>
