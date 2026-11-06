<div class="form-group">
    <label class="form-label font-semibold">
        {{ translate('Password') }}
        <span class="input-required-icon">*</span>
    </label>
    <div class="password-toggle rtl">
        <input class="form-control text-align-direction auth-password-input" name="password" type="password" id="si-password" placeholder="{{ translate('Enter your password')}}" required>
        <label class="password-toggle-btn">
            <input class="custom-control-input" type="checkbox">
            <i class="tio-hidden password-toggle-indicator"></i>
            <span class="sr-only">{{ translate('Show password') }}</span>
        </label>
    </div>
</div>
