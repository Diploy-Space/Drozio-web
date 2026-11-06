<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\LoginSetupService;
use Closure;
use Illuminate\Validation\Rule;

class IdentityVerificationRequest extends BaseRequest
{
    public function __construct(private readonly CustomerAuthService $customerAuthService)
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([LoginSetupService::CHANNEL_PHONE, LoginSetupService::CHANNEL_EMAIL])],
            'identity' => ['required', 'string', 'max:100', $this->unusedIdentity()],
            'otp' => 'required|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => translate('Choose whether you are verifying a phone number or an email'),
            'type.in' => translate('Choose whether you are verifying a phone number or an email'),
            'identity.required' => translate('Phone number or email is required'),
            'identity.max' => translate('Phone number or email must not be longer than 100 characters'),
            'otp.required' => translate('OTP is required'),
            'otp.max' => translate('OTP must not be longer than 10 characters'),
        ];
    }

    private function unusedIdentity(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->customerAuthService->identityExists(identity: (string) $value)) {
                $fail(translate('This :field is already registered', ['field' => translate($this->input('type') === LoginSetupService::CHANNEL_EMAIL ? 'email' : 'phone number')]));
            }
        };
    }
}
