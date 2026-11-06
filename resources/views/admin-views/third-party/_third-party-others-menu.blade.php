<div class="position-relative nav--tab-wrapper mb-4">
    <ul class="nav nav-pills nav--tab" id="pills-tab" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/third-party/social-login/view') ?'active':'' }}"
               href="{{ route('admin.settings.third-party.social-login.view') }}">
                {{ translate('Social Media') }}
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/third-party/mail') ?'active':'' }}"
               href="{{ route('admin.settings.third-party.mail.index') }}">
                {{ translate('Mail Configuration') }}
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/third-party/sms-module') ?'active':'' }}"
               href="{{ route('admin.settings.third-party.sms-module') }}">
                {{ translate('SMS Configuration') }}
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/third-party/recaptcha') ?'active':'' }}"
               href="{{ route('admin.settings.third-party.captcha') }}">
                {{ translate('reCAPTCHA') }}
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/third-party/map-api') ?'active':'' }}"
               href="{{ route('admin.settings.third-party.map-api') }}">
                {{ translate('Google Map APIs') }}
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/third-party/storage-connection-settings/index') ?'active':'' }}"
               href="{{ route('admin.settings.third-party.storage-connection-settings.index') }}">
                {{ translate('Storage Connection') }}
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/third-party/erp/index') ?'active':'' }}"
               href="{{ route('admin.settings.third-party.erp.index') }}">
                {{ translate('Integration') }}
            </a>
        </li>
    </ul>
    <div class="nav--tab__prev">
        <button type="button" class="btn btn-circle border-0 bg-white text-primary">
            <i class="fi fi-sr-angle-left"></i>
        </button>
    </div>
    <div class="nav--tab__next">
        <button type="button" class="btn btn-circle border-0 bg-white text-primary">
            <i class="fi fi-sr-angle-right"></i>
        </button>
    </div>

</div>
