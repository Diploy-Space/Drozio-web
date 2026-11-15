<div class="d-flex justify-content-center">
    <a href="javascript:">
        <img src="{{ theme_asset(path: "public/assets/front-end/img/media/OTP-Verification-fail.png")}}" alt="{{ translate('Logo') }}" class="img-fluid" width="220">
    </a>
</div>
<h3 class="title text-center my-3 text-capitalize">{{ translate('Something went wrong') }} !</h3>
<p class="text-center text-muted">
    {{ translate('Sorry, the number and email you provided during the order are incorrect').' '.translate('We cannot send any verification code').' '.translate('Please contact the admin or your vendor') }}
</p>

<div class="d-flex flex-column justify-content-center align-items-center gap-3 mt-5">

    @if(auth('customer')->check())
        <a href="{{route('account-tickets')}}" class="btn btn--primary w-auto min-w-180">{{ translate('Open Support Ticket')}}</a>
    @else
        <a href="{{route('customer.auth.login')}}" class="btn btn--primary w-auto min-w-180">{{ translate('Open Support Ticket')}}</a>
    @endif

</div>
