@php($refundDetailsSummery = \App\Utils\OrderManager::getRefundDetailsForSingleOrderDetails(orderDetailsId: $order_details['id']))

<div class="modal fade refund-details-modal" id="refundRequestDetailsModal{{ $id }}" tabindex="-1"
     aria-labelledby="refundRequestDetailsModalLabel{{ $id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header border-0 px-4 px-sm-5 pt-4 pb-2 align-items-start">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h1 class="modal-title fs-5 fw-bold m-0" id="refundRequestDetailsModalLabel{{ $id }}">
                        {{ translate('Refund details') }}
                    </h1>
                    @if($order_details->refund_request == 1)
                        <span class="refund-details-modal__status refund-details-modal__status--pending">{{ translate('Pending') }}</span>
                    @elseif($order_details->refund_request == 2)
                        <span class="refund-details-modal__status refund-details-modal__status--approved">{{ translate('Approved') }}</span>
                    @elseif($order_details->refund_request == 3)
                        <span class="refund-details-modal__status refund-details-modal__status--rejected">{{ translate('Rejected') }}</span>
                    @elseif($order_details->refund_request == 4)
                        <span class="refund-details-modal__status refund-details-modal__status--refunded">{{ translate('Refunded') }}</span>
                    @endif
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>

            <div class="modal-body px-4 px-sm-5 pt-3 pb-4 d-flex flex-column gap-4">
                <div class="refund-details-modal__product d-flex align-items-center gap-3 p-3 rounded-3">
                    <div class="refund-details-modal__thumb rounded-3 overflow-hidden flex-shrink-0">
                        <img class="w-100 h-100 object-fit-cover dark-support" alt="{{ translate('Product') }}"
                             src="{{ getStorageImages(path: $product?->thumbnail_full_url, type: 'product') }}">
                    </div>
                    <div class="flex-grow-1 overflow-hidden d-flex flex-column gap-1">
                        @if($product?->slug)
                            <a href="{{ route('product', [$product->slug]) }}" class="fs-14 fw-semibold text-truncate">
                                {{ $product->name }}
                            </a>
                        @else
                            <span class="fs-14 fw-semibold text-truncate">{{ translate('Not found') }}</span>
                        @endif
                        <div class="d-flex flex-wrap column-gap-3 row-gap-1 fs-12 text-muted">
                            <span>{{ translate('Unit Price') }}: <span class="text-dark">{{ webCurrencyConverter($order_details->price) }}</span></span>
                            <span>{{ translate('Quantity') }}: <span class="text-dark">{{ $order_details->qty }}</span></span>
                            @if($order_details->variant)
                                <span>{{ translate('Variant') }}: <span class="text-dark">{{ $order_details->variant }}</span></span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="refund-details-modal__card h-100 rounded-3 p-3 p-sm-4">
                            <h6 class="fs-14 fw-semibold mb-3">{{ translate('Price info') }}</h6>
                            <div class="d-flex flex-column gap-2 fs-14">
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="text-muted">{{ translate('Total price') }}</span>
                                    <span class="text-dark">{{ webCurrencyConverter(($order_details->qty * $order_details->price) - $order_details->discount) }}</span>
                                </div>
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="text-muted">{{ translate('Discount') }}</span>
                                    <span class="text-dark">-{{ webCurrencyConverter($order_details->discount) }}</span>
                                </div>
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="text-muted">{{ translate('VAT/TAX') }}</span>
                                    <span class="text-dark">{{ webCurrencyConverter($order_details->tax) }}</span>
                                </div>
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="text-muted">{{ translate('Subtotal') }}</span>
                                    <span class="text-dark">{{ webCurrencyConverter($refundDetailsSummery['sub_total']) }}</span>
                                </div>
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="text-muted">{{ translate('Coupon Discount') }}</span>
                                    <span class="text-dark">-{{ webCurrencyConverter($refundDetailsSummery['coupon_discount']) }}</span>
                                </div>
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="text-muted">{{ translate('Referral Discount') }}</span>
                                    <span class="text-dark">-{{ webCurrencyConverter($refundDetailsSummery['referral_discount']) }}</span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-top pt-3 mt-3">
                                <span class="fs-14 fw-semibold text-dark">
                                    {{ $order_details->refund_request == 4 ? translate('Total refunded amount') : translate('Total Refundable Amount') }}
                                </span>
                                <span class="fs-16 fw-bold text-primary">{{ webCurrencyConverter($refundDetailsSummery['total_refundable_amount']) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="refund-details-modal__card h-100 rounded-3 p-3 p-sm-4 d-flex flex-column gap-4">
                            <div>
                                <h6 class="fs-14 fw-semibold mb-2">{{ translate('Refund reason') }}</h6>
                                <p class="refund-details-modal__reason fs-14 text-dark mb-0">{{ $refund['refund_reason'] ?? translate('No reason provided') }}</p>
                            </div>

                            <div>
                                <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-2">
                                    <h6 class="fs-14 fw-semibold m-0">{{ translate('Uploaded Image') }}</h6>
                                    @if (!empty($refund) && count($refund->images_full_url) > 0)
                                        <small class="fs-12 text-muted">{{ count($refund->images_full_url) }} {{ translate('Files Uploaded') }}</small>
                                    @endif
                                </div>
                                @if (!empty($refund) && count($refund->images_full_url) > 0)
                                    <div class="gallery custom-image-popup-init d-flex flex-wrap gap-2">
                                        @foreach ($refund->images_full_url as $photo)
                                            <a href="{{ getStorageImages(path: $photo, type: 'product') }}"
                                               class="custom-image-popup refund-details-modal__image rounded-3 overflow-hidden">
                                                <img class="w-100 h-100 object-fit-cover" alt="{{ translate('Refund') }}"
                                                     src="{{ getStorageImages(path: $photo, type: 'product') }}">
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="fs-14 text-muted mb-0">{{ translate('No attachment found') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top px-4 px-sm-5 py-3">
                <button type="button" class="btn btn-light px-4 m-0" data-bs-dismiss="modal">{{ translate('Close') }}</button>
            </div>
        </div>
    </div>
</div>
