@php($rememberId = rand(111, 999))
<div class="form-group d-flex flex-wrap justify-content-between mb-1">
    <div class="rtl">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" name="remember"
                   id="remember{{ $rememberId }}" {{ old('remember') ? 'checked' : '' }}>
            <label class="custom-control-label text-primary" for="remember{{ $rememberId }}">{{ translate('Remember me') }}</label>
        </div>
    </div>
    @if(isset($forgotPassword) && $forgotPassword)
        <a class="font-size-sm text-primary text-underline" href="{{route('customer.auth.recover-password')}}">
            {{ translate('Forgot password') }}?
        </a>
    @endif
</div>
