@if ($totalHoldOrders > 0)
    <div class="table-responsive datatable-custom custom-scrollbar-pos min-h-300">
        <table class="table table-hover table-thead-bordered table-nowrap align-middle">
            <thead class="text-capitalize">
            <tr>
                <th>{{ translate('Serial')}}</th>
                <th>{{ translate('Date')}}</th>
                <th>{{ translate('Customer Information')}}</th>
                <th>{{ translate('Quantity')}}</th>
                <th>{{ translate('Total Amount')}}</th>
                <th class="text-center">{{ translate('Action')}}</th>
            </tr>
            </thead>

            <tbody>
            @if (session()->has('cart_name') && count(session()->get('cart_name')) > 0 )
                @foreach ($cartItems as $key => $singleCart)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            @if($singleCart['addedAt'])
                                <div>{{ $singleCart['addedAt']->format('d/m/Y') }}</div>
                                <div>{{ $singleCart['addedAt']->format('h:i A') }}</div>
                            @else
                                <div>{{ translate('Now') }}</div>
                            @endif
                        </td>
                        <td>
                            <div>{{ $singleCart['customerName'] }}</div>
                            <a href="tel:{{ $singleCart['customerPhone'] ?? '' }}"
                               class="text-dark">{{ $singleCart['customerPhone'] ?? '' }}</a>
                        </td>
                        <td>
                            <div class="table-items position-relative">
                                <div class="cursor-pointer">
                                    {{ $singleCart['countItem'] }} {{ translate('Items') }}
                                </div>
                                @if (session()->has($key) && count(session()->get($key)) > 0)
                                    <div class="bg-white p-0 box-shadow table-items-popup">
                                        @foreach($singleCart['cartItemValue'] as  $item)
                                            @if(is_array($item))
                                                <div class="p-3 item border-bottom rounded d-flex justify-content-between gap-2">
                                                    <div class="media d-flex align-items-center flex-wrap gap-2">
                                                        <img width="40" alt="" class="aspect-1 object-fit-cover rounded"
                                                             src="{{ getStorageImages(path: $item['image'], type: 'backend-product') }}">
                                                        <div class="media-body">
                                                            <h5 class="text-truncate"> {{ Str::limit($item['name'], 12 )}}</h5>
                                                            @if($item['variant'])
                                                                <div class="text-muted">{{ translate('Variation') }}
                                                                    : {{ $item['variant'] }}</div>
                                                            @endif
                                                            <div class="text-muted">{{ translate('Quantity') }}
                                                                : {{ $item['quantity'] }}</div>
                                                        </div>

                                                    </div>
                                                    <h5>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item['productSubtotal']), currencyCode: getCurrencyCode())}}</h5>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if ($singleCart['discountOnProduct']>0)
                                <del>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $singleCart['originalTotal']), currencyCode: getCurrencyCode()) }}</del>
                            @endif
                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $singleCart['payableTotal']), currencyCode: getCurrencyCode()) }}
                        </td>
                        <td>
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn bg-warning bg-opacity-10 text-warning action-cart-change" data-cart="{{ $key }}">
                                    {{ translate('Resume') }}
                                </button>
                                <button type="button" class="btn bg-danger bg-opacity-10 text-danger action-cancel-customer-order"
                                        data-cart-id="{{ $key }}">
                                    {{ translate('Cancel order') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>
    </div>
@else
    <div class="d-flex align-items-center justify-content-center h-100">
        <div>
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product.svg') }}" alt="">
            <h3 class="text-muted text-center mt-4">{{ translate('No Order Found') }}</h3>
        </div>
    </div>
@endif
