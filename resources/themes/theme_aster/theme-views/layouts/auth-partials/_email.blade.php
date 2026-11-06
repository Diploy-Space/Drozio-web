<div class="form-group mb-2">
    <label for="email">{{ translate('Email') }} / {{ translate('Phone') }}</label>
    <input
        name="user_identity" id="si-email"
        class="form-control auth-email-input" value="{{old('user_identity')}}"
        placeholder="{{translate('Enter email or phone number')}}" required
    />
</div>
