@php($refundDetailsSummery = \App\Utils\OrderManager::getRefundDetailsForSingleOrderDetails(orderDetailsId: $order_details['id']))

<div class="modal fade refund-modal" id="refundModal{{ $id }}" tabindex="-1"
     aria-labelledby="refundRequestModalLabel{{ $id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header align-items-center">
                <h6 class="fw-semibold m-0 flex-grow-1" id="refundRequestModalLabel{{ $id }}">
                    {{ translate('Refund request') }}
                </h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('refund-store') }}" method="post" enctype="multipart/form-data"
                  class="form-advance-validation form-advance-file-validation non-ajax-form-validate"
                  novalidate="novalidate">
                @csrf
                <input type="hidden" name="order_details_id" value="{{ $order_details->id }}">
                <input type="hidden" name="amount" value="{{ $refundDetailsSummery['total_refundable_amount'] }}">

                <div class="modal-body p-0">
                    <div class="row no-gutters">
                        <div class="col-md-5">
                            <div class="refund-modal-aside h-100 p-3 p-md-4">
                                @if (isset($order_details?->productAllStatus))
                                    <div class="d-flex align-items-center gap-3">
                                        <img class="refund-modal-aside-img get-view-by-onclick cursor-pointer"
                                             data-link="{{ route('product', $order_details->productAllStatus->slug) }}"
                                             src="{{ getStorageImages(path: $order_details->productAllStatus->thumbnail_full_url, type: 'product') }}"
                                             alt="{{ translate('Product') }}">
                                        <div class="d-flex flex-column gap-1 refund-modal-product-info">
                                            <a href="{{ route('product', [$order_details->productAllStatus->slug]) }}"
                                               class="fs-14 fw-bold text-dark line--limit-2">
                                                {{ Str::limit($product['name'], 50) }}
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
                                            <span class="text-dark">{{ webCurrencyConverter(amount: $order_details->price) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between gap-2">
                                            <span class="title-semidark">{{ translate('Product Discount') }}</span>
                                            <span class="text-dark">-{{ webCurrencyConverter(amount: $order_details->discount) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between gap-2">
                                            <span class="title-semidark">{{ translate('VAT/TAX') }}</span>
                                            <span class="text-dark">{{ webCurrencyConverter(amount: $order_details->tax) }}</span>
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
                                        <span class="fs-14 fw-semibold text-dark">{{ translate('Total refundable amount') }}</span>
                                        <span class="fs-16 fw-bold text-primary">{{ webCurrencyConverter(amount: $refundDetailsSummery['total_refundable_amount']) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-7">
                            <div class="p-3 p-md-4 d-flex flex-column gap-4">
                                <div>
                                    <label class="fs-14 fw-semibold mb-2" for="refund-reason-{{ $id }}">
                                        {{ translate('Refund reason') }} <span class="text-danger">*</span>
                                    </label>
                                    <textarea id="refund-reason-{{ $id }}" rows="5" class="form-control" name="refund_reason"
                                              data-required-msg="{{ translate('Refund reason is required') }}" required
                                              placeholder="{{ translate('Write here') }}..."></textarea>
                                </div>

                                <div class="upload_images_area">
                                    <label class="fs-14 fw-semibold mb-2">{{ translate('Upload images') }}</label>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <div class="filearray"></div>
                                        <div class="selected-files-container"></div>

                                        <label class="refund-modal-upload d-flex flex-column align-items-center justify-content-center gap-1 rounded m-0 cursor-pointer">
                                            <i class="fi fi-rr-camera lh-1"></i>
                                            <span class="fs-12 fw-semibold">{{ translate('Add') }}</span>
                                            <input type="file" class="msgfilesValue" hidden
                                                   data-max-size="{{ getFileUploadMaxSize() }}"
                                                   data-required-msg="{{ translate('Only image file is allowed') }}"
                                                   multiple accept="{{ getFileUploadFormats(skip: '.svg') }}">
                                        </label>
                                    </div>
                                    <div class="fs-12 title-semidark mt-2">
                                        {{ getFileUploadFormats(skip: '.svg,.gif,') }} &middot; {{ translate('Maximum size') }}: {{ getFileUploadMaxSize() }}MB
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex flex-column flex-sm-row justify-content-sm-end gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded px-4 order-2 order-sm-1"
                            data-dismiss="modal">
                        {{ translate('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn-sm btn--primary rounded px-4 order-1 order-sm-2">
                        {{ translate('Send request') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
