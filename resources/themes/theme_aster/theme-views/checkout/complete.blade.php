@extends('theme-views.layouts.app')

@section('title', translate('Order Complete').' | '.$web_config['company_name'].' '.translate('eCommerce'))

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-5">
        <div class="container">
            <div class="card">
                <div class="card-body p-md-5">
                    <div class="row justify-content-center">
                        <div class="col-xl-6 col-md-10">
                            <div class="text-center d-flex flex-column align-items-center gap-3">
                                <img width="46" src="{{ theme_asset('assets/img/icons/check.png') }}" class="dark-support" alt="">
                                <h3 class="text-capitalize">
                                    @if(isset($isNewCustomerInSession) && $isNewCustomerInSession)
                                        {{ translate('Order placed & account created successfully') }}!
                                    @else
                                        {{ translate('Order placed successfully') }}!
                                    @endif
                                </h3>
                                <p class="text-muted">{{ translate('Thank you for your order') }}! {{ translate('Your order has been processed').'.'.translate('Check your email to get the order id and details').'.' }}</p>
                                <div class="d-flex flex-wrap justify-content-center gap-3">
                                    <a href="{{route('home')}}" class="btn btn-outline-primary bg-primary-light border-transparent text-capitalize">{{ translate('Continue shopping') }}</a>
                                    <a href="{{ route('track-order.index') }}" class="btn btn-primary text-capitalize">{{ translate('Track Order') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
