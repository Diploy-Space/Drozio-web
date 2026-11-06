<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\BaseRequest;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\SocialProfileService;
use Closure;
use Illuminate\Validation\Rule;

class SocialRegistrationRequest extends BaseRequest
{
    public function __construct(private readonly CustomerAuthService $customerAuthService)
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'unique_id' => 'required|string',
            'medium' => ['required', Rule::in(SocialProfileService::mediums())],
            'phone' => ['required', 'string', 'max:20', $this->unusedIdentity()],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => translate('The sign-in token from the provider is required'),
            'unique_id.required' => translate('The account information from the provider is required'),
            'medium.required' => translate('Please choose a sign-in provider'),
            'medium.in' => translate('This social login option is not supported'),
            'phone.required' => translate('Phone number is required'),
            'phone.max' => translate('Phone number must not be longer than 20 characters'),
        ];
    }

    private function unusedIdentity(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->customerAuthService->identityExists(identity: (string) $value)) {
                $fail(translate('This phone number is already registered'));
            }
        };
    }
}
