<div class="inline-page-menu my-4">
    <ul class="list-unstyled">
        <li class="{{ Request::is('admin/settings/business-settings/system-setup/environment-setup') ?'active':'' }}">
            <a href="{{route('admin.settings.system-setup.environment-setup') }}">{{ translate('Environment Settings') }}</a>
        </li>

        <li class="{{ Request::is('admin/settings/system-setup/web-config/app-settings') ?'active':'' }}">
            <a href="{{route('admin.settings.system-setup.app-settings') }}">{{ translate('App Settings') }}</a>
        </li>

        <li class="{{ Request::is('admin/system-settings/software-update') ?'active':'' }}">
            <a href="{{route('admin.settings.system-setup.software-update') }}">{{ translate('Software Update') }}</a>
        </li>
        <li class="{{ Request::is('admin/settings/business-settings/language') ?'active':'' }}">
            <a href="{{route('admin.settings.system-setup.language.index') }}">{{ translate('Language') }}</a>
        </li>
        <li class="{{ Request::is('admin/currency/view') ?'active':'' }}">
            <a href="{{route('admin.settings.system-setup.currency.view') }}">{{ translate('Currency') }}</a>
        </li>
        <li class="{{ Request::is('admin/settings/business-settings/web-config/db-index') ?'active':'' }}">
            <a href="{{route('admin.settings.system-setup.db-index') }}">{{ translate('Clean Database') }}</a>
        </li>
    </ul>
</div>
