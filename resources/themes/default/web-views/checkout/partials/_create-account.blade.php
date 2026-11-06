<div class="co-card">
    <div class="co-card__body">
        <label class="co-account__toggle" for="is_check_create_account">
            <input type="checkbox" id="is_check_create_account" name="is_check_create_account" value="1">
            <span class="co-account__copy">
                <span class="co-account__title">{{ translate('Create an account with the above info') }}</span>
                <span class="co-account__note">{{ translate('Save your details for a faster checkout next time') }}</span>
            </span>
        </label>

        <div class="is_check_create_account_password_group d--none">
            <div class="co-account__fields">
                <div class="co-field">
                    <label class="co-label" for="customer_password">{{ translate('New password') }}</label>
                    <div class="password-toggle rtl">
                        <input class="form-control text-align-direction" name="customer_password" type="password" id="customer_password" placeholder="{{ translate('New password') }}" required>
                        <label class="password-toggle-btn">
                            <input class="custom-control-input" type="checkbox">
                            <i class="tio-hidden password-toggle-indicator"></i>
                            <span class="sr-only">{{ translate('Show password') }}</span>
                        </label>
                    </div>
                </div>
                <div class="co-field">
                    <label class="co-label" for="customer_confirm_password">{{ translate('Confirm password') }}</label>
                    <div class="password-toggle rtl">
                        <input class="form-control text-align-direction" name="customer_confirm_password" type="password" id="customer_confirm_password" placeholder="{{ translate('Confirm password') }}" required>
                        <label class="password-toggle-btn">
                            <input class="custom-control-input" type="checkbox">
                            <i class="tio-hidden password-toggle-indicator"></i>
                            <span class="sr-only">{{ translate('Show password') }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
