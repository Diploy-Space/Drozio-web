<div class="form-group m-0">
    <label class="form-label font-semibold">
        {{ translate('Phone number') }}
        <span class="input-required-icon">*</span>
    </label>
    <input class="form-control text-align-direction phone-input-with-country-picker"
           type="tel" value="{{ old('phone') }}" name="phone"
           placeholder="{{ translate('Enter phone number') }}">
</div>
