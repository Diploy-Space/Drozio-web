@php($reviewRatingLabels = [1 => translate('Poor'), 2 => translate('Average'), 3 => translate('Good'), 4 => translate('Great'), 5 => translate('Excellent')])
@php($currentRating = (int) ($order_details->reviewData?->rating ?? 0))

<div class="modal fade review-modal" id="submitReviewModal{{ $id }}" tabindex="-1"
     aria-labelledby="submitReviewModalLabel{{ $id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header align-items-center">
                <h6 class="fw-semibold m-0 flex-grow-1" id="submitReviewModalLabel{{ $id }}">
                    {{ $order_details->reviewData ? translate('Update review') : translate('Write a review') }}
                </h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('review.store') }}" method="post" enctype="multipart/form-data"
                  class="form-advance-validation form-advance-file-validation non-ajax-form-validate"
                  novalidate="novalidate">
                @csrf
                <div class="modal-body p-0">
                    <div class="row no-gutters">
                        <div class="col-md-4">
                            <div class="review-modal-aside h-100 p-3 p-md-4">
                                @if (isset($order_details->product))
                                    <div class="d-flex flex-md-column align-items-center align-items-md-stretch gap-3">
                                        <img class="review-modal-aside-img get-view-by-onclick cursor-pointer"
                                             data-link="{{ route('product', $order_details->product['slug']) }}"
                                             src="{{ getStorageImages(path: $order_details->product->thumbnail_full_url, type: 'product') }}"
                                             alt="{{ translate('Product') }}">
                                        <a href="{{ route('product', [$order_details->product['slug']]) }}"
                                           class="fs-14 fw-bold text-dark line--limit-2">
                                            {{ Str::limit($order_details->product['name'], 50) }}
                                        </a>
                                    </div>

                                    <div class="mt-3 fs-12">
                                        @if($order_details->variant)
                                            <div class="review-modal-spec d-flex justify-content-between gap-2 py-2">
                                                <span class="title-semidark">{{ translate('Variant') }}</span>
                                                <span class="fw-semibold text-dark">{{ $order_details->variant }}</span>
                                            </div>
                                        @endif
                                        <div class="review-modal-spec d-flex justify-content-between gap-2 py-2">
                                            <span class="title-semidark">{{ translate('Quantity') }}</span>
                                            <span class="fw-semibold text-dark">{{ $order_details->qty }}</span>
                                        </div>
                                        <div class="review-modal-spec d-flex justify-content-between gap-2 py-2">
                                            <span class="title-semidark">{{ translate('Price') }}</span>
                                            <span class="fw-semibold text-dark">{{ webCurrencyConverter(amount: $order_details->price) }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center text-capitalize">
                                        <img src="{{ theme_asset(path: 'public/assets/front-end/img/icons/nodata.svg') }}" alt="" width="90">
                                        <h6 class="fs-14 mt-2 mb-0">{{ translate('No product found') }}!</h6>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="p-3 p-md-4 d-flex flex-column gap-4">
                                <div>
                                    <label class="fs-14 fw-semibold mb-2">{{ translate('Rating') }}</label>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <div class="star-rating-form">
                                            <div class="star-wrap">
                                                <input class="rating" name="rating" value="{{ $currentRating ?: '' }}" hidden>
                                                <input class="star" type="radio" value="-1" id="skip-star-{{ $id }}"
                                                       name="star-radio" autocomplete="off" {{ $currentRating === 0 ? 'checked' : '' }}>
                                                <label class="star-label hidden"></label>
                                                @for($index = 1; $index <= 5; $index++)
                                                    <input class="star" type="radio" id="st-{{ $id }}-{{ $index }}"
                                                           value="{{ $index }}" name="star-radio" autocomplete="off"
                                                           data-rating-label="{{ $reviewRatingLabels[$index] }}"
                                                           {{ $currentRating === $index ? 'checked' : '' }}>
                                                    <label class="star-label" for="st-{{ $id }}-{{ $index }}">
                                                        <div class="star-shape"></div>
                                                    </label>
                                                @endfor
                                            </div>
                                        </div>
                                        <span class="star-rating-result fs-14 fw-semibold text-primary">{{ $reviewRatingLabels[$currentRating] ?? '' }}</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="fs-14 fw-semibold mb-2" for="review-comment-{{ $id }}">
                                        {{ translate('Your review') }}
                                    </label>
                                    <input name="product_id" value="{{ $order_details->product_id }}" hidden>
                                    <input name="order_id" value="{{ $order_details->order_id }}" hidden>
                                    <input name="review_id" value="{{ $order_details->reviewData?->id }}" hidden>
                                    <textarea id="review-comment-{{ $id }}" rows="4" class="form-control text-area-class"
                                              name="comment" required
                                              placeholder="{{ translate('What did you like or dislike about it') }}?">{{ $order_details->reviewData?->comment ?? '' }}</textarea>
                                </div>

                                <div class="upload_images_area">
                                    <label class="fs-14 fw-semibold mb-2">{{ translate('Photos') }}</label>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <div class="coba_review">
                                            @if ($order_details?->reviewData && $order_details?->reviewData?->attachment_full_url && count($order_details->reviewData->attachment_full_url) > 0)
                                                @foreach ($order_details->reviewData->attachment_full_url as $key => $attachment)
                                                    <div class="position-relative img_row{{ $key }} border rounded border-primary-light">
                                                        <span class="img_remove_icon remove-img-row-by-key cursor-pointer"
                                                              data-key="{{ $key }}"
                                                              data-review-id="{{ $order_details->reviewData->id }}"
                                                              data-photo="{{ $attachment['key'] }}"
                                                              data-route="{{ route('review.image.destroy') }}">
                                                            <i class="czi-close"></i>
                                                        </span>
                                                        <div class="overflow-hidden upload_img_box_img rounded">
                                                            <img class="h-100 w-100 object-cover"
                                                                 src="{{ getStorageImages(path: $attachment, type: 'product') }}"
                                                                 alt="{{ translate('Review') }}">
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>

                                        <div class="filearray"></div>
                                        <div class="selected-files-container"></div>

                                        <label class="review-modal-upload d-flex flex-column align-items-center justify-content-center gap-1 rounded m-0 cursor-pointer">
                                            <i class="fi fi-rr-camera lh-1"></i>
                                            <span class="fs-12 fw-semibold">{{ translate('Add') }}</span>
                                            <input type="file" class="reviewFilesValue" hidden
                                                   data-max-size="{{ getFileUploadMaxSize() }}"
                                                   data-required-msg="{{ translate(':field is required', ['field' => translate('Image')]) }}"
                                                   multiple accept="{{ getFileUploadFormats() }}">
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
                        {{ $order_details->reviewData ? translate('Update review') : translate('Submit review') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
    @push('script')
        <script>
            'use strict';
            $(document).on('change', ".star-rating-form input[name='star-radio']", function () {
                const reviewForm = $(this).closest('form');
                reviewForm.find('input[name=rating]').val($(this).val());
                reviewForm.find('.star-rating-result').text($(this).data('ratingLabel') || '');
            });
        </script>
    @endpush
@endonce
