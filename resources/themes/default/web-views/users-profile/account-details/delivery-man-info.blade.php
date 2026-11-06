@extends('layouts.front-end.profile')

@section('title', translate('Order details'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/deliveryman-info.css') }}">
@endpush

@section('content')

    <div class="container pb-5 mb-2 mb-md-4 mt-3 rtl __inline-47 text-align-direction deliveryman-info-page">
        <div class="row g-3">
            @include('web-views.partials._profile-aside')
            <section class="col-lg-9">
                @include('web-views.users-profile.account-details.partial',['order'=>$order])
                @if ($order->delivery_type == 'self_delivery' && isset($order->deliveryMan))
                    <div class="bg-sm-white mt-3">
                        <div class="p-sm-3">
                            <div class="delivery-man-info-box bg-white media gap-2 gap-sm-3 shadow-sm rounded p-3">
                                <div class="img-avatar-parent-element">
                                    <img class="rounded-circle" width="77" alt=""
                                         src="{{ getStorageImages(path: $order->deliveryMan->image_full_url, type: 'avatar') }}">
                                </div>
                                <div class="media-body">
                                    <div
                                        class="d-flex gap-2 gap-sm-3 align-items-start align-items-sm-center justify-content-between">
                                        <div class="">
                                            <h6 class="text-capitalize mb-2 max-w-320 line--limit-2">
                                                {{$order->deliveryMan->f_name}}
                                                &nbsp{{$order->deliveryMan->l_name}}
                                            </h6>
                                            @php($avg_rating = $order->deliveryMan->rating[0]->average ?? 0)
                                            @include('web-views.partials._rating-display', [
                                                'rating' => $avg_rating,
                                                'count' => 0,
                                                'size' => 'sm',
                                            ])
                                        </div>

                                        <div class="d-flex justify-content-end flex-wrap gap-3 gap-sm-3">
                                            <button type="button" class="btn btn-soft-info text-capitalize px-2 px-md-4"
                                                    data-toggle="modal"
                                                    data-target="#chatting_modal">
                                                <img
                                                    src="{{theme_asset(path: 'public/assets/front-end/img/seller-info-chat.png')}}"
                                                    alt="">
                                                <span
                                                    class="d-none d-md-inline-block">{{translate('Chat with delivery man')}}</span>
                                            </button>
                                            @if($order->payment_status == 'paid' && $order->order_type == 'default_type' && $order->order_status=='delivered' && $order->delivery_man_id)
                                                <button type="button" class="btn btn-sm btn-warning px-2 px-md-4"
                                                        data-toggle="modal"
                                                        data-target="#submitReviewModal">
                                                    <i class="tio-star-half"></i>
                                                    @if(isset($order->deliveryManReview->comment) || isset($order->deliveryManReview->rating))
                                                        {{translate('Update Review')}}
                                                    @else
                                                        {{translate('Review')}}
                                                    @endif
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @isset($order->deliveryManReview)
                                <div class="shadow-sm bg-white rounded p-3 mt-3">
                                    <div class="d-flex align-items-center flex-wrap justify-content-between gap-2 mb-3">
                                        <h6 class="d-flex gap-2 mb-0 review-item-title"><span>{{number_format($order->deliveryManReview?->rating ,1)}}<i
                                                    class="tio-star-half text-warning text-capitalize"></i></span> {{translate('Delivery man review')}}
                                        </h6>
                                        <div
                                            class="fs-12 text-muted">{{date('M d , Y h:i A', strtotime($order->deliveryManReview->updated_at))}}</div>
                                    </div>
                                    <p class="fs-12 text-muted">{{$order->deliveryManReview->comment}}</p>
                                </div>
                            @endisset

                            @if ($order->verificationImages->count()>0)
                                <div class="shadow-sm rounded bg-white p-3 mt-3">
                                    <h6 class="mb-0 fs-12 d-flex align-items-center gap-2 lh-1 mb-3">
                                        <i class="tio-photo-camera fs-16 text-primary text-capitalize"></i>
                                        {{translate('Picture upload by')}} {{$order->deliveryMan->f_name}}
                                        &nbsp{{$order->deliveryMan->l_name}}
                                    </h6>
                                    @foreach ($order->verificationImages as $image)
                                        <a data-lightbox="verification_gallery" class="aspect-1"
                                           href="{{ getStorageImages(path: $image->image_full_url, type: 'product') }}">
                                            <img class="rounded aspect-1 object-cover" width="100" src="{{ getStorageImages(path: $image->image_full_url, type: 'product') }}" alt="">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @elseif ($order->delivery_type == 'third_party_delivery')

                    <div class="card mt-3">
                        <div class="card-body">
                            <div class="border rounded bg-white p-2">
                                <div class="row g-2">
                                    <div class="col-sm-6 col-xl-4">
                                        <div class="media gap-3">
                                            <img alt="{{ translate('Delivery Man') }}" width="20"
                                                 src="{{ theme_asset(path: 'public/assets/front-end/img/icons/van.png') }}">
                                            <div class="media-body">
                                                <div class="text-muted text-capitalize">
                                                    {{translate('Delivery service name')}}
                                                </div>
                                                <div class="font-weight-bold">{{$order->delivery_service_name}}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-xl-4">
                                        <div class="media gap-3">
                                            <img alt="{{ translate('Delivery Man') }}" width="20"
                                                 src="{{ theme_asset(path:'public/assets/front-end/img/icons/track_order.png') }}">
                                            <div class="media-body">
                                                <div class="text-muted">{{translate('Tracking ID')}} </div>
                                                <div class="font-weight-bold">
                                                    {{$order->third_party_delivery_tracking_id ?? ''}}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="border-top mt-2 pt-2 fs-12 text-muted">
                                    {{translate('Delivered by a courier. Track it on the courier website or app.')}}.
                                    {{translate('Contact the delivery partner directly with these tracking details.')}}.
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="login-card">
                        <div class="text-center pt-5 text-capitalize">

                            <img src="{{theme_asset(path: 'public/assets/front-end/img/icons/delivery-man.svg')}}" alt="">
                            <p class="opacity-60 mt-3">
                                @if ($order->order_type == "POS")
                                    <span>{{translate('This is a POS order. No delivery man is assigned.')}}</span>
                                @else
                                    @if ($order->product_type_check =='digital')
                                        {{translate('This order contains one or more digital products.')}}
                                        {{translate('Delivery man is not assigned for digital products')}}!
                                    @else
                                        {{translate('No delivery man assigned yet')}}!
                                    @endif
                                @endif
                            </p>
                        </div>
                    </div>
                @endif

            </section>
        </div>
    </div>

    <div class="modal fade" id="submitReviewModal" tabindex="-1" aria-labelledby="submitReviewModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h6 class="text-center text-capitalize">{{translate('Submit a review')}}</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body d-flex flex-column gap-3">
                    <form action="{{route('review.deliveryman.store')}}" method="post" enctype="multipart/form-data">
                        @csrf
                        <input name="order_id" value="{{$order->id}}" hidden>
                        <div class="d-flex flex-column gap-2 align-items-center my-4">
                            <h5 class="text-center text-capitalize">{{translate('Rate the delivery quality')}}</h5>
                            <div class="rating-label-wrap position-relative">

                                @php($style = '')
                                @if(isset($order->deliveryManReview))
                                    <?php
                                        $rating = $order->deliveryManReview->rating;
                                        $sessionDirection = session()->get('direction') ?? 'ltr';
                                        if ($sessionDirection == 'ltr') {
                                            $style = match ($rating) {
                                                2 => 'left:36px',
                                                3 => 'left:85px',
                                                4 => 'left:112px',
                                                5 => 'left:155px',
                                                default => 'left:5px',
                                            };
                                        }else{
                                            $style = match ($rating) {
                                                2 => 'right:36px',
                                                3 => 'right:85px',
                                                4 => 'right:112px',
                                                5 => 'right:155px',
                                                default => 'right:5px',
                                            };
                                        }
                                    ?>
                                @endif
                                <label class="rating-label">
                                    <input
                                        name="rating"
                                        class="rating cursor-pointer"
                                        max="5"
                                        min="1"
                                        onchange="this.style.setProperty('--value', `${this.valueAsNumber}`)"
                                        step="1"
                                        style="--value:{{isset($order->deliveryManReview) ? $order->deliveryManReview->rating : 1}}"
                                        type="range"
                                        value="{{$rating??1}}">
                                </label>
                                <span class="rating_content_delivery_man text-primary fs-12 text-nowrap"
                                      style="{{$style}}">
                                    @if(isset($order->deliveryManReview))
                                            <?php
                                            $rating = $order->deliveryManReview->rating;
                                            $rating_status = match ($rating) {
                                                2 => translate('Average'),
                                                3 => translate('Good'),
                                                4 => translate('Very good'),
                                                5 => translate('Excellent'),
                                                default => translate('Poor'),
                                            };
                                            ?>
                                        {{$rating_status}}
                                    @else
                                        {{ translate('Poor') }}
                                    @endif
                                </span>
                            </div>
                        </div>

                        <h6 class="cursor-pointer mb-2">{{translate('Have thoughts to share')}}?</h6>
                        <div class="">
                            <textarea rows="4" name="comment" class="form-control text-area-class"
                                      placeholder="{{translate('Best delivery service, highly recommended')}}">{{$order->deliveryManReview->comment ?? ''}}</textarea>
                        </div>

                        <div class="mt-3 d-flex justify-content-end">
                            @if(isset($order->deliveryManReview->comment) || isset($order->deliveryManReview->rating))
                                <button type="submit" class="btn btn--primary">{{translate('Update')}}</button>
                            @else
                                <button type="submit" class="btn btn--primary">{{translate('Submit')}}</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @isset($order->deliveryMan->id)
        <div class="modal fade" id="chatting_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
             aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-faded-info">
                        <h5 class="modal-title"
                            id="exampleModalLongTitle">{{translate('Send Message to Deliveryman')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{route('messages')}}" method="post" id="deliveryman-chat-form">
                            @csrf
                            @if($order->deliveryMan->id != 0)
                                <input value="{{$order->deliveryMan->id}}" name="delivery_man_id" hidden>
                            @endif

                            <textarea name="message" class="form-control" required
                                      placeholder="{{ translate('Write here') }}..."></textarea>
                            <br>
                            <div class="justify-content-end gap-2 d-flex flex-wrap">
                                <a href="{{route('chat', ['type' => 'delivery-man'])}}"
                                   class="btn btn-soft-primary bg--secondary border">
                                    {{translate('Go to chatbox')}}
                                </a>

                                @if($order->deliveryMan->id != 0)
                                    <button class="btn btn--primary text-white">{{translate('Send')}}</button>
                                @else
                                    <button class="btn btn--primary text-white" disabled>{{translate('Send')}}</button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endisset

    <span id="message-ratingContent"
          data-poor="{{ translate('Poor') }}"
          data-average="{{ translate('Average') }}"
          data-good="{{ translate('Good') }}"
          data-good-message="{{ translate('The delivery service is good') }}"
          data-good2="{{ translate('Very Good') }}"
          data-good2-message="{{ translate('This delivery service is very good I am highly impressed') }}"
          data-excellent="{{ translate('Excellent') }}"
          data-excellent-message="{{ translate('Best delivery service highly recommended') }}"
    ></span>

@endsection
