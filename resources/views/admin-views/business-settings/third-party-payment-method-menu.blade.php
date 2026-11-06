<div class="inline-page-menu my-4">
    <ul class="list-unstyled">
        <li class="{{ Request::is('admin/settings/third-party/payment-method') ?'active':'' }}">
            <a class="text-capitalize"
               href="{{ route('admin.settings.third-party.payment-method.index') }}">
                {{ translate('Digital payment methods') }}
            </a>
        </li>
        <li class="{{ Request::is('admin/settings/third-party/offline-payment-method/*') ?'active':'' }}">
            <a class="text-capitalize"
               href="{{ route('admin.settings.third-party.offline-payment-method.index') }}">
                {{ translate('Offline payment methods') }}
            </a>
        </li>
    </ul>
</div>
