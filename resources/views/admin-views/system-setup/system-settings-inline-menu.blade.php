<div class="position-relative nav--tab-wrapper mb-4">
    <ul class="nav nav-pills nav--tab" id="pills-tab" role="tablist">

        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/system-setup/environment-setup') ? 'active' : '' }}"
               href="{{ route('admin.settings.system-setup.environment-setup') }}">
                {{ translate('Environment Settings') }}
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/system-setup/app-settings') ? 'active' : '' }}"
               href="{{ route('admin.settings.system-setup.app-settings') }}">
                {{ translate('App Settings') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/system-setup/app-deep-link') ? 'active' : '' }}"
               href="{{ route('admin.settings.system-setup.app-deep-link') }}">
                {{ translate('App Deep Link Setup') }}
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/system-setup/software-update') ? 'active' : '' }}"
               href="{{ route('admin.settings.system-setup.software-update') }}">
                {{ translate('Software Update') }}
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/system-setup/language') ? 'active' : '' }}"
               href="{{ route('admin.settings.system-setup.language.index') }}">
                {{ translate('Language') }}
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/system-setup/currency/*') ? 'active' : '' }}"
               href="{{ route('admin.settings.system-setup.currency.view') }}">
                {{ translate('Currency') }}
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/system-setup/db-index') ?'active' : '' }}"
               href="{{ route('admin.settings.system-setup.db-index') }}">
                {{ translate('Clean Database') }}
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
