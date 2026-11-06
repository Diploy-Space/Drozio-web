<div class="col-sm-12 mb-3">
    <div class="card">
        <div class="table-responsive datatable-custom">
            <table class="table table-hover table-borderless align-middle">
                <thead class="text-capitalize table-nowrap">
                <tr>
                    <th>{{ translate('Serial') }}</th>
                    <th>{{ translate('Order no') }}</th>
                    @if($wholesaleAvailable)
                        <th class="text-center text-capitalize">{{ translate('Module') }}</th>
                    @endif
                    <th>{{ translate('Earning') }}</th>
                    <th class="text-center text-capitalize">{{ translate('Earning status') }}</th>
                    <th class="text-center text-capitalize">{{ translate('Payment method') }}</th>
                    <th class="text-center">{{ translate('Status') }}</th>
                </tr>
                </thead>

                <tbody id="set-rows">
                @foreach($orders as $key=>$order)
                    <tr>
                        <td>{{ $orders->firstItem()+$key }}</td>
                        <td>
                            <div class="media d-flex align-items-center gap-10 flex-wrap">
                                <a class="text-dark" title="{{translate('Order details')}}"
                                   href="{{route($order->details_route_name,['id'=>$order['id']])}}">
                                    {{ $order->id }}
                                </a>
                            </div>
                        </td>
                        @if($wholesaleAvailable)
                            <td class="text-center text-capitalize">{{ $order->module_label }}</td>
                        @endif
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <div class="media-body">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order->deliveryman_charge), currencyCode: getCurrencyCode()) }}</div>
                            </div>
                        </td>
                        <td class="text-center text-capitalize">
                            @if($order['order_status'] == 'delivered' && $order['payment_status']=='paid')
                                <span class="badge badge-success text-bg-success">
                                        {{translate('Received')}}
                                </span>
                            @else
                                <span class="badge badge-danger text-bg-danger fs-12">
                                    {{translate('Not received')}}
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="text-center">
                                {{translate($order['payment_method'])}}
                            </div>
                        </td>
                        <td class="text-center text-capitalize">
                            @if($order['order_status']=='pending')
                                <span class="badge badge-info text-bg-info fs-12">
                                        {{translate($order['order_status'])}}
                                </span>

                            @elseif($order['order_status']=='out_for_delivery')
                                <span class="badge badge-warning text-bg-warning fs-12">
                                                {{translate(str_replace('_',' ',$order['order_status']))}}
                                            </span>
                            @elseif($order['order_status']=='processing')
                                <span class="badge badge-secondary text-bg-secondary fs-12">
                                                {{translate(str_replace('_',' ',$order['order_status']))}}
                                            </span>
                            @elseif($order['order_status']=='confirmed')
                                <span class="badge badge-success text-bg-success">
                                                {{translate($order['order_status'])}}
                                            </span>
                            @elseif($order['order_status']=='failed')
                                <span class="badge badge-danger text-bg-danger fs-12">
                                                {{translate('Failed to deliver')}}
                                            </span>
                            @elseif($order['order_status']=='delivered')
                                <span class="badge badge-success text-bg-success">
                                                {{translate($order['order_status'])}}
                                            </span>
                            @else
                                <span class="badge badge-danger text-bg-danger fs-12">
                                                {{translate($order['order_status'])}}
                                            </span>
                            @endif
                        </td>

                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="table-responsive mt-4">
            <div class="px-4 d-flex justify-content-lg-end">
             @if(isset($currentFilters))
                    {{ $orders->appends([
                        'order_status' => $currentFilters['order_status'],
                        'payment_status' => $currentFilters['payment_status'],
                        'business_module' => $currentFilters['business_module']
                    ])->links() }}
                @else
                    {{ $orders->links() }}
                @endif
            </div>
        </div>
        @if(count($orders)==0)
            <div class="text-center p-4">
                <img class="mb-3 w-160"
                     src="{{dynamicAsset(path: 'public/assets/back-end/svg/illustrations/sorry.svg')}}"
                     alt="{{translate('Image description')}}">
                <p class="mb-0">{{translate('No data to show')}}</p>
            </div>
        @endif
    </div>
</div>
