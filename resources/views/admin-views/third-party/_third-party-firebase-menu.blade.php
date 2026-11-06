<div class="position-relative nav--tab-wrapper mb-4">
    <ul class="nav nav-pills nav--tab" id="pills-tab" role="tablist">

        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/third-party/firebase-configuration/setup') ?'active':'' }}"
               href="{{ route('admin.settings.third-party.firebase-configuration.setup') }}">
                {{ translate('Configuration') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/settings/third-party/firebase-configuration/authentication') ?'active':'' }}"
               href="{{route('admin.settings.third-party.firebase-configuration.authentication')}}">
                {{ translate('Authentication') }}
            </a>
        </li>
    </ul>
</div>
