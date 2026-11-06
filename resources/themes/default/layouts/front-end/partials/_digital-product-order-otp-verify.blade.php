<div class="d-flex justify-content-center">
    <a href="javascript:">
        <img src="{{ theme_asset(path: "public/assets/front-end/img/media/OTP-Verification.png")}}" alt="{{ translate('Logo') }}" class="img-fluid" width="220">
    </a>
</div>
<h3 class="title text-center my-3 text-capitalize">{{ translate('OTP Verification') }}</h3>
<p class="text-center text-muted">
    {{ translate('An OTP has been sent to your email and phone').' '.translate('Enter the OTP below to verify this download.') }}
</p>

<form action="{{route('digital-product-download-otp-verify')}}" method="post" autocomplete="off" class="otp-form digital_product_download_otp_verify">
    @csrf

    <p class="text-center text-primary lead">
        {{ translate('Resend code within') }}
        <strong><span class="verifyCounter" data-second="{{ $time_count }}">{{ $time_count }}</span>s</strong>
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
    <p class="text-center verify-message mt-4 mb-0 min-h-30px d-block d--none"></p>
    <div class="d-flex flex-wrap justify-content-center align-items-center gap-3 mt-5">
        <button type="button"
            class="btn btn--primary w-auto min-w-180 resend-otp-button download-otp-resend-button" id="resend_otp">{{ translate('Resend OTP')}}</button>
            <span type="button" class="btn btn--primary w-auto min-w-180 submit-btn">{{ translate('Verify')}}</span>
    </div>
</form>
