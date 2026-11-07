<div class="d-flex justify-content-center">
    <a href="javascript:">
        <img src="{{theme_asset("assets/img/media/OTP-Verification-fail.png")}}" alt="{{translate('Logo')}}" class="img-fluid" width="220">
    </a>
</div>
<h3 class="title text-center my-3 text-capitalize">{{ translate('Something went wrong').'!' }} </h3>
<p class="text-center text-muted">
    {{ translate('Sorry, the number and email you provided during the order are incorrect').' '.translate('We cannot send any verification code').' '.translate('Please contact the admin or your vendor') }}
</p>
<div class="d-flex flex-column justify-content-center align-items-center gap-3 mt-5">
    <button type="button" class="btn btn-outline-primary w-auto min-w-180" data-bs-toggle="modal" data-bs-target="#loginModal">{{translate('Support ticket')}}</button>
    <button type="button" class="btn btn-primary w-auto min-w-180 text-capitalize" data-bs-toggle="modal" data-bs-target="#loginModal">{{translate('Open support ticket')}}</button>
</div>
