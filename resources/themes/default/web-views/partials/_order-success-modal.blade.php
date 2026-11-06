@if($orderSuccessIds)
        <?php
        $isPlural = count($orderSuccessIds) > 1;
        $orderText = $isPlural ? translate('Order IDs') : translate('Order ID');
        ?>
    <div class="modal fade" id="order_successfully" aria-labelledby="order_successfully" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal--md modal-dialog-centered">
            <div class="modal-content">
                  @if(!auth('customer')->check())
                    @if(count($orderSuccessIds) > 1)
                        <div class="modal-body rtl">
                            <div class="d-flex justify-content-end pb-2">
                                <button class="close close-quick-view-modal ps-2 pe-1 z-index-99" type="button" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="text-center px-sm-3 pb-2 pt-4 mt-xl-1">
                                <div class="mb-20">
                                    <img width="56" height="56" class="" src="{{theme_asset(path: "public/assets/front-end/img/icons/checked-circle.png")}}" alt="">
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
                                        {{ translate('We have emailed your order details for your reference.') }}
                                        {{ translate('Log in anytime to track your order and manage your purchase.') }}
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
                                            <th class="text-start fw-semibold">{{ translate('Orders') }} ({{ count($orderSuccessIds) }})</th>
                                            <th class="text-center fw-semibold">{{ translate('Invoice') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($orderSuccessIds as $orderId)
                                            <tr>
                                                <td>
                                                    <div class="d-flex gap-2 align-items-center lh-1 order-item">
                                                        <span>{{translate('Order ID')}} #<span class="order-id-text">{{$orderId}}</span></span>
                                                        <a href="javascript:" class="text-primary copy-order-id">
                                                            <i class="fi fi-rr-copy lh-1"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center justify-content-center">
                                                        <a target="_blank" href="{{route('generate-invoice',[$orderId]) }}" class="btn btn--primary btn-circle p-0 fs-10" style="--size: 20px;">
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
                        <div class="modal-body rtl">
                            <div class="d-flex justify-content-end pb-2">
                                <button class="close close-quick-view-modal ps-2 pe-1 z-index-99" type="button" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="text-center px-sm-3 pb-2 pt-4 mt-xl-1">
                                <div class="mb-20">
                                    <img width="56" height="56" class="" src="{{theme_asset(path: "public/assets/front-end/img/icons/checked-circle.png")}}" alt="">
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
                                        {{ translate('We have emailed your order details for your reference.') }}
                                        {{ translate('Log in anytime to track your order and manage your purchase.') }}
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
                                    <div class="fs-14 pl-3 order-item">{{translate('Order ID')}} #<span  class="order-id-text">{{$orderSuccessIds[0]}}</span></div>
                                    <button class="btn pt-1 pb-2 btn--primary font-bold px-3 rounded-pill font-weight-normal copy-order-id">
                                        {{ translate('Copy') }}
                                    </button>
                                </div>

                                @if(isset($orderSuccessIds[0]))
                                    <div class="d-flex flex-wrap gap-4 justify-content-center">
                                        <a target="_blank" href="{{route('generate-invoice',[$orderSuccessIds[0]]) }}" type="button" class="btn w-fit-content web-text-primary p-0 fs-14 font-normal d-flex align-items-center gap-2 justify-content-center">
                                            <i class="fi fi-rr-down-to-line top-02"></i> {{ translate('Download Invoice') }}
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
