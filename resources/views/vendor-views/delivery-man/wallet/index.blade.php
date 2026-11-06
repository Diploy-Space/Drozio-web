@extends('layouts.vendor.app')

@section('title',translate('Earning statement'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{dynamicAsset(path: 'public/assets/back-end/img/add-new-seller.png')}}" alt="">
                {{translate('Earning statement')}}
            </h2>
        </div>
        @include('vendor-views.delivery-man.pages-inline-menu')
        <div class="flex-between d-sm-flex row align-items-center justify-content-between mb-2 mx-1">
            <div>
                <a href="{{route('vendor.delivery-man.list')}}"
                   class="btn btn--primary mt-3 mb-3">{{translate('Back to delivery-man list')}}</a>
            </div>
            <div></div>
        </div>
        <div class="card mb-3">
            <div class="card-body">

                <div class="row justify-content-between align-items-center g-2 mb-3">
                    <div class="col-sm-6">
                        <h4 class="d-flex align-items-center text-capitalize gap-10 mb-0">
                            <img width="20" class="mb-1" src="{{dynamicAsset(path: 'public/assets/back-end/img/admin-wallet.png')}}" alt="">
                            {{translate('Deliveryman Wallet')}}
                        </h4>
                    </div>
                </div>

                <div class="row g-2" id="order_stats">
                    <div class="col-lg-4">
                        <div class="card h-100 d-flex justify-content-center align-items-center">
                            <div class="card-body d-flex flex-column gap-10 align-items-center justify-content-center">
                                <img width="48" src="{{dynamicAsset(path: 'public/assets/back-end/img/cc.png')}}" alt="">
                                <h3 class="for-card-count mb-0 fz-24">{{ $deliveryMan->wallet ? setCurrencySymbol(amount: usdToDefaultCurrency(amount:$deliveryMan->wallet->cash_in_hand), currencyCode: getCurrencyCode()) : setCurrencySymbol(amount: 0, currencyCode: getCurrencyCode()) }}</h3>
                                <div class="font-weight-bold text-capitalize mb-30">
                                    {{translate('Cash in hand')}}
                                </div>
                            </div>
                            <a href="{{ route('vendor.delivery-man.wallet.cash-collect', ['id' => $deliveryMan->id]) }}" class="btn btn--primary mb-4 text-capitalize">{{translate('Collect Cash')}}</a>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="card card-body h-100 justify-content-center py-5">
                                    <div class="d-flex gap-2 justify-content-between align-items-center">
                                        <div class="d-flex flex-column align-items-start">
                                            <h3 class="mb-1 fz-24">{{ $deliveryMan->wallet ? setCurrencySymbol(amount: usdToDefaultCurrency(amount:$deliveryMan->wallet->current_balance), currencyCode: getCurrencyCode()) : setCurrencySymbol(amount:0, currencyCode: getCurrencyCode())}}</h3>
                                            <div class="text-capitalize mb-0">{{translate('Current Balance')}}</div>
                                        </div>
                                        <div>
                                            <img width="40" src="{{dynamicAsset(path: 'public/assets/back-end/img/withdraw-icon.png')}}" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-body h-100 justify-content-center py-5">
                                    <div class="d-flex gap-2 justify-content-between align-items-center">
                                        <div class="d-flex flex-column align-items-start">
                                            <h3 class="mb-1 fz-24">{{ $deliveryMan->wallet ? setCurrencySymbol(amount: usdToDefaultCurrency(amount:$deliveryMan->wallet->total_withdraw), currencyCode: getCurrencyCode()) : setCurrencySymbol(amount:0, currencyCode: getCurrencyCode())}}</h3>
                                            <div class="text-capitalize mb-0">{{translate('Total withdrawn')}}</div>
                                        </div>
                                        <div>
                                            <img width="40" src="{{dynamicAsset(path: 'public/assets/back-end/img/aw.png')}}" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-body h-100 justify-content-center py-5">
                                    <div class="d-flex gap-2 justify-content-between align-items-center">
                                        <div class="d-flex flex-column align-items-start">
                                            <h3 class="mb-1 fz-24">{{$deliveryMan->wallet ? setCurrencySymbol(amount: usdToDefaultCurrency(amount:$deliveryMan->wallet->pending_withdraw), currencyCode: getCurrencyCode()) : setCurrencySymbol(amount:0, currencyCode: getCurrencyCode())}}</h3>
                                            <div class="text-capitalize mb-0">{{translate('Pending Withdraw')}}</div>
                                        </div>
                                        <div>
                                            <img width="40" class="mb-2" src="{{dynamicAsset(path: 'public/assets/back-end/img/pw.png')}}" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-body h-100 justify-content-center py-5">
                                    <div class="d-flex gap-2 justify-content-between align-items-center">
                                        <div class="d-flex flex-column align-items-start">
                                            <h3 class="mb-1 fz-24">
                                                {{ empty($withdrawableBalance) ? setCurrencySymbol(amount: 0, currencyCode: getCurrencyCode()) : setCurrencySymbol(amount: usdToDefaultCurrency(amount: $withdrawableBalance), currencyCode: getCurrencyCode()) }}
                                            </h3>
                                            <div class="text-capitalize mb-0">{{translate('Withdrawable balance')}}</div>
                                        </div>
                                        <div>
                                            <img width="40" class="mb-2" src="{{dynamicAsset(path: 'public/assets/back-end/img/withdraw.png')}}" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mt-3">
                <div class="card">
                    <div class="card-header text-capitalize">
                        <h5 class="mb-0 text-capitalize">{{translate('Delivery Man Account')}}</h5>
                    </div>
                    <div class="card-body">
                        <div class="flex-start">
                            <div><h4>{{translate('Status')}} : </h4></div>
                            <div class="mx-1">
                                <h4>{!! $deliveryMan->is_active == 1?'<label class="badge badge-success">Active</label>':'<label class="badge badge-danger">In-Active</label>' !!}</h4>
                            </div>
                        </div>
                        <div class="flex-start">
                            <div><h5 class="text-nowrap">{{translate('Name')}} : </h5></div>
                            <div class="mx-1"><h5>{{$deliveryMan->f_name}} {{$deliveryMan->l_name}}</h5></div>
                        </div>
                        <div class="flex-start">
                            <div><h5>{{translate('Email')}} : </h5></div>
                            <div class="mx-1"><h5>{{$deliveryMan->email}}</h5></div>
                        </div>
                        <div class="flex-start">
                            <div><h5>{{translate('Phone')}} : </h5></div>
                            <div class="mx-1"><h5>{{$deliveryMan->phone}}</h5></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mt-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"> {{translate('Bank information')}}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mt-2">
                            <div class="flex-start">
                                <div><h4>{{translate('Bank Name')}} : </h4></div>
                                <div class="mx-1">
                                    <h4>{{$deliveryMan->bank_name ??  translate('No Data Found')}}</h4>
                                </div>
                            </div>
                            <div class="flex-start">
                                <div><h6>{{translate('Branch')}} : </h6></div>
                                <div class="mx-1">
                                    <h6>{{$deliveryMan->branch ??  translate('No Data Found')}}</h6>
                                </div>
                            </div>
                            <div class="flex-start">
                                <div><h6>{{translate('Holder Name')}} : </h6></div>
                                <div class="mx-1">
                                    <h6>{{$deliveryMan->holder_name ?? translate('No Data Found')}}</h6>
                                </div>
                            </div>
                            <div class="flex-start">
                                <div><h6>{{translate('Account No')}} : </h6></div>
                                <div class="mx-1">
                                    <h6>{{$deliveryMan->account_no ?? translate('No Data Found')}}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade py-5" id="exampleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{translate('Cash Withdraw')}}</h5>
                    <button id="invoice_close" type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body row">
                    <div class="col-md-12 mb-3">
                        <div class="d-flex flex-wrap gap-2 mt-3 title-color" id="chosen_price_div">
                            <div class="product-description-label">{{translate('Total Cash In Hand')}}: </div>
                            <div class="product-price">
                                <strong>{{ $deliveryMan->wallet ? setCurrencySymbol(amount: usdToDefaultCurrency(amount:$deliveryMan->wallet->cash_in_hand), currencyCode: getCurrencyCode()) : 0  }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <input type="number" class="form-control" name="amount" placeholder="Enter Amount to withdraw">
                    </div>
                    <div class="col-md-12 mb-3">
                        <div class="mt-4 text-center">
                            <form action="">
                                <button type="button" class="btn btn-danger" data-dismiss="modal">{{translate('Close')}}</button>
                                <button class="btn btn--primary text-capitalize" data-toggle="modal" data-target="#exampleModal">{{translate('Collect Cash')}}</button>
                            </form>
                        </div>
                        <hr class="non-printable">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
