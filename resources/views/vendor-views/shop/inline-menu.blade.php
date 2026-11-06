<div class="inline-page-menu my-4">
    <ul class="list-unstyled flex-wrap">
        <li class="{{ Request::is('vendor/shop/index') && !request()->has('pagetype') ?'active':'' }}">
            <a href="{{ route('vendor.shop.index') }}">
                {{ translate('Overview') }}
            </a>
        </li>
        <li class="{{ Request::is('vendor/shop/payment-information') ? 'active' : '' }}">
            <a href="{{ route('vendor.shop.payment-information.index') }}">
                {{ translate('Payment Information') }}
            </a>
        </li>
        <li class="{{ Request::is('vendor/shop/other-setup') ? 'active' : '' }}">
            <a href="{{ route('vendor.shop.other-setup') }}">
                {{ translate('Other Setup') }}
            </a>
        </li>
    </ul>
</div>
