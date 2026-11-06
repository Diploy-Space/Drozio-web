<div class="modal fade review-modal" id="reviewModal{{ $id }}" tabindex="-1" aria-labelledby="reviewModalLabel{{ $id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header border-0 px-4 px-sm-5 pt-4 pb-2 align-items-start">
                <div>
                    <h1 class="modal-title fs-5 fw-bold mb-1" id="reviewModalLabel{{ $id }}">
                        {{ $order_details->reviewData ? translate('Update review') : translate('Submit a review') }}
                    </h1>
                    <p class="text-muted fs-13 mb-0">{{ translate('Share your experience to help other shoppers') }}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>

            <form action="{{ route('review.store') }}" method="post" enctype="multipart/form-data" class="d-flex flex-column">
                @csrf
                <input type="hidden" name="product_id" value="{{ $order_details->product_id }}">
                <input type="hidden" name="order_id" value="{{ $order_details->order_id }}">
                <input type="hidden" name="review_id" value="{{ $order_details->reviewData?->id ?? '' }}">

                <div class="modal-body px-4 px-sm-5 pt-3 pb-4 d-flex flex-column gap-4">
                    <div class="review-modal__product d-flex align-items-center gap-3 p-3 rounded-3">
                        <div class="review-modal__thumb rounded-3 overflow-hidden flex-shrink-0">
                            <img class="w-100 h-100 object-fit-cover" alt=""
                                 src="{{ getStorageImages(path: $order_details?->productAllStatus?->thumbnail_full_url, type: 'product') }}">
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <h6 class="fs-14 fw-semibold mb-1 text-truncate">
                                {{ $order_details?->productAllStatus?->name ?? translate('Product') }}
                            </h6>
                            @if ($order_details->variant)
                                <span class="fs-12 text-muted">{{ translate('Variant') }}: {{ $order_details->variant }}</span>
                            @endif
                        </div>
                    </div>

                    <fieldset>
                        <legend class="fs-14 fw-semibold mb-2">
                            {{ translate('Rating') }} <span class="text-danger">*</span>
                        </legend>
                        <div class="review-modal__stars" role="radiogroup">
                            @foreach ([5, 4, 3, 2, 1] as $star)
                                <input type="radio" class="visually-hidden" name="rating" value="{{ $star }}"
                                       id="rating-{{ $id }}-{{ $star }}" required
                                       {{ (int) $order_details->reviewData?->rating === $star ? 'checked' : '' }}>
                                <label for="rating-{{ $id }}-{{ $star }}" class="review-modal__star"
                                       title="{{ $star }}/5">
                                    <i class="bi bi-star-fill"></i>
                                </label>
                            @endforeach
                        </div>
                        <small class="d-block text-muted fs-12 mt-2">{{ translate('Tap a star to rate this product') }}</small>
                    </fieldset>

                    <div>
                        <label for="comment-{{ $id }}" class="form-label fs-14 fw-semibold mb-2">
                            {{ translate('Comment') }} <span class="text-danger">*</span>
                        </label>
                        <textarea name="comment" id="comment-{{ $id }}" class="form-control rounded-3" rows="4" required
                                  placeholder="{{ translate('What did you like or dislike about this product?') }}">{{ $order_details->reviewData?->comment ?? '' }}</textarea>
                    </div>

                    <div>
                        <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-2">
                            <span class="fs-14 fw-semibold">{{ translate('Add photos') }}</span>
                            <small class="text-muted fs-12">
                                {{ getFileUploadFormats(skip: '.svg,.gif,') }} · {{ translate('Maximum size') }} {{ getFileUploadMaxSize() }}MB
                            </small>
                        </div>

                        @if ($order_details->reviewData && isset($order_details->reviewData->attachment_full_url))
                            <div class="d-flex flex-wrap gap-3 mb-3">
                                @foreach ($order_details->reviewData->attachment_full_url as $key => $attachmentItem)
                                    <div class="review-exist-images review-modal__existing img-container-{{ $key }}">
                                        <button type="button" class="img-remove-icon-2 remove-img-row-by-key border-0"
                                                aria-label="{{ translate('Remove') }}"
                                                data-key="{{ $key }}"
                                                data-review-id="{{ $order_details->reviewData->id }}"
                                                data-photo="{{ $attachmentItem['key'] }}"
                                                data-route="{{ route('review.image.destroy') }}">
                                            <i class="bi bi-x"></i>
                                        </button>
                                        <img src="{{ getStorageImages(path: $attachmentItem, type: 'product') }}" alt="">
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="row g-3 coba review-modal__uploader"></div>
                    </div>
                </div>

                <div class="modal-footer border-top px-4 px-sm-5 py-3 gap-2">
                    <button type="button" class="btn btn-light px-4 m-0" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary px-4 m-0">
                        {{ $order_details->reviewData ? translate('Update review') : translate('Submit review') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
