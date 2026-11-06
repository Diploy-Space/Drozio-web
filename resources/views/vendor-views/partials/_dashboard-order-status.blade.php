<div class="col-sm-6 col-lg-3">
    <a class="order-stats order-stats_pending" href="{{route('vendor.retail.orders.list',['pending'])}}">
        <div class="order-stats__content">
            <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/pending.png')}}" alt="">
            <h6 class="order-stats__subtitle">{{translate('Pending')}}</h6>
        </div>
        <span class="order-stats__title">{{$orderStatus['pending']}}</span>
    </a>
</div>
<div class="col-sm-6 col-lg-3">
    <a class="order-stats order-stats_confirmed" href="{{route('vendor.retail.orders.list',['confirmed'])}}">
        <div class="order-stats__content">
            <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/confirmed.png')}}" alt="">
            <h6 class="order-stats__subtitle">{{translate('Confirmed')}}</h6>
        </div>
        <span class="order-stats__title">{{$orderStatus['confirmed']}}</span>
    </a>
</div>
<div class="col-sm-6 col-lg-3">
    <a class="order-stats order-stats_packaging" href="{{route('vendor.retail.orders.list',['processing'])}}">
        <div class="order-stats__content">
            <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/packaging.png')}}" alt="">
            <h6 class="order-stats__subtitle">{{translate('Packaging')}}</h6>
        </div>
        <span class="order-stats__title">{{$orderStatus['processing']}}</span>
    </a>
</div>
<div class="col-sm-6 col-lg-3">
    <a class="order-stats order-stats_out-for-delivery" href="{{route('vendor.retail.orders.list',['out_for_delivery'])}}">
        <div class="order-stats__content">
            <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/out-of-delivery.png')}}" alt="">
            <h6 class="order-stats__subtitle">{{translate('Out For Delivery')}}</h6>
        </div>
        <span class="order-stats__title">{{$orderStatus['out_for_delivery']}}</span>
    </a>
</div>

<div class="col-sm-6 col-lg-3">
    <a class="order-stats order-stats_delivered" href="{{route('vendor.retail.orders.list',['delivered'])}}">
        <div class="order-stats__content">
            <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/delivered.png')}}" alt="">
            <h6 class="order-stats__subtitle">{{translate('Delivered')}}</h6>
        </div>
        <span class="order-stats__title">{{$orderStatus['delivered']}}</span>
    </a>
</div>
<div class="col-sm-6 col-lg-3">
    <a class="order-stats order-stats_canceled" href="{{route('vendor.retail.orders.list',['canceled'])}}">
        <div class="order-stats__content">
            <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/canceled.png')}}" alt="">
            <h6 class="order-stats__subtitle">{{translate('Canceled')}}</h6>
        </div>
        <span class="order-stats__title">{{$orderStatus['canceled']}}</span>
    </a>
</div>
<div class="col-sm-6 col-lg-3">
    <a class="order-stats order-stats_returned" href="{{route('vendor.retail.orders.list',['returned'])}}">
        <div class="order-stats__content">
            <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/returned.png')}}" alt="">
            <h6 class="order-stats__subtitle">{{translate('Returned')}}</h6>
        </div>
        <span class="order-stats__title">{{$orderStatus['returned']}}</span>
    </a>
</div>
<div class="col-sm-6 col-lg-3">
    <a class="order-stats order-stats_failed" href="{{route('vendor.retail.orders.list',['failed'])}}">
        <div class="order-stats__content">
            <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/failed-to-deliver.png')}}" alt="">
            <h6 class="order-stats__subtitle">{{translate('Failed to deliver')}}</h6>
        </div>
        <span class="order-stats__title">{{$orderStatus['failed']}}</span>
    </a>
</div>
