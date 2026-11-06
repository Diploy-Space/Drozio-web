<div class="inline-page-menu my-4">
    <ul class="list-unstyled">
        <li class="{{ Request::is('vendor/delivery-man/wallet/index*') ?'active':'' }}"><a href="{{ route('vendor.delivery-man.wallet.index', ['id' => $deliveryMan['id']]) }}">{{translate('Overview')}}</a></li>
        <li class="{{ Request::is('vendor/delivery-man/wallet/order-history*') ?'active':'' }}"><a href="{{ route('vendor.delivery-man.wallet.order-history', ['id' => $deliveryMan['id']]) }}">{{translate('Order History Log')}}</a></li>
        <li class="{{ Request::is('vendor/delivery-man/wallet/earning*') ?'active':'' }}"><a href="{{ route('vendor.delivery-man.wallet.earning', ['id' => $deliveryMan['id']]) }}">{{translate('Earning')}}</a></li>
    </ul>
</div>
