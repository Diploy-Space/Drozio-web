@if($detail->refund_request == 1)
    <div class="fs-12 text-muted">({{ translate('Refund pending') }})</div>
@elseif($detail->refund_request == 2)
    <div class="fs-12 text-muted">({{ translate('Refund approved') }})</div>
@elseif($detail->refund_request == 3)
    <div class="fs-12 text-muted">({{ translate('Refund rejected') }})</div>
@elseif($detail->refund_request == 4)
    <div class="fs-12 text-muted">({{ translate('Refund refunded') }})</div>
@endif
