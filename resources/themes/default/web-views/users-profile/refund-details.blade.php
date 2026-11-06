@php($refundDetailsSummery = \App\Utils\OrderManager::getRefundDetailsForSingleOrderDetails(orderDetailsId: $order_details['id']))

<div class="row no-gutters">
    <div class="col-md-5">
        <div class="refund-modal-aside h-100 p-3 p-md-4">
            @if (isset($refund->product))
                <div class="d-flex align-items-center gap-3">
                    <img class="refund-modal-aside-img get-view-by-onclick cursor-pointer"
                         data-link="{{ route('product', $refund->product['slug']) }}"
                         src="{{ getStorageImages(path: $refund->product->thumbnail_full_url, type: 'product') }}"
                         alt="{{ translate('Product') }}">
                    <div class="d-flex flex-column gap-1 refund-modal-product-info">
                        <a href="{{ route('product', [$refund->product['slug']]) }}"
                           class="fs-14 fw-bold text-dark line--limit-2">
                            {{ Str::limit($refund->product['name'], 50) }}
                        </a>
                        <span class="fs-12 title-semidark">
                            {{ $order_details->created_at->format('d M Y, h:i a') }}
                        </span>
                    </div>
                </div>

                <div class="mt-3 fs-12">
                    @if($order_details->variant)
                        <div class="refund-modal-spec d-flex justify-content-between gap-2 py-2">
                            <span class="title-semidark">{{ translate('Variant') }}</span>
                            <span class="fw-semibold text-dark text-end">{{ $order_details->variant }}</span>
                        </div>
                    @endif
                    <div class="refund-modal-spec d-flex justify-content-between gap-2 py-2">
                        <span class="title-semidark">{{ translate('Quantity') }}</span>
                        <span class="fw-semibold text-dark">{{ $order_details->qty }}</span>
                    </div>
                    <div class="refund-modal-spec d-flex justify-content-between gap-2 py-2">
                        <span class="title-semidark">{{ translate('Unit price') }}</span>
                        <span class="fw-semibold text-dark">{{ webCurrencyConverter(amount: $order_details->price) }}</span>
                    </div>
                </div>
            @else
                <div class="text-center">
                    <img src="{{ theme_asset(path: 'public/assets/front-end/img/icons/nodata.svg') }}" alt="" width="90">
                    <h6 class="fs-14 mt-2 mb-0">{{ translate('Not found') }}</h6>
                </div>
            @endif

            <div class="refund-modal-price mt-4 rounded bg-white p-3 fs-12">
                <h6 class="fs-14 fw-bold text-dark mb-3">{{ translate('Price info') }}</h6>
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex justify-content-between gap-2">
                        <span class="title-semidark">{{ translate('Total price') }}</span>
                        <span class="text-dark">{{ webCurrencyConverter(amount: $refundDetailsSummery['product_price']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-2">
                        <span class="title-semidark">{{ translate('Product Discount') }}</span>
                        <span class="text-dark">-{{ webCurrencyConverter(amount: $refundDetailsSummery['product_discount']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-2">
                        <span class="title-semidark">{{ translate('VAT/TAX') }}</span>
                        <span class="text-dark">{{ webCurrencyConverter(amount: $refundDetailsSummery['tax']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-2">
                        <span class="title-semidark">{{ translate('Subtotal') }}</span>
                        <span class="text-dark">{{ webCurrencyConverter(amount: $refundDetailsSummery['sub_total']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-2">
                        <span class="title-semidark">{{ translate('Coupon Discount') }}</span>
                        <span class="text-dark">-{{ webCurrencyConverter(amount: $refundDetailsSummery['coupon_discount']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-2">
                        <span class="title-semidark">{{ translate('Referral Discount') }}</span>
                        <span class="text-dark">-{{ webCurrencyConverter(amount: $refundDetailsSummery['referral_discount']) }}</span>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border-top pt-3 mt-3">
                    <span class="fs-14 fw-semibold text-dark">
                        {{ $refund->status == 'refunded' ? translate('Total refunded amount') : translate('Total refundable amount') }}
                    </span>
                    <span class="fs-16 fw-bold text-primary">{{ webCurrencyConverter(amount: $refundDetailsSummery['total_refundable_amount']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="p-3 p-md-4 d-flex flex-column gap-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fs-14 fw-semibold text-dark">{{ translate('Refund status') }}</span>
                @if ($refund->status == 'pending')
                    <span class="rounded-pill __badge badge-soft-warning border-soft-warning fs-12 font-semibold">{{ translate('Pending') }}</span>
                @elseif($refund->status == 'approved')
                    <span class="rounded-pill __badge badge-soft-primary border-soft-primary fs-12 font-semibold">{{ translate('Approved') }}</span>
                @elseif($refund->status == 'refunded')
                    <span class="rounded-pill __badge badge-soft-success border-soft-success fs-12 font-semibold">{{ translate('Refunded') }}</span>
                @elseif($refund->status == 'rejected')
                    <span class="rounded-pill __badge badge-soft-danger border-soft-danger fs-12 font-semibold">{{ translate('Rejected') }}</span>
                @endif
            </div>

            <div>
                <h6 class="fs-14 fw-semibold mb-2">{{ translate('Refund reason') }}</h6>
                <div class="refund-modal-reason rounded p-3 fs-14 text-dark">{{ $refund->refund_reason }}</div>
            </div>

            @if ($refund->images != null)
                <div>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <h6 class="fs-14 fw-semibold m-0">{{ translate('Upload images') }}</h6>
                        <span class="fs-12 title-semidark">{{ count($refund->images_full_url) }} {{ translate('Files Uploaded') }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($refund->images_full_url as $photo)
                            <a data-lightbox="mygallery" href="{{ $photo['path'] }}" class="refund-modal-image rounded overflow-hidden">
                                <img class="w-100 h-100"
                                     src="{{ getStorageImages(path: $photo, type: 'product') }}"
                                     alt="{{ translate('Product') }}">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
