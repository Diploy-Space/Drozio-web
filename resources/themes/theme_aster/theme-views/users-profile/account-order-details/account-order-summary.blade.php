@extends('theme-views.layouts.profile')
@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/front-end/css/payment.css') }}">
@endpush
@section('title', translate('Order details').' | '.$web_config['company_name'].' '.translate('eCommerce'))
@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-5">
        <div class="container">
            <div class="row g-3">
                @include('theme-views.partials._profile-aside')
                <div class="col-lg-9">
                    <div class="card h-100">
                        <div class="card-body p-lg-4">
                            @include('theme-views.users-profile.account-order-details._order-details-head',['order'=>$order])
                            @include('theme-views.layouts.partials.modal._refund-request-list-modal')
                            <div class="mt-4 card">
                                <div class="card-body">
                                    @php($showVerificationCode = $order->order_type == 'default_type' && getWebConfig(name: 'order_verification') && $order['order_status'] != 'delivered')
                                    @php($shipping = is_string($order->shipping_address_data) ? json_decode($order->shipping_address_data) : $order->shipping_address_data)
                                    @php($billing = is_string($order->billing_address_data) ? json_decode($order->billing_address_data) : $order->billing_address_data)
                                    @php($isSameAddress = $billing && $shipping && $billing->address == $shipping->address && $billing->city == $shipping->city && $billing->zip == $shipping->zip)
                                    <div class="row g-3 mb-2">
                                        @if(($order['payment_method'] == 'cash_on_delivery' || $order?->latestEditHistory?->order_due_payment_method == 'cash_on_delivery') && $order['bring_change_amount'] > 0)
                                            <div class="col-12">
                                                <div class="__badge soft-primary py-2 fs-14 text-dark rounded w-100">
                                                    {{ translate('Please bring') }}
                                                    <strong> {{ $order['bring_change_amount'] }} {{ $order['bring_change_amount_currency'] ?? '' }}</strong> {{ translate('In change when making the delivery') }}
                                                </div>
                                            </div>
                                        @endif

                                        <div class="{{ $showVerificationCode ? 'col-md-6' : 'col-12' }}">
                                            <div class="section-bg-cmn rounded-2 py-3 px-3 h-100">
                                                <h5 class="text-capitalize fs-16 mb-3">{{ translate('Order info') }}</h5>
                                                <div class="d-flex flex-column gap-2">
                                                    <div class="fs-12 d-flex justify-content-start gap-2">
                                                        <span class="text-muted text-capitalize">{{ translate('Order Type') }} :</span>
                                                        <span class="text-primary fw-semibold">{{ $order->order_type == 'POS' ? translate('POS order') : translate('Retail order') }}</span>
                                                    </div>
                                                    <div class="fs-12 d-flex justify-content-start gap-2">
                                                        <span class="text-muted text-capitalize">{{ translate('Payment status') }} :</span>
                                                        @if($order->edited_status == 1 && $order->edit_due_amount > 0 && $order->latestEditHistory->order_due_payment_method != 'cash_on_delivery' && $order->latestEditHistory->order_due_payment_status == 'unpaid')
                                                            <span class="text-success text-capitalize fw-semibold">{{ translate('Partially Paid') }}</span>
                                                        @else
                                                            <span class="text-{{ $order['payment_status'] == 'paid' ? 'success' : 'danger' }} text-capitalize fw-semibold">{{ translate($order['payment_status']) }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="fs-12 d-flex justify-content-start gap-2">
                                                        <span class="text-muted text-capitalize">{{ translate('Payment method') }} :</span>
                                                        <span class="text-dark text-capitalize fw-semibold">{{ translate($order['payment_method']) }}</span>
                                                    </div>
                                                    @if($order->payment_method == 'offline_payment' && isset($order->offlinePayments))
                                                        <div>
                                                            <button type="button" class="btn btn-outline-primary btn-sm rounded-10 fs-12 fw-medium"
                                                                    data-bs-toggle="modal" data-bs-target="#verificationModal">
                                                                {{ translate('See payment details') }}
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        @if($showVerificationCode)
                                            <div class="col-md-6">
                                                <div class="section-bg-cmn rounded-2 py-3 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 h-100">
                                                    <h6 class="mb-0">{{ translate('Order verification code') }}</h6>
                                                    <h3 class="text-primary mb-0">{{ $order['verification_code'] }}</h3>
                                                </div>
                                            </div>
                                        @endif

                                        @if($order->edited_status == 1 && ($order?->latestEditHistory?->order_due_payment_method == 'offline_payment' || $order?->latestEditHistory?->order_due_payment_method == 'cash_on_delivery' || $order?->latestEditHistory?->order_due_payment_status == 'paid'))
                                            <div class="col-12">
                                                <div class="section-bg-cmn rounded-2 py-3 px-3 h-100">
                                                    <h6 class="text-capitalize mb-3">
                                                        {{ translate('Another Payment Info') }}
                                                        <span class="text-{{ $order?->latestEditHistory?->order_due_payment_status == 'paid' ? 'success' : 'danger' }} fs-12 fw-semibold">{{ translate($order?->latestEditHistory?->order_due_payment_status) }}</span>
                                                    </h6>
                                                    <div class="d-flex flex-column gap-2">
                                                        <div class="fs-12 d-flex justify-content-start gap-2">
                                                            <span class="text-muted text-capitalize">{{ translate('Payment Method') }} :</span>
                                                            <span class="text-dark text-capitalize fw-semibold">{{ translate($order?->latestEditHistory?->order_due_payment_method) }}</span>
                                                        </div>
                                                        <div class="fs-12 d-flex justify-content-start gap-2">
                                                            <span class="text-muted text-capitalize">{{ translate('Due amount') }} :</span>
                                                            <span class="text-dark fw-semibold">{{ webCurrencyConverter(amount: $order?->latestEditHistory?->order_due_amount ?? 0) }}</span>
                                                        </div>
                                                        @if($order?->latestEditHistory?->order_due_payment_method == 'offline_payment' && !empty($order?->latestEditHistory?->order_due_payment_info))
                                                            <div>
                                                                <button type="button" class="btn btn-outline-primary btn-sm rounded-10 fs-12 fw-medium"
                                                                        data-bs-toggle="modal" data-bs-target="#orderDuePaymentInfoModal">
                                                                    {{ translate('See payment details') }}
                                                                </button>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif($order->edited_status == 1 && $order->edit_due_amount > 0 && $order?->payment_method != 'cash_on_delivery')
                                            <div class="col-12">
                                                <div class="section-bg-cmn rounded-2 p-3 d-flex flex-column text-center align-items-center gap-1">
                                                    <h5 class="fs-16 mb-2 text-danger fw-semibold text-capitalize">{{ translate('Pay Due Bill') }}</h5>
                                                    <h5 class="fw-bold">{{ webCurrencyConverter(amount: $order['edit_due_amount']) }}</h5>
                                                    <p class="fs-12">{{ translate('The order total increased. Pay the amount to continue processing.') }}</p>
                                                    <button type="button" class="btn btn-primary py-2 px-3 fw-semibold"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#choosePaymentMethodModal-{{ $order['id'] }}">
                                                        {{ translate('Pay Now') }}
                                                    </button>
                                                </div>
                                            </div>
                                        @elseif($order->edited_status == 1 && $order?->latestEditHistory?->order_return_payment_status == 'pending')
                                            <div class="col-12">
                                                <div class="section-bg-cmn rounded-2 p-3 d-flex flex-column text-center align-items-center gap-1">
                                                    <h6 class="fs-16 mb-2 text-danger fw-semibold text-capitalize">{{ translate('Amount to Be Returned') }}</h6>
                                                    <h6>{{ webCurrencyConverter(amount: $order['edit_return_amount']) }}</h6>
                                                    <p class="fs-12">{{ translate('You will receive this amount. Wait for admin to process the return.') }}</p>
                                                </div>
                                            </div>
                                        @elseif($order->edited_status == 1 && $order?->latestEditHistory?->order_return_payment_status == 'returned')
                                            <div class="col-12">
                                                <div class="section-bg-cmn rounded-2 py-3 px-3">
                                                    <h6 class="text-capitalize mb-3">{{ translate('Return Payment info') }}</h6>
                                                    <div class="d-flex flex-column gap-2">
                                                        <div class="fs-12 d-flex justify-content-start gap-2">
                                                            <span class="text-muted text-capitalize">{{ translate('Payment Status') }} :</span>
                                                            <span class="text-{{ $order['payment_status'] == 'paid' ? 'success' : 'danger' }} text-capitalize fw-semibold">{{ translate($order['payment_status']) }}</span>
                                                        </div>
                                                        <div class="fs-12 d-flex justify-content-start gap-2">
                                                            <span class="text-muted text-capitalize">{{ translate('Return Payment method') }} :</span>
                                                            <span class="text-dark text-capitalize fw-semibold">{{ $order?->latestEditHistory?->order_return_payment_method }}</span>
                                                        </div>
                                                        <div class="fs-12 d-flex justify-content-start gap-2">
                                                            <span class="text-muted text-capitalize">{{ translate('Return Amount') }} :</span>
                                                            <span class="text-dark fw-semibold">{{ webCurrencyConverter(amount: $order?->latestEditHistory?->order_return_amount) }}</span>
                                                        </div>
                                                        <div class="bg-white py-2 px-2 rounded text-start fs-12">
                                                            #{{ translate('Note') }} : {{ $order?->latestEditHistory?->order_return_payment_note }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @if($order->order_type == 'default_type')
                                            @foreach([['title' => translate('Shipping Address'), 'address' => $shipping, 'isSameAsShipping' => false], ['title' => translate('Billing Address'), 'address' => $billing, 'isSameAsShipping' => $isSameAddress]] as $addressCard)
                                                @if($addressCard['address'])
                                                    <div class="col-md-6">
                                                        <div class="section-bg-cmn rounded-2 py-3 px-3 h-100">
                                                            <h6 class="text-capitalize mb-3">{{ $addressCard['title'] }}</h6>
                                                            @if($addressCard['isSameAsShipping'])
                                                                <div class="bg-white rounded-2 d-flex align-items-center justify-content-center py-4 px-3">
                                                                    <p class="fs-14 text-dark m-0">{{ translate('Same as shipping address') }}</p>
                                                                </div>
                                                            @else
                                                                <div class="d-flex flex-column gap-2 fs-12">
                                                                    <div class="d-flex gap-2">
                                                                        <span class="text-muted min-w-70px">{{ translate('Name') }}</span>
                                                                        <span>:</span>
                                                                        <span class="text-dark">{{ $addressCard['address']->contact_person_name ?? '-' }}</span>
                                                                    </div>
                                                                    <div class="d-flex gap-2">
                                                                        <span class="text-muted min-w-70px">{{ translate('Phone') }}</span>
                                                                        <span>:</span>
                                                                        <span class="text-dark">{{ $addressCard['address']->phone ?? '-' }}</span>
                                                                    </div>
                                                                    <div class="d-flex gap-2">
                                                                        <span class="text-muted min-w-70px">{{ translate('City / Zip') }}</span>
                                                                        <span>:</span>
                                                                        <span class="text-dark">{{ collect([$addressCard['address']->city ?? null, $addressCard['address']->zip ?? null])->filter()->implode(', ') ?: '-' }}</span>
                                                                    </div>
                                                                    <div class="d-flex gap-2">
                                                                        <span class="text-muted min-w-70px">{{ translate('Address') }}</span>
                                                                        <span>:</span>
                                                                        <span class="text-dark">{{ $addressCard['address']->address ?? '-' }}</span>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @endif

                                        @if(filled($order->order_note))
                                            <div class="col-12">
                                                <div class="section-bg-cmn rounded-2 py-3 px-3">
                                                    <h6 class="text-capitalize mb-2">{{ translate('Order Note') }}</h6>
                                                    <p class="fs-12 text-dark text-break mb-0">{{ $order->order_note }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    @php($digitalProduct = $order->details->contains(fn ($detail) => $detail->product?->retailDetail?->digital_product_type !== null && $detail->product->product_type === 'digital'))
                                    @php($canShowItemActions = ($order->order_type == 'default_type' && ($order->order_status == 'delivered' || ($order->payment_status == 'paid' && $digitalProduct))) || ($order->order_type != 'default_type' && $order->order_status == 'delivered'))
                                    @php($itemDetails = $order->details->filter(fn ($detail) => $detail?->productAllStatus ?? json_decode($detail->product_details, true))->values())

                                    <div class="table-responsive d-none d-lg-block">
                                        <table class="table align-middle table-striped">
                                            <thead class="text-primary">
                                            <tr>
                                                <th>{{ translate('SL') }}</th>
                                                <th class="text-capitalize">{{ translate('Item List') }}</th>
                                                <th class="text-center">{{ translate('Qty') }}</th>
                                                <th class="text-end">{{ translate('Price') }}</th>
                                                <th class="text-end">{{ translate('Discount') }}</th>
                                                <th class="text-end">{{ translate('Total') }}</th>
                                                <th class="text-center">{{ translate('Action') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach ($itemDetails as $detail)
                                                @php($product = $detail?->productAllStatus ?? json_decode($detail->product_details, true))
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        <div class="media gap-3 align-items-center mn-w200">
                                                            <div class="avatar rounded size-3-75rem aspect-1 overflow-hidden d-flex align-items-center">
                                                                <img class="img-fit dark-support rounded" alt="{{ $product['name'] ?? '' }}"
                                                                     src="{{ getStorageImages(path: $detail?->productAllStatus?->thumbnail_full_url, type: 'product') }}">
                                                            </div>
                                                            <div class="media-body">
                                                                <h6>
                                                                    <a class="line-limit-1 max-w-200px" href="{{ route('product', [$product['slug']]) }}">{{ $product['name'] ?? '' }}</a>
                                                                </h6>
                                                                <div class="fs-12 text-muted">{{ translate('Unit price') }} : {{ webCurrencyConverter($detail->price) }}</div>
                                                                @if($detail->variant)
                                                                    <div class="fs-12 text-muted">{{ translate('Variant') }} : {{ $detail->variant }}</div>
                                                                @endif
                                                                @include('theme-views.users-profile.account-order-details._item-refund-status')
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center text-nowrap">{{ $detail->qty }}</td>
                                                    <td class="text-end text-nowrap">{{ webCurrencyConverter($detail->price * $detail->qty) }}</td>
                                                    <td class="text-end text-nowrap">{{ webCurrencyConverter($detail->discount) }}</td>
                                                    <td class="text-end text-nowrap">{{ webCurrencyConverter(($detail->qty * $detail->price) - $detail->discount) }}</td>
                                                    <td class="text-center">
                                                        <div class="d-flex flex-wrap justify-content-center align-items-center gap-2">
                                                            @if($canShowItemActions)
                                                                @include('theme-views.users-profile.account-order-details._item-actions')
                                                            @else
                                                                -
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="row g-2 d-lg-none">
                                        @foreach ($itemDetails as $detail)
                                            @php($product = $detail?->productAllStatus ?? json_decode($detail->product_details, true))
                                            <div class="col-md-6">
                                                <div class="border rounded-10 p-3 h-100 d-flex flex-column">
                                                    <div class="d-flex gap-3 align-items-start">
                                                        <div class="avatar rounded size-3-75rem aspect-1 overflow-hidden flex-shrink-0 border">
                                                            <img class="img-fit dark-support rounded" alt="{{ $product['name'] ?? '' }}"
                                                                 src="{{ getStorageImages(path: $detail?->productAllStatus?->thumbnail_full_url, type: 'product') }}">
                                                        </div>
                                                        <div class="min-w-0 flex-grow-1">
                                                            <h6 class="fs-14 mb-1 text-break">
                                                                <a href="{{ route('product', [$product['slug']]) }}">{{ $product['name'] ?? '' }}</a>
                                                            </h6>
                                                            <div class="fs-12 text-muted">{{ translate('Unit price') }} : {{ webCurrencyConverter($detail->price) }}</div>
                                                            @if($detail->variant)
                                                                <div class="fs-12 text-muted">{{ translate('Variant') }} : {{ $detail->variant }}</div>
                                                            @endif
                                                            @include('theme-views.users-profile.account-order-details._item-refund-status')
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-column gap-1 mt-auto pt-2 mt-2 border-top fs-12">
                                                        <div class="d-flex justify-content-between gap-2">
                                                            <span class="text-muted">{{ translate('Qty') }}</span>
                                                            <span class="text-dark fw-medium">{{ $detail->qty }}</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between gap-2">
                                                            <span class="text-muted">{{ translate('Price') }}</span>
                                                            <span class="text-dark text-nowrap">{{ webCurrencyConverter($detail->price * $detail->qty) }}</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between gap-2">
                                                            <span class="text-muted">{{ translate('Discount') }}</span>
                                                            <span class="text-dark text-nowrap">{{ webCurrencyConverter($detail->discount) }}</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between gap-2 fs-13">
                                                            <span class="text-dark fw-semibold">{{ translate('Total') }}</span>
                                                            <span class="text-dark fw-bold text-nowrap">{{ webCurrencyConverter(($detail->qty * $detail->price) - $detail->discount) }}</span>
                                                        </div>
                                                    </div>
                                                    @if($canShowItemActions)
                                                        <div class="d-flex flex-wrap justify-content-end align-items-center gap-2 mt-2">
                                                            @include('theme-views.users-profile.account-order-details._item-actions')
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @php($orderTotalPriceSummary = \App\Utils\OrderManager::getOrderTotalPriceSummary(order: $order))
                                    <div class="row justify-content-end mt-2">
                                        <div class="col-md-8 col-lg-5">
                                            @if($order['edited_status'] == 1)
                                                <div class="fs-14 text-dark p-10px rounded bg-primary bg-opacity-10 mb-2">
                                                    #{{ translate('Note') }}: {{ translate('Total bill has been updated after the edits.') }}
                                                </div>
                                            @endif
                                            <div class="section-bg-cmn rounded-2 py-3 px-3">
                                                <div class="d-flex flex-column gap-2">
                                                    <div class="d-flex justify-content-between gap-2">
                                                        <span class="fs-14 text-muted">{{ translate('Total item') }}</span>
                                                        <span class="fs-14">{{ $orderTotalPriceSummary['totalItemQuantity'] }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between gap-2">
                                                        <span class="fs-14 text-muted">{{ translate('Item price') }}</span>
                                                        <span class="fs-14">{{ webCurrencyConverter(amount: $orderTotalPriceSummary['itemPrice']) }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between gap-2">
                                                        <span class="fs-14 text-muted">{{ translate('Item discount') }}</span>
                                                        <span class="fs-14">- {{ webCurrencyConverter(amount: $orderTotalPriceSummary['itemDiscount']) }}</span>
                                                    </div>
                                                    @if($order->order_type != 'default_type')
                                                        <div class="d-flex justify-content-between gap-2">
                                                            <span class="fs-14 text-muted">{{ translate('Extra discount') }}</span>
                                                            <span class="fs-14">- {{ webCurrencyConverter(amount: $orderTotalPriceSummary['extraDiscount']) }}</span>
                                                        </div>
                                                    @endif
                                                    <div class="d-flex justify-content-between gap-2">
                                                        <span class="fs-14 text-muted">{{ translate('Subtotal') }}</span>
                                                        <span class="fs-14">{{ webCurrencyConverter(amount: $orderTotalPriceSummary['subTotal']) }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between gap-2">
                                                        <span class="fs-14 text-muted">{{ translate('Coupon discount') }}</span>
                                                        <span class="fs-14">- {{ webCurrencyConverter(amount: $orderTotalPriceSummary['couponDiscount']) }}</span>
                                                    </div>
                                                    @if($orderTotalPriceSummary['referAndEarnDiscount'] > 0)
                                                        <div class="d-flex justify-content-between gap-2">
                                                            <span class="fs-14 text-muted">{{ translate('Referral Discount') }}</span>
                                                            <span class="fs-14">- {{ webCurrencyConverter(amount: $orderTotalPriceSummary['referAndEarnDiscount']) }}</span>
                                                        </div>
                                                    @endif
                                                    <div class="d-flex justify-content-between gap-2">
                                                        <span class="fs-14 text-muted">{{ translate('Tax fee') }}</span>
                                                        <span class="fs-14">{{ webCurrencyConverter(amount: $orderTotalPriceSummary['taxTotal']) }}</span>
                                                    </div>
                                                    @if($order->order_type == 'default_type' && $order?->is_shipping_free == 0)
                                                        <div class="d-flex justify-content-between gap-2">
                                                            <span class="fs-14 text-muted">{{ translate('Shipping fee') }}</span>
                                                            <span class="fs-14">{{ webCurrencyConverter(amount: $orderTotalPriceSummary['shippingTotal']) }}</span>
                                                        </div>
                                                    @endif
                                                    <div class="d-flex justify-content-between gap-2 border-top pt-2">
                                                        <span class="fs-16 fw-bold text-dark">
                                                            {{ translate('Total') }}
                                                            @if($orderTotalPriceSummary['tax_model'] == 'include')
                                                                <span class="fs-10 fw-medium">({{ translate('Tax : Inc.') }})</span>
                                                            @endif
                                                        </span>
                                                        <span class="fs-16 fw-bold text-dark">{{ webCurrencyConverter(amount: $orderTotalPriceSummary['totalAmount']) }}</span>
                                                    </div>

                                                    @if($order['edited_status'] == 1 && $order?->latestEditHistory)
                                                        @if($order?->latestEditHistory?->order_due_amount > 0)
                                                            @if($order?->latestEditHistory?->order_due_payment_status == 'paid')
                                                                <div class="d-flex justify-content-between gap-2">
                                                                    <span class="fs-14 text-muted">{{ translate('Paid Amount') }}</span>
                                                                    <span class="fs-14">{{ webCurrencyConverter(amount: $order?->latestEditHistory?->order_amount - $order?->latestEditHistory?->order_due_amount) }}</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between gap-2">
                                                                    <span class="fs-14 text-muted">{{ translate('Due Amount Paid By') }} ({{ translate($order?->latestEditHistory?->order_due_payment_method) }})</span>
                                                                    <span class="fs-14">{{ webCurrencyConverter(amount: $order?->latestEditHistory?->order_due_amount) }}</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between gap-2">
                                                                    <span class="fs-14 fw-bold text-dark">{{ translate('Total Paid Amount') }}</span>
                                                                    <span class="fs-14 fw-bold text-dark">{{ webCurrencyConverter(amount: $order?->latestEditHistory?->order_amount) }}</span>
                                                                </div>
                                                            @else
                                                                <div class="d-flex justify-content-between gap-2">
                                                                    <span class="fs-16 fw-bold text-danger">{{ translate('Due Amount') }}</span>
                                                                    <span class="fs-16 fw-bold text-danger">{{ webCurrencyConverter(amount: $order?->latestEditHistory?->order_due_amount) }}</span>
                                                                </div>
                                                            @endif
                                                        @endif

                                                        @if($order?->latestEditHistory?->order_return_amount > 0)
                                                            @if($order?->latestEditHistory?->order_return_payment_status == 'returned')
                                                                <div class="d-flex justify-content-between gap-2">
                                                                    <span class="fs-14 text-muted">{{ translate('Paid Amount') }}</span>
                                                                    <span class="fs-14">{{ webCurrencyConverter(amount: $order?->latestEditHistory?->order_amount + $order?->latestEditHistory?->order_return_amount) }}</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between gap-2">
                                                                    <span class="fs-14 fw-bold text-dark">{{ translate('Returned by') }} ({{ translate($order?->latestEditHistory?->order_return_payment_method) }})</span>
                                                                    <span class="fs-14 fw-bold text-dark">{{ webCurrencyConverter(amount: $order?->latestEditHistory?->order_return_amount) }}</span>
                                                                </div>
                                                            @else
                                                                <div class="d-flex justify-content-between gap-2">
                                                                    <span class="fs-16 fw-bold text-dark">{{ translate('Amount To Return') }}</span>
                                                                    <span class="fs-16 fw-bold text-dark">{{ webCurrencyConverter(amount: $order?->latestEditHistory?->order_return_amount) }}</span>
                                                                </div>
                                                            @endif
                                                        @endif
                                                    @endif

                                                    @if($order->order_type == 'POS' || $order->order_type == 'pos')
                                                        <div class="d-flex justify-content-between gap-2 border-top pt-2">
                                                            <span class="fs-14 fw-bold text-dark">{{ translate('Paid Amount') }}</span>
                                                            <span class="fs-14 fw-bold text-dark">{{ webCurrencyConverter(amount: $orderTotalPriceSummary['paidAmount']) }}</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between gap-2">
                                                            <span class="fs-14 fw-bold text-dark">{{ translate('Change Amount') }}</span>
                                                            <span class="fs-14 fw-bold text-dark">{{ webCurrencyConverter(amount: $orderTotalPriceSummary['changeAmount']) }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            @if($order['order_status'] == 'pending' && $order['payment_method'] == 'cash_on_delivery')
                                                <button type="button" class="btn btn-outline-danger w-100 mt-3 rounded-10 fw-medium delete-action"
                                                        data-action="{{ route('order-cancel', [$order->id]) }}"
                                                        data-message="{{ translate('Want to cancel this order') . '?' }}">
                                                    {{ translate('Cancel order') }}
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    @if($order->order_type == 'default_type' && $order->order_status == 'delivered')
        @foreach ($order->details as $detail)
            @if($detail?->productAllStatus ?? json_decode($detail->product_details, true))
                @include('theme-views.layouts.partials.modal._review', ['id' => $detail->id, 'order_details' => $detail])
            @endif
        @endforeach
    @endif
    <span class="get-payment-method-list" data-action="{{ route('pay-offline-method-list') }}"></span>
@endsection

@push('script')
    <script src="{{ theme_asset('assets/js/spartan-multi-image-picker.js') }}" defer></script>
    <script src="{{ theme_asset('assets/js/order-summary.js') }}" defer></script>
    <script src="{{ theme_asset('assets/js/payment-page.js') }}" defer></script>
    <script src="{{ dynamicAsset(path: 'public/assets/front-end/js/payment.js') }}" defer></script>

@endpush
