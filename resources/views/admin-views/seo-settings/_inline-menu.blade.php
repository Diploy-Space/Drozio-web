<div class="position-relative nav--tab-wrapper mb-4">
    <ul class="nav nav-pills nav--tab" id="pills-tab" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/seo-settings/web-master-tool') ? 'active' : '' }}" href="{{ route('admin.settings.seo-settings.web-master-tool') }}">
                {{ translate('Webmaster Tools') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/seo-settings/robot-txt') ? 'active' : '' }}" href="{{ route('admin.settings.seo-settings.robot-txt') }}">
                {{ translate('Robots.txt') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/seo-settings/sitemap') ? 'active' : '' }}" href="{{ route('admin.settings.seo-settings.sitemap') }}">
                {{ translate('Sitemap') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/seo-settings/robots-meta-content*') ? 'active' : '' }}" href="{{ route('admin.settings.seo-settings.robots-meta-content.index') }}">
                {{ translate('Robots Meta Content') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/seo-settings/error-logs/index') ? 'active' : '' }}"
               href="{{ route('admin.settings.seo-settings.error-logs.index') }}">
                {{ translate('404 Logs') }}
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
