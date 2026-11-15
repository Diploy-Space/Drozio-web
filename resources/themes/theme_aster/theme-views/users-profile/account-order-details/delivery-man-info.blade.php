@extends('theme-views.layouts.profile')

@section('title', translate('Order details').' | '.$web_config['company_name'].' '.translate('eCommerce'))

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-sm-4">
        <div class="container">
            <div class="row g-3">
                @include('theme-views.partials._profile-aside')
                <div class="col-lg-9">
                    <div class="card h-100">
                        <div class="card-body p-lg-4">
                            @include('theme-views.users-profile.account-order-details._order-details-head',['order'=>$order])
                            <div class="mt-4 card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center gap-4 flex-wrap">
                                        @if($order->delivery_type == 'self_delivery' && isset($order->deliveryMan))
                                            <div class="media gap-2 gap-sm-3">
                                                <div class="avatar overflow-hidden rounded store-avatar2 d-flex align-items-center">
                                                    <img src="{{ getStorageImages(path: $order?->deliveryMan->image_full_url, type:'avatar') }}"
                                                         class="dark-support rounded img-fit" alt="">
                                                </div>
                                                <div class="media-body d-flex flex-column gap-2">
                                                    <h4 class="max-w-350 line-clamp-2">
                                                        {{ $order->deliveryMan->f_name.' '.$order->deliveryMan->l_name}}
                                                    </h4>
                                                    <div class="d-flex gap-2 align-items-center">
                                                        @php($avg_rating = $order?->deliveryMan?->rating[0]?->average ?? 0)                                                        @include('theme-views.partials._rating-display', [
                                                            'rating' => $avg_rating,
                                                            'count' => 0,
                                                            'size' => 'sm',
                                                        ])
                                                    </div>
                                                    <ul class="list-unstyled list-inline-dot flex-wrap fs-12">
                                                        <li class="text-capitalize">{{$delivered_count}} {{translate('Delivered orders')}}</li>
                                                        <li>{{$order->deliveryMan->review_count}} {{ translate('Ratings') }} </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-wrap flex-md-column gap-3">
                                                <button data-bs-toggle="modal" data-bs-target="#chatModal" class="btn btn-outline-primary flex-grow-1 text-capitalize">
                                                    <i class="bi bi-chat-square-fill"></i>
                                                    {{translate('Chat with delivery')}}
                                                </button>
                                                @if($order->payment_status == 'paid' && $order->order_type == 'default_type' && $order->order_status=='delivered' && $order->delivery_man_id)
                                                    <button  class="btn btn-primary flex-grow-1"
                                                             data-bs-toggle="modal" data-bs-target="#reviewModal">
                                                        <i class="bi bi-chat-square-fill"></i>
                                                        {{translate('Review')}}
                                                    </button>
                                                @endif
                                            </div>
                                            <div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header border-0 px-sm-4 py-4">
                                                            <button type="button"
                                                                    class="close-custom-btn btn d-center border-0 text-muted fs-12 p-1 w-30 h-30 lh-1 rounded-pill position-absolute top-0 inset-inline-end-0 m-2"
                                                                    data-bs-dismiss="modal" aria-label="Close">
                                                                <i class="fi fi-sr-cross d-flex"></i>
                                                            </button>
                                                            <div>
                                                                <h2 class="fw-bold fs-20 mb-1">{{translate('Send Message To Deliveryman')}}</h2>
                                                                <p>{{ translate('Communicate order-related instructions instantly') }}</p>
                                                            </div>
                                                        </div>

                                                        <div class="modal-body px-sm-4 py-0">
                                                            <form action="{{route('messages')}}" method="post" id="chat-form">
                                                                @csrf
                                                                @if($order->deliveryMan->id != 0)
                                                                    <input value="{{$order->deliveryMan->id}}" name="delivery_man_id" hidden>
                                                                @endif
                                                                <textarea name="message" class="form-control min-height-100px max-height-200px" placeholder="{{ translate('Type here') }}" required></textarea>
                                                            </form>
                                                        </div>
                                                        <div class="modal-footer border-0 gap-3 py-4 px-sm-4">
                                                            <a href="{{route('chat',['type' => 'delivery-man'])}}" class="btn btn-secondary m-0">
                                                                {{translate('Go to chatbox')}}
                                                            </a>
                                                            @if($order->deliveryMan->id != 0)
                                                                <button type="submit" form="chat-form" class="btn btn-primary d-flex gap-2 m-0 px-3">
                                                                    <i class="fi fi-sr-paper-plane d-flex"></i>
                                                                    {{translate('Send')}}
                                                                </button>
                                                            @else
                                                                <button class="btn btn-primary d-flex gap-2 m-0 px-3" disabled>
                                                                    <i class="fi fi-sr-paper-plane d-flex"></i>
                                                                    {{translate('Send')}}
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header px-sm-5">
                                                            <h1 class="modal-title fs-5" id="reviewModalLabel">{{translate('Submit a review')}}</h1>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{route('review.deliveryman.store')}}" method="post" enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="modal-body px-sm-5">
                                                                <div class="form-group mb-4">
                                                                    <label for="rating">{{'rating'}}</label>
                                                                    <select name="rating" id="rating" class="form-select">
                                                                        <option value="1">{{translate('1')}}</option>
                                                                        <option value="2">{{translate('2')}}</option>
                                                                        <option value="3">{{translate('3')}}</option>
                                                                        <option value="4">{{translate('4')}}</option>
                                                                        <option value="5">{{translate('5')}}</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group mb-4">
                                                                    <label for="comment">{{translate('Comment')}}</label>
                                                                    <input name="order_id" value="{{$order->id}}" hidden>

                                                                    <textarea name="comment" id="comment" class="form-control" rows="4" placeholder="{{translate('Leave a comment')}}"></textarea>
                                                                </div>

                                                            </div>
                                                            <div class="modal-footer gap-3 pb-4 px-sm-5">
                                                                <a href="{{ URL::previous() }}" class="btn btn-secondary m-0" data-bs-dismiss="modal">{{translate('Back')}}</a>
                                                                <button type="submit" class="btn btn-primary m-0">{{translate('Submit')}}</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif($order->delivery_type == 'third_party_delivery')
                                            @php($partnerName = ($deliveryPartnerShipment['provider_label'] ?? null) ?: $order->delivery_service_name)
                                            @php($partnerTracking = ($deliveryPartnerShipment['tracking_number'] ?? null) ?: $order->third_party_delivery_tracking_id)
                                            <div class="media align-items-center gap-3">
                                                <div class="media-body d-flex flex-column gap-2">
                                                    <div class="d-flex gap-2 align-items-center">
                                                        <div class="card-body">
                                                            <address>
                                                                @if($partnerName)
                                                                    <dl class="mb-0 flexible-grid sm-down-1 width--15">
                                                                        <dt>{{translate('Current delivery partner')}}</dt>
                                                                        <dd class="text-capitalize">{{$partnerName}}</dd>
                                                                    </dl>
                                                                @endif
                                                                @if($partnerTracking)
                                                                    <dl class="mb-0 flexible-grid sm-down-1 width--15">
                                                                        <dt>{{translate('Tracking Number')}}</dt>
                                                                        <dd>{{$partnerTracking}}</dd>
                                                                    </dl>
                                                                @endif
                                                                @if(!empty($deliveryPartnerShipment['status']))
                                                                    <dl class="mb-0 flexible-grid sm-down-1 width--15">
                                                                        <dt>{{translate('Shipment Status')}}</dt>
                                                                        <dd class="text-capitalize">{{translate(str_replace('_', ' ', $deliveryPartnerShipment['status']))}}</dd>
                                                                    </dl>
                                                                @endif
                                                                @if(!empty($deliveryPartnerShipment['tracking_url']))
                                                                    <dl class="mb-0 flexible-grid sm-down-1 width--15">
                                                                        <dt>{{translate('Track Shipment')}}</dt>
                                                                        <dd>
                                                                            <a href="{{$deliveryPartnerShipment['tracking_url']}}" target="_blank" rel="noopener">
                                                                                {{translate('Open Link')}}
                                                                            </a>
                                                                        </dd>
                                                                    </dl>
                                                                @endif
                                                            </address>
                                                            <div class="fs-12 opacity-75 mt-2">
                                                                {{translate('Delivered by a courier. Track it on the courier website or app.')}}.
                                                                {{translate('Contact the delivery partner directly with these tracking details.')}}.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="d-flex justify-content-center flex-grow-1">
                                                <div class="d-flex flex-column align-items-center">
                                                    <img width="120" src="{{ theme_asset('assets/img/not_found.png') }}" alt="">
                                                    <h6 class="text-center text-danger mt-3">
                                                        {{ translate('No delivery man assigned yet') }}
                                                    </h6>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if($order->delivery_type == 'self_delivery' && isset($order->deliveryMan))
                                @if (count($order->verificationImages)>0 && $order->verification_status == 1)
                                    <div class="w-100 mt-4">
                                        <div class="card border-0">
                                            <div class="card-body">
                                                <h5 class="text-muted mb-3">
                                                    <span class="text-base me-2"><i class="bi bi-camera"></i></span>
                                                    {{ translate('Picture Upload by') }} {{$order->deliveryMan->f_name}}&nbsp{{$order->deliveryMan->l_name}}
                                                </h5>

                                                <div class="d-flex flex-wrap gap-3 custom-image-popup-init">
                                                    @foreach ($order->verificationImages as $image)
                                                        <a href="{{ getStorageImages(path: $image->image_full_url, type:'product') }}" class="custom-image-popup">
                                                            <img class="height-100 rounded remove-mask-img" alt=""
                                                                 src="{{ getStorageImages(path: $image->image_full_url, type:'product') }}">
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
@push('script')
    <script src="{{ theme_asset('assets/js/chat.js') }}" defer></script>
@endpush
