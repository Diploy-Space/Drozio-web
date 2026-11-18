<div class="position-relative nav--tab-wrapper mb-4">
    <ul class="nav nav-pills nav--tab" id="pills-tab" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/pages-and-media/vendor-registration-settings/index') ? 'active' : '' }}"
               href="{{ route('admin.settings.pages-and-media.vendor-registration-settings.index') }}">
                {{ translate('Header') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/pages-and-media/vendor-registration-settings/with-us') ? 'active' : '' }}"
               href="{{ route('admin.settings.pages-and-media.vendor-registration-settings.with-us') }}">
                {{ translate('Why Sell With Us') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/pages-and-media/vendor-registration-settings/business-process') ? 'active' : '' }}"
               href="{{ route('admin.settings.pages-and-media.vendor-registration-settings.business-process') }}">
                {{ translate('Business Process') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/pages-and-media/vendor-registration-settings/download-app') ? 'active' : '' }}"
               href="{{ route('admin.settings.pages-and-media.vendor-registration-settings.download-app') }}">
                {{ translate('Download App') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/pages-and-media/vendor-registration-settings/faq') ? 'active' : '' }}"
               href="{{ route('admin.settings.pages-and-media.vendor-registration-settings.faq') }}">
                {{ translate('FAQ') }}
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