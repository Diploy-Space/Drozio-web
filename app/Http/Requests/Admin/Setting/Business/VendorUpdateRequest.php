<?php

namespace App\Http\Requests\Admin\Setting\Business;

use App\Enums\WebConfigKey;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class VendorUpdateRequest extends BaseRequest
{
    public const FORGOT_PASSWORD_METHODS = ['email', 'phone'];

    public function rules(): array
    {
        return [
            'seller_registration' => ['nullable', 'boolean'],
            'vendor_forgot_password_method' => ['nullable', Rule::in(self::FORGOT_PASSWORD_METHODS)],
            WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'seller_registration.boolean' => translate('Please turn vendor registration on or off.'),
            'vendor_forgot_password_method.in' => translate('Please choose email or phone as the verification option.'),
            WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS.'.boolean' => translate('Show verified badge must be turned on or off'),
        ];
    }
}
