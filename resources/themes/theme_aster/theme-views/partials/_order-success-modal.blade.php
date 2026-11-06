@if($orderSuccessIds)
        <?php
        $isPlural = count($orderSuccessIds) > 1;
        $orderText = $isPlural ? translate('Order IDs') : translate('Order ID');
        ?>
        <div class="modal fade" id="order_successfully" tabindex="-1">
            <div class="modal-dialog modal--md modal-dialog-centered">
                <div class="modal-content">
                    @if(!auth('customer')->check())
                        @if(count($orderSuccessIds) > 1)
                        <div class="modal-body rtl">
                            <div class="d-flex justify-content-end pb-2">
                                <button class="btn-close outside opacity-100 mt-lg-0 mt-3 shadow top-0-lg" type="button" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="text-center pb-3 pt-4 mt-xl-2">
                                <div class="mb-20">
                                    <img width="56" height="56" class="" src="{{theme_asset(path: "/assets/img/icons/check-fill.png")}}" alt="">
                                </div>
                                @if(isset($isNewCustomerInSession) && $isNewCustomerInSession)
                                    <h6 class="mb-3 fs-18 fw-semibold">{{translate('Order placed & account created successfully')}}!</h6>
                                @else
                                    <h6 class="mb-3 fs-18 fw-semibold">{{translate('Thank You for Your Purchase!')}}</h6>
                                @endif
                                @if(isset($isNewCustomerInSession) && $isNewCustomerInSession)
                                    <p class="fs-14 title-semidark mb-1">
                                        {{ translate('Your order is placed and an account was created for you.') }}
                                    </p>
                                    <p class="fs-14 title-semidark mb-30">
                                        {{ translate('We have emailed your order details for your reference.') }}  {{ translate('Log in anytime to track your order and manage your purchase.') }}
                                    </p>
                                @else
                                    <p class="fs-14 title-semidark mb-1">
                                        {{ translate('We have received your order and will process it shortly.') }}
                                    </p>
                                    <p class="fs-14 title-semidark mb-30">
                                        {{ translate('You can track your order at any time using your order details.') }}
                                        {{ translate('We also emailed a copy of your order confirmation.') }}
                                    </p>
                                @endif
                                <div class="table-responsive overflow-x-auto border rounded-10 mb-20">
                                    <table class="table table-borderless align-middle text-dark text-nowrap tr-border-bottom border--dashed">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="text-start fw-semibold">{{ translate('Orders') }} ({{count($orderSuccessIds)}})</th>
                                                <th class="text-center fw-semibold">{{ translate('Invoice') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($orderSuccessIds as $orderId)
                                            <tr>
                                                <td>
                                                    <div class="d-flex gap-2 align-items-center lh-1 order-item">
                                                        <span>{{translate('Order ID')}} #<span class="order-id-text">{{$orderId}}</span></span>
                                                        <a href="javascript:" class="text-primary copy-order-id"><i class="fi fi-rr-copy lh-1"></i></a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center justify-content-center">
                                                        <a target="_blank" href="{{route('generate-invoice',[$orderId]) }}" class="btn text-white bg-primary btn-circle p-0 fs-10" style="--size: 20px;">
                                                            <i class="fi fi-rr-download"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @else
                            <div class="modal-body rtl" >
                                <div class="d-flex justify-content-end pb-2">
                                    <button class="btn-close outside opacity-100 mt-lg-0 mt-3 shadow top-0-lg" type="button" data-bs-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="text-center pb-3 pt-4 mt-xl-2">
                                    <div class="mb-20">
                                        <img width="56" height="56" class="" src="{{theme_asset(path: "/assets/img/icons/check-fill.png")}}" alt="">
                                    </div>
                                    @if(isset($isNewCustomerInSession) && $isNewCustomerInSession)
                                        <h6 class="mb-3 fs-18 fw-semibold">{{translate('Order placed & account created successfully')}}!</h6>
                                    @else
                                        <h6 class="mb-3 fs-18 fw-semibold">{{translate('Thank You for Your Purchase!')}}</h6>
                                    @endif
                                    @if(isset($isNewCustomerInSession) && $isNewCustomerInSession)
                                        <p class="fs-14 title-semidark mb-1">
                                            {{ translate('Your order is placed and an account was created for you.') }}
                                        </p>
                                        <p class="fs-14 title-semidark mb-30">
                                            {{ translate('We have emailed your order details for your reference.') }}  {{ translate('Log in anytime to track your order and manage your purchase.') }}
                                        </p>
                                    @else
                                        <p class="fs-14 title-semidark mb-1">
                                            {{ translate('We have received your order and will process it shortly.') }}
                                        </p>
                                        <p class="fs-14 title-semidark mb-30">
                                            {{ translate('You can track your order at any time using your order details.') }}
                                            {{ translate('We also emailed a copy of your order confirmation.') }}
                                        </p>
                                    @endif
                                    <div class="mb-20 max-w-400 mx-auto d-flex align-items-center justify-content-between gap-2 border-dash-custom rounded-pill py-1 px-1">
                                        <div class="fs-14 ps-xl-3 ps-2 text-dark order-item">{{translate('Order ID')}} #<span  class="order-id-text">{{$orderSuccessIds[0]}}</span> </div>
                                        <button class="btn pb-2 btn-primary fw-normal px-3 rounded-pill copy-order-id">
                                            {{ translate('Copy') }}
                                        </button>
                                    </div>
                                   @if(isset($orderSuccessIds[0]))
                                        <div class="d-flex flex-wrap gap-4 justify-content-center">
                                            <a target="_blank" href="{{route('generate-invoice',[$orderSuccessIds[0]]) }}" type="button" class="btn w-fit-content text-primary p-0 fs-14 fw-medium d-flex align-items-center gap-2 justify-content-center border-0">
                                                <i class="fi fi-sr-down-to-line d-flex"></i> {{ translate('Download Invoice') }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
@endif
