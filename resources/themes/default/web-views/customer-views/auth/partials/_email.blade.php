<div class="form-group">
    <label class="form-label font-semibold">
        {{ translate('Email') }} / {{ translate('Phone')}}
        <span class="input-required-icon">*</span>
    </label>
    <input class="form-control text-align-direction auth-email-input" type="text" name="user_identity" id="si-email"
           value="{{old('user_identity')}}" placeholder="{{ translate('Enter email or phone') }}"
           required>
    <div class="invalid-feedback">{{ translate('Please provide valid email or phone number') }}</div>
</div>
