<div class="modal fade" id="review-update-for-{{ $review['id'] }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0 mb-3 d-flex justify-content-between align-items-center gap-3">
                <h3 class="mb-0">
                    {{ $review?->reply?->reply_text ? translate('Update Reply') : translate('Review Reply') }}
                </h3>
                <button type="button" class="btn btn-circle border-0 fs-12 text-body bg-section2 shadow-none flex-shrink-0"
                        style="--size: 2rem;" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}">
                    <i class="fi fi-sr-cross"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.people.reviews.add-review-reply', ['business_module' => $activeBusinessModule]) }}">
                @csrf
                <input type="hidden" name="review_id" value="{{ $review['id'] }}">

                <div class="modal-body px-20 py-0 mb-30">
                    <div class="d-flex gap-3 flex-wrap flex-sm-nowrap justify-content-between align-items-center border-bottom mb-4 pb-4">
                        <div class="d-flex gap-2 align-items-center">
                            <img class="h-50px aspect-1 rounded border object-fit-cover"
                                 src="{{ getStorageImages(path: $review?->product?->thumbnail_full_url, type: 'backend-product') }}"
                                 alt="{{ translate('Product') }}">
                            <div>
                                <div class="fs-14 text-dark fw-medium line-1">
                                    {{ $review?->product?->name ?? translate('Product has been deleted') }}
                                </div>
                                @if($review['order_id'])
                                    <span class="fs-12 text-body-light">{{ translate('Order ID') }} #{{ $review['order_id'] }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="bg-section h-50px w-120 rounded-10 text-dark fs-20 fw-medium d-flex gap-2 align-items-center justify-content-center lh-1 flex-shrink-0">
                            <span>{{ $review->rating }}</span>
                            <span class="lh-1"><i class="fi fi-sr-star text-warning"></i></span>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-2">
                        <h4 class="mb-0">{{ translate('Review') }}</h4>
                        <div class="d-flex gap-2 align-items-center fs-12 text-body-light">
                            <span>{{ date('d M, Y', strtotime($review->created_at)) }}</span>
                            <span class="d-none d-sm-block">|</span>
                            <span>{{ date('h:i A', strtotime($review->created_at)) }}</span>
                        </div>
                    </div>

                    <div class="bg-section p-3 p-sm-20 rounded-10 mb-20">
                        <div class="d-flex gap-2 align-items-center mb-2">
                            <img class="h-30 aspect-1 rounded-circle object-fit-cover"
                                 src="{{ getStorageImages(path: $review->customer?->image_full_url, type: 'backend-profile') }}"
                                 alt="{{ translate('Customer') }}">
                            <span class="fs-14 fw-medium text-dark">
                                {{ $review->customer?->f_name ?? translate('Customer') }}
                            </span>
                        </div>
                        <p class="mb-0 fs-14">{{ $review['comment'] ?? translate('No comment found') }}</p>

                        @if(count($review->attachment_full_url) > 0)
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                @foreach ($review->attachment_full_url as $img)
                                    <a href="{{ getStorageImages(path: $img, type: 'backend-basic') }}"
                                       data-lightbox="review-gallery-modal{{ $review['id'] }}">
                                        <img class="w-50px aspect-1 rounded border object-fit-cover"
                                             src="{{ getStorageImages(path: $img, type: 'backend-basic') }}"
                                             alt="{{ translate('Review image') }}">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="form-group mb-20">
                        <label class="form-label" for="reply-text-{{ $review['id'] }}">
                            {{ translate('Reply') }}
                        </label>
                        <textarea class="form-control text-area-max-min" rows="4" name="reply_text"
                                  id="reply-text-{{ $review['id'] }}"
                                  data-maxlength="250"
                                  placeholder="{{ translate('Write the reply to the product review') }}...">{{ $review?->reply?->reply_text ?? '' }}</textarea>
                        <div class="d-flex justify-content-end">
                            <span class="text-body-light fs-12">0/250</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-secondary max-w-120 flex-grow-1" data-bs-dismiss="modal">
                            {{ translate('Cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary max-w-120 flex-grow-1">
                            {{ $review?->reply?->reply_text ? translate('Update') : translate('Submit') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
