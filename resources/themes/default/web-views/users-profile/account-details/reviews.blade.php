@extends('layouts.front-end.profile')

@section('title', translate('Order details'))

@section('content')

    <div class="container pb-5 mb-2 mb-md-4 mt-3 rtl __inline-47 text-start">
        <div class="row g-3">
            @include('web-views.partials._profile-aside')
            <section class="col-lg-9">

                @include('web-views.users-profile.account-details.partial')

                <div class="bg-sm-white mt-3">
                    <div class="p-sm-3 d-flex flex-column gap-3 pb-md-5">
                        @php($review_count = 0)
                        @foreach ($order->details as $order_details)
                            @isset($order_details->reviewData)
                                @php($review_count++)
                                <div class="border rounded bg-white p-3">
                                    <div class="media gap-3">
                                        <div class="position-relative">
                                            <img class="d-block review-item-img get-view-by-onclick border rounded"
                                                 data-link="{{ $order_details?->product?->slug ? route('product', $order_details?->product?->slug) : 'javascript:' }}"
                                                 src="{{ getStorageImages(path:$order_details?->productAllStatus?->thumbnail_full_url, type: 'product') }}"
                                                 alt="{{ translate('Product') }}" width="100">

                                            @if($order_details?->product?->discount > 0)
                                                <span class="price-discount badge badge-primary position-absolute top-1 left-1">
                                                    @if ($order_details?->product?->discount_type == 'percent')
                                                        {{round($order_details?->product->discount)}}%
                                                    @elseif($order_details?->product?->discount_type =='flat')
                                                        {{ webCurrencyConverter(amount: $order_details?->product?->discount) }}
                                                    @endif
                                                </span>
                                            @endif
                                        </div>
                                        <div class="media-body">
                                            <div>
                                                <a href="{{ $order_details?->product?->slug ? route('product', $order_details?->product?->slug) : 'javascript:' }}">
                                                    <h6 class="mb-1 review-item-title text-capitalize">
                                                        {{ $order_details?->product?->name ? Str::limit($order_details?->product?->name, 40) : translate('Not found') }}
                                                    </h6>
                                                </a>
                                            </div>
                                            @if($order_details->variant)
                                                <div>
                                                    <small class="text-muted">
                                                        {{translate('Variant')}} : {{$order_details->variant}}
                                                    </small>
                                                </div>
                                            @endif

                                            <div class="d-flex justify-content-end justify-content-sm-start mt-2">
                                                <button type="button" class="btn btn-sm rounded btn-warning"
                                                        data-toggle="modal"
                                                        data-target="#submitReviewModal{{$order_details->id}}">
                                                    <i class="tio-star-half"></i>
                                                    {{translate('Update review')}}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <div class="d-flex justify-content-between gap-2 mb-3">
                                            <h6 class="d-flex gap-2 mb-0 review-item-title text-capitalize">
                                                    <span>
                                                        <i class="tio-star-half text-warning"></i>
                                                    </span>
                                                {{translate('My review')}}
                                            </h6>
                                            <div  class="text-muted fs-12">{{ $order_details->reviewData->created_at ? $order_details->reviewData->created_at->format("d M Y h:i:s A") : ($order_details->reviewData->updated_at->format("d M Y h:i:s A")) }}</div>
                                        </div>
                                        <p class="fs-12">{{ $order_details->reviewData?->comment ?? ''}}</p>
                                        @if ($order_details->reviewData && isset($order_details->reviewData->attachment) && $order_details->reviewData->attachment != [])
                                            @if(($order_details->reviewData->attachment) != [])
                                                <div class="mt-3">
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        @foreach ($order_details->reviewData->attachment_full_url as $key => $photo)
                                                            <a data-lightbox="mygallery" class="aspect-1"
                                                               href="{{ getStorageImages(path: $photo, type: 'product') }}">
                                                                <img class="border rounded border-primary-light object-cover aspect-1"
                                                                     src="{{ getStorageImages(path: $photo, type: 'product') }}"
                                                                     alt="{{ translate('Product') }}" width="50">
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    </div>

                                    @if($order_details->reviewData && $order_details->reviewData->reply)
                                        <div class="pl-md-4 mt-3">
                                            <div class="review-reply rounded bg-E9F3FF80 p-3 ml-md-4 before-border-left">
                                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="{{theme_asset('public/assets/front-end/img/seller-reply-icon.png')}}" alt="">
                                                        <h6 class="font-bold fs-14 m-0">
                                                            {{ translate('Reply by Seller') }}
                                                        </h6>
                                                    </div>
                                                    <span class="opacity-50 fs-12">
                                                        {{ isset($order_details->reviewData->reply->created_at) ? $order_details->reviewData->reply->created_at->format('M-d-Y') : '' }}
                                                    </span>
                                                </div>
                                                <p class="fs-14">
                                                    {!! $order_details->reviewData->reply->reply_text !!}
                                                </p>

                                                <div class="d-flex flex-wrap gap-2 mt-3">
                                                    @if (isset($order_details->reviewData->reply->attachment) && !empty(json_decode($order_details->reviewData->reply->attachment)))
                                                        @foreach ($productReview->reply->attachment_full_url as $key => $photo)
                                                            <img data-link="{{ getStorageImages(path: $photo, type: 'product')}}"
                                                                 class="cz-image-zoom __img-70 rounded border show-instant-image"
                                                                 src="{{ getStorageImages(path: $photo, type: 'product') }}"
                                                                 alt="{{ translate('Product') }}">
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            @endisset
                        @endforeach
                        @if ($review_count == 0)
                            <div class="text-center pt-5 text-capitalize">
                                <img class="mb-3" src="{{dynamicAsset(path: 'public/assets/front-end/img/icons/empty-review.svg')}}"
                                     alt="">
                                <p class="opacity-60 mt-3 text-capitalize">{{translate('No review found')}}!</p>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{theme_asset(path: 'public/assets/front-end/js/spartan-multi-image-picker.js') }}" defer></script>
@endpush

