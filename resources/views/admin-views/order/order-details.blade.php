@extends('layouts.admin.app')

@section('title', translate('Order Details').' - #'.$order['id'])

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <h2 class="fs-20 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" height="20"
                     src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/all-orders.png') }}" alt="">
                <span>{{translate('Order details')}}</span>
            </h2>
            <div class="d-flex gap-1 align-items-center">
                <a href="{{ $previousOrder ? route('admin.retail.orders.details', [$previousOrder['id']]) : 'javascript:' }}"
                   class="btn btn-circle text-primary bg-primary bg-opacity-10 border-0 {{ $previousOrder ? '' : 'disabled opacity-40' }}">
                    <i class="fi fi-sr-angle-left d-flex"></i>
                </a>
                <a href="{{ $nextOrder ? route('admin.retail.orders.details', [$nextOrder['id']]) : 'javascript:' }}"
                   class="btn btn-circle text-primary bg-primary bg-opacity-10 border-0 {{ $nextOrder ? '' : 'disabled opacity-40' }}">
                    <i class="fi fi-sr-angle-right d-flex"></i>
                </a>
            </div>
        </div>

        <div class="row g-3" id="printableArea">
            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap flex-md-nowrap gap-10 justify-content-between mb-4">
                            <div class="d-flex flex-column gap-3 flex-1">
                                <h4 class="text-capitalize fs-16 fw-bold d-flex align-items-center gap-3 flex-wrap mb-0">
                                    <span class="d-flex align-items-center gap-2">
                                        <span>
                                            {{ translate('Order details') }} #{{ $order['id'] }}
                                        </span>

                                        @if($order?->edited_status == 1)
                                            <span class="badge badge-info text-bg-info">
                                                {{ translate('Edited') }}
                                            </span>
                                        @endif
                                    </span>
                                </h4>
                                <div class="fs-12">
                                    {{ date('d M, Y , h:i A', strtotime($order['created_at'])) }}
                                </div>

                                <div class="d-flex flex-column gap-3">
                                    <div class="d-flex flex-wrap gap-2">
                                        @if ($linkedOrders->count() >0)
                                            <div class="text-dark fs-12 d-flex align-items-center rounded">
                                                {{translate('Linked orders')}}
                                                ({{ $linkedOrders->count()}}) :
                                            </div>
                                            @foreach($linkedOrders as $linked)
                                                <a href="{{ route('admin.retail.orders.details', [$linked['id']]) }}"
                                                   class="text-primary text-underline fw-bold">#{{$linked['id'] }}</a>
                                            @endforeach
                                        @endif
                                    </div>
                                    @if (!is_null($order['order_note']) && !empty($order['order_note']))
                                        <div
                                            class="bg-section fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                        <span>
                                            # {{ translate('Note') }}:
                                            {{ \Illuminate\Support\Str::limit($order['order_note'], 250) }}
                                            @if(strlen($order['order_note']) > 250)
                                                <a href="javascript:void(0)"
                                                   class="text-primary ms-1"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#orderNoteModal">
                                                    {{ translate('Read more') }}
                                                </a>

                                                <div class="modal fade" id="orderNoteModal" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h4 class="modal-title mb-0">{{ translate('Order Note') }}</h4>
                                                                <button type="button" class="btn-close me-0"
                                                                        data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p class="mb-0">{{ $order['order_note'] }}</p>
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
                                            class="bg-success bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
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
                                    @if ($order->verificationImages && count($order->verificationImages)>0 && $order->verification_status ==1)
                                        <div>
                                            <button class="btn btn-primary btn-sm px-4" data-bs-toggle="modal"
                                                    data-bs-target="#order_verification_modal">
                                                <i class="fi fi-sr-shield-trust"></i> {{ translate('Order verification') }}
                                            </button>
                                        </div>
                                    @endif

                                    @if($isOrderEditable['status'] === true)
                                        <buton type="button" class="btn btn-outline-primary btn-sm"
                                               data-bs-toggle="modal"
                                               data-bs-target="#confirm-edit-order-modal">
                                            <i class="fi fi-sr-pencil"></i> {{ translate('Edit Products') }}
                                        </buton>
                                    @else
                                        <buton type="button" class="btn btn-outline-primary btn-sm opacity-50"
                                               data-bs-toggle="tooltip" data-bs-placement="top"
                                               data-bs-title="{{ $isOrderEditable['message'] }}" disabled>
                                            <i class="fi fi-sr-pencil"></i> {{ translate('Edit Products') }}
                                        </buton>
                                    @endif

                                    <a class="btn btn-primary btn-sm" target="_blank"
                                       href={{ route('admin.retail.orders.generate-invoice', [$order['id']]) }}>
                                        <img alt="" class="mr-1"
                                             src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/icons/uil_invoice.svg') }}">
                                        {{translate('Print Invoice')}}
                                    </a>
                                </div>
                                <div class="d-flex flex-column gap-2 mt-3">
                                    <div
                                        class="order-status d-flex justify-content-sm-end gap-10 text-capitalize fs-12">
                                        <span class="text-dark">{{translate('Status')}}: </span>
                                        @if($order['order_status'] == 'pending')
                                            <span
                                                class="badge color-caribbean-green-soft fw-bold rounded d-flex align-items-center py-1 px-2">{{translate(str_replace('_',' ',$order['order_status']))}}</span>
                                        @elseif($order['order_status']=='failed')
                                            <span
                                                class="badge badge-danger text-bg-danger fw-bold rounded d-flex align-items-center py-1 px-2">{{translate(str_replace('_',' ',$order['order_status'] == 'failed' ? 'Failed to Deliver' : ''))}}
                                            </span>
                                        @elseif($order['order_status']=='processing' || $order['order_status']=='out_for_delivery')
                                            <span
                                                class="badge badge-warning text-bg-warning fw-bold rounded d-flex align-items-center py-1 px-2">
                                                {{translate(str_replace('_',' ',$order['order_status'] == 'processing' ? 'Packaging' : $order['order_status']))}}
                                            </span>
                                        @elseif($order['order_status']=='delivered' || $order['order_status']=='confirmed')
                                            <span
                                                class="badge badge-success text-bg-success fw-bold rounded d-flex align-items-center py-1 px-2">
                                                {{translate(str_replace('_',' ',$order['order_status']))}}
                                            </span>
                                        @else
                                            <span
                                                class="badge badge-danger text-bg-danger fw-bold rounded d-flex align-items-center py-1 px-2">
                                                {{translate(str_replace('_',' ',$order['order_status']))}}
                                            </span>
                                        @endif
                                    </div>

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
                        <div class="table-responsive">
                            <table
                                class="table fs-12 table-hover table-borderless align-middle">
                                <thead class="text-capitalize">
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
                                        <tr class="">
                                            <td>{{ $detailDisplayRows[$detail->id]['serial'] }}</td>
                                            <td>
                                                <div class="{{ $detailDisplayRows[$detail->id]['is_product_unavailable'] ? 'table-row-disabled' : '' }}"
                                                     data-bs-toggle="tooltip"
                                                     title="{{ $detailDisplayRows[$detail->id]['is_product_unavailable'] ? translate('Product has been deleted') : '' }}">

                                                    <div class="media align-items-center gap-10">
                                                        <a target="_blank"
                                                           href="{{ route('admin.retail.products.view', ['addedBy' => $detailDisplayRows[$detail->id]['product']['added_by'] == 'seller' ? 'vendor' : 'in-house', 'id' => $detailDisplayRows[$detail->id]['product']['id']]) }}">
                                                            <img class="avatar avatar-60 rounded img-fit"
                                                                 src="{{ getStorageImages(path:$detail?->productAllStatus?->thumbnail_full_url, type: 'backend-product') }}"
                                                                 alt="{{translate('Image description')}}">
                                                        </a>
                                                        <div>
                                                            <a target="_blank"
                                                               class="text-dark fs-12 fw-bold"
                                                               href="{{ route('admin.retail.products.view', ['addedBy' => $detailDisplayRows[$detail->id]['product']['added_by'] == 'seller' ? 'vendor' : 'in-house', 'id' => $detailDisplayRows[$detail->id]['product']['id']]) }}"
                                                               @if(!$detailDisplayRows[$detail->id]['is_product_unavailable'] && strlen($detailDisplayRows[$detail->id]['product']['name']) > 30)
                                                                   data-bs-toggle="tooltip"
                                                               title="{{ $detailDisplayRows[$detail->id]['product']['name'] }}"
                                                                @endif
                                                            >
                                                                {{ Str::limit($detailDisplayRows[$detail->id]['product']['name'], 30) }}
                                                            </a>

                                                            <div class="fs-10">
                                                                <strong>{{translate('Unit Price')}} :</strong>
                                                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $detail['price'])) }}
                                                            </div>
                                                            @if ($detail->variant)
                                                                <div class="max-w-150px text-wrap fs-10">
                                                                    <strong>{{translate('Variation')}} :</strong>
                                                                    {{$detail['variant']}}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    @if($detailDisplayRows[$detail->id]['digital_product_type'] == 'ready_after_sell')
                                                        <button type="button" class="btn btn-sm btn-primary mt-2"
                                                                title="{{translate('File Upload')}}"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#fileUploadModal-{{ $detail->id }}">
                                                            <i class="fi fi-rr-file"></i> {{translate('File')}}
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">{{$detail['qty']}}</td>
                                            <td class="text-end">
                                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $detail['price']*$detail['qty']), currencyCode: getCurrencyCode()) }}
                                            </td>
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
                                                    action="{{ route('admin.retail.orders.digital-file-upload-after-sell') }}"
                                                    method="post" enctype="multipart/form-data"
                                                    class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate"
                                                    novalidate="novalidate">
                                                    @csrf
                                                    <div class="modal-header border-0 px-2 pt-2 pb-0 d-flex justify-content-end">
                                                        <button type="button" class="btn btn-circle border-0 fs-12 text-body bg-section2 shadow-none"
                                                                style="--size: 1.5rem;" data-bs-dismiss="modal" aria-label="Close">
                                                            <i class="fi fi-sr-cross-small fs-16 d-flex"></i>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body pt-0">
                                                        @if($detailDisplayRows[$detail->id]['is_in_house_upload'])
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
                                                        @else
                                                            <h4 class="mt-3 text-center">{{translate('Admin has no permission for vendor digital product upload')}}</h4>
                                                        @endif
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">{{translate('Close')}}</button>
                                                        @if($detailDisplayRows[$detail->id]['is_in_house_upload'])
                                                            <button type="submit"
                                                                    class="btn btn-primary">{{translate('Upload')}}</button>
                                                        @endif
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
                                                    <span>{{ translate('Referral discount') }}</span></td>
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

                                    @if($order?->edited_status)
                                        <div class="d-flex justify-content-end mt-2">
                                            <div
                                                class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center w-100">
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

                        @if(!empty($order?->orderEditHistory) && count($order?->orderEditHistory) > 0)
                            <hr/>
                            <div>
                                <h4 class="fs-14 fw-bold mb-3">{{ translate('Edit Log') }}</h4>

                                <div class="d-flex flex-column gap-20">
                                    @foreach($order?->orderEditHistory as $orderEditHistory)
                                        <div>
                                            <div class="fs-12 mb-2">
                                                {{ translate('Order Edited by') }} {{ $orderEditHistory['edited_user_name'] }}
                                                ({{ translate($orderEditHistory['edit_by']) }})
                                            </div>
                                            <div class="fs-12 mb-0 text-dark">
                                                {{ $orderEditHistory?->created_at->format('d M, Y h:i') }}
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
                                class="badge badge-success text-bg-success fw-semibold rounded d-flex align-items-center py-1 px-2">
                                {{ translate('Paid') }}
                            </span>
                        @else
                            <span
                                class="badge badge-danger text-bg-danger fw-semibold rounded d-flex align-items-center py-1 px-2">
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
                                        <span class="fw-semibold">
                                            @if(strlen($order?->payment_note) > 250)
                                                {{ Str::limit($order?->payment_note, 250) }}
                                                <a href="javascript:void(0)"
                                                   class="text-primary ms-1"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#paymentNoteModal">
                                                   {{ translate('Read more') }}
                                                </a>
                                            @else
                                                {{ $order?->payment_note ?? 'N/A' }}
                                            @endif
                                        </span>
                                    </div>
                                @endif
                                <div class="modal fade" id="paymentNoteModal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ translate('Payment Note') }}</h5>
                                                <button type="button" class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body">
                                                <p class="mb-0" style="white-space: pre-wrap;">
                                                    {{ $order?->payment_note ?? 'N/a' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($order['payment_method'] == 'offline_payment' && $order['payment_status'] == 'unpaid')
                            <div class="row g-2 mt-2">
                                <div class="col-12">
                                    <button type="button"
                                            class="btn btn-primary btn-sm w-100"
                                            data-bs-toggle="modal"
                                            data-bs-target="#markAsPaidModal-{{ $order['id'] }}">
                                        {{ translate('Mark as Paid') }}
                                    </button>
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
                                            class="badge badge-success text-bg-success fw-semibold rounded d-flex align-items-center py-1 px-2 w-max-content">
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
                                            class="badge badge-success text-bg-success fw-semibold rounded d-flex align-items-center py-1 px-2 w-max-content">
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
                                            class="badge badge-danger text-bg-danger fw-semibold rounded d-flex align-items-center py-1 px-2 w-max-content">
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
                                            class="badge badge-danger text-bg-danger fw-semibold rounded d-flex align-items-center py-1 px-2 w-max-content">
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
                                                        <span class="fw-semibold">
                                                        @if($orderDuePaymentInfoKey === 'payment_note' && strlen($orderDuePaymentInfo) > 250)
                                                                {{ Str::limit($orderDuePaymentInfo, 250) }}
                                                                <a href="javascript:void(0)"
                                                                   class="text-primary ms-1"
                                                                   data-bs-toggle="modal"
                                                                   data-bs-target="#paymentNoteModal-{{$orderDuePaymentInfoKey}}">
                                                                    {{ translate('Read more') }}
                                                                    </a>
                                                            @else
                                                                {{ $orderDuePaymentInfo ?? 'N/A' }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endif
                                                <div class="modal fade"
                                                     id="paymentNoteModal-{{ $orderDuePaymentInfoKey }}" tabindex="-1"
                                                     aria-hidden="true">
                                                    <div
                                                        class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Payment Note</h5>
                                                                <button type="button" class="btn-close"
                                                                        data-bs-dismiss="modal"></button>
                                                            </div>

                                                            <div class="modal-body">
                                                                <p class="mb-0" style="white-space: pre-wrap;">
                                                                    {{ $order?->latestEditHistory?->order_due_payment_info['payment_note'] ?? 'N/A' }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="row g-2 mt-2">
                                        <div class="col-12">
                                            <button type="button"
                                                    class="btn btn-primary btn-sm w-100"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#markAsPaidModal-{{ $order['id'] }}">
                                                {{ translate('Mark as Paid') }}
                                            </button>
                                        </div>
                                    </div>
                                @endif
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
                        <h3 class="mb-0 text-center fw-bold">
                            {{ translate('Order & Shipping Information') }}
                        </h3>

                        @if($order?->shipping_responsibility == 'sellerwise_shipping')
                            <span class="d-block fs-12 mt-1 text-center">
                                ({{ translate('Order Placed Via Vendor-wise Shipping') }})
                            </span>
                        @endif
                    </div>
                    <div class="card-body d-flex flex-column gap-4">
                        @if ($editSettlement['edit_due_amount'] > 0 && $order?->latestEditHistory?->order_due_payment_status === 'unpaid' && $order?->latestEditHistory?->order_due_payment_method != '')
                            <div class="bg-section rounded-10 p-12 p-sm-20 text-center">
                                <h4 class="text-danger mb-3">{{ translate('Due Amount') }}</h4>
                                <h3 class="fw-bold">
                                    {{ webCurrencyConverter(amount: $editSettlement['edit_due_amount']) }}
                                </h3>
                                <p class="fs-12 mb-0">
                                    {{ translate('After editing, the order amount has increased.') }}
                                    {{ translate('Please collect the due amount and mark the order as paid.') }}
                                </p>
                                <button type="button"
                                        class="btn btn-primary mt-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#markAsPaidModal-{{ $order['id'] }}">
                                    {{ translate('Mark as Paid') }}
                                </button>
                            </div>
                        @elseif ($editSettlement['edit_due_amount'] > 0 && !in_array($order->payment_method, ['cash_on_delivery']) && $order?->latestEditHistory?->order_due_payment_method != "cash_on_delivery")
                            <div class="bg-section rounded-10 p-12 p-sm-20 text-center">
                                <h4 class="text-danger mb-3">{{ translate('Due Amount') }}</h4>
                                <h3 class="fw-bold">
                                    {{ webCurrencyConverter(amount: $editSettlement['edit_due_amount']) }}
                                </h3>
                                <p class="fs-12 mb-0">
                                    {{ translate('After editing, the order amount has increased.') }}
                                    {{ translate('Switch to cash on delivery, or contact the customer from order details.') }}
                                </p>
                                <button type="button"
                                        class="btn btn-primary mt-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#switchToCODModal">
                                    {{ translate('Switch to COD') }}
                                </button>
                            </div>
                        @elseif ($editSettlement['edit_return_amount'] > 0)
                            <div class="bg-section rounded-10 p-12 p-sm-20 text-center">
                                <h4 class="text-danger mb-3">{{ translate('Need to Return') }}</h4>
                                <h3 class="fw-bold">
                                    {{ webCurrencyConverter(amount: $editSettlement['edit_return_amount']) }}
                                </h3>
                                <p class="fs-12 mb-0">
                                    {{ translate('After editing, the order amount has decreased.') }}
                                    {{ translate('After returning the amount, update the status to paid.') }}
                                </p>
                                <button type="button"
                                        class="btn btn-primary mt-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#returnDueAmountModal-{{ $order['id'] }}">
                                    {{ translate('Return Due Amount') }}
                                </button>
                            </div>
                        @endif
                        <div class="text-capitalize">
                            <label
                                class="form-label fw-bold mb-2">{{translate('Change order status')}}</label>
                            <div class="select-wrapper">
                                <select name="order_status" id="order_status"
                                        class="status form-select" data-id="{{$order['id']}}">

                                    <option
                                        value="pending" {{$order->order_status == 'pending'?'selected':''}} > {{translate('Pending')}}</option>
                                    <option
                                        value="confirmed" {{$order->order_status == 'confirmed'?'selected':''}} > {{translate('Confirmed')}}</option>
                                    <option
                                        value="processing" {{$order->order_status == 'processing'?'selected':''}} >{{translate('Packaging')}} </option>
                                    <option class="text-capitalize"
                                            value="out_for_delivery" {{$order->order_status == 'out_for_delivery'?'selected':''}} >{{translate('Out For Delivery')}} </option>
                                    <option
                                        value="delivered" {{$order->order_status == 'delivered'?'selected':''}} >{{translate('Delivered')}} </option>
                                    <option
                                        value="returned" {{$order->order_status == 'returned'?'selected':''}} > {{translate('Returned')}}</option>
                                    <option
                                        value="failed" {{$order->order_status == 'failed'?'selected':''}} >{{translate('Failed to deliver')}} </option>
                                    <option
                                        value="canceled" {{$order->order_status == 'canceled'?'selected':''}} >{{translate('Canceled')}} </option>
                                </select>
                            </div>
                        </div>
                        <div
                            class="d-flex justify-content-between align-items-center gap-10 form-control py-10 h-auto flex-wrap  text-capitalize">
                            <span class="text-dark">
                                {{translate('Payment Status')}}
                            </span>
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <span
                                    class="text-primary fw-bold">{{ $order->payment_status=='paid' ? translate('Paid'):translate('Unpaid')}}</span>
                                <label
                                    class="switcher payment-status-text">
                                    <input class="switcher_input payment-status" type="checkbox" name="status"
                                           data-id="{{$order->id}}"
                                           value="{{$order->payment_status}}"
                                        {{ $order->payment_status == 'paid' ? 'checked':''}} >
                                    <span class="switcher_control switcher_control_add
                                        {{ $order->payment_status=='paid' ? 'checked':'unchecked'}}"></span>
                                </label>
                            </div>
                        </div>
                        @if($physicalProduct || $shippingAddress)
                            <ul class="list-unstyled list-unstyled-py-4 d-flex flex-column gap-4 mb-0 pe-0">
                                <li class="">
                                    @if ($order->shipping_type == 'order_wise')
                                        <label class="form-label fw-bold mb-2">
                                            {{translate('Shipping Method')}}
                                        </label>
                                    @endif
                                    <div class="select-wrapper"
                                         @if($disableDeliveryType)
                                             data-bs-toggle="tooltip"
                                         data-bs-placement="top"
                                         title="{{ translate('You cannot select delivery type as the order only contains digital products') }}"
                                        @endif
                                    >
                                        <select class="form-select"
                                                name="delivery_type"
                                                id="choose_delivery_type"
                                            {{ ($order->order_status == 'delivered' || $disableDeliveryType) ? 'disabled' : '' }}>
                                            <option value="0">
                                                {{ translate('Choose delivery type') }}
                                            </option>
                                            <option value="self_delivery"
                                                {{ $order->delivery_type == 'self_delivery' ? 'selected' : '' }}>
                                                {{ translate('By self delivery man') }}
                                            </option>
                                            @if ($deliveryPartner['is_enabled'] || $deliveryPartner['is_assigned'])
                                                <option value="third_party_delivery" {{ $deliveryPartner['is_assigned'] ? 'selected' : '' }}>
                                                    {{ translate('Current delivery partner') }}
                                                </option>
                                            @endif
                                        </select>
                                    </div>
                                </li>

                                @if(!$disableDeliveryType)
                                    <li class="choose_delivery_man">
                                        <label class="form-label fw-bold mb-2">
                                            {{translate('Delivery Man')}}
                                        </label>
                                        <select class="custom-select"
                                                name="delivery_man_id"
                                                id="addDeliveryMan"
                                                data-order-id="{{$order['id']}}"
                                                data-placeholder="{{ translate('Select Deliveryman') }}"
                                            {{ $order->order_status == 'delivered' ? 'disabled' : '' }}
                                        >
                                            <option value="" readonly>--{{ translate('Select Deliveryman') }}--</option>
                                            @foreach($deliveryMen as $deliveryMan)
                                                <option
                                                    value="{{$deliveryMan['id']}}" {{$order['delivery_man_id']==$deliveryMan['id']?'selected':''}}>
                                                    {{$deliveryMan['f_name'].' '.$deliveryMan['l_name'].' ('.(isset($deliveryMan['country_code']) ? $deliveryMan['country_code'] : '').$deliveryMan['phone'].' )'}}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (isset($order->deliveryMan))
                                            <div class="p-2 bg-section rounded mt-4">
                                                <div class="media m-1 gap-3">
                                                    <img class="avatar rounded-circle"
                                                         src="{{ getStorageImages(path: $order->deliveryMan?->image_full_url, type: 'backend-profile') }}"
                                                         alt="{{translate('Image')}}">
                                                    <div class="media-body">
                                                        <h5 class="mb-1">{{ $order->deliveryMan?->f_name.' '.$order->deliveryMan?->l_name}}</h5>
                                                        <a href="tel:{{$order->deliveryMan?->phone}}"
                                                           class="fs-12 text-dark">{{ (isset($deliveryMan['country_code']) ? $deliveryMan['country_code'] : '') . ($order->deliveryMan?->phone ?? '') }}</a>
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
                                        <li class="choose_delivery_man">
                                            <label class="form-label fw-semibold">
                                                {{translate('Delivery man incentive')}} ({{ getCurrencySymbol() }})
                                                <span class="tooltip-icon cursor-pointer"
                                                      data-bs-toggle="tooltip"
                                                      data-bs-placement="top"
                                                      aria-label="{{translate('Encourage your delivery man by giving an incentive').' '.translate('This amount will be counted as an admin expense').'.'}}"
                                                      data-bs-title="{{translate('Encourage your delivery man by giving an incentive').' '.translate('This amount will be counted as an admin expense').'.'}}">
                                                  <i class="fi fi-sr-info"></i>
                                            </span>
                                            </label>
                                            <div class="d-flex gap-2 align-items-center">
                                                <input type="number"
                                                       value="{{ usdToDefaultCurrency(amount: $order->deliveryman_charge) }}"
                                                       name="deliveryman_charge" data-order-id="{{$order['id']}}"
                                                       class="form-control" placeholder="{{translate('Ex').': 20'}}"
                                                       {{$order['order_status']=='delivered' ? 'readonly':''}} required>
                                                <button
                                                    class="btn btn-primary h-40 {{$order['order_status']=='delivered' ? 'disabled deliveryman-charge-alert':'deliveryman-charge'}}">{{translate('Update')}}</button>
                                            </div>
                                        </li>
                                        <li class="choose_delivery_man">
                                            <label
                                                class="form-label fw-semibold">{{translate('Expected delivery date')}}</label>
                                            <input type="date" data-order-id="{{$order['id']}}"
                                                   value="{{ $order->expected_delivery_date }}"
                                                   name="expected_delivery_date" id="expected_delivery_date"
                                                   class="form-control set-today-date-minimum" {{ $order->order_status == 'delivered' ? 'disabled' : 'required' }}>
                                        </li>
                                    @endif
                                    <li class="mt-1" id="by_third_party_delivery_service_info">
                                        @if ($deliveryPartner['is_service_available'])
                                            @include('courier::admin.delivery-partner-selection', ['courierOrder' => [
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
                                            <div class="p-2 bg-section rounded">
                                                <div class="media overflow-hidden m-1 gap-3">
                                                    <img class="avatar rounded-circle"
                                                         src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/third-party-delivery.png')}}"
                                                         alt="{{translate('Image')}}">
                                                    <div class="media-body w-100">
                                                        <h5 class="">{{$order->delivery_service_name ?? translate('Not assign yet')}}</h5>
                                                        <span
                                                            class="fs-12 text-dark text-wrap d-block">{{translate('Track ID').' '.':'.' '.$order->third_party_delivery_tracking_id}}</span>
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
                    @include('courier::admin.send-to-courier', ['courierOrder' => [
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
                                        src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/vendor-information.png')}}"
                                        alt="">
                                    {{translate('Shipping address')}}
                                </h4>
                                @if($order['order_status'] != 'delivered')
                                    <button
                                        class="btn btn-outline-primary icon-btn"
                                        title="Edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#shippingAddressUpdateModal">
                                        <i class="fi fi-sr-pencil" style="font-size: 15px;"></i>
                                    </button>
                                @endif
                            </div>
                            <table class="overflow-wrap-anywhere">
                                <tbody>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Name')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2">
                                                <span class="fw-semibold">
                                                {{$shippingAddress->contact_person_name}}
                                                </span>
                                        {{ $order->is_guest ? '('. translate('Guest customer') .')':''}}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Contact')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="fw-semibold px-0 py-2">{{$shippingAddress->phone}}</td>
                                </tr>
                                @if ($order->is_guest && $shippingAddress->email)
                                    <tr>
                                        <td class="px-0 py-2 text-nowrap">{{translate('Email')}}</td>
                                        <td class="px-3 py-2">:</td>
                                        <td class="px-0 py-2" class="fw-semibold">{{$shippingAddress->email}}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Country')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2" class="fw-semibold">{{$shippingAddress->country}}</td>
                                </tr>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('City')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2" class="fw-semibold">{{$shippingAddress->city}}</td>
                                </tr>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Zip Code')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="fw-semibold px-0 py-2">{{$shippingAddress->zip}}</td>
                                </tr>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{ translate('Address') }}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2">
                                        <div class="d-flex align-items-start gap-2">
                                            <span>{{$shippingAddress->address  ?? translate('Empty')}}</span>
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

                @if ($mapApiStatus == 1 && isset($shippingAddress?->latitude) && isset($shippingAddress?->longitude))
                    <div class="card card-body">
                        <div class="d-flex justify-content-between gap-3 align-items-center flex-wrap">
                            <h3 class="mb-0">{{ translate('Shipping Location') }}</h3>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#locationModal">{{ translate('On Map') }}</button>
                        </div>
                    </div>
                @endif
                @if(!empty((array) $billing))
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex gap-2 align-items-center justify-content-between mb-3">
                                <h4 class="d-flex gap-2 fs-14 fw-bold mb-0">
                                    <img
                                        src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/vendor-information.png')}}"
                                        alt="">
                                    {{translate('Billing address')}}
                                </h4>
                                @if($order['order_status'] != 'delivered')
                                    <button
                                        class="btn btn-outline-primary icon-btn"
                                        title="{{ translate('Edit') }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#billingAddressUpdateModal">
                                        <i class="fi fi-sr-pencil" style="font-size: 15px;"></i>
                                    </button>
                                @endif
                            </div>
                            @if($isBillingSameAsShipping)
                                <p class="mb-0 fw-semibold">{{ translate('Same as shipping address') }}</p>
                            @else
                            <table class="overflow-wrap-anywhere">
                                <tbody>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Name')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2">
                                        <span class="fw-semibold">
                                            {{$billing->contact_person_name}}
                                        </span>
                                        {{ $order->is_guest ? '('. translate('Guest customer') .')':''}}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Contact')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2 fw-semibold">{{$billing->phone}}</td>
                                </tr>
                                @if ($order->is_guest && $billing->email)
                                    <tr>
                                        <td class="px-0 py-2 text-nowrap">{{translate('Email')}}</td>
                                        <td class="px-3 py-2">:</td>
                                        <td class="px-0 py-2 fw-semibold">{{$billing->email}}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Country')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2 fw-semibold">{{$billing->country}}</td>
                                </tr>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('City')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="px-0 py-2 fw-semibold">{{$billing->city}}</td>
                                </tr>
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{translate('Zip Code')}}</td>
                                    <td class="px-3 py-2">:</td>
                                    <td class="fw-semibold">{{$billing->zip}}</td>
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
                                        src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/vendor-information.png')}}"
                                        alt="">
                                    {{translate('Customer Information')}}
                                </h4>
                            </div>

                            <div class="media flex-wrap gap-3 gap-sm-4">
                                <a class="d-block"
                                   href="{{ route('admin.people.customer.view', ['user_id' => $order->customer['id']]) }}"
                                   target="_blank">
                                    <img class="avatar rounded-circle avatar-70 object-fit-cover"
                                         src="{{ getStorageImages(path: $order->customer->image_full_url , type: 'backend-basic') }}"
                                         alt="{{translate('Image')}}">
                                </a>
                                <div class="media-body d-flex flex-column gap-1">
                                    <a class="text-dark"
                                       href="{{ route('admin.people.customer.view', ['user_id' => $order->customer['id']]) }}"
                                       target="_blank">
                                        <span
                                            class="fw-semibold">{{ $order->customer['f_name'].' '.$order->customer['l_name']}} </span>
                                    </a>


                                    @if($order?->customer?->email !== 'walking@customer.com')
                                        <span class="text-dark fs-12"> <span class="fw-bold">{{ $orderCount }}</span> {{translate('Orders')}}</span>
                                        <span class="text-dark break-all fs-12">
                                        <a href="tel:{{ $order->customer['phone'] }}"
                                           class="fw-semibold text-dark text-decoration-none">
                                            {{ $order->customer['phone'] }}
                                        </a>
                                    </span>
                                        <span class="text-dark break-all fs-12">
                                        <a href="mailto:{{ $order->customer['email'] }}"
                                           class="text-dark text-decoration-none">
                                            {{ $order->customer['email'] }}
                                        </a>
                                    </span>
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
                                <a class="d-block" target="_blank"
                                   href="{{ route('admin.settings.business-settings.inhouse-shop') }}">
                                    <img class="avatar rounded avatar-70 img-fit-contain "
                                         src="{{ getStorageImages(path: $inHouseShopImage, type: 'shop') }}"
                                         alt="">
                                </a>
                                <div class="media-body d-flex flex-column gap-2">
                                    <a class="fs-14 fw-semibold mb-0 text-dark hover-c1"
                                       href="{{ route('admin.settings.business-settings.inhouse-shop') }}"
                                       target="_blank">{{ $inHouseShopName }}</a>
                                    <span class="text-dark fs-12"><strong>{{ $totalDelivered }}</strong> {{translate('Orders Served')}}</span>
                                </div>
                            @else
                                @if(!empty($order->seller->shop))
                                    <a class="d-block" target="_blank"
                                       href="{{ route('admin.people.vendors.view', ['id' => $order->seller->id]) }}">
                                        <img class="avatar rounded avatar-70 img-fit"
                                             src="{{ getStorageImages(path:$order->seller->shop->image_full_url , type: 'backend-basic') }}"
                                             alt="">
                                    </a>
                                    <div class="media-body d-flex flex-column gap-2">
                                        <div class="d-flex align-items-center gap-1">
                                            <a class="fs-14 fw-semibold mb-0 text-dark hover-c1" target="_blank"
                                               href="{{ route('admin.people.vendors.view', ['id' => $order->seller->id]) }}">
                                                {{ $order->seller->shop->name }}
                                            </a>
                                            @include('admin-views.vendor.partials._verified-badge', ['isVerified' => $order->seller->hasVerifiedBadge(), 'size' => 16])
                                        </div>
                                        <span class="text-dark fs-12"><strong>{{ $totalDelivered }}</strong> {{translate('Orders Served')}}</span>
                                        <span class="text-dark fs-12">
                                        <a class="text-decoration-none text-dark" href="tel:{{ $order->seller->shop->contact }}">
                                            <strong>{{ $order->seller->shop->contact }}</strong>
                                        </a>
                                    </span>
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

    @if ($order->verificationImages && count($order->verificationImages)>0)
        <div class="modal fade" id="order_verification_modal" tabindex="-1" aria-labelledby="order_verification_modal"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header d-flex justify-content-between align-items-center pb-4">
                        <h3 class="mb-0">{{translate('Order verification images')}}</h3>
                        <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                data-bs-dismiss="modal" aria-label="Close">
                        </button>
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
                                                data-bs-dismiss="modal">{{translate('Close')}}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(!empty((array) $shippingAddress))
        <div class="modal fade" id="shippingAddressUpdateModal" tabindex="-1"
             aria-labelledby="shippingAddressUpdateModal"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-4 d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">{{translate('Shipping address')}}</h3>
                        <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 px-sm-5 pt-0">
                        <form action="{{route('admin.retail.orders.address-update')}}" method="post"
                              id="shippingAddressUpdateForm">
                            @csrf
                            <div class="d-flex flex-column align-items-center gap-2">
                                <input name="address_type" value="shipping" hidden>
                                <input name="order_id" value="{{$order->id}}" hidden>
                                <div class="row gx-3 gy-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name"
                                                   class="form-label">{{translate('Contact Person Name')}}</label>
                                            <input type="text" name="name" id="name" class="form-control"
                                                   value="{{$shippingAddress? $shippingAddress->contact_person_name : ''}}"
                                                   placeholder="{{ translate('Ex') }}: John Doe"
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone_number"
                                                   class="form-label">{{translate('Phone number')}}</label>
                                            <input class="form-control form-control-user"
                                                   type="tel"
                                                   value="{{$shippingAddress ? $shippingAddress->phone  : ''}}"
                                                   placeholder="{{ translate('Ex').': 017xxxxxxxx' }}"
                                                   name="phone_number"
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="country" class="form-label">{{translate('Country')}}</label>
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
                                            <label for="city" class="form-label">{{translate('City')}}</label>
                                            <input type="text" name="city" id="city"
                                                   value="{{$shippingAddress ? $shippingAddress->city : ''}}"
                                                   class="form-control"
                                                   placeholder="{{ translate('Ex') }}: Dhaka" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="zip_code" class="form-label">{{translate('Zip')}}</label>
                                            @if($zipRestrictStatus == 1)
                                                <select name="zip" class="form-control" data-live-search="true"
                                                        required>
                                                    @forelse($zipCodes as $code)
                                                        <option
                                                            value="{{ $code->zipcode }}"{{ isset($shippingAddress) && $code->zipcode == $shippingAddress->zip ? 'selected'  : ''}}>{{ $code->zipcode }}</option>
                                                    @empty
                                                        <option value="">{{ translate('No zip to deliver') }}</option>
                                                    @endforelse
                                                </select>
                                            @else
                                                <input type="text" class="form-control"
                                                       value="{{$shippingAddress ? $shippingAddress->zip  : ''}}"
                                                       id="zip"
                                                       name="zip"
                                                       placeholder="{{ translate('Ex') }}: 1216" {{$shippingAddress?'required':''}}>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="address" class="form-label">{{translate('Address')}}</label>
                                            <textarea name="address" id="address" name="address" rows="3"
                                                      class="form-control"
                                                      placeholder="{{ translate('Ex') }}: Street 1, street 2, street 3, street 4">{{$shippingAddress ? $shippingAddress->address : ''}}</textarea>
                                        </div>
                                    </div>
                                    <input type="hidden" id="latitude"
                                           name="latitude" class="form-control d-inline"
                                           placeholder="{{ translate('Ex') }} : -94.22213"
                                           value="{{$shippingAddress->latitude ?? 0}}" required readonly>
                                    <input type="hidden"
                                           name="longitude" class="form-control"
                                           placeholder="{{ translate('Ex') }} : 103.344322" id="longitude"
                                           value="{{$shippingAddress->longitude ??0}}" required readonly>
                                    @if($mapApiStatus ==1 )
                                        <div class="col-12 ">
                                            <input id="pac-input" class="form-control rounded w-200 mt-1"
                                                   title="{{translate('Search your location here')}}" type="text"
                                                   placeholder="{{translate('Search here')}}"/>
                                            <div class="dark-support rounded w-100 h-200 mb-2"
                                                 id="location_map_canvas_shipping"></div>
                                        </div>
                                    @endif
                                    <div class="col-12">
                                        <div class="d-flex justify-content-end gap-3">
                                            <button type="button" class="btn btn-secondary px-5"
                                                    data-bs-dismiss="modal">{{translate('Cancel')}}</button>
                                            <button type="submit"
                                                    class="btn btn-primary px-5">{{translate('Update')}}</button>
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
            <div class="modal fade" id="courierAddressChangeModal" tabindex="-1"
                 aria-labelledby="courierAddressChangeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold"
                                id="courierAddressChangeModalLabel">{{translate('Update the delivery address')}}?</h5>
                            <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                    data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">{{translate('Changing the address here does not update it with the delivery partner.')}}</p>
                            <div class="alert alert-warning fs-12 mb-0">
                                {{translate('Correct the address with your provider, or contact the delivery partner.')}}
                            </div>
                        </div>
                        <div class="modal-footer border-0 d-flex gap-3">
                            <button type="button" class="btn btn-secondary flex-fill"
                                    data-bs-dismiss="modal">{{translate('Cancel')}}</button>
                            <button type="button" class="btn btn-primary flex-fill"
                                    id="courier-address-change-confirm">{{translate('Confirm and Update')}}</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                (function () {
                    const form = document.getElementById('shippingAddressUpdateForm');
                    const modal = document.getElementById('courierAddressChangeModal');
                    const confirmBtn = document.getElementById('courier-address-change-confirm');
                    if (!form || !modal || !confirmBtn) return;

                    form.addEventListener('submit', function (event) {
                        if (!form.reportValidity()) return;
                        event.preventDefault();
                        bootstrap.Modal.getOrCreateInstance(modal).show();
                    });

                    confirmBtn.addEventListener('click', function () {
                        bootstrap.Modal.getOrCreateInstance(modal).hide();
                        form.submit();
                    });
                })();
            </script>
        @endif
    @endif

    @if($billing)
        <div class="modal fade" id="billingAddressUpdateModal" tabindex="-1" aria-labelledby="billingAddressUpdateModal"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-4 d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">{{translate('Billing address')}}</h3>
                        <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 px-sm-5 pt-0">
                        <div class="d-flex flex-column align-items-center gap-2">
                            <form action="{{route('admin.retail.orders.address-update')}}" method="post">
                                @csrf
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <input name="address_type" value="billing" hidden>
                                    <input name="order_id" value="{{$order->id}}" hidden>
                                    <div class="row gx-3 gy-4">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name"
                                                       class="form-label">{{translate('Contact Person Name')}}</label>
                                                <input type="text" name="name" id="name" class="form-control"
                                                       value="{{$billing? $billing->contact_person_name : ''}}"
                                                       placeholder="{{ translate('Ex') }}: John Doe"
                                                       required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="phone_number"
                                                       class="form-label">{{translate('Phone number')}}</label>
                                                <input class="form-control form-control-user"
                                                       type="tel" value="{{$billing ? $billing->phone  : ''}}"
                                                       placeholder="{{ translate('Ex').': 017xxxxxxxx' }}"
                                                       name="phone_number" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="country"
                                                       class="form-label">{{translate('Country')}}</label>
                                                <select name="country" id="country" class="form-control">
                                                    @forelse($countries as $country)
                                                        <option
                                                            value="{{ $country['name'] }}" {{ isset($billing) && $country['name'] == $billing->country ? 'selected'  : ''}}>{{ $country['name'] }}</option>
                                                    @empty
                                                        <option
                                                            value="">{{ translate('No country to deliver') }}</option>
                                                    @endforelse
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="city" class="form-label">{{translate('City')}}</label>
                                                <input type="text" name="city" id="city"
                                                       value="{{$billing ? $billing->city : ''}}" class="form-control"
                                                       placeholder="{{ translate('Ex') }}: Dhaka"
                                                       required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="zip_code" class="form-label">{{translate('Zip')}}</label>
                                                @if($zipRestrictStatus == 1)
                                                    <select name="zip" class="form-control" data-live-search="true"
                                                            required>
                                                        @forelse($zipCodes as $code)
                                                            <option
                                                                value="{{ $code->zipcode }}"{{ isset($billing) && $code->zipcode == $billing->zip ? 'selected'  : ''}}>{{ $code->zipcode }}</option>
                                                        @empty
                                                            <option
                                                                value="">{{ translate('No zip to deliver') }}</option>
                                                        @endforelse
                                                    </select>
                                                @else
                                                    <input type="text" class="form-control"
                                                           value="{{$billing ? $billing->zip  : ''}}" id="zip"
                                                           name="zip"
                                                           placeholder="{{ translate('Ex').': 1216' }}" {{$billing?'required':''}}>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="address"
                                                       class="form-label">{{translate('Address')}}</label>
                                                <textarea name="address" id="billing_address" rows="3"
                                                          class="form-control"
                                                          placeholder="{{ translate('Ex') }}: Street 1, street 2, street 3, street 4">{{$billing ? $billing->address : ''}}</textarea>
                                            </div>
                                        </div>
                                        <input type="hidden" id="billing_latitude"
                                               name="latitude" class="form-control d-inline"
                                               placeholder="{{ translate('Ex') }} : -94.22213"
                                               value="{{$billing->latitude ?? 0}}" required readonly>
                                        <input type="hidden"
                                               name="longitude" class="form-control"
                                               placeholder="{{ translate('Ex') }} : 103.344322" id="billing_longitude"
                                               value="{{$billing->longitude ?? 0}}" required readonly>
                                        @if($mapApiStatus ==1 )
                                            <div class="col-12 ">
                                                <input id="billing-pac-input" class="form-control rounded w-200 mt-1"
                                                       title="{{translate('Search your location here')}}" type="text"
                                                       placeholder="{{translate('Search here')}}"/>
                                                <div class="rounded w-100 h-200 mb-2"
                                                     id="location_map_canvas_billing"></div>
                                            </div>
                                        @endif
                                        <div class="col-12">
                                            <div class="d-flex justify-content-end gap-3">
                                                <button type="button" class="btn btn-secondary px-5"
                                                        data-bs-dismiss="modal">{{translate('Cancel')}}</button>
                                                <button type="submit"
                                                        class="btn btn-primary px-5">{{translate('Update')}}</button>
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

    <div class="modal fade" id="locationModal" tabindex="-1" role="dialog" aria-labelledby="locationModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <h3 class="modal-title text-center flex-grow-1"
                        id="locationModalLabel">{{translate('Location on Map')}}</h3>
                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                            data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <div>
                        <div class="row">
                            <div class="col-md-12 rounded border p-3">
                                <div class="h3 text-cyan-blue text-center">{{ translate('Order') }}
                                    #{{ $order->id }}</div>
                                <ul class="nav nav-tabs border-0 media-tabs nav-justified order-track-info">
                                    <li class="nav-item">
                                        <div class="nav-link active-status">
                                            <div class="d-flex flex-sm-column gap-3 gap-sm-0">
                                                <div class="media-tab-media mx-sm-auto mb-3">
                                                    <img
                                                        src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/track-order/order-placed.png') }}"
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
                                                    class="nav-link {{ ($order['order_status']=='confirmed') || ($order['order_status']=='processing') || ($order['order_status']=='processed') || ($order['order_status']=='out_for_delivery') || ($order['order_status']=='delivered')?'active-status' : ''}}">
                                                    <div class="d-flex flex-sm-column gap-3 gap-sm-0">
                                                        <div class="media-tab-media mb-3 mx-sm-auto">
                                                            <img
                                                                src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/track-order/order-confirmed.png') }}"
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
                                                                    {{ date('h:i A, d M Y',strtotime(($statusHistoryDates['confirmed'] ?? null))) }}
                                                                </span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                            <li class="nav-item">
                                                <div
                                                    class="nav-link {{ ($order['order_status']=='processing') || ($order['order_status']=='processed') || ($order['order_status']=='out_for_delivery') || ($order['order_status']=='delivered')?'active-status' : ''}}">
                                                    <div class="d-flex flex-sm-column gap-3 gap-sm-0">
                                                        <div class="media-tab-media mb-3 mx-sm-auto">
                                                            <img alt=""
                                                                 src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/track-order/shipment.png') }}">
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
                                                    class="nav-link {{ ($order['order_status']=='out_for_delivery') || ($order['order_status']=='delivered')?'active-status' : ''}}">
                                                    <div class="d-flex flex-sm-column gap-3 gap-sm-0">
                                                        <div class="media-tab-media mb-3 mx-sm-auto">
                                                            <img
                                                                src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/track-order/on-the-way.png') }}"
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
                                                    class="nav-link {{ ($order['order_status']=='delivered')?'active-status' : ''}}">
                                                    <div class="d-flex flex-sm-column gap-3 gap-sm-0">
                                                        <div class="media-tab-media mb-3 mx-sm-auto">
                                                            <img
                                                                src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/track-order/delivered.png') }}"
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
                                                                 src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/track-order/shipment.png') }}">
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
                                                    class="nav-link {{ ($order['order_status']=='confirmed' && $digitalProductProcessComplete)?'active-status' : ''}}">
                                                    <div class="d-flex flex-sm-column gap-3 gap-sm-0">
                                                        <div class="media-tab-media mb-3 mx-sm-auto">
                                                            <img
                                                                src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/track-order/delivered.png') }}"
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
                                                            src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/track-order/'.$order['order_status'].'.png') }}"
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
                                                            src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/track-order/order-failed.png') }}"
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

                            @if(!empty((array) $shippingAddress))
                                <div class="col-md-12 modal_body_map mt-5 pl-0 pr-0">
                                    <div class="mb-2">
                                        <img src="{{ dynamicAsset('assets/new/back-end/img/location-blue.png') }}"
                                             alt="">
                                        <span>{{ $shippingAddress ? $shippingAddress->address : ($billing ? $billing->address : '') }}</span>
                                    </div>
                                    @if($mapApiStatus ==1 )
                                        <div class="location-map" id="location-map">
                                            <div class="w-100 h-200" id="location_map_canvas"></div>
                                        </div>
                                    @endif
                                </div>
                            @endif

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
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn btn-circle border-0 fs-12 text-body bg-section2 shadow-none"
                            style="--size: 2rem;" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fi fi-sr-cross"></i>
                    </button>
                </div>
                <div class="modal-body px-20 py-0 mb-30">
                    <div class="d-flex flex-column align-items-center text-center mb-30">
                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/modal/warning-new.png') }}"
                             width="70" class="aspect-1 mb-20" id="" alt="">
                        <h2 class="modal-title mb-3" id="">{{ translate('Are you sure') }}?</h2>
                        <div class="text-center" id="">
                            {{ translate('Update this order status? The customer is notified. This is final.') }}
                        </div>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-primary min-w-120"
                                data-bs-dismiss="modal">{{ translate('Yes change it!') }}</button>
                        <button type="button" class="btn btn-danger min-w-120"
                                data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade pt-5" id="quick-view" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" id="quick-view-modal"></div>
        </div>
    </div>

    @include('admin-views.order.partials.modal.order-edit-due-amount-mark-as-paid', ['order' => $order])
    @include('admin-views.order.partials.modal.order-edit-due-amount-switch-to-cod', ['order' => $order])
    @include('admin-views.order.partials.modal.order-edit-return-amount-modal', ['order' => $order])
    @include('admin-views.order.partials.modal._confirm-edit-order')
    @include('admin-views.order.partials.offcanvas._edit-products-offcanvas', ['order' => $order])

    <span id="message-status-title-text"
          data-text="{{$order['payment_method'] != 'cash_on_delivery' && $order['order_status']=='delivered' ? translate('This order is delivered and paid. Changing status may cause miscalculation.') : translate('Are you sure change this') }}"></span>
    <span id="message-status-subtitle-text"
          data-text="{{ $order['payment_method'] != 'cash_on_delivery' && $order['order_status']=='delivered' ? translate('Think before you proceed') : translate('You will not be able to revert this') }}!"></span>
    <span id="payment-status-message" data-title="{{translate('Confirm payments before changing the status').'.'}}"
          data-message="{{ translate('Mark as Paid only after you receive and verify the payment.') }}"></span>
    <span id="message-status-confirm-text" data-text="{{ translate('Yes change it!') }}!"></span>
    <span id="message-status-cancel-text" data-text="{{ translate('Cancel') }}"></span>
    <span id="message-status-success-text" data-text="{{ translate(':item changed successfully', ['item' => translate('Status')]) }}"></span>
    <span id="message-status-warning-text"
          data-text="{{ translate('Account has been deleted, you cannot change the status') }}"></span>
    <span id="message-order-status-delivered-text"
          data-text="{{ translate('Order is already delivered you cannot change it') }}!"></span>
    <span id="message-order-status-paid-first-text"
          data-text="{{ translate('Before delivery you need to set the payment status to paid') }}!"></span>
    <span id="order-status-url" data-url="{{route('admin.retail.orders.status')}}"></span>
    <span id="payment-status-url" data-url="{{ route('admin.retail.orders.payment-status') }}"></span>

    <span id="message-deliveryman-add-success-text"
          data-text="{{ translate('Delivery man assigned/changed successfully') }}"></span>
    <span id="message-deliveryman-add-error-text"
          data-text="{{ translate('Delivery man cannot be assigned or changed in that status') }}"></span>
    <span id="message-deliveryman-add-invalid-text"
          data-text="{{ translate('Delivery man cannot be assigned or changed in that status') }}"></span>
    <span id="delivery-type" data-type="{{ $deliveryPartner['is_assigned'] ? 'third_party_delivery' : $order->delivery_type }}"></span>
    <span id="add-delivery-man-url" data-url="{{url('/admin/retail/orders/add-delivery-man/'.$order['id'])}}/"></span>

    <span id="message-deliveryman-charge-success-text"
          data-text="{{ translate(':item added successfully', ['item' => translate('Deliveryman charge')]) }}"></span>
    <span id="message-deliveryman-charge-error-text"
          data-text="{{ translate('Failed to add deliveryman charge') }}"></span>
    <span id="message-deliveryman-charge-invalid-text" data-text="{{ translate('Add valid data') }}"></span>
    <span id="add-date-update-url" data-url="{{route('admin.retail.orders.amount-date-update')}}"></span>

    <span id="customer-name" data-text="{{$order->customer['f_name']??""}} {{$order->customer['l_name']??""}}}"></span>
    <span id="is-shipping-exist" data-status="{{$shippingAddress ? 'true':'false'}}"></span>
    <span id="shipping-address" data-text="{{$shippingAddress->address??''}}"></span>
    <span id="shipping-latitude" data-latitude="{{$shippingAddress->latitude??'-33.8688'}}"></span>
    <span id="shipping-longitude" data-longitude="{{$shippingAddress->longitude??'151.2195'}}"></span>
    <span id="billing-latitude" data-latitude="{{$billing->latitude??'-33.8688'}}"></span>
    <span id="billing-longitude" data-longitude="{{$billing->longitude??'151.2195'}}"></span>
    <span id="location-icon"
          data-path="{{ dynamicAsset(path: 'public/assets/front-end/img/customer_location.png')}}"></span>
    <span id="customer-image"
          data-path="{{dynamicStorage(path: 'storage/app/public/profile/')}}{{$order->customer->image??""}}"></span>
    <span id="deliveryman-charge-alert-message"
          data-message="{{translate('When the order status is delivered you can\'t update the delivery man incentive').'.'}}"></span>
    <span id="payment-status-alert-message"
          data-message="{{translate('A paid payment status cannot be changed back to unpaid.').'.'}}"></span>
    <span id="amount-cannot-be-empty-message"
          data-message="{{translate('Amount cannot be empty or zero').'.'}}"></span>

    <span id="get-search-product-for-edit-order" data-action="{{ route('admin.retail.orders.search-for-edit-order-product') }}"
          data-order-id="{{ $order['id'] }}"></span>
    <span id="edit-order-product-modal-view"
          data-action="{{ route('admin.retail.orders.edit-order-product-modal-view') }}"></span>
@endsection

@push('script')
    @if($mapApiStatus == 1)
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{ $mapApiKey }}&callback=mapCallBackFunction&loading=async&libraries=places&v=3.56"
            defer>
        </script>
    @endif

    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf-worker.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/multiple-document-upload.js') }}"></script>

    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/order.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/order-edit.js') }}"></script>
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
