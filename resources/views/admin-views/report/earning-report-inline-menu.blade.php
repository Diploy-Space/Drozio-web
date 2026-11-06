<div class="inline-page-menu my-4">
    <ul class="nav nav-pills nav--tab gap-3">
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/report/*/admin-earning') ? 'active' : '' }}" href="{{ route(reportModuleRoute('admin-earning'), ['date_type' => 'this_year']) }}">
                {{ translate('Admin Earning') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/report/*/vendor-earning') ? 'active' : '' }}" href="{{ route(reportModuleRoute('vendor-earning'), ['date_type' => 'this_year']) }}">
                {{ translate('Vendor Earning') }}
            </a>
        </li>
    </ul>
</div>
