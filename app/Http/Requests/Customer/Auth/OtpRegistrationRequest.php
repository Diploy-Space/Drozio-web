<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;
use App\Services\New\Auth\CustomerAuthService;
use Closure;

class OtpRegistrationRequest extends BaseRequest
{
    public function __construct(private readonly CustomerAuthService $customerAuthService)
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'f_name' => 'required|string|max:100',
            'l_name' => 'nullable|string|max:100',
            'phone' => ['required', 'string', 'max:20', $this->unusedIdentity()],
            'email' => ['nullable', 'email', 'max:100', $this->unusedIdentity()],
            'temporary_token' => 'required|string',
            'referral_code' => ['nullable', 'string', 'max:255', $this->knownReferralCode()],
        ];
    }

    public function messages(): array
    {
        return [
            'f_name.required' => translate('First name is required'),
            'f_name.max' => translate('First name must not be longer than 100 characters'),
            'l_name.max' => translate('Last name must not be longer than 100 characters'),
            'phone.required' => translate('Phone number is required'),
            'phone.max' => translate('Phone number must not be longer than 20 characters'),
            'email.email' => translate('Enter a valid email address'),
            'email.max' => translate('Email must not be longer than 100 characters'),
            'temporary_token.required' => translate('Please verify your phone number before creating the account'),
            'referral_code.max' => translate('Referral code must not be longer than 255 characters'),
        ];
    }

    private function knownReferralCode(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->customerAuthService->findByReferralCode(code: (string) $value) === null) {
                $fail(translate('This referral code does not belong to any account'));
            }
        };
    }

    private function unusedIdentity(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->customerAuthService->identityExists(identity: (string) $value)) {
                $fail(translate('This :field is already registered', ['field' => translate($attribute === 'email' ? 'email' : 'phone number')]));
            }
        };
    }
}
