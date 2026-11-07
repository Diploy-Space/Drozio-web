@if($order->order_type == 'default_type')
    @if($order->order_status == 'delivered')
        <button class="btn border-0 outline-0 p-0 text-nowrap text-primary fs-14"
                data-bs-toggle="modal"
                data-bs-target="#reviewModal{{ $detail->id }}">
            {{ isset($detail->reviewData) ? translate('Update Review') : translate('Give Review') }}
        </button>
    @endif
@else
    <label class="badge bg-info rounded-pill text-capitalize">{{ translate('POS order') }}</label>
@endif
@if($detail?->product && $order->payment_status == 'paid' && $detail?->product->retailDetail?->digital_product_type == 'ready_product')
    <a href="javascript:"
       class="btn bg-icon p-0 d-center w-30 h-30 rounded-1 text-primary digital-product-download"
       data-action="{{ route('digital-product-download', $detail->id) }}">
        <i class="bi bi-download fs-18"></i>
    </a>
@elseif($detail?->product && $order->payment_status == 'paid' && $detail?->product->retailDetail?->digital_product_type == 'ready_after_sell')
    @if($detail->digital_file_after_sell)
        <a href="javascript:"
           data-action="{{ route('digital-product-download', $detail->id) }}"
           class="btn bg-icon p-0 d-center w-30 h-30 rounded-1 text-primary digital-product-download"
           data-bs-toggle="tooltip"
           data-bs-placement="bottom"
           data-bs-custom-class="custom-tooltip"
           data-bs-title="{{ translate('Download') }}">
            <i class="bi bi-download fs-18"></i>
        </a>
    @else
        <a href="javascript:"
           class="btn bg-icon p-0 d-center w-30 h-30 rounded-1 text-primary"
           data-bs-toggle="tooltip"
           data-bs-placement="bottom"
           data-bs-title="{{ translate('Admin hasn’t uploaded it yet') }}">
            <i class="bi bi-download fs-18"></i>
        </a>
    @endif
@endif
