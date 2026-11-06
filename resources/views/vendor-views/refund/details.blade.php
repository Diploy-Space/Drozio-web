@extends('layouts.vendor.app')

@section('title', translate('Refund details'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/new/back-end/libs/owl-carousel/owl.carousel.min.css')}}"/>
@endpush

@section('content')

    <div class="content container-fluid">
        <div class="d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/refund_transaction.png') }}"
                     alt="{{ translate('Refund details') }}">
                {{ translate('Refund details') }}
            </h2>
            <div class="d-flex flex-wrap gap-3">
                @if ($refund->change_by !='admin')
                    @if($refund['status'] != 'rejected' && $refund['denied_count'] < $statusChangeLimit)
                        <button class="btn btn-danger min-w--100 p-2 px-3" data-toggle="modal" data-target="#rejectModal">
                            {{ translate('Reject') }}
                        </button>
                    @endif
                    @if($refund['status'] != 'approved' && $refund['approved_count'] < $statusChangeLimit)
                        <button class="btn btn-primary min-w--100 p-2 px-3" data-toggle="modal" data-target="#approveModal">
                            {{ translate('Approve') }}
                        </button>
                    @endif
                @endif
            </div>
        </div>
        <div class="card p-4">

            @if ($refund['change_by'] !='admin')
            <div class="mb-3 alert--message">
                <div class="d-flex justify-content-between w-100">
                    <span class="">
                        <img class="mb-1" src="{{ dynamicAsset(path: 'public/assets/back-end/img/warning-icon.png') }}" alt="{{ translate('Warning') }}">

                        @if($refund['approved_count'] >= $statusChangeLimit || $refund['denied_count'] >= $statusChangeLimit)
                            {{ translate('You have already') . $refund['status'] . translate('Refund status twice') . '.' }}
                        @elseif($refund['status'] != 'pending')

                            @if($refund['status'] == 'approved')
                                @if($refund['denied_count'] >= 1)
                                    {{ translate('You have already denied refund status once') . '.' }}
                                @else
                                    {{ translate('You can change refund status maximum 2 times') . '.' }}
                                @endif

                            @elseif($refund['status'] == 'rejected')
                                @if($refund['approved_count'] >= 1)
                                    {{ translate('You have already approved refund status once') . '.' }}
                                @else
                                    {{ translate('You can change refund status maximum 2 times') . '.' }}
                                @endif
                            @endif

                        @else
                            {{ translate('You can change refund status maximum 2 times') . '.' }}
                        @endif
                    </span>
                    <a href="javascript:" class="align-items-center close-alert-message">
                        <i class="tio-clear"></i>
                    </a>
                </div>
            </div>
            @endif
            <div class="row g-2">
                <div class="col-lg-4">
                    <div class="card card-body shadow-none border-0 h-100 bg-section">
                        <h4 class="mb-20">{{translate('Refund summary')}}</h4>
                        <ul class="dm-info p-0 m-0 text-dark">
                            <li class="align-items-center">
                                <span class="left">{{translate('Refund id')}} </span> <span>:</span> <span class="right fw-medium">{{$refund->id}}</span>
                            </li>
                            <li class="align-items-center">
                                <span class="left text-capitalize">{{translate('Refund requested date')}}</span>
                                <span>:</span>
                                <span class="right fw-medium">{{date('d M Y, h:s:A',strtotime($refund['created_at']))}}</span>
                            </li>
                            <li class="align-items-center">
                                <span class="left">{{translate('Refund status')}}</span> <span>:</span> <span class="right fw-medium">
                                    @if ($refund['status'] == 'pending')
                                        <span class="badge badge-secondary-2 fw-medium lh-base"> {{translate($refund['status'])}}</span>
                                    @elseif($refund['status'] == 'approved')
                                        <span class="badge badge--primary-2 fw-medium lh-base"> {{translate($refund['status'])}}</span>
                                    @elseif($refund['status'] == 'refunded')
                                        <span class="badge badge-success-2 fw-medium lh-base"> {{translate($refund['status'])}}</span>
                                    @elseif($refund['status'] == 'rejected')
                                        <span class="badge badge--danger-2 fw-medium lh-base"> {{translate($refund['status'])}}</span>
                                    @endif
                                </span>
                            </li>
                            <li class="align-items-center">
                                <span class="left">{{translate('Payment Method')}} </span> <span>:</span> <span class="right fw-medium">{{str_replace('_',' ',$order->payment_method)}}</span>
                            </li>
                        </ul>
                        <div class="mt-2 text-center">
                            <a class="py-3 text-center mx-auto text-primary fs-12 fw-semibold px-2" href="{{route('vendor.retail.orders.details',['id'=>$order->id])}}">{{translate('View Order Details')}}</a></span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card card-body shadow-none border-0 h-100 bg-section">
                        <div class="gap-3 mb-4 d-flex justify-content-between flex-wrap align-items-center">
                            <h4 class="m-0">{{translate('Product Details')}}</h4>
                        </div>
                        <div class="refund-details d-flex justify-content-between">
                            <div class="d-flex flex-xxs-nowrap flex-wrap gap-3 {{ $isProductUnavailable ? 'pe-none opacity-50 cus-disabled' : '' }}" @if($isProductUnavailable) data-toggle="tooltip" title="{{ translate('Product has been deleted') }}" @endif>
                                <div class="img ">
                                    <div class="onerror-image w-80px min-w-60px h-80px border rounded">
                                        <img src="{{getStorageImages(path:$productThumbnail,type: 'backend-product')}}" alt="" class="w-100 h-100 aspect-1">
                                    </div>
                                </div>
                                <div class="--content flex-grow-1">
                                    @if($isProductUnavailable)
                                    <h4 class="fs-14 fw-medium line--limit-2 max-w-170px min-w-100">{{ $productName }}</h4>
                                    @else
                                        <a href="{{route('vendor.retail.products.view',[$refund->product->id])}}">
                                            <h5 class="fw-medium">{{ $productName }}</h5>
                                        </a>
                                        @endif
                                    @if ($refund->orderDetails->variant)
                                        <div class="fs-12 text-dark">
                                            <span>{{translate('Variation')}}</span>
                                            <span>:</span>
                                            <span class="fw-medium">{{$refund->orderDetails->variant}}</span>
                                        </div>
                                    @endif
                                    @if($refund->orderDetails->digital_file_after_sell)
                                        <a href="{{ $digitalFileDownloadPath ?? 'javascript:' }}" class="btn btn-outline--primary btn-sm mt-3 {{ $digitalFileDownloadPath ?? 'download-path-not-found' }}" title="{{translate('Download')}}">
                                            {{translate('Download')}} <i class="tio-download"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <ul class="dm-info p-0 m-0 w-l-115">
                                <li>
                                    <span class="left text-dark">{{translate('Quantity')}}</span>
                                    <span class="text-dark">:</span>
                                    <span class="right fw-medium">
                                        <strong class="fw-medium text-dark">
                                            {{$refund->orderDetails->qty}}
                                        </strong>
                                    </span>
                                </li>
                                <li>
                                    <span class="left text-dark">{{translate('Total price')}} </span>
                                    <span class="text-dark">:</span>
                                    <span class="right fw-medium">
                                        <strong class="fw-medium text-dark">
                                            {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $refund->orderDetails->price*$refund->orderDetails->qty), currencyCode: getCurrencyCode())}}
                                        </strong>
                                    </span>
                                </li>

                                <li>
                                    <span class="left text-dark">{{translate('Total discount')}} </span>
                                    <span class="text-dark">:</span>
                                    <span class="right fw-medium">
                                        <strong class="fw-medium text-dark">
                                            {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $refund->orderDetails->discount), currencyCode: getCurrencyCode())}}
                                        </strong>
                                    </span>
                                </li>
                                <li>
                                    <span class="left text-dark">{{translate('Coupon Discount')}} </span>
                                    <span class="text-dark">:</span>
                                    <span class="right fw-medium">
                                        <strong class="fw-medium text-dark">
                                            {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $couponDiscount), currencyCode: getCurrencyCode())}}
                                        </strong>
                                    </span>
                                </li>

                                <li>
                                    <span class="left text-dark">{{translate('Referral Discount')}} </span>
                                    <span class="text-dark">:</span>
                                    <span class="right fw-medium">
                                        <strong class="fw-medium text-dark">
                                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $referralDiscount), currencyCode: getCurrencyCode())}}
                                        </strong>
                                    </span>
                                </li>

                                <li>
                                    <span class="left text-dark">{{translate('Total Tax')}} </span>
                                    <span class="text-dark">:</span>
                                    <span class="right fw-medium">
                                        <strong class="fw-medium text-dark">
                                            {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $refund->orderDetails->tax), currencyCode: getCurrencyCode())}}
                                        </strong>
                                    </span>
                                </li>

                                <li>
                                    <span class="left text-dark">{{translate('Subtotal')}} </span>
                                    <span class="text-dark">:</span>
                                    <span class="right fw-medium">
                                        <strong class="fw-medium text-dark">
                                            {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $subtotal), currencyCode: getCurrencyCode())}}
                                        </strong>
                                    </span>
                                </li>

                                <li>
                                    <span class="left text-dark">{{translate('Refundable amount')}} </span>
                                    <span class="text-dark">:</span>
                                    <span class="right fw-medium">
                                        <strong class="fw-medium text-dark">
                                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $refundAmount), currencyCode: getCurrencyCode())}}
                                        </strong>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="card card-body shadow-none border-0 h-100 bg-section">
                        <h4 class="mb-20 text-capitalize">{{translate('Refund reason by customer')}}</h4>
                        <p>
                            {{ Str::limit($refund->refund_reason, 100, '...') }}
                            @if(Str::length($refund->refund_reason) > 100)
                                <span class="text-primary text-underline text-nowrap mx-1"
                                      data-toggle="modal" href="#refund-reason-modal">
                                    {{ translate('See More') }}
                                </span>
                            @endif
                        </p>
                        @if ($refund->images)
                            <div class="position-relative">
                                <div class="refund-image-wrapper d-flex gap-3 overflow-x-auto scrollbar-hidden">
                                    @foreach ($refund->images_full_url as $key => $photo)
                                        <a
                                            href="javascript:void(0)"
                                            data-toggle="modal"
                                            data-target="#imgViewModal"
                                            data-index="{{ $key }}"
                                            class="w-60px aspect-1 d-block border overflow-hidden rounded flex-shrink-0">
                                            <img src="{{ getStorageImages(path: $photo,type:'backend-basic') }}"
                                                class="img-fluid w-100 h-100 object-fit-cover" alt="">
                                        </a>
                                    @endforeach
                                </div>

                                <div class="prev_btn nav--tab__prev">
                                    <button type="button" class="btn btn-circle border-0 btn--primary" style="--size: 1.5rem;">
                                        <i class="fi fi-sr-angle-left d-flex fs-10"></i>
                                    </button>
                                </div>

                                <div class="next_btn nav--tab__next">
                                    <button type="button" class="btn btn-circle border-0 btn--primary" style="--size: 1.5rem;">
                                        <i class="fi fi-sr-angle-right d-flex fs-10"></i>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card card-body shadow-none border-0 h-100 bg-section">
                        <h4 class="mb-3 text-capitalize">{{translate('Deliveryman information')}}</h4>
                        <div class="bg-white rounded p-3 h-100">
                            <div class="key-val-list d-flex flex-column gap-2 min-width--60px h-100">
                                @if($order->deliveryMan)
                                    <div class="key-val-list-item d-flex gap-3">
                                        <span class="text-capitalize">{{translate('Name')}}</span>:
                                        <span>{{$order->deliveryMan->f_name . ' ' .$order->deliveryMan->l_name}}</span>
                                    </div>
                                    <div class="key-val-list-item d-flex gap-3">
                                        <span class="text-capitalize">{{translate('Email Address')}}</span>:
                                        <span>
                                        <a class="text-dark"
                                        href="mailto:{{ $order->deliveryMan->email }}">{{$order->deliveryMan?->email }}
                                        </a>
                                    </span>
                                    </div>
                                    <div class="key-val-list-item d-flex gap-3">
                                        <span class="text-capitalize">{{translate('Phone number')}} </span>:
                                        <span>
                                        <a class="text-dark"
                                        href="tel:{{ $order->deliveryMan->phone }}">{{$order->deliveryMan?->phone }}
                                        </a>
                                    </span>
                                    </div>
                                @else
                                    <div class="d-flex justify-content-center align-items-center gap-3 h-100">
                                        <h5 class="fs-12 fw-medium mb-0">{{ translate('No delivery man assigned') }}</h5>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <div class="card">
                <div class="card-body ">
                    <h4 class="mb-20">{{translate('Refund Request Logs')}}</h4>
                    <div class="table-responsive datatable-custom">
                        <table
                            class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>{{translate('Serial')}}</th>
                                <th class="text-center">{{translate('Changed by')}}</th>
                                <th>{{translate('Date')}}</th>
                                <th class="text-center">{{translate('Status')}}</th>
                                <th>{{translate('Approved / rejected note')}}</th>

                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($refund->refundStatus as $key=>$status)
                                <tr>
                                    <td>
                                        {{$key+1}}
                                    </td>
                                    <td class="text-capitalize text-center">
                                        {{$status->change_by == 'seller' ? 'vendor' : $status->change_by}}
                                    </td>
                                    <td>{{date('d M Y, h:s:A',strtotime($refund['created_at']))}}</td>


                                    <td class="text-capitalize text-center">
                                        @if ($status['status'] == 'pending')
                                        <span class="badge bg-info text-info bg-opacity-10">
                                            {{ translate($status['status']) }}
                                        </span>
                                        @elseif($status['status'] == 'approved')
                                            <span class="badge bg-primary text-primary bg-opacity-10">
                                                {{ translate($status['status']) }}
                                            </span>
                                        @elseif($status['status'] == 'refunded')
                                            <span class="badge bg-success text-success bg-opacity-10">
                                                {{ translate($status['status']) }}
                                            </span>
                                        @elseif($status['status'] == 'rejected')
                                            <span class="badge bg-danger text-danger bg-opacity-10">
                                                {{ translate($status['status']) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-break">
                                        @if($status?->message)
                                            <div class="word-break max-w-370 min-w-180">
                                                {{ Str::limit($status->message, 100, '...') }}
                                                @if(Str::length($status->message) > 100)
                                                    <span class="text-primary text-underline text-nowrap mx-1"
                                                          data-toggle="modal" href="#note-modal-{{ $status['id'] }}">
                                                        {{ translate('See More') }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            {{ '-' }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        @if(count($refund->refundStatus)==0)
                            @include('layouts.vendor.partials._empty-state',['text'=>'no_data_found'], ['image' => 'product-empty'])
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if ($refund['change_by'] !='admin')
        @if($refund['denied_count'] < $statusChangeLimit)
            <div class="modal fade" id="rejectModal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{route('vendor.retail.refund.update-status')}}" method="post" id="submit-rejected-form">
                            @csrf
                            <div class="modal-body">
                                <input type="hidden" name="id" value="{{$refund->id}}">
                                <input type="hidden" name="refund_status" value="rejected">
                                <div class="text-center">
                                    <img class="mb-3" src="{{dynamicAsset(path: 'public/assets/back-end/img/refund-reject.png')}}" alt="{{translate('Refund reject')}}">
                                    <h4 class="mb-4 mx-auto max-w-283">
                                        {{translate('You can reject that refund request two times').', '.translate('Then you cannot change this status').'.'}}
                                    </h4>
                                </div>
                                <textarea class="form-control text-area-max-min" placeholder="{{translate('Please write the reject reason').'...'}}" name="rejected_note" rows="3"></textarea>
                                <div class="d-flex flex-wrap justify-content-end gap-3 mt-3">
                                    <button type="button" class="btn btn-secondary px-3" data-dismiss="modal">{{ translate('Close') }}</button>
                                    <button type="button" class="btn btn--primary form-submit" data-form-id="submit-rejected-form" data-message="{{translate('Want to reject this refund request').'?'}}"  data-redirect-route="{{route('vendor.retail.refund.index',['status'=>$refund['status']])}}">{{ translate('Submit') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
        @if($refund['approved_count'] < $statusChangeLimit)
            <div class="modal fade" id="approveModal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{route('vendor.retail.refund.update-status')}}" method="post" id="submit-approve-form">
                            @csrf
                            <div class="modal-body">
                                <input type="hidden" name="id" value="{{$refund->id}}">
                                <input type="hidden" name="refund_status" value="approved">
                                <div class="text-center">
                                    <img class="mb-3" src="{{dynamicAsset(path: 'public/assets/back-end/img/refund-approve.png')}}" alt="{{translate('Refund approve')}}">
                                    <h4 class="mb-4 mx-auto max-w-283">
                                        {{translate('You can approve that refund request two times').', '.translate('Then you cannot change this status').'.'}}
                                    </h4>
                                </div>
                                <textarea class="form-control text-area-max-min" placeholder="{{translate('Please write the approve reason..').'...'}}" name="approved_note" rows="3"></textarea>
                                <div class="d-flex flex-wrap justify-content-end gap-3 mt-3">
                                    <button type="button" class="btn btn-secondary px-3" data-dismiss="modal">{{ translate('Close') }}</button>
                                    <button type="button" class="btn btn--primary form-submit" data-form-id="submit-approve-form" data-message="{{translate('Want to approve this refund request').'?'}}" data-redirect-route="{{route('vendor.retail.refund.index',['status'=>$refund['status']])}}">{{ translate('Submit') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endif

    @foreach ($refund?->refundStatus as $key => $status)
        @include('vendor-views.refund.partials._note-modal', ['status' => $status])
    @endforeach

    @include('vendor-views.refund.partials._refund-reason-modal')
    @include('vendor-views.refund.partials._img-view-modal')
@endsection
@push('script_2')
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/libs/owl-carousel/owl.carousel.min.js') }}"></script>
    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/vendor/refund.js')}}"></script>
@endpush
