@extends('layouts.vendor.app')

@section('title', translate('Order Details').' - #'.$order['id'])

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet"
          href="{{ dynamicAsset(path: 'public/assets/back-end/plugins/intl-tel-input/css/intlTelInput.css') }}">
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/css/vendor-product.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <h2 class="fs-20 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" height="20"
                     src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/all-orders.png') }}" alt="">
                <span>{{translate('Order details')}}</span>
            </h2>
            <div class="d-flex gap-1 align-items-center">
                <a href="{{ $previousOrder ? route('vendor.retail.orders.details', [$previousOrder['id']]) : 'javascript:' }}"
                   class="btn btn-circle text-primary bg-primary-light {{ $previousOrder ? '' : 'disabled opacity-40' }}">
                    <i class="fi fi-sr-angle-left d-flex"></i>
                </a>
                <a href="{{ $nextOrder ? route('vendor.retail.orders.details', [$nextOrder['id']]) : 'javascript:' }}"
                   class="btn btn-circle text-primary bg-primary-light {{ $nextOrder ? '' : 'disabled opacity-40' }}">
                    <i class="fi fi-sr-angle-right d-flex"></i>
                </a>
            </div>
        </div>

        <div class="row g-2" id="printableArea">
            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-10 flex-md-nowrap justify-content-between mb-4">
                            <div class="d-flex flex-column gap-10 flex-1">
                                <h4 class="text-capitalize fs-16 fw-bold d-flex align-items-center gap-3 flex-wrap mb-0">
                                    <span>{{ translate('Order details') }}</span>
                                    <span class="d-flex align-items-center gap-2">
                                        <span>#{{ $order['id'] }}</span>
                                         @if($order?->edited_status == 1)
                                            <span class="badge badge-soft-info fs-12">{{ translate('Edited') }}</span>
                                        @endif
                                    </span>
                                </h4>
                                <div class="fs-12">
                                    {{date('d M, Y , h:i A', strtotime($order['created_at']))}}
                                </div>

                                <div class="d-flex flex-column gap-3">

                                    @if (!empty($order['order_note']))
                                        <div
                                            class="bg-section fs-12 p-3 text-dark rounded d-flex gap-2 align-items-center">
                                        <span>
                                            # {{ translate('Note') }}:
                                            {{ \Illuminate\Support\Str::limit($order['order_note'], 250) }}
                                            @if(strlen($order['order_note']) > 250)
                                                <a href="javascript:void(0)"
                                                   class="text-primary ms-1"
                                                   data-toggle="modal"
                                                   data-target="#orderNoteModal">
                                                    {{ translate('Read more') }}
                                                </a>

                                                <div class="modal fade" id="orderNoteModal" tabindex="-1"
                                                     aria-labelledby="orderNoteModal"
                                                     aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header pb-4">
                                                                <h3 class="mb-0">
                                                                    {{ translate('Order Note') }}
                                                                </h3>
                                                                <button type="button" class="btn-close border-0"
                                                                        data-dismiss="modal" aria-label="Close"><i
                                                                        class="tio-clear"></i></button>
                                                            </div>
                                                            <div class="modal-body px-4 px-sm-5 pt-0">
                                                                <p class="mb-0 fs-14">{{ $order['order_note'] }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </span>
                                        </div>
                                    @endif

                                    @if($order['payment_method'] == 'cash_on_delivery' && $order['bring_change_amount'] > 0)
                                        <div
                                            class="badge badge--success title-color fs-12 font-weight-normal text-wrap p-3 d-flex gap-2 align-items-center">
                                        <span>
                                            {{ translate('Please bring') }}
                                            <span class="fw-semibold">
                                                {{ $order['bring_change_amount'] }} {{ $order['bring_change_amount_currency'] ?? '' }}
                                            </span>
                                            {{ translate('In change for the customer when making the delivery') }}
                                        </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="text-sm-end flex-grow-1">
                                <div class="d-flex flex-wrap gap-10 justify-content-start justify-content-lg-end">
                                    @if (isset($order->verificationImages) && $order->verification_status ==1)
                                        <div>
                                            <button class="btn btn--primary btn-sm px-4" data-toggle="modal"
                                                    data-target="#order_verification_modal"><i
                                                    class="tio-verified"></i> {{translate('Order verification')}}
                                            </button>
                                        </div>
                                    @endif
                                    @if($isOrderEditable['status'] === true)
                                        <buton type="button"
                                               class="btn text-primary border-primary bg-transparent btn-sm"
                                               data-toggle="modal"
                                               data-target="#confirm-edit-order-modal">
                                            <i class="fi fi-sr-pencil fs-12 d-flex"></i> {{ translate('Edit Products') }}
                                        </buton>
                                    @else
                                        <buton type="button"
                                               class="btn text-primary border-primary bg-transparent btn-sm opacity--70"
                                               data-toggle="tooltip" data-placement="top"
                                               title="{{ $isOrderEditable['message'] }}" disabled>
                                            <i class="fi fi-sr-pencil fs-12 d-flex"></i> {{ translate('Edit Products') }}
                                        </buton>
                                    @endif
                                    <a class="btn btn--primary btn-sm text-nowrap" target="_blank"
                                       href="{{ route('vendor.retail.orders.generate-invoice', [$order['id']]) }}">
                                        <i class="tio-print mr-1"></i> {{ translate('Print Invoice') }}
                                    </a>
                                </div>
                                <div class="d-flex flex-column gap-2 mt-3">
                                    <div
                                        class="order-status d-flex justify-content-sm-end gap-10 text-capitalize fs-12">
                                        <span class="title-color">{{translate('Status')}}: </span>
                                        @if($order['order_status']=='pending')
                                            <span
                                                class="badge color-caribbean-green-soft font-weight-bold radius-50 d-flex align-items-center py-1 px-2">{{str_replace('_',' ', translate($order['order_status']) )}}</span>
                                        @elseif($order['order_status']=='failed')
                                            <span
                                                class="badge badge-danger font-weight-bold radius-50 d-flex align-items-center py-1 px-2">{{str_replace('_',' ', translate('Failed to deliver'))}}</span>
                                        @elseif($order['order_status']=='processing' || $order['order_status']=='out_for_delivery')
                                            <span
                                                class="badge badge-soft-warning font-weight-bold radius-50 d-flex align-items-center py-1 px-2 fs-12">{{str_replace('_',' ', $order['order_status'] == 'processing' ? translate('Packaging') : translate($order['order_status']))}}</span>

                                        @elseif($order['order_status']=='delivered' || $order['order_status']=='confirmed')
                                            <span
                                                class="badge badge-soft-success font-weight-bold radius-50 d-flex align-items-center py-1 px-2 fs-12">{{translate(str_replace('_',' ',$order['order_status']))}}</span>
                                        @else
                                            <span
                                                class="badge badge-soft-danger font-weight-bold radius-50 d-flex align-items-center py-1 px-2 fs-12">{{translate(str_replace('_',' ',$order['order_status']))}}</span>
                                        @endif
                                    </div>

                                    @if(isset($order['transaction_ref']) && $order->payment_method != 'cash_on_delivery' && $order->payment_method != 'pay_by_wallet' && !isset($order->offlinePayments))
                                        <div
                                            class="reference-code d-flex justify-content-sm-end gap-10 text-capitalize fs-12">
                                            <span class="title-color">{{translate('Reference Code')}} :</span>
                                            <strong>{{translate(str_replace('_',' ',$order['transaction_ref']))}} {{ $order->payment_method == 'offline_payment' ? '('.$order->payment_by.')':'' }}</strong>
                                        </div>
                                    @endif

                                    @if($orderVerificationStatus)
                                        <span class="d-flex justify-content-sm-end gap-10 fs-12">
                                            <b>
                                                {{translate('Order verification code')}} : {{$order['verification_code'] }}
                                            </b>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive datatable-custom">
                            <table
                                class="table fs-12 table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                                <thead class="thead-light thead-50 text-capitalize">
                                <tr>
                                    <th>{{translate('Serial')}}</th>
                                    <th>{{translate('Item details')}}</th>
                                    <th class="text-center">{{ translate('Quantity') }}</th>
                                    <th class="text-end">{{translate('Item Price')}}</th>
                                    <th class="text-end">{{translate('Discount')}}</th>
                                    <th class="text-end">{{translate('Total price')}}</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($order->details as $key=>$detail)
                                    @if($detailDisplayRows[$detail->id]['product'])
                                        <tr>
                                            <td>{{ $detailDisplayRows[$detail->id]['serial'] }}</td>
                                            <td>
                                                <div
                                                    class="{{ $detailDisplayRows[$detail->id]['is_product_unavailable'] ? 'opacity-50 cus-disabled' : '' }}"
                                                    data-toggle="tooltip" data-placement="bottom"
                                                    title="{{ $detailDisplayRows[$detail->id]['is_product_unavailable'] ? translate('Product has been deleted') : '' }}">
                                                    <div class="media align-items-center gap-10">
                                                        <img class="avatar avatar-60 rounded img-fit" alt=""
                                                             src="{{ getStorageImages(path:$detail?->productAllStatus?->thumbnail_full_url, type: 'backend-product') }}">
                                                        <div>
                                                            <a class="title-color fs-12"
                                                               href="{{ route('vendor.retail.products.view', [$detailDisplayRows[$detail->id]['product']['id']]) }}"
                                                               @if(!$detailDisplayRows[$detail->id]['is_product_unavailable'] && strlen($detailDisplayRows[$detail->id]['product']['name']) > 30)
                                                                   data-toggle="tooltip"
                                                               title="{{ $detailDisplayRows[$detail->id]['product']['name'] }}" @endif
                                                            >
                                                                {{ Str::limit($detailDisplayRows[$detail->id]['product']['name'], 30) }}
                                                            </a>
                                                            <div class="fs-10">
                                                                <strong>{{translate('Unit Price')}} :</strong>
                                                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $detail['price'])) }}
                                                            </div>
                                                            @if ($detail->variant)
                                                                <div class="fs-10"><strong>{{translate('Variation')}}
                                                                        :</strong> {{$detail['variant']}}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @if($detailDisplayRows[$detail->id]['digital_product_type'] == 'ready_after_sell')
                                                        <button type="button" class="btn btn-sm btn--primary mt-2"
                                                                title="File Upload" data-toggle="modal"
                                                                data-target="#fileUploadModal-{{ $detail->id }}"
                                                        >
                                                            <i class="tio-file-outlined"></i> {{translate('File')}}
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">{{$detail['qty']}}</td>
                                            <td class="text-end">{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $detail['price']*$detail['qty']), currencyCode: getCurrencyCode())}}</td>
                                            <td class="text-end">{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $detail['discount']), currencyCode: getCurrencyCode())}}</td>

                                            <td class="text-end">{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $detailDisplayRows[$detail->id]['total_price']), currencyCode: getCurrencyCode())}}</td>
                                        </tr>
                                    @endif
                                @endforeach
                                </tbody>
                            </table>

                            @foreach($order->details as $key=>$detail)
                                @if($detailDisplayRows[$detail->id]['digital_product_type'] == 'ready_after_sell')
                                    <div class="modal fade" id="fileUploadModal-{{ $detail->id }}" tabindex="-1"
                                         aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form
                                                    action="{{ route('vendor.retail.orders.digital-file-upload-after-sell') }}"
                                                    method="post" enctype="multipart/form-data"
                                                    class="form-advance-validation non-ajax-form-validate"
                                                    novalidate="novalidate">
                                                    @csrf
                                                    <div class="modal-header border-0 px-2 pt-2 pb-0 d-flex justify-content-end">
                                                        <button type="button" class="btn btn-circle border-0 fs-12 text-body bg-section2 shadow-none"
                                                                style="--size: 1.5rem;" data-dismiss="modal" aria-label="Close">
                                                            <i class="fi fi-sr-cross-small fs-16 d-flex"></i>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body pt-0">
                                                        <div class="mb-3">
                                                            <h2 class="mb-1 fs-18">
                                                                {{ translate('File Upload') }} <span class="text-danger">*</span>
                                                            </h2>
                                                            <p class="fs-12 mb-0">
                                                                {{ translate('Upload the product file that customers will receive.') }}
                                                            </p>
                                                        </div>
                                                        <div class="d-flex justify-content-center position-relative lg document-upload-container single_mx-100 h-100">
                                                            <div class="document-file-assets"
                                                                data-picture-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/picture.svg') }}"
                                                                data-document-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/document.svg') }}"
                                                                data-blank-thumbnail="{{ dynamicAsset(path: 'public/assets/back-end/img/file-placeholder.png') }}">
                                                            </div>

                                                            <div class="document-existing-file"
                                                                data-file-url="{{ $detailDisplayRows[$detail->id]['digital_file_url'] }}"
                                                                data-file-name="{{ $detailDisplayRows[$detail->id]['digital_file_name'] }}"
                                                                data-file-type="{{ $detailDisplayRows[$detail->id]['digital_file_type'] }}">
                                                            </div>

                                                            <div class="position-absolute end-0 top-0 p-2 z-2 after_upload_buttons d-none">
                                                                <div class="d-flex gap-3 align-items-center">
                                                                    <button type="button" class="btn btn-primary icon-btn doc_edit_btn" style="--size: 26px;">
                                                                        <i class="fi fi-sr-pencil"></i>
                                                                    </button>
                                                                    <a type="button" class="btn btn-success icon-btn doc_download_btn" style="--size: 26px;">
                                                                        <i class="fi fi-sr-download"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            <div class="document-upload-wrapper lg mw-100 doc-upload-wrapper">
                                                                <input type="file" class="document_input z-index-1"
                                                                    name="digital_file_after_sell"
                                                                    data-max-size="{{ getFileUploadMaxSize(type: 'file') }}"
                                                                    data-validation-error-msg="{{ translate('File size is too large Maximum').' '.getFileUploadMaxSize(type: 'file').' '.'MB' }}"
                                                                    accept=".jpg,.jpeg,.png,.gif,.zip,.pdf,.xlsx"/>
                                                                <div class="textbox">
                                                                    <img class="svg"
                                                                        src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/doc-upload-icon.svg') }}"
                                                                        alt="">
                                                                    <p class="mb-3">
                                                                        {{ translate('Select a file or') }}
                                                                        <span class="fw-semibold">
                                                                        {{ translate('Drag and Drop here') }}
                                                                    </span>
                                                                    </p>
                                                                    <button type="button" class="btn btn-outline-primary">
                                                                        {{ translate('Select File') }}
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <input type="hidden" value="{{ $detail->id }}"
                                                               name="order_id">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                                data-dismiss="modal">{{translate('Close')}}</button>
                                                        <button type="submit"
                                                                class="btn btn--primary">{{translate('Upload')}}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <hr/>
                        <div class="row justify-content-end">
                            <div class="col-12 col-sm-auto">
                                <div class="overflow-x-auto min-w-300 min-w-100-mobile">
                                    <table class="table table-borderless table-sm mb-0 text-sm-right text-nowrap fs-12">
                                        <tbody>
                                        <tr>
                                            <td class="text-start text-dark text-capitalize">
                                                <span>{{ translate('Item Price') }}</span></td>
                                            <td class="text-end text-dark">
                                                <span>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['itemPrice']), currencyCode: getCurrencyCode()) }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-start text-dark text-capitalize">
                                                <span>{{ translate('Item Discount') }}</span></td>
                                            <td class="text-end text-dark">
                                                -
                                                <span>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['itemDiscount']), currencyCode: getCurrencyCode()) }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-start text-dark text-capitalize">
                                                <span>{{ translate('Subtotal') }}</span></td>
                                            <td class="text-end text-dark">
                                                <span>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['subTotal']), currencyCode: getCurrencyCode()) }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-start text-dark">
                                                <span>{{ translate('Coupon Discount') }}</span>
                                                <br>
                                                {{(!in_array($order['coupon_code'], [0, NULL]) ? '('.translate('Expense bearer').($order['coupon_discount_bearer']=='inhouse' ? 'admin' : ($order['coupon_discount_bearer'] == 'seller' ? 'vendor' : $order['coupon_discount_bearer'])).')': '' )}}
                                            </td>
                                            <td class="text-end text-dark">
                                                    <span>-
                                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['couponDiscount']), currencyCode: getCurrencyCode()) }}</span>
                                            </td>
                                        </tr>
                                        @if($orderTotalPriceSummary['referAndEarnDiscount'] > 0)
                                            <tr>
                                                <td class="text-start text-dark">
                                                    <span>{{ translate('Referral Discount') }}</span></td>
                                                <td class="text-end text-dark">
                                                    <span>-
                                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['referAndEarnDiscount']), currencyCode: getCurrencyCode()) }}</span>
                                                </td>
                                            </tr>
                                        @endif

                                        <tr>
                                            <td class="text-start text-dark text-uppercase"><span>{{ translate('VAT') }}/{{ translate('Tax') }}</span>
                                            </td>
                                            <td class="text-end text-dark">
                                                <span>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['totalTaxAmount']), currencyCode: getCurrencyCode()) }}</span>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-start text-dark text-capitalize">
                                                <span>{{ translate('Shipping Fee') }}</span>
                                                @if($order['is_shipping_free'])
                                                    <br>
                                                    ({{ translate('Expense bearer').($order['free_delivery_bearer'] == 'seller' ? 'vendor' : $order['free_delivery_bearer']) }}
                                                    )
                                                @endif
                                            </td>
                                            <td class="text-end text-dark">
                                                    <span>
                                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['shippingTotal'])) }}
                                                    </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-start text-dark text-capitalize">
                                                <strong>{{ translate('Total') }}</strong>
                                                <span
                                                    class="fs-10 fw-medium">{{ $orderTotalPriceSummary['tax_model'] == 'include' ? '('.translate('Tax : Inc.').')' : '' }}</span>
                                            </td>
                                            <td class="text-end text-dark">
                                                <strong>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['totalAmount']), currencyCode: getCurrencyCode()) }}</strong>
                                            </td>
                                        </tr>
                                        @if ($order->order_type == 'pos' || $order->order_type == 'POS')
                                            <tr>
                                                <td class="text-start text-dark text-capitalize">
                                                    <span>{{ translate('Paid Amount') }}</span></td>
                                                <td class="text-end text-dark">
                                                    <span>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['paidAmount']), currencyCode: getCurrencyCode()) }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-start text-dark text-capitalize">
                                                    <span>{{ translate('Change Amount') }}</span></td>
                                                <td class="text-end text-dark">
                                                    <span>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['changeAmount']), currencyCode: getCurrencyCode()) }}</span>
                                                </td>
                                            </tr>
                                        @endif

                                        @if(($orderTotalPriceSummary['edited_total_paid_amount'] ?? 0) > 0)
                                            <tr>
                                                <td class="text-start text-dark text-capitalize">
                                                    {{ translate('Paid Amount') }}
                                                </td>
                                                <td class="text-end text-dark">
                                                    {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['edited_total_paid_amount']), currencyCode: getCurrencyCode()) }}
                                                </td>
                                            </tr>
                                        @endif

                                        @if($order['edited_status'] == 1 && $order?->latestEditHistory)

                                            @if($order?->latestEditHistory?->order_due_amount > 0)
                                                @if($order?->latestEditHistory?->order_due_payment_status == 'paid')
                                                    <tr>
                                                        <td class="text-start text-dark text-capitalize">
                                                            {{ translate('Paid Amount') }}
                                                        </td>
                                                        <td class="text-end text-dark">
                                                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $editSettlement['paid_before_due']), currencyCode: getCurrencyCode()) }}
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <td class="text-start text-dark text-capitalize d-flex flex-column">
                                                            <span>{{ translate('Due Amount Paid By') }}</span>
                                                            <span>({{ ucwords(str_replace('_', ' ', $order?->latestEditHistory?->order_due_payment_method)) }})</span>
                                                        </td>
                                                        <td class="text-end text-dark">
                                                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->latestEditHistory?->order_due_amount), currencyCode: getCurrencyCode()) }}
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <td class="text-start text-dark text-capitalize d-flex flex-column">
                                                            <strong>{{ translate('Total Paid Amount') }}</strong>
                                                        </td>
                                                        <td class="text-end text-dark">
                                                            <strong>
                                                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->latestEditHistory?->order_amount), currencyCode: getCurrencyCode()) }}
                                                            </strong>
                                                        </td>
                                                    </tr>
                                                @else
                                                    <tr>
                                                        <td class="text-start text-danger text-capitalize">
                                                            <strong>{{ translate('Due Amount') }}</strong>
                                                        </td>
                                                        <td class="text-end text-danger">
                                                            <strong>
                                                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->latestEditHistory?->order_due_amount), currencyCode: getCurrencyCode()) }}
                                                            </strong>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endif

                                            @if($order?->latestEditHistory?->order_return_amount > 0)
                                                @if($order?->latestEditHistory?->order_return_payment_status == "returned")
                                                    <tr>
                                                        <td class="text-start text-dark text-capitalize">
                                                            {{ translate('Paid Amount') }}
                                                        </td>
                                                        <td class="text-end text-dark">
                                                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $editSettlement['paid_before_return']), currencyCode: getCurrencyCode()) }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start text-dark text-capitalize">
                                                            <strong>{{ translate('Returned by') }}</strong>
                                                            <span>({{ ucwords(str_replace('_', ' ', $order?->latestEditHistory?->order_return_payment_method)) }})</span>
                                                        </td>
                                                        <td class="text-end text-dark">
                                                            <strong>
                                                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->latestEditHistory?->order_return_amount), currencyCode: getCurrencyCode()) }}
                                                            </strong>
                                                        </td>
                                                    </tr>
                                                @else
                                                    <tr>
                                                        <td class="text-start text-danger text-capitalize">
                                                            <strong>{{ translate('Amount To Return') }}</strong>
                                                        </td>
                                                        <td class="text-end text-danger">
                                                            <strong>
                                                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->latestEditHistory?->order_return_amount), currencyCode: getCurrencyCode()) }}
                                                            </strong>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endif
                                        @endif

                                        </tbody>
                                    </table>
                                    @if($order->edited_status == 1)
                                        <div class="d-flex justify-content-end mt-2">
                                            <div
                                                class="bg-warning bg-opacity-10 fs-12 px-12 py-2 text-dark rounded d-flex gap-2 align-items-center w-100">
                                                <i class="fi fi-sr-info text-warning"></i>
                                                <span>
                                                {{ translate('Total bill has been updated after the edits.') }}
                                            </span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if($order?->orderEditHistory?->isNotEmpty())
                            <hr/>
                            <div>
                                <h4 class="fs-14 fw-bold mb-3">{{ translate('Edit Log') }}</h4>
                                <div class="d-flex flex-column gap-20">
                                    @foreach($order->orderEditHistory as $history)
                                        <div>
                                            <div class="fs-12 mb-2">
                                                {{ translate('Order Edited by') }}
                                                {{ $history['edited_user_name'] }}
                                                ({{ translate($history['edit_by']) }})
                                            </div>
                                            <div class="fs-12 mb-0 text-dark">
                                                {{ $history?->created_at->format('d M, Y h:i') }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-4 d-flex flex-column gap-3">
                <div class="card card-body">
                    <h4 class="fw-bold d-flex align-items-center gap-2 mb-3">
                        {{ translate('Payment') }}
                        @if($order['payment_status']=='paid')
                            <span
                                class="badge text-success bg-success bg-opacity-10 fw-semibold rounded d-flex align-items-center py-1 px-2">
                                {{ translate('Paid') }}
                            </span>
                        @else
                            <span
                                class="badge text-danger bg-danger bg-opacity-10 fw-semibold rounded d-flex align-items-center py-1 px-2">
                                {{ translate('Unpaid') }}
                            </span>
                        @endif
                    </h4>
                    <div class="d-grid gap-2 fs-12">
                        <div class="d-flex gap-3 align-items-center overflow-wrap-anywhere">
                                <span class="w-120 flex-shrink-0 text-capitalize">
                                    {{ translate('Payment Method') }}
                                </span>
                            <span>:</span>
                            <span class="fw-semibold">{{ translate($order['payment_method']) }}</span>
                        </div>

                        @if($order->payment_method != 'cash_on_delivery' && $order->payment_method != 'pay_by_wallet' && !isset($order->offlinePayments))
                            <div class="d-flex gap-3 align-items-center overflow-wrap-anywhere">
                                <span class="w-120 flex-shrink-0 text-capitalize">
                                    {{ translate('Reference Code') }}
                                </span>
                                <span>:</span>
                                <span class="fw-semibold">
                                    {{ str_replace('_',' ',$order['transaction_ref']) }} {{ $order->payment_method == 'offline_payment' ? '('.$order->payment_by.')':'' }}
                                </span>
                            </div>
                        @endif

                        <div class="d-flex gap-3 align-items-center overflow-wrap-anywhere">
                            <span class="w-120 flex-shrink-0 text-capitalize">{{ translate('Order Amount') }}</span>
                            <span>:</span>
                            <span class="fw-semibold">
                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order['init_order_amount']), currencyCode: getCurrencyCode()) }}
                            </span>
                        </div>
                    </div>

                    @if($order->payment_method == 'offline_payment' && isset($order->offlinePayments))
                        <div class="bg-section p-2 mt-3 rounded">
                            <h5 class="fs-12 mb-3">{{ translate('Customer Payment information') }}</h5>
                            <div class="d-grid gap-2 fs-12">
                                @foreach ($order->offlinePayments->payment_info as $key=>$item)
                                    @if (isset($item) && $key != 'method_id')
                                        <div class="d-flex gap-3 align-items-center overflow-wrap-anywhere">
                                            <span class="w-120 flex-shrink-0 text-capitalize text-capitalize">
                                                {{ translate($key) }}
                                            </span>
                                            <span>:</span>
                                            <span class="fw-semibold">{{ $item ?? 'N/a' }}</span>
                                        </div>
                                    @endif
                                @endforeach
                                @if(isset($order?->payment_note) && $order?->payment_method == 'offline_payment')
                                    <div class="d-flex gap-3 align-items-center overflow-wrap-anywhere">
                                        <span class="w-120 flex-shrink-0 text-capitalize text-capitalize">
                                            {{ translate('Payment Note') }}
                                        </span>
                                        <span>:</span>
                                        <span class="fw-semibold">{{ $order?->payment_note ?? 'N/a' }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if ($order['payment_method'] == 'offline_payment' && $order['payment_status'] == 'unpaid')
                            <div class="row g-2 mt-2">
                                <div class="col-12">
                                    <div class="d-flex align-items-center gap-2 alert alert-soft-warning" role="alert">
                                        <i class="fi fi-sr-info"></i>
                                        <p class="fs-12 mb-0 text-dark">
                                            {{ translate('This payment has not been verified yet.') }}
                                            {{ translate('Please wait for admin verification to complete before taking further actions.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                    @if(count($editSettlement['settled_histories']) > 0 || $editSettlement['has_unpaid_due'])
                        <div class="payment-logs-info-section fs-12 bg-section p-2 mt-3 rounded">
                            <div class="d-flex gap-2 align-items-center">
                                <span class="fw-semibold">{{ translate('Another Amount Information') }}</span>
                            </div>

                            @foreach($editSettlement['settled_histories'] as $orderEditHistoryItem)
                                @if($orderEditHistoryItem['order_due_payment_status'] == 'paid')
                                    <div class="d-grid gap-2 my-1">
                                        <div class="d-flex gap-3 align-items-center overflow-wrap-anywhere">
                                        <span class="w-120 flex-shrink-0 text-capitalize">
                                            {{ translate('Pay By') }}
                                        </span>
                                            <span>:</span>
                                            <span class="fw-semibold d-flex flex-wrap gap-2">
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderEditHistoryItem['order_due_amount']), currencyCode: getCurrencyCode()) }}
                                                @if(!empty($orderEditHistoryItem['order_due_payment_method']))
                                                    ({{ translate($orderEditHistoryItem['order_due_payment_method']) }})
                                                @endif
                                        <span
                                            class="badge text-success bg-success bg-opacity-10 fw-semibold rounded d-flex align-items-center py-1 px-2 w-max-content">
                                            {{ translate('Paid') }}
                                        </span>
                                    </span>
                                        </div>
                                    </div>
                                @else
                                    <div class="d-grid gap-2 my-1">
                                        <div class="d-flex gap-3 align-items-center overflow-wrap-anywhere">
                                        <span class="w-120 flex-shrink-0 text-capitalize">
                                            {{ translate('Return Amount') }}
                                        </span>
                                            <span>:</span>
                                            <span class="fw-semibold d-flex flex-wrap gap-2">
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderEditHistoryItem['order_return_amount']), currencyCode: getCurrencyCode()) }}
                                                @if(!empty($orderEditHistoryItem['order_return_payment_method']))
                                                    ({{ translate($orderEditHistoryItem['order_return_payment_method']) }}
                                                    )
                                                @endif
                                        <span
                                            class="badge text-success bg-success bg-opacity-10 fw-semibold rounded d-flex align-items-center py-1 px-2 w-max-content">
                                                    {{ translate('Returned') }}
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            @if ($order['edit_return_amount'] > 0 && $order?->latestEditHistory && $order?->latestEditHistory?->order_return_payment_status === 'pending')
                                <div class="d-grid gap-2 my-2">
                                    <div class="d-flex gap-3 align-items-center overflow-wrap-anywhere">
                                    <span class="w-120 flex-shrink-0 text-capitalize">
                                        {{ translate('Return Amount') }}
                                    </span>
                                        <span>:</span>
                                        <span class="fw-semibold d-flex flex-wrap gap-2">
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->latestEditHistory?->order_return_amount), currencyCode: getCurrencyCode()) }}
                                            @if(!empty($order?->latestEditHistory?->order_return_payment_method))
                                                <span class="fs-12">({{ translate($order?->latestEditHistory?->order_return_payment_method) }})</span>
                                            @endif
                                        <span
                                            class="badge text-danger bg-danger bg-opacity-10 fw-semibold rounded d-flex align-items-center py-1 px-2 w-max-content">
                                            {{ translate($order?->latestEditHistory?->order_return_payment_status) }}
                                        </span>
                                    </span>
                                    </div>
                                </div>
                            @endif

                            @if ($editSettlement['has_unpaid_due'])
                                <div class="d-grid gap-2 my-2">
                                    <div class="d-flex gap-3 align-items-center overflow-wrap-anywhere">
                                    <span class="w-120 flex-shrink-0 text-capitalize">
                                        {{ translate('Due Amount') }}
                                    </span>
                                        <span>:</span>
                                        <span class="fw-semibold d-flex flex-wrap gap-2">
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->latestEditHistory?->order_due_amount), currencyCode: getCurrencyCode()) }}
                                            @if(!empty($order?->latestEditHistory?->order_due_payment_method))
                                                <span class="fs-12">({{ translate($order?->latestEditHistory?->order_due_payment_method) }})</span>
                                            @endif
                                        <span
                                            class="badge text-danger bg-danger bg-opacity-10 fw-semibold rounded d-flex align-items-center py-1 px-2 w-max-content">
                                            {{ translate('Unpaid') }}
                                        </span>
                                    </span>
                                    </div>
                                </div>

                                @if($order?->latestEditHistory?->order_due_payment_method == 'offline_payment' && !empty($order?->latestEditHistory?->order_due_payment_info))
                                    <div class="bg-section p-2 mt-2 rounded">
                                        <h5 class="fs-12 mb-3">{{ translate('Receiving Account Details') }}</h5>
                                        <div class="d-grid gap-2 fs-12">
                                            @foreach($order?->latestEditHistory?->order_due_payment_info as $orderDuePaymentInfoKey => $orderDuePaymentInfo)
                                                @if(!in_array($orderDuePaymentInfoKey, ['method_id', 'id']))
                                                    <div class="d-flex gap-3 align-items-center overflow-wrap-anywhere">
                                                        <span
                                                            class="w-120 flex-shrink-0 text-capitalize text-capitalize">
                                                            {{ translate($orderDuePaymentInfoKey) }}
                                                        </span>
                                                        <span>:</span>
                                                        <span
                                                            class="fw-semibold">{{ $orderDuePaymentInfo ?? 'N/a' }}</span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endif

                            @if ($order['payment_method'] == 'offline_payment' && $order?->latestEditHistory?->order_due_payment_method == 'offline_payment' && $order?->latestEditHistory?->order_due_payment_status == 'unpaid')
                                <div class="row g-2 mt-2">
                                    <div class="col-12">
                                        <div class="d-flex align-items-center gap-2 alert alert-soft-warning"
                                             role="alert">
                                            <i class="fi fi-sr-info"></i>
                                            <p class="fs-12 mb-0 text-dark">
                                                {{ translate('This payment has not been verified yet.') }}
                                                {{ translate('Please wait for admin verification to complete before taking further actions.') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if((count($editSettlement['settled_histories']) + ($editSettlement['has_unpaid_due'] ? 1 : 0)) >= 1)
                            <button type="button"
                                    class="btn text-primary fw-medium bg-transparent border-0 shadow-none p-0 w-max-content mx-auto mt-3"
                                    id="paymentInfoCollapse">
                                {{ translate('See More') }}
                            </button>
                        @endif
                    @endif
                </div>
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0 text-center fw-bold">{{translate('Order & Shipping Information')}}</h4>
                    </div>
                    <div class="card-body d-flex flex-column gap-4">
                        @if ($editSettlement['edit_due_amount'] > 0 && !in_array($order->payment_method, ['cash_on_delivery']) && ($order?->latestEditHistory?->order_due_payment_method != "cash_on_delivery" && $order?->latestEditHistory?->order_due_payment_method == ''))
                            <div class="bg-section rounded-10 p-12 p-sm-20 text-center">
                                <h5 class="text-danger mb-3">{{ translate('Due Amount') }}</h5>
                                <h4 class="fw-bold">{{ webCurrencyConverter(amount: $order['edit_due_amount']) }}</h4>
                                <p class="fs-12 mb-0">
                                    {{ translate('After editing, the order amount has increased.') }}
                                    {{ translate('Switch the payment method to cash on delivery to collect it.') }}
                                    (COD)
                                    {{ translate('Or contact the customer to pay the bill from the order details page') }}
                                </p>
                                <button type="button" class="btn btn--primary mt-3" data-toggle="modal"
                                        data-target="#switchToCODModal">{{ translate('Switch to COD') }}</button>
                            </div>
                        @elseif(($editSettlement['edit_return_amount'] ?? 0) > 0)
                            <div class="bg-section rounded-10 p-12 p-sm-20 text-center">
                                <h5 class="text-danger mb-3">{{ translate('Need to Return') }}</h5>
                                <h4 class="fw-bold">{{ webCurrencyConverter(amount: $editSettlement['edit_return_amount']) }}</h4>
                                <p class="fs-12 mb-0">
                                    {{ translate('The order amount was reduced after editing.') }}
                                    {{ translate('Please contact the admin to return the amount to the customer.') }}
                                </p>
                            </div>
                        @endif

                        <div>
                            <label
                                class="font-weight-bold title-color fz-14 mb-2">{{translate('Change order status')}}</label>
                            <select name="order_status" id="order_status" class="status form-control"
                                    data-id="{{$order['id']}}">
                                <option
                                    value="pending" {{$order->order_status == 'pending'?'selected':''}} > {{translate('Pending')}}</option>
                                <option
                                    value="confirmed" {{$order->order_status == 'confirmed'?'selected':''}} > {{translate('Confirmed')}}</option>
                                <option
                                    value="processing" {{$order->order_status == 'processing'?'selected':''}} >{{translate('Packaging')}} </option>

                                @if($order?->shipping_responsibility == 'sellerwise_shipping')
                                    <option
                                        value="out_for_delivery" {{$order->order_status == 'out_for_delivery'?'selected':''}} >{{translate('Out For Delivery')}} </option>
                                    <option
                                        value="delivered" {{$order->order_status == 'delivered'? 'selected':''}} >{{translate('Delivered')}} </option>
                                    <option
                                        value="returned" {{$order->order_status == 'returned'?'selected':''}} > {{translate('Returned')}}</option>
                                    <option
                                        value="failed" {{$order->order_status == 'failed'?'selected':''}} >{{translate('Failed to deliver')}} </option>
                                    <option
                                        value="canceled" {{$order->order_status == 'canceled'?'selected':''}} >{{translate('Canceled')}} </option>
                                @endif
                            </select>
                        </div>
                        <div
                            class="d-flex justify-content-between align-items-center gap-10 form-control flex-wrap h-100">
                            <span class="title-color">
                                {{translate('Payment Status')}}
                            </span>
                            <div class="d-flex justify-content-end min-w-100 align-items-center gap-2">
                                <span
                                    class="text--primary font-weight-bold">{{ $order->payment_status=='paid' ? translate('Paid'):translate('Unpaid')}}</span>
                                <label
                                    class="switcher payment-status-text {{$order['payment_status'] == 'paid' ? 'payment-status-alert' : ''}}">
                                    <input class="switcher_input payment-status" type="checkbox" name="status"
                                           data-id="{{$order->id}}"
                                           value="{{$order->payment_status}}"
                                        {{ $order->payment_status=='paid' ? 'disabled' : ''}}
                                        {{ $order->payment_status=='paid' ? 'checked':''}} >
                                    <span class="switcher_control switcher_control_add
                                        {{ $order->payment_status=='paid' ? 'checked':'unchecked'}}"></span>
                                </label>
                            </div>
                        </div>
                        @if($physicalProduct || $shippingAddress)
                            <ul class="list-unstyled d-flex flex-column mb-0 pr-0">
                                @if ($order->shipping_type == 'order_wise')
                                    <li>
                                        <label class="font-weight-bold title-color fz-14 mb-2">
                                            {{translate('Shipping Method')}}
                                        </label>
                                    </li>
                                @endif

                                @if($order?->shipping_responsibility == 'inhouse_shipping')
                                <div class="d-flex gap-2 alert alert-soft-warning" role="alert">
                                    <i class="fi fi-sr-info"></i>
                                    <p class="fs-12 mb-0 text-dark">
                                        {{ translate('This inhouse order must use the shipping option chosen at checkout.') }}
                                    </p>
                                </div>
                                @endif

                                @if ($order?->shipping_responsibility == 'sellerwise_shipping')
                                    <li>
                                        <select class="form-control text-capitalize" name="delivery_type"
                                                id="choose_delivery_type"
                                                {{ $order->order_status == 'delivered' || $disableDeliveryType ? 'disabled' : '' }}
                                                @if($disableDeliveryType)
                                                    data-toggle="tooltip"
                                                data-placement="top"
                                                title="{{ translate('You cannot select delivery type as the order only contains digital products') }}"
                                            @endif
                                        >
                                            <option value="0">
                                                {{translate('Choose delivery type')}}
                                            </option>

                                            <option
                                                value="self_delivery" {{$order->delivery_type=='self_delivery'?'selected':''}}>
                                                {{translate('By self delivery man')}}
                                            </option>
                                            @if ($deliveryPartner['is_enabled'] || $deliveryPartner['is_assigned'])
                                                <option value="third_party_delivery" {{ $deliveryPartner['is_assigned'] ? 'selected' : '' }}>
                                                    {{ translate('Current delivery partner') }}
                                                </option>
                                            @endif
                                        </select>
                                    </li>
                                    @if(!$disableDeliveryType)
                                        <li id="choose_delivery_man" class="mt-3 choose_delivery_man">
                                            <label for="" class="font-weight-bold title-color fz-14">
                                                {{translate('Delivery Man')}}
                                            </label>
                                            <select class="form-control text-capitalize js-select2-custom"
                                                    name="delivery_man_id" id="addDeliveryMan"
                                                    data-placeholder="{{ translate('Select Deliveryman') }}"
                                                    data-order-id="{{$order['id']}}" {{ $order->order_status == 'delivered'? 'disabled' : '' }}>
                                                <option value="" readonly>--{{ translate('Select Deliveryman') }}--
                                                </option>
                                                @foreach($deliveryMen as $deliveryMan)
                                                    <option
                                                        value="{{$deliveryMan['id']}}" {{$order['delivery_man_id']==$deliveryMan['id']?'selected':''}}>
                                                        {{$deliveryMan['f_name'].' '.$deliveryMan['l_name'].' ('.$deliveryMan['phone'].' )'}}
                                                    </option>
                                                @endforeach
                                            </select>

                                            @if (isset($order->deliveryMan))
                                                <div class="p-2 bg-section rounded mt-4">
                                                    <div class="media m-1 gap-3">
                                                        <img class="avatar rounded-circle"
                                                             src="{{getStorageImages(path:$order?->deliveryMan->image_full_url,type: 'backend-profile')}}"
                                                             alt="Image">
                                                        <div class="media-body">
                                                            <h5 class="mb-1">{{  $order->deliveryMan?->f_name.' '.$order->deliveryMan?->l_name}}</h5>
                                                            <a href="tel:{{ $order->deliveryMan?->phone}}"
                                                               class="fs-12 title-color">{{$order->deliveryMan?->phone }}</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="p-3 bg-section rounded mt-4">
                                                    <div class="media m-1 gap-2 align-items-center">
                                                        <img class="avatar rounded-circle"
                                                             src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/delivery-man.png')}}"
                                                             alt="{{translate('Image')}}">
                                                        <div class="media-body">
                                                            <div
                                                                class="fs-12">{{translate('No delivery man assigned')}}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </li>
                                        @if (isset($order->deliveryMan))
                                            <li class="choose_delivery_man mt-3">
                                                <label class="font-weight-bold title-color d-flex fz-14">
                                                    {{translate('Delivery man incentive')}} ({{ getCurrencySymbol() }})
                                                    <span class="input-label-secondary cursor-pointer"
                                                          data-toggle="tooltip"
                                                          data-placement="right"
                                                          title="{{translate('Encourage your delivery man by giving an incentive').' '.translate('This amount will be counted as a vendor expense').'.'}}">
                                                    <img width="16"
                                                         src="{{dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg')}}"
                                                         alt="">
                                                </span>
                                                </label>
                                                <div class="d-flex gap-2 align-items-center">
                                                    <input type="number"
                                                           value="{{ usdToDefaultCurrency(amount: $order->deliveryman_charge) }}"
                                                           name="deliveryman_charge" data-order-id="{{$order['id']}}"
                                                           class="form-control" placeholder="{{translate('Ex').': 20'}}"
                                                           {{$order['order_status']=='delivered' ? 'readonly':''}} required>
                                                    <button
                                                        class="btn btn--primary {{$order['order_status']=='delivered' ? 'disabled deliveryman-charge-alert':'deliveryman-charge'}}">{{translate('Update')}}</button>
                                                </div>
                                            </li>
                                            <li class="choose_delivery_man mt-3">
                                                <label class="font-weight-bold title-color fz-14">
                                                    {{translate('Expected delivery date')}}
                                                </label>
                                                <input type="date"
                                                       value="{{ $order->expected_delivery_date }}"
                                                       data-order-id="{{$order['id']}}"
                                                       name="expected_delivery_date"
                                                       id="expected_delivery_date"
                                                       class="form-control set-today-date-minimum deliveryDateUpdate" {{ $order->order_status == 'delivered'? 'disabled' : 'required' }} >
                                            </li>
                                        @endif
                                    @endif
                                @endif

                                @if (!$disableDeliveryType && ($deliveryPartner['is_enabled'] || $deliveryPartner['is_assigned']))
                                    <li class="mt-1" id="by_third_party_delivery_service_info">
                                        @if ($deliveryPartner['is_service_available'])
                                            @include('courier::vendor.delivery-partner-selection', ['courierOrder' => [
                                                'id' => $order['id'],
                                                'name' => $shippingAddress?->contact_person_name,
                                                'phone' => $shippingAddress?->phone,
                                                'address' => $shippingAddress?->address,
                                                'cod' => $deliveryPartner['cod'],
                                                'cod_locked' => $deliveryPartner['is_paid'],
                                                'amount' => $order['order_amount'] ?? 0,
                                                'has_delivery_man' => (bool) $order->delivery_man_id,
                                                'can_dispatch' => $deliveryPartner['is_enabled'],
                                                'manual_assignment' => $deliveryPartner['is_assigned'] ? [
                                                    'partner' => $order->delivery_service_name,
                                                    'tracking_id' => $order->third_party_delivery_tracking_id,
                                                ] : null,
                                            ]])
                                        @else
                                            <div class="p-2 bg-light rounded mt-3">
                                                <div class="media m-1 gap-3">
                                                    <img class="avatar rounded-circle"
                                                         src="{{dynamicAsset(path: 'public/assets/back-end/img/third-party-delivery.png')}}"
                                                         alt="{{translate('Image')}}">
                                                    <div class="media-body">
                                                        <h5 class="">{{$order->delivery_service_name ?? translate('Not assign yet')}}</h5>
                                                        <span
                                                            class="fs-12 title-color">{{translate('Track ID')}} :  {{$order->third_party_delivery_tracking_id}}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </li>
                                @endif
                            </ul>
                        @endif
                    </div>
                </div>

                @if ($deliveryPartner['is_enabled'])
                    @include('courier::vendor.send-to-courier', ['courierOrder' => [
                        'id' => $order['id'],
                        'name' => $shippingAddress?->contact_person_name,
                        'phone' => $shippingAddress?->phone,
                        'address' => $shippingAddress?->address,
                        'latitude' => $deliveryPartner['has_coordinates'] ? $shippingAddress->latitude : null,
                        'longitude' => $deliveryPartner['has_coordinates'] ? $shippingAddress->longitude : null,
                        'cod' => $deliveryPartner['cod'],
                        'cod_locked' => $deliveryPartner['is_paid'],
                        'amount' => $order['order_amount'] ?? 0,
                    ]])
                @endif

                <div class="card">
                    @if(!empty((array) $shippingAddress))
                        <div class="card-body">
                            <div class="d-flex gap-2 align-items-center justify-content-between mb-3">
                                <h4 class="d-flex gap-2 fs-14 fw-bold mb-0">
                                    <img
                                        src="{{dynamicAsset(path: 'public/assets/back-end/img/vendor-information.png')}}"
                                        alt="">
                                    {{translate('Shipping address')}}
                                </h4>
                                @if($order['order_status'] != 'delivered')
                                    <button class="btn btn-outline-primary btn-sm square-btn"
                                            title="{{translate('Edit')}}"
                                            data-toggle="modal"
                                            data-target="#shippingAddressUpdateModal">
                                        <i class="tio-edit" style="font-size: 15px;"></i>
                                    </button>
                                @endif
                            </div>
                            <table class="overflow-wrap-anywhere">
                                <tbody>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Name')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2">
                                        <strong>{{$shippingAddress->contact_person_name}}</strong> {{ $order->is_guest ? '('. translate('Guest customer') .')':''}}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Contact')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2">
                                        <strong>{{$shippingAddress->phone}}</strong>
                                    </td>
                                </tr>
                                @if ($order->is_guest && $shippingAddress->email)
                                    <tr>
                                        <td class="px-0 py-2 text-nowrap">{{translate('Email')}}</td>
                                        <td class="px-3 py-2">:</td>
                                        <td class="px-0 py-2">
                                            <strong>{{$shippingAddress->email}}</strong>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Country')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2">
                                        <strong>{{$shippingAddress->country}}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('City')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2"><strong>{{$shippingAddress->city}}</strong></td>
                                </tr>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Zip Code')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2">
                                        <strong>{{$shippingAddress->zip}}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{ translate('Address') }}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2">
                                        <div class="d-flex align-items-start gap-2">
                                            <span>{{$shippingAddress->address}}</span>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    @elseif($physicalProduct)
                        <div class="card-body">
                            <div class="media align-items-center">
                                <span>{{translate('No shipping address found')}}</span>
                            </div>
                        </div>
                    @endif
                </div>
                @if ($mapApiStatus == 1 && isset($shippingAddress->latitude) && isset($shippingAddress->longitude))
                    <div class="card card-body">
                        <div class="d-flex justify-content-between gap-3 align-items-center flex-wrap">
                            <h4 class="mb-0">{{ translate('Shipping Location') }}</h4>
                            <button type="button" class="btn btn-outline-primary px-3 py-1" data-toggle="modal"
                                    data-target="#locationModal">{{ translate('On Map') }}</button>
                        </div>
                    </div>
                @endif
                @if(!empty((array) $billing))
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex gap-2 align-items-center justify-content-between mb-3">
                                <h4 class="d-flex gap-2 fs-14 fw-bold mb-0">
                                    <img
                                        src="{{dynamicAsset(path: 'public/assets/back-end/img/vendor-information.png')}}"
                                        alt="">
                                    {{translate('Billing address')}}
                                </h4>
                                @if($order['order_status'] !== 'delivered')
                                    <button class="btn btn-outline-primary btn-sm square-btn"
                                            title="Edit"
                                            data-toggle="modal"
                                            data-target="#billingAddressUpdateModal">
                                        <i class="tio-edit" style="font-size: 15px;"></i>
                                    </button>
                                @endif
                            </div>

                            @if($isBillingSameAsShipping)
                                <p class="mb-0 fw-semibold text-body">{{ translate('Same as shipping address') }}</p>
                            @else
                            <table class="overflow-wrap-anywhere">
                                <tbody>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Name')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2">
                                        <strong>{{$billing->contact_person_name}}</strong> {{ $order->is_guest ? '('. translate('Guest customer') .')':''}}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Contact')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2">
                                        <strong>{{$billing->phone}}</strong>
                                    </td>
                                </tr>
                                @if ($order->is_guest && $billing->email)
                                    <tr>
                                        <td class="px-0 py-2 text-nowrap">{{translate('Email')}}</td>
                                        <td class="px-3 py-2">:</td>
                                        <td class="px-0 py-2">
                                            <strong>{{$billing->email}}</strong>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Country')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2">
                                        <strong>{{$billing->country}}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('City')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2">
                                        <strong>{{$billing->city}}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Zip Code')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2">
                                        <strong>{{$billing->zip}}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{ translate('Address') }}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2">
                                        <div class="d-flex align-items-start gap-2">
                                            <span>{{$billing->address}}</span>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            @endif
                        </div>
                    </div>
                @endif
                @if(!$order->is_guest && $order->customer)
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex gap-2 align-items-center justify-content-between mb-3">
                                <h4 class="d-flex gap-2 fs-14 fw-bold mb-0">
                                    <img
                                        src="{{dynamicAsset(path: 'public/assets/back-end/img/vendor-information.png')}}"
                                        alt="">
                                    {{translate('Customer Information')}}
                                </h4>
                            </div>
                            <div class="media flex-wrap gap-3 gap-sm-4">
                                <div class="">
                                    <img class="avatar rounded-circle avatar-70 object-fit-cover"
                                         src="{{ getStorageImages(path: $order->customer->image_full_url , type: 'backend-basic') }}"
                                         alt="{{translate('Image')}}">
                                </div>
                                <div class="media-body d-flex flex-column gap-1">
                                    <span class="text-dark">
                                        <span
                                            class="fw-semibold">{{$order->customer['f_name'].' '.$order->customer['l_name']}} </span>
                                    </span>

                                    @if($order?->customer?->email !== 'walking@customer.com')
                                        <span class="text-dark fs-12"> <span class="fw-bold">{{ $orderCount }}</span> {{translate('Orders')}}</span>
                                        <span
                                            class="text-dark break-all fs-12"><span
                                                class="fw-semibold">{{$order->customer['phone']}}</span></span>
                                        <span class="text-dark break-all fs-12">{{$order->customer['email']}}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="card">
                    <div class="card-body">
                        <h4 class="d-flex gap-2 fs-14 fw-bold mb-3">
                            <img src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/shop-information.png') }}"
                                 alt="">
                            {{ translate('Shop Information') }}
                        </h4>
                        <div class="media d-flex gap-3 align-items-center">
                            @if($order->seller_is == 'admin')
                                <div class="">
                                    <img class="avatar rounded avatar-70 img-fit-contain "
                                         src="{{ getStorageImages(path: $inHouseShopImage, type: 'shop') }}"
                                         alt="">
                                </div>
                                <div class="media-body d-flex flex-column gap-2">
                                    <h5 class="fs-14 mb-0">{{ $inHouseShopName }}</h5>
                                    <span class="text-dark fs-12"><strong>{{ $totalDelivered }}</strong> {{translate('Orders Served')}}</span>
                                </div>
                            @else
                                @if(!empty($order->seller->shop))
                                    <div class="">
                                        <img class="avatar rounded avatar-70 img-fit"
                                             src="{{ getStorageImages(path:$order->seller->shop->image_full_url , type: 'backend-basic') }}"
                                             alt="">
                                    </div>
                                    <div class="media-body d-flex flex-column gap-2">
                                        <div class="d-flex align-items-center gap-1">
                                            <h5 class="fs-14 mb-0">{{ $order->seller->shop->name }}</h5>
                                            @include('vendor-views.shop.partials._verified-badge', ['isVerified' => $order->seller->hasVerifiedBadge(), 'size' => 16])
                                        </div>
                                        <span class="text-dark fs-12"><strong>{{ $totalDelivered }}</strong> {{translate('Orders Served')}}</span>
                                        <span
                                            class="text-dark fs-12"> <strong>{{ $order->seller->shop->contact }}</strong></span>
                                        <div class="d-flex align-items-start gap-2">
                                            <img
                                                src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/location.png')}}"
                                                class="mt-1" alt="">
                                            {{ $order->seller->shop->address }}
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center p-4">
                                        <img class="w-25"
                                             src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/empty-state-icon/shop-not-found.png')}}"
                                             alt="{{translate('Image description')}}">
                                        <p class="mb-0 fs-12">{{ translate('No shop found').'!'}}</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (isset($order->verificationImages))
        <div class="modal fade" id="order_verification_modal" tabindex="-1" aria-labelledby="order_verification_modal"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header pb-4">
                        <h3 class="mb-0">{{translate('Order verification images')}}</h3>
                        <button type="button" class="btn-close border-0" data-dismiss="modal" aria-label="Close"><i
                                class="tio-clear"></i></button>
                    </div>
                    <div class="modal-body px-4 px-sm-5 pt-0">
                        <div class="d-flex flex-column align-items-center gap-2">
                            <div class="row gx-2">
                                @foreach ($order->verificationImages as $image)
                                    <div class="col-lg-4 col-sm-6 ">
                                        <div class="mb-2 mt-2 border-1">
                                            <img
                                                src="{{ getStorageImages(path: $image->image_full_url , type: 'backend-basic') }}"
                                                class="w-100" alt="">
                                        </div>
                                    </div>
                                @endforeach
                                <div class="col-12">
                                    <div class="d-flex justify-content-end gap-3">
                                        <button type="button" class="btn btn-secondary px-5"
                                                data-dismiss="modal">{{translate('Close')}}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if($order['order_status'] != 'delivered')
        <div class="modal fade" id="shippingAddressUpdateModal" tabindex="-1"
             aria-labelledby="shippingAddressUpdateModal"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header pb-4">
                        <h3 class="mb-0 text-center w-100">{{translate('Shipping address')}}</h3>
                        <button type="button" class="btn-close border-0" data-dismiss="modal" aria-label="Close"><i
                                class="tio-clear"></i></button>
                    </div>
                    <div class="modal-body px-4 px-sm-5 pt-0">
                        <form action="{{route('vendor.retail.orders.address-update')}}" method="post"
                              id="shippingAddressUpdateForm">
                            @csrf
                            <div class="d-flex flex-column align-items-center gap-2">
                                <input name="address_type" value="shipping" hidden>
                                <input name="order_id" value="{{$order->id}}" hidden>
                                <div class="row gx-2">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name"
                                                   class="title-color">{{translate('Contact Person Name')}}</label>
                                            <input type="text" name="name" id="name" class="form-control"
                                                   value="{{$shippingAddress? $shippingAddress->contact_person_name : ''}}"
                                                   placeholder="{{ translate('Ex').' '.':'.' '.'John Doe'}}"
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone_number"
                                                   class="title-color">{{translate('Phone number')}}</label>
                                            <input class="form-control form-control-user"
                                                   type="tel"
                                                   value="{{$shippingAddress ? $shippingAddress->phone  : ''}}"
                                                   name="phone_number"
                                                   placeholder="{{ translate('Ex').': 017xxxxxxxx' }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="country" class="title-color">{{translate('Country')}}</label>
                                            <select name="country" id="country" class="form-control">
                                                @forelse($countries as $country)
                                                    <option
                                                        value="{{ $country['name'] }}" {{ isset($shippingAddress) && $country['name'] == $shippingAddress->country ? 'selected'  : ''}}>{{ $country['name'] }}</option>
                                                @empty
                                                    <option value="">{{ translate('No country to deliver') }}</option>
                                                @endforelse
                                            </select>

                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="city" class="title-color">{{translate('City')}}</label>
                                            <input type="text" name="city" id="city"
                                                   value="{{$shippingAddress ? $shippingAddress->city : ''}}"
                                                   class="form-control"
                                                   placeholder="{{ translate('Ex') }}: Dhaka"
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="zip_code" class="title-color">{{translate('Zip')}}</label>
                                            @if($zipRestrictStatus == 1)
                                                <select name="zip" class="form-control" data-live-search="true"
                                                        required>
                                                    @forelse($zipCodes as $code)
                                                        <option
                                                            value="{{ $code->zipcode }}"{{isset($shippingAddress) && $code->zipcode == $shippingAddress->zip ? 'selected'  : ''}}>{{ $code->zipcode }}</option>
                                                    @empty
                                                        <option value="">{{ translate('No zip to deliver') }}</option>
                                                    @endforelse
                                                </select>
                                            @else
                                                <input type="text" class="form-control"
                                                       value="{{$shippingAddress ? $shippingAddress->zip  : ''}}"
                                                       id="zip"
                                                       name="zip"
                                                       placeholder="{{ translate('Ex').' '.':'.' '.'1216'}}" {{$shippingAddress?'required':''}}>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="address" class="title-color">{{translate('Address')}}</label>
                                            <textarea name="address" id="address" name="address" rows="3"
                                                      class="form-control"
                                                      placeholder="{{ translate('Ex') }}: Street 1, street 2, street 3, street 4">{{$shippingAddress ? $shippingAddress->address : ''}}</textarea>
                                        </div>
                                    </div>
                                    <input type="hidden" id="latitude"
                                           name="latitude" class="form-control d-inline"
                                           placeholder="{{ translate('Ex').' '.':'.' '.'-94.22213' }}"
                                           value="{{$shippingAddress->latitude ?? 0}}" required readonly>
                                    <input type="hidden"
                                           name="longitude" class="form-control"
                                           placeholder="{{ translate('Ex').' '.':'.' '. '103.344322'}}" id="longitude"
                                           value="{{$shippingAddress->longitude??0}}" required readonly>
                                    @if($mapApiStatus ==1 )
                                        <div class="col-12 ">
                                            <input id="pac-input" class="form-control rounded __map-input mt-1"
                                                   title="{{translate('Search your location here')}}" type="text"
                                                   placeholder="{{translate('Search here')}}"/>
                                            <div class="dark-support rounded w-100 __h-200px mb-5"
                                                 id="location_map_canvas_shipping"></div>
                                        </div>
                                    @endif
                                    <div class="col-12">
                                        <div class="d-flex justify-content-end gap-3">
                                            <button type="button" class="btn btn-secondary px-5"
                                                    data-dismiss="modal">{{translate('Cancel')}}</button>
                                            <button type="submit"
                                                    class="btn btn--primary px-5">{{translate('Update')}}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($courierPartnerAssigned))
            <div class="modal fade" id="courierAddressChangeModal" tabindex="-1" role="dialog"
                 aria-labelledby="courierAddressChangeModalLabel">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header pb-0">
                            <h5 class="modal-title font-weight-bold"
                                id="courierAddressChangeModalLabel">{{translate('Update the delivery address')}}?</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">{{translate('Changing the address here does not update it with the delivery partner.')}}</p>
                            <div class="alert alert-warning fs-12 mb-0">
                                {{translate('Correct the address with your provider, or contact the delivery partner.')}}
                            </div>
                        </div>
                        <div class="modal-footer border-0 d-flex gap-3">
                            <button type="button" class="btn btn-secondary flex-fill"
                                    data-dismiss="modal">{{translate('Cancel')}}</button>
                            <button type="button" class="btn btn-primary flex-fill"
                                    id="courier-address-change-confirm">{{translate('Confirm and Update')}}</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                (function () {
                    const form = document.getElementById('shippingAddressUpdateForm');
                    const confirmBtn = document.getElementById('courier-address-change-confirm');
                    if (!form || !confirmBtn || !window.jQuery) return;

                    form.addEventListener('submit', function (event) {
                        if (!form.reportValidity()) return;
                        event.preventDefault();
                        $('#courierAddressChangeModal').modal('show');
                    });

                    confirmBtn.addEventListener('click', function () {
                        $('#courierAddressChangeModal').modal('hide');
                        form.submit();
                    });
                })();
            </script>
        @endif
        @if($billing)
            <div class="modal fade" id="billingAddressUpdateModal" tabindex="-1"
                 aria-labelledby="billingAddressUpdateModal"
                 aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header pb-4">
                            <h3 class="mb-0 text-center w-100">{{translate('Billing address')}}</h3>
                            <button type="button" class="btn-close border-0" data-dismiss="modal" aria-label="Close"><i
                                    class="tio-clear"></i></button>
                        </div>
                        <div class="modal-body px-4 px-sm-5 pt-0">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <form action="{{route('vendor.retail.orders.address-update')}}" method="post">
                                    @csrf
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <input name="address_type" value="billing" hidden>
                                        <input name="order_id" value="{{$order->id}}" hidden>
                                        <div class="row gx-2">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="name"
                                                           class="title-color">{{translate('Contact Person Name')}}</label>
                                                    <input type="text" name="name" id="name" class="form-control"
                                                           value="{{$billing? $billing->contact_person_name : ''}}"
                                                           placeholder="{{ translate('Ex') }}: John Doe"
                                                           required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="phone_number"
                                                           class="title-color">{{translate('Phone number')}}</label>
                                                    <input class="form-control form-control-user"
                                                           type="tel" value="{{$billing ? $billing->phone  : ''}}"
                                                           name="phone_number"
                                                           placeholder="{{ translate('Ex').': 017xxxxxxxx' }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="country"
                                                           class="title-color">{{translate('Country')}}</label>
                                                    <select name="country" id="country" class="form-control">
                                                        @forelse($countries as $country)
                                                            <option
                                                                value="{{ $country['name'] }}" {{ isset($billing) && $country['name'] == $billing->country ? 'selected'  : ''}}>{{ $country['name'] }}</option>
                                                        @empty
                                                            <option value="">
                                                                {{ translate('No country to deliver') }}
                                                            </option>
                                                        @endforelse
                                                    </select>

                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="city" class="title-color">{{translate('City')}}</label>
                                                    <input type="text" name="city" id="city"
                                                           value="{{$billing ? $billing->city : ''}}"
                                                           class="form-control"
                                                           placeholder="{{ translate('Ex') }}: Dhaka"
                                                           required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="zip_code"
                                                           class="title-color">{{translate('Zip')}}</label>
                                                    @if($zipRestrictStatus == 1)
                                                        <select name="zip" class="form-control" data-live-search="true"
                                                                required>
                                                            @forelse($zipCodes as $code)
                                                                <option
                                                                    value="{{ $code->zipcode }}"{{isset($billing) && $code->zipcode == $billing->zip ? 'selected'  : ''}}>{{ $code->zipcode }}</option>
                                                            @empty
                                                                <option
                                                                    value="">{{ translate('No zip to deliver') }}</option>
                                                            @endforelse
                                                        </select>
                                                    @else
                                                        <input type="text" class="form-control"
                                                               value="{{$billing ? $billing->zip  : ''}}" id="zip"
                                                               name="zip"
                                                               placeholder="{{ translate('Ex').' '.':'.' '.'1216' }}" {{$billing?'required':''}}>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="address"
                                                           class="title-color">{{translate('Address')}}</label>
                                                    <textarea name="address" id="billing_address" rows="3"
                                                              class="form-control"
                                                              placeholder="{{ translate('Ex') }}: Street 1, street 2, street 3, street 4">{{$billing ? $billing->address : ''}}</textarea>
                                                </div>
                                            </div>
                                            <input type="hidden" id="billing_latitude"
                                                   name="latitude" class="form-control d-inline"
                                                   placeholder="{{ translate('Ex').' '.':'.' '.'-94.22213'}}"
                                                   value="{{$billing->latitude ?? 0}}" required readonly>
                                            <input type="hidden"
                                                   name="longitude" class="form-control"
                                                   placeholder="{{ translate('Ex').' '.':'.' '. '103.344322'}}"
                                                   id="billing_longitude"
                                                   value="{{$billing->longitude ?? 0}}" required readonly>
                                            @if($mapApiStatus ==1 )
                                                <div class="col-12 ">
                                                    <input id="billing-pac-input"
                                                           class="form-control rounded __map-input mt-1"
                                                           title="{{translate('Search your location here')}}"
                                                           type="text"
                                                           placeholder="{{translate('Search here')}}"/>
                                                    <div class="rounded w-100 __h-200px mb-5"
                                                         id="location_map_canvas_billing"></div>
                                                </div>
                                            @endif
                                            <div class="col-12">
                                                <div class="d-flex justify-content-end gap-3">
                                                    <button type="button" class="btn btn-secondary px-5"
                                                            data-dismiss="modal">{{translate('Cancel')}}</button>
                                                    <button type="submit"
                                                            class="btn btn--primary px-5">{{translate('Update')}}</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
    <div class="modal fade" id="locationModal" tabindex="-1" role="dialog" aria-labelledby="locationModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header pb-0 pt-4">
                    <button type="button" class="close position-absolute right-3 top-3" data-dismiss="modal"
                            aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-header justify-content-center pt-0 pb-0">
                    <h3 class="modal-title"
                        id="locationModalLabel">{{translate('Location Data')}}</h3>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 rounded border p-3">
                            <div class="h3 text-cyan-blue text-center">{{ translate('Order') }} #{{ $order->id }}</div>
                            <ul class="nav nav-tabs border-0 media-tabs nav-justified order-track-info">
                                <li class="nav-item">
                                    <div class="nav-link active-status">
                                        <div class="d-flex flex-sm-column gap-3 gap-sm-0">
                                            <div class="media-tab-media mx-sm-auto mb-3">
                                                <img
                                                    src="{{ dynamicAsset(path: 'public/assets/back-end/img/track-order/order-placed.png') }}"
                                                    alt="">
                                            </div>
                                            <div class="media-body">
                                                <div class="text-sm-center text-start">
                                                    <h6 class="media-tab-title text-nowrap mb-0 text-capitalize fs-14">{{ translate('Order Placed') }}</h6>
                                                </div>
                                                <div
                                                    class="d-flex align-items-center justify-content-sm-center gap-1 mt-2">
                                                <span
                                                    class="text-muted fs-12">{{date('h:i A, d M Y',strtotime($order->created_at))}}</span>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </li>


                                @if ($order['order_status']!='returned' && $order['order_status']!='failed' && $order['order_status']!='canceled')
                                    @if(!$isOrderOnlyDigital)
                                        <li class="nav-item ">
                                            <div
                                                class="nav-link {{($order['order_status']=='confirmed') || ($order['order_status']=='processing') || ($order['order_status']=='processed') || ($order['order_status']=='out_for_delivery') || ($order['order_status']=='delivered')?'active-status' : ''}}">
                                                <div class="d-flex flex-sm-column gap-3 gap-sm-0">
                                                    <div class="media-tab-media mb-3 mx-sm-auto">
                                                        <img
                                                            src="{{ dynamicAsset(path: 'public/assets/back-end/img/track-order/order-confirmed.png') }}"
                                                            alt="">
                                                    </div>
                                                    <div class="media-body">
                                                        <div class="text-sm-center text-start">
                                                            <h6 class="media-tab-title text-nowrap mb-0 text-capitalize fs-14">{{ translate('Order confirmed') }}</h6>
                                                        </div>
                                                        @if(in_array($order['order_status'], ['confirmed', 'processing', 'processed', 'out_for_delivery', 'delivered']) && ($statusHistoryDates['confirmed'] ?? null))
                                                            <div
                                                                class="d-flex align-items-center justify-content-sm-center mt-2 gap-1">
                                                            <span class="text-muted fs-12">
                                                                {{ date('h:i A, d M Y', strtotime(($statusHistoryDates['confirmed'] ?? null)))}}
                                                            </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="nav-item">
                                            <div
                                                class="nav-link {{($order['order_status']=='processing') || ($order['order_status']=='processed') || ($order['order_status']=='out_for_delivery') || ($order['order_status']=='delivered')?'active-status' : ''}}">
                                                <div class="d-flex flex-sm-column gap-3 gap-sm-0">
                                                    <div class="media-tab-media mb-3 mx-sm-auto">
                                                        <img alt=""
                                                             src="{{ dynamicAsset(path: 'public/assets/back-end/img/track-order/shipment.png') }}">
                                                    </div>
                                                    <div class="media-body">
                                                        <div class="text-sm-center text-start">
                                                            <h6 class="media-tab-title text-nowrap mb-0 text-capitalize fs-14">
                                                                {{ translate('Preparing shipment') }}
                                                            </h6>
                                                        </div>
                                                        @if(in_array($order['order_status'], ['processing', 'processed', 'out_for_delivery', 'delivered']) && ($statusHistoryDates['processing'] ?? null))
                                                            <div
                                                                class="d-flex align-items-center justify-content-sm-center mt-2 gap-2">
                                                            <span class="text-muted fs-12">
                                                                {{date('h:i A, d M Y',strtotime(($statusHistoryDates['processing'] ?? null)))}}
                                                            </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="nav-item">
                                            <div
                                                class="nav-link {{($order['order_status']=='out_for_delivery') || ($order['order_status']=='delivered')?'active-status' : ''}}">
                                                <div class="d-flex flex-sm-column gap-3 gap-sm-0">
                                                    <div class="media-tab-media mb-3 mx-sm-auto">
                                                        <img
                                                            src="{{ dynamicAsset(path: 'public/assets/back-end/img/track-order/on-the-way.png') }}"
                                                            alt="">
                                                    </div>
                                                    <div class="media-body">
                                                        <div class="text-sm-center text-start">
                                                            <h6 class="media-tab-title text-nowrap mb-0 fs-14">{{ translate('Order is on the way') }}</h6>
                                                        </div>
                                                        @if(in_array($order['order_status'], ['out_for_delivery', 'delivered']) && ($statusHistoryDates['out_for_delivery'] ?? null))
                                                            <div
                                                                class="d-flex align-items-center justify-content-sm-center mt-2 gap-2">
                                                            <span class="text-muted fs-12">
                                                                {{date('h:i A, d M Y',strtotime(($statusHistoryDates['out_for_delivery'] ?? null)))}}
                                                            </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="nav-item">
                                            <div
                                                class="nav-link {{($order['order_status']=='delivered')?'active-status' : ''}}">
                                                <div class="d-flex flex-sm-column gap-3 gap-sm-0">
                                                    <div class="media-tab-media mb-3 mx-sm-auto">
                                                        <img
                                                            src="{{ dynamicAsset(path: 'public/assets/back-end/img/track-order/delivered.png') }}"
                                                            alt="">
                                                    </div>
                                                    <div class="media-body">
                                                        <div class="text-sm-center text-start">
                                                            <h6 class="media-tab-title text-nowrap mb-0 fs-14">{{ translate('Order Shipped') }}</h6>
                                                        </div>
                                                        @if(($order['order_status']=='delivered') && ($statusHistoryDates['delivered'] ?? null))
                                                            <div
                                                                class="d-flex align-items-center justify-content-sm-center mt-2 gap-2">
                                                            <span class="text-muted fs-12">
                                                                {{date('h:i A, d M Y',strtotime(($statusHistoryDates['delivered'] ?? null)))}}
                                                            </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    @else

                                        <li class="nav-item">
                                            <div
                                                class="nav-link {{ ($order['order_status']=='confirmed') ? 'active-status' : ''}}">
                                                <div class="d-flex flex-sm-column gap-3 gap-sm-0">
                                                    <div class="media-tab-media mb-3 mx-sm-auto">
                                                        <img alt=""
                                                             src="{{ dynamicAsset(path: 'public/assets/back-end/img/track-order/shipment.png') }}">
                                                    </div>
                                                    <div class="media-body">
                                                        <div class="text-sm-center text-start">
                                                            <h6 class="media-tab-title text-nowrap mb-0 text-capitalize fs-14">
                                                                {{ translate('Processing') }}
                                                            </h6>
                                                        </div>
                                                        @if($order['order_status']=='confirmed' && ($statusHistoryDates['confirmed'] ?? null))
                                                            <div
                                                                class="d-flex align-items-center justify-content-sm-center mt-2 gap-2">
                                                            <span class="text-muted fs-12">
                                                                {{date('h:i A, d M Y',strtotime(($statusHistoryDates['confirmed'] ?? null)))}}
                                                            </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="nav-item">
                                            <div
                                                class="nav-link {{($order['order_status']=='confirmed' && $digitalProductProcessComplete)?'active-status' : ''}}">
                                                <div class="d-flex flex-sm-column gap-3 gap-sm-0">
                                                    <div class="media-tab-media mb-3 mx-sm-auto">
                                                        <img
                                                            src="{{ dynamicAsset(path: 'public/assets/back-end/img/track-order/delivered.png') }}"
                                                            alt="">
                                                    </div>
                                                    <div class="media-body">
                                                        <div class="text-sm-center text-start">
                                                            <h6 class="media-tab-title text-nowrap mb-0 fs-14">{{ translate('Delivery complete') }}</h6>
                                                        </div>

                                                        @if(($order['order_status']=='confirmed') && $digitalProductProcessComplete && ($statusHistoryDates['confirmed'] ?? null))
                                                            <div
                                                                class="d-flex align-items-center justify-content-sm-center mt-2 gap-2">
                                                            <span class="text-muted fs-12">
                                                                {{date('h:i A, d M Y',strtotime(($statusHistoryDates['confirmed'] ?? null)))}}
                                                            </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    @endif
                                @elseif(in_array($order['order_status'], ['returned', 'canceled']))
                                    <li class="nav-item">
                                        <div class="nav-link active-status">
                                            <div class="d-flex flex-sm-column gap-3 gap-sm-0">
                                                <div class="media-tab-media mx-sm-auto mb-3">
                                                    <img
                                                        src="{{ dynamicAsset(path: 'public/assets/back-end/img/track-order/'.$order['order_status'].'.png') }}"
                                                        alt="">
                                                </div>
                                                <div class="media-body">
                                                    <div class="text-sm-center text-start">
                                                        <h6 class="media-tab-title text-nowrap mb-0 text-capitalize fs-14">
                                                            {{ translate('Order') }} {{ translate($order['order_status']) }}
                                                        </h6>
                                                    </div>
                                                    @if(($statusHistoryDates[$order['order_status']] ?? null))
                                                        <div
                                                            class="d-flex align-items-center justify-content-sm-center gap-1 mt-2">
                                                        <span class="text-muted fs-12">
                                                            {{ date('h:i A, d M Y', strtotime(($statusHistoryDates[$order['order_status']] ?? null))) }}
                                                        </span>
                                                        </div>
                                                    @endif

                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @else
                                    <li class="nav-item">
                                        <div class="nav-link active-status">
                                            <div class="d-flex flex-sm-column gap-3 gap-sm-0">
                                                <div class="media-tab-media mx-sm-auto mb-3">
                                                    <img
                                                        src="{{ dynamicAsset(path: 'public/assets/back-end/img/track-order/order-failed.png') }}"
                                                        alt="">
                                                </div>
                                                <div class="media-body">
                                                    <div class="text-sm-center text-start">
                                                        <h6 class="media-tab-title text-nowrap mb-0 text-capitalize fs-14">{{ translate('Failed to deliver') }}</h6>
                                                    </div>
                                                    <div
                                                        class="d-flex align-items-center justify-content-sm-center gap-1 mt-2">
                                                <span class="text-muted fs-12">
                                                    {{ translate('Sorry we cannot complete your order') }}
                                                </span>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </li>
                                @endif
                            </ul>
                        </div>

                        <div class="col-md-12 modal_body_map mt-5 pl-0 pr-0">
                            <div class="mb-2">
                                <img src="{{ dynamicAsset('assets/back-end/img/location-blue.png') }}" alt="">
                                <span>{{ $shippingAddress ? $shippingAddress->address : ($billing ? $billing->address : '') }}</span>
                            </div>
                            <div class="location-map" id="location-map">
                                <div class="w-100 h-200" id="location_map_canvas"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="changeOrderStatusModal" tabindex="-1" aria-labelledby="changeOrderStatusModal"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 p-2 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn btn-circle border-0 fs-12 text-body bg-section2 shadow-none"
                            style="--size: 2rem;" data-dismiss="modal" aria-label="Close">
                        <i class="fi fi-sr-cross"></i>
                    </button>
                </div>
                <div class="modal-body px-20 py-0 mb-30">
                    <div class="d-flex flex-column align-items-center text-center mb-30">
                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/modal/warning-new.png') }}"
                             width="70" class="aspect-1 mb-20" id="" alt="">
                        <h3 class="modal-title mb-3" id="">{{ translate('Are you sure') }}?</h3>
                        <div class="text-center" id="">
                            {{ translate('Update this order status? The customer is notified. This is final.') }}
                        </div>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn--primary min-w-120"
                                data-dismiss="modal">{{ translate('Yes change it!') }}</button>
                        <button type="button" class="btn btn-danger min-w-120"
                                data-dismiss="modal">{{ translate('Cancel') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade pt-5" id="quick-view" tabindex="-1" style="z-index:2000;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" id="quick-view-modal"></div>
        </div>
    </div>

    @include('vendor-views.order.partials.modal.order-edit-due-amount-mark-as-paid',['order' => $order])
    @include('vendor-views.order.partials.modal.order-edit-due-amount-switch-to-cod',['order' => $order])

    @include('vendor-views.order.partials.modal._confirm-edit-order')
    @include('vendor-views.order.partials.offcanvas._edit-products-offcanvas')

    <span id="payment-status-message" data-title="{{ translate('Confirm payments before changing the status') }}"
          data-message="{{ translate('Mark as Paid only after you receive and verify the payment.') }}"></span>

    <span id="message-status-title-text" data-text="{{ translate('Are you sure change this') }}"></span>
    <span id="message-status-subtitle-text" data-text="{{ translate('You will not be able to revert this') }}!"></span>
    <span id="message-status-confirm-text" data-text="{{ translate('Yes change it!') }}!"></span>
    <span id="message-status-cancel-text" data-text="{{ translate('Cancel') }}"></span>
    <span id="message-status-success-text" data-text="{{ translate(':item changed successfully', ['item' => translate('Status')]) }}"></span>
    <span id="message-status-warning-text"
          data-text="{{ translate('Account has been deleted, you cannot change the status') }}"></span>

    <span id="message-order-status-delivered-text"
          data-text="{{ translate('Order is already delivered you cannot change it') }}!"></span>
    <span id="message-order-status-paid-first-text"
          data-text="{{ translate('Before delivery you need to set the payment status to paid') }}!"></span>
    <span id="order-status-url" data-url="{{ route('vendor.retail.orders.status') }}"></span>
    <span id="payment-status-url" data-url="{{ route('vendor.retail.orders.payment-status') }}"></span>

    <span id="message-deliveryman-add-success-text"
          data-text="{{ translate('Delivery man assigned/changed successfully') }}"></span>
    <span id="message-deliveryman-add-error-text"
          data-text="{{ translate('Delivery man cannot be assigned or changed in that status') }}"></span>
    <span id="message-deliveryman-add-invalid-text"
          data-text="{{ translate('Delivery man cannot be assigned or changed in that status') }}"></span>
    <span id="delivery-type" data-type="{{ $deliveryPartner['is_assigned'] ? 'third_party_delivery' : $order->delivery_type }}"></span>
    <span id="add-delivery-man-url" data-url="{{url('/vendor/retail/orders/add-delivery-man/'.$order['id'])}}/"></span>

    <span id="message-deliveryman-charge-success-text"
          data-text="{{ translate(':item added successfully', ['item' => translate('Deliveryman charge')]) }}"></span>
    <span id="message-deliveryman-charge-error-text"
          data-text="{{ translate('Failed to add deliveryman charge') }}"></span>
    <span id="message-deliveryman-charge-invalid-text" data-text="{{ translate('Add valid data') }}"></span>
    <span id="add-date-update-url" data-url="{{route('vendor.retail.orders.amount-date-update')}}"></span>

    <span id="customer-name" data-text="{{$order->customer['f_name']??""}} {{$order->customer['l_name']??""}}}"></span>
    <span id="is-shipping-exist" data-status="{{$shippingAddress ? 'true':'false'}}"></span>
    <span id="shipping-address" data-text="{{$shippingAddress->address??''}}"></span>
    <span id="shipping-latitude" data-latitude="{{$shippingAddress->latitude??'-33.8688'}}"></span>
    <span id="shipping-longitude" data-longitude="{{$shippingAddress->longitude??'151.2195'}}"></span>
    <span id="billing-latitude" data-latitude="{{$billing->latitude??'-33.8688'}}"></span>
    <span id="billing-longitude" data-longitude="{{$billing->longitude??'151.2195'}}"></span>
    <span id="location-icon"
          data-path="{{dynamicAsset(path: 'public/assets/front-end/img/customer_location.png')}}"></span>
    <span id="customer-image"
          data-path="{{dynamicStorage(path: 'storage/app/public/profile/')}}{{$order->customer->image??""}}"></span>
    <span id="deliveryman-charge-alert-message"
          data-message="{{translate('When the order status is delivered you can\'t update the delivery man incentive').'.'}}"></span>
    <span id="payment-status-alert-message"
          data-message="{{translate('A paid payment status cannot be changed back to unpaid.').'.'}}"></span>
    <span id="get-search-product-for-edit-order"
          data-action="{{ route('vendor.retail.orders.search-for-edit-order-product') }}"
          data-order-id="{{ $order['id'] }}"></span>
    <span id="edit-order-product-modal-view"
          data-action="{{ route('vendor.retail.orders.edit-order-product-modal-view') }}"></span>
@endsection
@push('script')
    @if($mapApiStatus ==1 )
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{ $mapApiKey }}&callback=mapCallBackFunction&loading=async&libraries=places&v=3.56"
            defer>
        </script>
    @endif

    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf-worker.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/multiple-document-upload.js') }}"></script>

    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/vendor/order.js')}}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/vendor/js/order-edit.js') }}"></script>
    <script>
        openOffcanvasAfterModal('#confirm-edit-order', '#confirm-edit-order-modal', '#offcanvasEditProducts');

        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('paymentInfoCollapse');
            const paymentSection = document.querySelector('.payment-logs-info-section');

            if (!toggleBtn || !paymentSection) return;

            toggleBtn.addEventListener('click', function () {
                const isExpanded = paymentSection.classList.toggle('expanded');

                toggleBtn.innerText = isExpanded
                    ? "{{ translate('See Less') }}"
                    : "{{ translate('See More') }}";
            });
        });
    </script>
@endpush
