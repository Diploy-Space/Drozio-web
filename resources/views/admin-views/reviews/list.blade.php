@extends('layouts.admin.app')

@section('title', translate('Review List'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex gap-2 align-items-center">
                <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/customer_review.png')}}" alt="">
                {{translate('Customer Reviews')}}
                <span class="badge badge-info text-bg-info">{{ $reviews->total() }}</span>
            </h2>
        </div>

        <div class="mb-3">
            @include('admin-views.partials._business-module-tabs')
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap gap-3 align-items-center mb-4">
                    <h3 class="mb-0">
                        {{translate('Customer Reviews List')}}
                    </h3>

                    <div class="d-flex flex-wrap gap-3 align-items-center justify-content-start justify-content-md-end min-w-100-mobile">
                        <div class="flex-grow-1 max-w-300 min-w-100-mobile">
                            <form action="{{ url()->current() }}" method="GET">
                                <div class="form-group mb-0">
                                    <div class="input-group">
                                        <input type="hidden" name="product_id" value="{{ request('product_id') }}">
                                        <input type="hidden" name="vendor_id" value="{{ request('vendor_id') }}">
                                        <input type="hidden" name="customer_id" value="{{ request('customer_id') }}">
                                        <input type="hidden" name="status" value="{{ request('status') }}">
                                        <input type="hidden" name="from" value="{{ request('from') }}">
                                        <input type="hidden" name="to" value="{{ request('to') }}">
                                        <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                                               placeholder="{{ translate('Search by Id, Product, Reviewer, Review') }}"
                                               aria-label="{{ translate('Search reviews') }}" value="{{ request('searchValue') }}">
                                        <div class="input-group-append search-submit">
                                            <button type="submit">
                                                <i class="fi fi-rr-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <a type="button" class="btn btn-outline-primary" href="{{ route('admin.people.reviews.export', ['business_module' => $activeBusinessModule, 'searchValue'=>$searchValue, 'product_id' => $product_id, 'vendor_id' => $vendor_id, 'customer_id' => $customer_id, 'status' => $status, 'from' => $from, 'to' => $to]) }}">
                            <i class="fi fi-sr-inbox-in"></i>
                            <span class="fs-12">{{ translate('Export') }}</span>
                        </a>

                        <div class="position-relative">
                            @if($hasActiveFilter)
                                <div class="position-absolute inset-inline-end-0 top-0 mt-n1 me-n1 btn-circle bg-danger border border-white border-2"
                                     style="--size: 12px;"></div>
                            @endif
                            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas"
                                    data-bs-target="#reviewFilterOffcanvas">
                                <i class="fi fi-sr-settings-sliders"></i>
                                {{ translate('Filter') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive datatable-custom">
                    <table class="table table-hover table-borderless table-thead-bordered table-nowrap align-middle card-table w-100">
                        <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>{{ translate('Serial') }}</th>
                                <th>{{ translate('Product') }}</th>
                                <th>{{ translate('Customer') }}</th>
                                <th>{{ translate('Review & Reply') }}</th>
                                <th class="text-center">{{ translate('Status') }}</th>
                                <th class="text-center">{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($reviews as $key => $review)

                            <?php
                                $isProductUnavailable = $review?->product === null;
                                $productDetails = $review?->product;
                                if ($isProductUnavailable) {
                                    $getOrderProduct = $review?->order?->details?->where('product_id', $review['product_id'])?->first()?->product_details ?? null;
                                    $productDetails = $getOrderProduct ? json_decode($getOrderProduct, true) : null;
                                }
                            ?>

                            @php($comment = $review->comment)
                            @php($reply = $review?->reply?->reply_text)

                            <tr>
                                <td>
                                    {{ $reviews->firstItem()+$key }}
                                </td>
                                <td>
                                    @if(isset($review?->product))
                                        <a
                                            href="{{$review['product_id'] ? route('admin.retail.products.view', ['addedBy'=>($review->product->added_by =='seller'?'vendor' : 'in-house'),'id'=>$review->product->id]) : 'javascript:'}}"
                                            class="media align-items-center gap-2">
                                            <img
                                                src="{{ getStorageImages(path: $review->product->thumbnail_full_url, type: 'backend-product') }}"
                                                class="avatar border object-fit-cover flex-shrink-0" alt="">
                                            <div class="max-w-200 min-w-130">
                                                <div class="text-dark line-1 text-hover-primary" data-bs-toggle="tooltip" title="{{ $review->product['name'] }}">
                                                    {{ Str::limit($review->product['name'], 25) }}
                                                </div>
                                                <div class="fs-12 text-body-light">{{ translate('Review ID') }} #{{ $review->id }}</div>
                                            </div>
                                        </a>
                                    @else
                                        <div class="media align-items-center gap-2">
                                            <img src="{{ getStorageImages(path: '', type: 'backend-product') }}"
                                                class="avatar border object-fit-cover flex-shrink-0" alt="">
                                            <div class="max-w-200 min-w-130">
                                                <div class="text-dark line-1"
                                                     data-bs-toggle="tooltip"
                                                     title="{{ translate('Product has been deleted') }}">
                                                    {{ $productDetails ? Str::limit($productDetails['name'], 25) : ($getOrderProduct ? $getOrderProduct['name'] : translate('Product has been deleted')) }}
                                                </div>
                                                <div class="fs-12 text-body-light">{{ translate('Review ID') }} #{{ $review->id }}</div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if ($review->customer)
                                        <a
                                            href="{{ route('admin.people.customer.view', [$review->customer_id]) }}"
                                            class="media align-items-center gap-2">
                                            <img
                                                src="{{getStorageImages(path:$review->customer->image_full_url,type:'backend-profile')}}"
                                                class="avatar border object-fit-cover flex-shrink-0 rounded-circle" alt="">
                                            <div class="max-w-200 min-w-130">
                                                <div class="text-dark line-1 text-hover-primary">
                                                    {{ $review->customer->f_name . ' ' . $review->customer->l_name }}
                                                </div>
                                                <div class="fs-12 text-body-light">{{ date('d M Y', strtotime($review->updated_at)) }}</div>
                                            </div>
                                        </a>
                                    @else
                                        <div class="max-w-200 min-w-130">
                                            <label class="badge badge-soft-danger mb-0">{{ translate('Customer removed') }}</label>
                                            <div class="fs-12 text-body-light">{{ date('d M Y', strtotime($review->updated_at)) }}</div>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="max-w-360 min-w-200 text-wrap">
                                        <div class="d-flex gap-2 align-items-start mb-1">
                                            <label class="badge badge-info text-bg-info mb-0 flex-shrink-0">
                                                <span class="fs-12 d-flex align-items-center gap-1">{{ $review->rating }}
                                                    <i class="fi fi-sr-star"></i>
                                                </span>
                                            </label>
                                            <div class="fs-12 line-2 word-break"
                                                 @if($comment && Str::length($comment) > 60)
                                                     data-bs-toggle="tooltip"
                                                     data-bs-placement="top"
                                                     title="{{ $comment }}"
                                                @endif
                                            >
                                                {{ $comment ? Str::limit($comment, 60) : translate('No comment found') }}
                                            </div>
                                        </div>

                                        @if(count($review->attachment_full_url) > 0)
                                            <div class="d-flex flex-wrap gap-1 mb-1">
                                                @foreach ($review->attachment_full_url as $img)
                                                    <a href="{{ $img['path'] }}"
                                                       data-lightbox="mygallery-{{ $review->id }}">
                                                        <img width="40" height="40"
                                                             class="aspect-1 rounded object-fit-cover border"
                                                             src="{{ getStorageImages(path: $img, type: 'backend-basic') }}"
                                                             alt="{{translate('Image')}}">
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if($reply)
                                            <div class="d-flex gap-1 align-items-start fs-12 text-body-light border-start border-2 ps-2">
                                                <i class="fi fi-rr-reply-all flex-shrink-0 mt-1"></i>
                                                <div class="line-2 word-break"
                                                     @if(Str::length($reply) > 60)
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="{{ $reply }}"
                                                    @endif
                                                >
                                                    {{ Str::limit($reply, 60) }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <form action="{{ route('admin.people.reviews.status', ['business_module' => $activeBusinessModule]) }}"
                                          method="post" id="reviews-status{{$review['id']}}-form"
                                          class="no-reload-form">
                                        <input type="hidden" name="id" value="{{$review['id']}}">
                                        <label class="switcher mx-auto" for="reviews-status{{$review['id']}}">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="status"
                                                id="reviews-status{{$review['id']}}"
                                                {{ $review->status ? 'checked' : '' }}
                                                data-modal-type="input-change-form"
                                                data-modal-form="#reviews-status{{$review['id']}}-form"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/customer-reviews-on.png') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/customer-reviews-off.png') }}"
                                                data-on-title = "{{translate('Want to Turn ON :item', ['item' => translate('Customer Reviews')]).'?'}}"
                                                data-off-title = "{{translate('Want to Turn OFF :item', ['item' => translate('Customer Reviews')]).'?'}}"
                                                data-on-message = "<p>{{translate('Shows this review on the website and app.')}}</p>"
                                                data-off-message = "<p>{{translate('Hides this review on the website and app.')}}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </form>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <div data-bs-toggle="modal" data-bs-target="#review-view-for-{{ $review['id'] }}">
                                            <a class="btn btn-outline-info icon-btn" title="{{ translate('View') }}" data-bs-toggle="tooltip">
                                                <i class="fi fi-rr-eye"></i>
                                            </a>
                                        </div>

                                        @if(isset($review->product) && $review?->product?->added_by == 'admin')
                                            <div data-bs-toggle="modal" data-bs-target="#review-update-for-{{ $review['id'] }}">
                                                @if($review?->reply)
                                                    <a class="btn btn-outline-primary icon-btn" title="{{ translate('Update Review') }}" data-bs-toggle="tooltip">
                                                        <i class="fi fi-rr-pencil"></i>
                                                    </a>
                                                @else
                                                    <div class="btn btn-outline-primary icon-btn" title="{{ translate('Review Reply') }}" data-bs-toggle="tooltip">
                                                        <i class="fi fi-rr-reply-all"></i>
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif($review?->product?->added_by == 'seller')
                                            <div>
                                                <a class="btn btn-outline-primary icon-btn" title="{{ translate('Admin cannot reply to vendor product review') }}" data-bs-toggle="tooltip">
                                                    @if($review?->reply)
                                                        <i class="fi fi-rr-pencil"></i>
                                                    @else
                                                    <i class="fi fi-rr-reply-all"></i>
                                                    @endif
                                                </a>
                                            </div>
                                        @else
                                            <div>
                                                <a class="btn btn-outline-primary icon-btn" title="{{ translate('Not found') }}" data-bs-toggle="tooltip">
                                                    @if($review?->reply)
                                                    <i class="fi fi-rr-pencil"></i>
                                                    @else
                                                    <i class="fi fi-rr-reply-all"></i>
                                                    @endif
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                @foreach($reviews as $key => $review)
                    @if(isset($review->customer))
                        @include('admin-views.reviews.partials._review-reply-modal', ['review' => $review])
                    @endif

                    @include('admin-views.reviews._review-modal', ['review' => $review])
                @endforeach

                <div class="table-responsive mt-4">
                    <div class="px-4 d-flex justify-content-lg-end">
                        {!! $reviews->links() !!}
                    </div>
                </div>
                @if(count($reviews)==0)
                    @include('layouts.admin.partials._empty-state',['text'=>'no_review_found'],['image'=>'default'])
                @endif
            </div>
        </div>
    </div>

    @include('admin-views.reviews.partials._filter-offcanvas')
@endsection

@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/new/back-end/js/search-product.js')}}"></script>
    <script>
        $(document).ready(function () {

            function toggleCustomDate(wrapper) {
                let selectValue = wrapper.find('.date-type-select').val();

                if (selectValue === 'custom') {
                    wrapper.find('.custom-date-div').slideDown(200);
                } else {
                    wrapper.find('.custom-date-div').slideUp(200);
                }
            }

            $(document).on('change', '.date-type-select', function () {
                let wrapper = $(this).closest('.date-filter-wrapper');
                toggleCustomDate(wrapper);
            });

            $('.date-filter-wrapper').each(function () {
                toggleCustomDate($(this));
            });

            $(document).on('input', '.search-review-product', function () {
                $.get($(this).data('route'), {name: $(this).val().trim()}, (response) => {
                    $('.search-result-box').empty().html(response.result);
                });
            });

        });
    </script>
@endpush
