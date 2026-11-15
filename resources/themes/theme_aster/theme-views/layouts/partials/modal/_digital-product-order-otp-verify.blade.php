<div class="d-flex justify-content-center">
    <a href="javascript:">
        <img src="{{theme_asset("assets/img/media/otp-2.png")}}" alt="{{translate('Logo')}}" class="img-fluid" width="220">
    </a>
</div>
<h3 class="title text-center my-3 text-capitalize text-capitalize">{{ translate('OTP verification') }}</h3>
<p class="text-center text-muted">
    {{ translate('An OTP has been sent to your email and phone').' '.translate('Enter the OTP below to verify this download.') }}
</p>
<form action="{{route('digital-product-download-otp-verify')}}" method="post" autocomplete="off" class="otp-form submit-digital-product-download-otp">
    @csrf
    <p class="text-center text-primary lead">
        {{ translate('Resend code within') }}
        <strong><span class="verify-counter" data-second="{{ $time_count }}">{{ $time_count }}</span>{{translate('S')}}</strong>
    </p>

    <div class="d-flex gap-2 gap-sm-3 align-items-end justify-content-center">
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
    <input class="identity" type="hidden" name="order_details_id" value="{{ $orderDetailID }}">
    <p class="text-center verify-message mt-4 mb-0 min-h-30px d-block d-none"></p>
    <div class="d-flex flex-wrap justify-content-center align-items-center gap-3 mt-2">
        <button type="button"
            class="btn btn-outline-primary w-auto min-w-180 resend-otp-button" id="resend-otp">{{translate('Resend OTP')}}</button>
            <span type="button" class="btn btn-primary w-auto min-w-180 submit-btn" id="verify-otp">{{translate('Verify')}}</span>
    </div>
</form>
