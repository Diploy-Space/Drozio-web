@php
use Illuminate\Support\Facades\Session;
@endphp
@extends('layouts.admin.app')

@section('title', $seller?->shop->name ?? translate('Not found'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/order-items-popover.css') }}">
@endpush

@section('content')
    @php($direction = Session::get('direction'))
    <div class="content container-fluid">
        

        <div class="flex-between d-sm-flex row align-items-center justify-content-between mb-2 mx-1">
            <div>
                @if ($seller->status=="pending")
                    <div class="mt-4 pe-2">
                        <div class="">
                            <div class="mx-1">
                                <h4><i class="fi fi-rr-shop"></i></h4>
                            </div>
                            <div>{{ translate('Vendor request to open a shop.') }}</div>
                        </div>
                        <div class="text-center">
                            <form class="d-inline-block" action="{{route('admin.people.vendors.update-status')}}" method="POST">
                                @csrf
                                <input type="hidden" name="id" value="{{$seller->id}}">
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn btn-primary btn-sm">{{translate('Approve')}}</button>
                            </form>
                            <form class="d-inline-block" action="{{route('admin.people.vendors.update-status')}}" method="POST">
                                @csrf
                                <input type="hidden" name="id" value="{{$seller->id}}">
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn btn-danger btn-sm">{{translate('Reject')}}</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @include('admin-views.vendor.partials._detail-header')

        <div class="card card-body mb-20">
            <h3 class="mb-20">{{ translate('Current Order Summary') }}</h3>
            <div class="row g-3">
                <div class="col-lg-6 col-xl-3">
                    <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded">
                        <div class="d-flex gap-3 align-items-center">
                            <img width="20"
                                 src="{{ dynamicAsset(path: 'public/assets/back-end/img/pending.png') }}"
                                 alt="">
                            <h4 class="mb-0">{{ translate('Pending') }}</h4>
                        </div>
                        <span class="text-primary h3 mb-0 overflow-wrap-anywhere">
                            {{ $allOrdersInfo['pending_order'] }}
                        </span>
                    </div>
                </div>
                <div class="col-lg-6 col-xl-3">
                    <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded order-stats_confirmed">
                        <div class="d-flex gap-3 align-items-center">
                            <img width="20"
                                 src="{{ dynamicAsset(path: 'public/assets/back-end/img/confirmed.png') }}"
                                 alt="">
                            <h4 class="mb-0">{{ translate('Confirmed') }}</h4>
                        </div>
                        <span class="text-success h3 mb-0 overflow-wrap-anywhere">
                                {{ $allOrdersInfo['confirmed_order'] }}
                            </span>
                    </div>
                </div>

                <div class="col-lg-6 col-xl-3">
                    <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded order-stats_packaging">
                        <div class="d-flex gap-3 align-items-center">
                            <img width="20"
                                 src="{{ dynamicAsset(path: 'public/assets/back-end/img/packaging.png') }}"
                                 alt="">
                            <h4 class="mb-0">{{ translate('Packaging') }}</h4>
                        </div>
                        <span class="text-danger h3 mb-0 overflow-wrap-anywhere">
                            {{ $allOrdersInfo['processing_order'] }}
                        </span>
                    </div>
                </div>

                <div class="col-lg-6 col-xl-3">
                    <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded order-stats_out-for-delivery">
                        <div class="d-flex gap-3 align-items-center">
                            <img width="20"
                                 src="{{ dynamicAsset(path: 'public/assets/back-end/img/out-of-delivery.png') }}"
                                 alt="">
                            <h4 class="mb-0">{{ translate('Out For Delivery') }}</h4>
                        </div>
                        <span class="text-success h3 mb-0 overflow-wrap-anywhere">
                            {{ $allOrdersInfo['out_for_delivery_order'] }}
                        </span>
                    </div>
                </div>

                <div class="col-lg-6 col-xl-3">
                    <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded order-stats_delivered">
                        <div class="d-flex gap-3 align-items-center">
                            <img width="20"
                                 src="{{ dynamicAsset(path: 'public/assets/back-end/img/delivered.png') }}"
                                 alt="">
                            <h4 class="mb-0">{{ translate('Delivered') }}</h4>
                        </div>
                        <span class="text-primary h3 mb-0 overflow-wrap-anywhere">
                            {{ $allOrdersInfo['delivered_order'] }}
                        </span>
                    </div>
                </div>

                <div class="col-lg-6 col-xl-3">
                    <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded order-stats_canceled">
                        <div class="d-flex gap-3 align-items-center">
                            <img width="20"
                                 src="{{ dynamicAsset(path: 'public/assets/back-end/img/canceled.png') }}"
                                 alt="">
                            <h4 class="mb-0">{{ translate('Canceled') }}</h4>
                        </div>
                        <span class="text-danger h3 mb-0 overflow-wrap-anywhere">
                            {{ $allOrdersInfo['canceled_order'] }}
                        </span>
                    </div>
                </div>

                <div class="col-lg-6 col-xl-3">
                    <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded order-stats_returned">
                        <div class="d-flex gap-3 align-items-center">
                            <img width="20"
                                 src="{{ dynamicAsset(path: 'public/assets/back-end/img/returned.png') }}"
                                 alt="">
                            <h4 class="mb-0">{{ translate('Returned') }}</h4>
                        </div>
                        <span class="text-warning h3 mb-0 overflow-wrap-anywhere">
                            {{ $allOrdersInfo['returned_order'] }}
                        </span>
                    </div>
                </div>

                <div class="col-lg-6 col-xl-3">
                    <div class="d-flex gap-3 align-items-center justify-content-between p-20 bg-section rounded order-stats_failed">
                        <div class="d-flex gap-3 align-items-center">
                            <img width="20"
                                 src="{{ dynamicAsset(path: 'public/assets/back-end/img/failed-to-deliver.png') }}"
                                 alt="">
                            <h4 class="mb-0">{{ translate('Failed to deliver') }}</h4>
                        </div>
                        <span class="text-danger h3 mb-0 overflow-wrap-anywhere">
                            {{ $allOrdersInfo['failed_order'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center">
                    <div>
                        <h3 class="mb-0 d-flex gap-2 align-items-center">
                            {{ translate('Order List') }}
                            <span class="badge text-dark bg-body-secondary fw-semibold rounded-45">
                                {{ $orders->total() }}
                            </span>
                        </h3>
                    </div>

                    <div class="d-flex gap-3 align-items-center flex-wrap">
                        <form action="{{ url()->current() }}" method="GET" class="min-w-100-mobile min-w-280">
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="hidden" name="order_date" value="{{request('order_date')}}">
                                    <input type="hidden" name="customer_joining_date" value="{{request('customer_joining_date')}}">
                                    <input type="hidden" name="is_active" value="{{request('is_active')}}">
                                    <input type="hidden" name="sort_by" value="{{request('sort_by')}}">
                                    <input type="hidden" name="choose_first" value="{{request('choose_first')}}">
                                    <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                                           placeholder="{{ translate('Search by Customer name')}}"  aria-label="Search orders" value="{{ request('searchValue') }}">
                                    <div class="input-group-append search-submit">
                                        <button type="submit">
                                            <i class="fi fi-rr-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <a type="button" class="btn btn-outline-primary min-h-40" href="{{route('admin.people.vendors.order-list-export',[$seller['id'],'searchValue' => request('searchValue'),'order_status' => request('order_status')])}}">
                            <i class="fi fi-sr-inbox-in"></i>
                            <span class="fs-12">{{ translate('Export') }}</span>
                        </a>
                    </div>
                </div>

                <div class="table-responsive datatable-custom mt-4">
                    <table id="datatable"
                           style="text-align: {{$direction === "rtl" ? 'right' : 'left'}};"
                           class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{translate('Serial')}}</th>
                            <th>{{translate('Order ID')}}</th>
                            <th>{{translate('Order Date')}}</th>
                            <th>{{translate('Customer Information')}}</th>
                            <th class="text-end">{{translate('Total Amount')}}</th>
                            <th class="text-center">{{ translate('Order Status') }}</th>
                            <th class="text-center">{{translate('Action')}}</th>
                        </tr>
                        </thead>
                        <tbody id="set-rows">
                        @foreach($orders as $key=>$order)
                            <tr class="status class-all">
                                <td>
                                    {{$orders->firstItem()+$key}}
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-column">
                                        <div class="d-flex align-items-center gap-1">
                                            <a class="hover-primary text-dark"
                                               href="{{route('admin.people.vendors.order-details',['order_id'=>$order['id'],'vendor_id'=>$order['seller_id']])}}">
                                                {{ $order['id'] }}
                                                {!! $order->order_type == 'POS' ? '<span class="text--primary">(POS)</span>' : '' !!}
                                            </a>
                                            @if($order->edited_status == 1 && $order?->latestEditHistory?->order_due_payment_status == 'unpaid' && $order?->latestEditHistory?->order_due_payment_method != "offline_payment" && $order?->latestEditHistory?->order_due_payment_method != "cash_on_delivery" && $order?->latestEditHistory?->order_due_amount > 0)
                                                <span class="flex-shrink-0" data-bs-toggle="tooltip"
                                                      title="{{ translate('Customer will pay the due amount') }}">
                                                        <img width="14"
                                                             src="{{ dynamicAsset(path: 'public/assets/back-end/img/due-amount-icon.png') }}"
                                                             alt="">
                                                    </span>
                                            @elseif($order->edited_status == 1 && $order?->latestEditHistory?->order_return_payment_status == 'pending' && $order?->latestEditHistory?->order_return_amount > 0)
                                                <span class="flex-shrink-0" data-bs-toggle="tooltip"
                                                      title="{{ translate('You need to return the excess amount to the customer.') }}">
                                                        <img width="16"
                                                             src="{{ dynamicAsset(path: 'public/assets/back-end/img/return-amount-icon.png') }}"
                                                             alt="">
                                                    </span>
                                            @endif
                                        </div>
                                        @if (count($itemPreviews[$order['id']] ?? []) > 0)
                                            @include('admin-views.order.partials._items-popover', ['orderId' => $order['id'], 'items' => $itemPreviews[$order['id']]])
                                        @endif
                                        @if($order->edited_status == 1)
                                            <span class="badge badge-info text-bg-info w-max-content">
                                                {{ translate('Edited') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div>{{date('d M Y',strtotime($order['created_at']))}},</div>
                                    <div>{{ date("h:i A",strtotime($order['created_at'])) }}</div>
                                </td>
                                <td>
                                    @if($order->is_guest)
                                        <strong class="title-name">{{translate('Guest customer')}}</strong>
                                    @elseif($order->customer_id == 0)
                                        <strong class="title-name">
                                            {{ translate('Walk-In-Customer') }}
                                        </strong>
                                    @else
                                        @if($order->customer)
                                            <a class="text-dark text-capitalize" href="{{route('admin.people.customer.view',['user_id'=>$order['customer_id']])}}">
                                                <strong class="title-name d-block max-w-300 text-wrap">
                                                    {{$order->customer['f_name'].' '.$order->customer['l_name']}}
                                                </strong>
                                            </a>
                                            <a class="d-block text-dark" href="tel:{{ $order->customer['phone'] }}">{{ $order->customer['phone'] }}</a>
                                        @else
                                            <label class="badge badge-danger text-bg-danger">{{translate('Invalid customer data')}}</label>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div>
                                        @php($orderTotalPriceSummary = \App\Utils\OrderManager::getOrderTotalPriceSummary(order: $order))
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['totalAmount']), currencyCode: getCurrencyCode()) }}
                                    </div>

                                    @if ($order->payment_status == 'paid')
                                        <span
                                            class="fs-12 fw-medium text-success">{{ translate('Paid') }}</span>
                                    @else
                                        <span
                                            class="fs-12 fw-medium text-danger">{{ translate('Unpaid') }}</span>
                                    @endif
                                </td>
                                <td class="text-capitalize text-center">
                                    @if($order['order_status']=='pending')
                                        <span class="badge badge-info text-bg-info">
                                            {{translate('Pending')}}
                                        </span>
                                    @elseif($order['order_status']=='confirmed')
                                        <span class="badge badge-info text-bg-info">
                                            {{translate('Confirmed')}}
                                        </span>
                                    @elseif($order['order_status']=='processing')
                                        <span class="badge badge-warning text-bg-warning">
                                            {{translate('Processing')}}
                                        </span>
                                    @elseif($order['order_status']=='out_for_delivery')
                                        <span class="badge badge-warning text-bg-warning">
                                            {{translate('Out For Delivery')}}
                                        </span>
                                    @elseif($order['order_status']=='delivered')
                                        <span class="badge badge-success text-bg-success">
                                            {{translate('Delivered')}}
                                        </span>
                                    @else
                                        <span class="badge badge-danger text-bg-danger">
                                            {{ translate(str_replace('_',' ',$order['order_status'])) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        @if($order->edited_status == 1 && $order?->edit_return_amount > 0)
                                            <button type="button" class="btn btn-outline-warning icon-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#returnDueAmountModal-{{$order['id']}}">
                                                <i class="fi fi-sr-undo-alt d-flex"></i>
                                            </button>
                                        @endif
                                        <a class="btn btn-outline-success btn-outline-success-dark icon-btn"
                                           title="{{ translate('View') }}"
                                           href="{{ route('admin.people.vendors.order-details',['order_id' => $order['id'],'vendor_id' => $order['seller_id']]) }}">
                                            <i class="fi fi-sr-eye d-flex"></i>
                                        </a>
                                        <a class="btn btn-outline-success btn-outline-success-dark icon-btn"
                                           target="_blank" title="{{ translate('Invoice') }}"
                                           href="{{ route('admin.retail.orders.generate-invoice', [$order['id']]) }}">
                                            <i class="fi fi-sr-down-to-line d-flex"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="table-responsive mt-4">
                    <div class="px-4 d-flex justify-content-end">
                        {!! $orders->links() !!}
                    </div>
                </div>

                @if(count($orders)==0)
                    @include('layouts.admin.partials._empty-state',['text'=>'no_order_found'],['image'=>'default'])
                @endif
            </div>
        </div>
    </div>

    @if($orders->isNotEmpty())
        @foreach($orders as $order)
            @include('admin-views.order.partials.modal.order-edit-return-amount-modal',['order' => $order])
        @endforeach
    @endif

    @include('admin-views.vendor.partials.offcanvas._filter-offcanvas')
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/order-items-popover.js') }}"></script>
    <script>
        $(function () {
            let slider = $("#price_range_slider");
            let minThumb = $("#thumb_min");
            let maxThumb = $("#thumb_max");
            let range = $(".slider-range");
            let minInput = $("#min_price");
            let maxInput = $("#max_price");

            let sliderMin = slider?.data('min-value') ?? 0;
            let sliderMax = slider?.data('max-value') ?? 100000000;

            let minValue = sliderMin;
            let maxValue = sliderMax;

            let isRtl = $('html').attr('dir') === 'rtl';

            function updateSlider() {
                let sliderWidth = slider.width();

                let minLeft = (((minValue - sliderMin) / (sliderMax - sliderMin)) * sliderWidth);
                let maxLeft = ((maxValue - sliderMin) / (sliderMax - sliderMin)) * sliderWidth;

                if (isRtl) {
                    minLeft = sliderWidth - minLeft;
                    maxLeft = sliderWidth - maxLeft;
                }

                minThumb.css(isRtl ? "insetInlineEnd" : "insetInlineStart", minLeft + "px");
                maxThumb.css(isRtl ? "insetInlineEnd" : "insetInlineStart", maxLeft + "px");

                range.css({
                    [isRtl ? 'insetInlineEnd' : 'insetInlineStart']: Math.min(minLeft, maxLeft) + "px",
                    width: Math.abs(maxLeft - minLeft) + "px",
                });

                minInput.val(minValue !== null ? minValue : minInput.attr('placeholder'));
                maxInput.val(maxValue !== null ? maxValue : maxInput.attr('placeholder'));

                let distance = maxValue - minValue;
                $('#slider_distance').text("$" + distance.toLocaleString());
            }

            function clamp(value, min, max) {
                return Math.min(Math.max(value, min), max);
            }

            function handleDrag(thumb, isMinThumb) {
                function startDrag(startX, startValue) {
                    let sliderWidth = slider.width();

                    function moveHandler(e) {
                        let pageX = e.pageX || (e.originalEvent.touches && e.originalEvent.touches[0].pageX);
                        if (!pageX) return;

                        let deltaX = isRtl ? (startX - pageX) : (pageX - startX);
                        let valueChange = (deltaX / sliderWidth) * (sliderMax - sliderMin);
                        let newValue = clamp(startValue + valueChange, sliderMin, sliderMax);

                        newValue = Math.round(newValue);

                        if (isMinThumb) {
                            minValue = Math.min(newValue, maxValue || sliderMax);
                        } else {
                            maxValue = Math.max(newValue, minValue || sliderMin);
                        }

                        updateSlider();
                    }

                    function stopHandler() {
                        $(document).off(".slider");
                    }

                    $(document).on("mousemove.slider touchmove.slider", moveHandler);
                    $(document).on("mouseup.slider touchend.slider touchcancel.slider", stopHandler);
                }

                thumb.on("mousedown touchstart", function (e) {
                    e.preventDefault();
                    let pageX = e.pageX || (e.originalEvent.touches && e.originalEvent.touches[0].pageX);
                    if (!pageX) return;

                    console.log("drag start", this.id, pageX);

                    let startValue = isMinThumb ? minValue : maxValue;
                    startDrag(pageX, startValue);
                });
            }

            minInput.on("input", function () {
                let inputValue = parseInt($(this).val(), 10);
                if (!isNaN(inputValue)) {
                    minValue = clamp(inputValue, sliderMin, maxValue || sliderMax);
                } else {
                    minValue = null;
                }
                updateSlider();
            });

            maxInput.on("input", function () {
                let inputValue = parseInt($(this).val(), 10);
                if (!isNaN(inputValue)) {
                    maxValue = clamp(inputValue, minValue || sliderMin, sliderMax);
                } else {
                    maxValue = null;
                }
                updateSlider();
            });

            handleDrag(minThumb, true);
            handleDrag(maxThumb, false);

            updateSlider();

            $(window).on("resize", function () {
                updateSlider();
            });
        });

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

        });
    </script>
@endpush
