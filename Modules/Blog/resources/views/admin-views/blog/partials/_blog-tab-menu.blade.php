<div class="position-relative nav--tab-wrapper my-4">
    <ul class="nav nav-pills nav--tab">
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/content/blog/configuration*') ? 'active' : '' }}"
               href="{{ route('admin.settings.content.blog.configuration') }}">{{ translate('Blog Section') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/content/blog/app-download-setup*') ? 'active' : '' }}"
               href="{{ route('admin.settings.content.blog.app-download-setup') }}">{{ translate('App Download Setup') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/content/blog/priority-setup*') ? 'active' : '' }}"
               href="{{ route('admin.settings.content.blog.priority-setup.index') }}">{{ translate('Priority Setup') }}</a>
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
