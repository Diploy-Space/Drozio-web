@extends('theme-views.layouts.profile')

@section('title', translate('OTP verification').' | '.$web_config['company_name'].' '.translate('eCommerce'))

@section('content')
<main class="main-content d-flex flex-column gap-3 py-3 mb-30">
    <div class="card mb-5">
        <div class="card-body py-5 px-lg-5">
            <div class="row align-items-center pb-5">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <h2 class="text-center mb-2">{{ translate('OTP verification') }}</h2>
                    <p class="text-center mb-5 text-muted">{{ translate('Please Verify that its you') }}.</p>
                    <div class="d-flex justify-content-center">
                        <img width="283" class="dark-support" src="{{theme_asset('assets/img/otp-found.png')}}"
                             alt="{{translate('Image')}}">
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="d-flex justify-content-center mb-3">
                        <img width="50" class="dark-support" src="{{theme_asset('assets/img/otp-lock.png')}}"
                             alt="{{translate('Image')}}">
                    </div>
                    <p class="text-muted mx-w mx-auto width--27-5rem">
                        {{ translate('An OTP (One Time Password) has been sent to').' '.request('identity').' '.translate('Please enter the OTP in the field below to verify your phone').'.' }}
                    </p>
                    <div class="resend-otp-custom">
                        <p class="text-primary mb-2 ">{{ translate('Resend code within') }}</p>
                        <h6 class="text-primary mb-5 verifyTimer">
                            <span class="verifyCounter" data-second="{{$time_count}}"></span>s
                        </h6>
                    </div>
                    <form action="{{ route('customer.auth.otp-verification') }}" class="otp-form" method="POST"
                          id="customer-verify">
                        @csrf
                        <div class="d-flex gap-2 gap-sm-3 align-items-end justify-content-center">
                            <input class="otp-field" type="text" name="opt-field[]" maxlength="1"
                                   autocomplete="off">
                            <input class="otp-field" type="text" name="opt-field[]" maxlength="1"
                                   autocomplete="off">
                            <input class="otp-field" type="text" name="opt-field[]" maxlength="1"
                                   autocomplete="off">
                            <input class="otp-field" type="text" name="opt-field[]" maxlength="1"
                                   autocomplete="off">
                            <input class="otp-field" type="text" name="opt-field[]" maxlength="1"
                                   autocomplete="off">
                            <input class="otp-field" type="text" name="opt-field[]" maxlength="1"
                                   autocomplete="off">
                        </div>
                        <input class="otp-value" type="hidden" name="otp">
                        <input class="identity" type="hidden" name="identity" value="{{ request('identity') }}">
                        <div class="d-flex justify-content-center gap-3 mt-5">
                            <button type="button"
                                    data-field = 'identity'
                                    data-route="{{ route('customer.auth.resend-otp-reset-password') }}"
                                    class="btn btn-outline-primary resend-otp-button"
                                    id="resend-otp">
                                {{ translate('Resend OTP') }}
                            </button>
                            <button class="btn btn-primary px-sm-5" type="submit" disabled>{{ translate('Verify') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@push('script')
    <script src="{{ theme_asset('assets/js/auth.js') }}" defer></script>
@endpush
