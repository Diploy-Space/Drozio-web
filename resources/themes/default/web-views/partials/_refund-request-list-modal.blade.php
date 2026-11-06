
<div class="modal fade" id="refund_request" tabindex="-1" aria-labelledby="refund_requestLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="flex-grow-1">
                    <h6 class="text-capitalize mb-1 fw-semibold">{{translate('Refund request')}}</h6>
                    <p class="m-0 fs-14 title-semidark">{{translate('Choose the item you’d like to refund.')}}</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body d-flex flex-column gap-3">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table m-0 table-border table-thead-bordered align-middle">
                            <thead class="text-capitalize bg-light">
                            <tr>
                                <th class="fs-14 text-nowrap fw-bold text-dark bg-section1 ">{{ translate('Product Details') }}</th>
                                <th class="fs-14 text-nowrap fw-bold text-dark bg-section1 text-center">{{ translate('Qty') }}</th>
                                <th class="fs-14 text-nowrap fw-bold text-dark bg-section1 text-center">{{ translate('Total') }}</th>
                                <th class="fs-14 text-nowrap fw-bold text-dark bg-section1 text-center">{{ translate('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($order->details as $key=>$detail)
                                @php($product = $detail?->productAllStatus ?? json_decode($detail->product_details, true))
                                @if($product)
                                        <?php
                                        $refund_day_limit = getWebConfig(name: 'refund_day_limit');
                                        $current = \Carbon\Carbon::now();
                                        $length = $detail?->refund_started_at?->diffInDays($current);
                                        ?>
                                    <tr>
                                        <td>
                                            <div class="min-w-200 max-w-250 d-flex align-items-center gap-2">
                                                <div class="w-70px h-70px min-w-60px rounded d-center">
                                                    <img src="{{ getStorageImages(path: $detail?->productAllStatus?->thumbnail_full_url, type: 'product') }}" alt="" class="object-cover rounded w-100 h-100">
                                                </div>
                                                <div class="">
                                                    <h6 class="text-dark fs-14 fw-bold mb-1 line--limit-1">
                                                        {{isset($product['name']) ? Str::limit($product['name'],40) : ''}}
                                                    </h6>
                                                    <p class="m-0 fs-12 text-dark">
                                                        {{ translate('Unit price :') }}
                                                        {{ webCurrencyConverter($detail->price) }}
                                                    </p>
                                                    @if($detail->refund_request == 1)
                                                        <span class="d-inline-block mt-1 rounded-pill __badge badge-soft-warning border-soft-warning fs-12 font-semibold">{{ translate('Pending') }}</span>
                                                    @elseif($detail->refund_request == 2)
                                                        <span class="d-inline-block mt-1 rounded-pill __badge badge-soft-primary border-soft-primary fs-12 font-semibold">{{ translate('Approved') }}</span>
                                                    @elseif($detail->refund_request == 3)
                                                        <span class="d-inline-block mt-1 rounded-pill __badge badge-soft-danger border-soft-danger fs-12 font-semibold">{{ translate('Rejected') }}</span>
                                                    @elseif($detail->refund_request == 4)
                                                        <span class="d-inline-block mt-1 rounded-pill __badge badge-soft-success border-soft-success fs-12 font-semibold">{{ translate('Refunded') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            {{$detail->qty}}
                                        </td>
                                        <td class="text-center">
                                            <div class="min-w-120">
                                                {{ webCurrencyConverter(($detail->qty * $detail->price) + $detail?->tax - $detail->discount) }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($detail->refund_request !=0)
                                                <button type="button"
                                                        class="btn btn--primary fw-semibold px-4 rounded-10 action-get-refund-details"
                                                        data-route="{{ route('refund-details', ['id'=>$detail->id]) }}">
                                                    {{translate('Refund details')}}
                                                </button>
                                            @endif
                                            @if($refund_day_limit > 0 && !is_null($length) && $length <= $refund_day_limit && $detail->refund_request == 0)
                                                <button class="btn btn--primary fw-semibold px-4 rounded-10"  data-toggle="modal" data-target="#refundModal{{$detail->id}}">
                                                   {{ translate('Req. Refund') }}
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
