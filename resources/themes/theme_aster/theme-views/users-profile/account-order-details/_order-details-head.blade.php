<?php
$isOrderOnlyDigital = true;
if ($order->details) {
    foreach ($order->details as $detail) {
        $product = json_decode($detail->product_details);
        if (isset($product->product_type) && $product->product_type == 'physical') {
            $isOrderOnlyDigital = false;
        }
    }
}

use Carbon\Carbon;

$isEligibleForRefundButtonShow = 0;
$refund_day_limit = getWebConfig(name: 'refund_day_limit');
$current = Carbon::now();
foreach ($order->details as $key => $detail) {
    $product = $detail?->productAllStatus ?? json_decode($detail->product_details, true);
    if ($product) {
        $length = $detail?->refund_started_at?->diffInDays($current);
        if ($order->order_type == 'default_type' && $order->order_status == 'delivered') {
            if ($detail->refund_request != 0) {
                $isEligibleForRefundButtonShow++;
            }
            if ($refund_day_limit > 0 && !is_null($length) && $length <= $refund_day_limit && $detail->refund_request == 0) {
                $isEligibleForRefundButtonShow++;
            }
        }
    }
}
?>


<div class="border-bottom d-flex align-items-center justify-content-between flex-wrap gap-3 pb-20 mb-20">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <h5 class="mb-0 fs-16">
            {{ translate('Order') }} #{{ $order['id'] }}
            @if($order['edited_status'] == 1)
                <span class="edit-text fw-medium text-muted fs-14">({{ translate('Edited') }})</span>
            @endif
        </h5>
        @if($order['order_status']=='failed' || $order['order_status']=='canceled')
            <span class="badge text-danger border-danger-1 text-bg-danger rounded-1 fw-normal fs-12 bg-opacity-10">
                {{ translate($order['order_status']=='failed' ? 'Failed To Deliver' : $order['order_status']) }}
            </span>
        @elseif($order['order_status']=='confirmed' || $order['order_status']=='processing' || $order['order_status']=='delivered')
            <span class="badge text-success border-success-1 text-bg-success rounded-1 fw-normal fs-12 bg-opacity-10">
                {{ translate($order['order_status']=='processing' ? 'packaging' : $order['order_status']) }}
            </span>
        @else
            <span class="badge text-primary border-primary-1 text-bg-primary rounded-1 fw-normal fs-12 bg-opacity-10">
                {{ translate($order['order_status']) }}
            </span>
        @endif
        <span class="fs-14 text-muted">{{ date('d M Y, h:i A', strtotime($order->created_at)) }}</span>
    </div>
    <div class="d-flex align-items-center gap-xl-2 gap-2">
        @if($isEligibleForRefundButtonShow > 0)
            <button class="btn btn-outline-primary px-3 rounded-10 fw-medium" data-bs-toggle="modal"
                    data-bs-target="#refund-modal">{{ translate('Refund') }}</button>
        @endif
        @if($order->order_status=='delivered' && $order->order_type == 'default_type')
            <a href="javascript:" class="btn btn-primary px-3 rounded-10 fw-medium order-again"
               data-action="{{ route('cart.order-again') }}"
               data-order-id="{{ $order['id'] }}">{{ translate('Reorder') }}</a>
        @endif
        <a target="_blank" href="{{ route('generate-invoice', [$order->id]) }}"
           class="btn btn--reset px-3 rounded-10 fw-semibold"
           data-bs-toggle="tooltip"
           data-bs-placement="bottom"
           data-bs-title="{{ translate('Download invoice') }}">
            <i class="fi fi-rr-file-download"></i> {{ translate('Invoice') }}
        </a>
    </div>
</div>

<div class="mt-4">
    <nav>
        <div class="nav nav-nowrap gap-3 gap-xl-4 nav--tabs hide-scrollbar">
            <a href="{{ route('account-order-details', ['id'=>$order->id]) }}"
               class="{{ request()->routeIs('account-order-details') ? 'active' : '' }} text-capitalize">{{ translate('Order summary') }}</a>
            <a href="{{ route('account-order-details-vendor-info', ['id'=>$order->id]) }}"
               class="{{ request()->routeIs('account-order-details-vendor-info') ? 'active' : '' }} text-capitalize">{{ translate('Vendor info') }}</a>
            @if($order->order_type != 'POS')
                <a href="{{ route('account-order-details-delivery-man-info', ['id'=>$order->id]) }}"
                   class="{{ request()->routeIs('account-order-details-delivery-man-info') ? 'active' : '' }} text-capitalize">{{ translate('Delivery man info') }}</a>
                <a href="{{ route('account-order-details-reviews', ['id'=>$order->id]) }}"
                   class="{{ request()->routeIs('account-order-details-reviews') ? 'active' : '' }} text-capitalize">{{ translate('Reviews') }}</a>
                <a href="{{ route('track-order.order-wise-result-view', ['order_id'=>$order['id']]) }}"
                   class="{{ request()->routeIs('track-order.order-wise-result-view') ? 'active' : '' }} text-capitalize">{{ translate('Track order') }}</a>
            @endif
        </div>
    </nav>
</div>

@if($order->payment_method == 'offline_payment' && isset($order->offlinePayments))
    <div class="modal fade" id="verificationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-0 rtl">
                    <div class="d-flex justify-content-end gap-2 p-2">
                        <button class="close-custom-btn btn d-center border-0 fs-16 p-1 w-30 h-30 rounded-pill"
                                type="button" data-bs-dismiss="modal" aria-label="Close">
                            <span class="top--02" aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="pt-0 px-3 px-sm-4 pb-4">
                        <h3 class="text-center mb-4">{{ translate('Payment Verification') }}</h3>
                        <div class="d-flex flex-column gap-3">

                            @foreach ($order->offlinePayments->payment_info as $key=>$value)
                                @if ($key != 'method_id')
                                    <div class="fs-12 d-flex justify-content-start gap-2">
                                        <span class="text-muted text-capitalize">{{translate($key)}} :</span>
                                        <span class="text-dark text-capitalize fw-semibold"> {{$value ?? "N/a"}}</span>
                                    </div>
                                @endif
                            @endforeach
                            @if($order->payment_note)
                                <div class="fs-12 d-flex justify-content-start gap-2">
                                    <span class="text-muted text-capitalize">{{translate('Payment Note')}} :</span>
                                    <span
                                        class="text-dark text-capitalize fw-semibold">{{ $order->payment_note }} </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@if($order?->latestEditHistory?->order_due_payment_method === 'offline_payment' && !empty($order?->latestEditHistory?->order_due_payment_info))
    <div class="modal fade" id="orderDuePaymentInfoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-0 rtl">
                    <div class="d-flex justify-content-end gap-2 p-2">
                        <button class="close-custom-btn btn d-center border-0 fs-16 p-1 w-30 h-30 rounded-pill"
                                type="button" data-bs-dismiss="modal" aria-label="Close">
                            <span class="top--02" aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="pt-0 px-3 px-sm-4 pb-4">
                        <h3 class="text-center mb-4">{{ translate('Payment Information') }}</h3>
                        <div class="d-flex flex-column gap-3">
                            @foreach($order->latestEditHistory->order_due_payment_info as $key => $value)
                                <div class="fs-12 d-flex justify-content-start gap-2">
                                <span class="text-muted text-capitalize">
                                    {{ translate($key) }} :
                                </span>
                                    <span class="text-dark fw-semibold">
                                    {{ $value ?? 'N/A' }}
                                </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@include('theme-views.order.partials._choose-payment-method-order-details',[
  'order' => $order,
  'paymentGatewayList' => $paymentGatewayList,
 ])
