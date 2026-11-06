<?php

namespace App\Http\Requests\Vendor\EmergencyContact;

use App\Http\Requests\BaseRequest;
use App\Services\New\EmergencyContactService;
use Illuminate\Validation\Validator;

class AddRequest extends BaseRequest
{
    public function __construct(
        private readonly EmergencyContactService $emergencyContactService,
    ) {}

    public function rules(): array
    {
        return [
            'name' => 'required',
            'country_code' => 'required',
            'phone' => 'required|max:20|min:4',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate(':field is required', ['field' => translate('Name')]),
            'country_code.required' => translate(':field is required', ['field' => translate('Country code')]),
            'phone.required' => translate(':field is required', ['field' => translate('Phone')]),
            'phone.max' => translate('Enter a valid phone number of up to 20 characters.'),
            'phone.min' => translate('Phone number with a minimum length requirement of 4 characters'),
        ];
    }

    protected function sellerId(): mixed
    {
        return $this->attributes->get('seller')?->id ?? auth('seller')->id();
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->isPhoneAlreadyStored()) {
                    $validator->errors()->add(
                        'phone',
                        translate('This phone number already exists for you')
                    );
                }
            },
        ];
    }

    private function isPhoneAlreadyStored(): bool
    {
        return $this->emergencyContactService->findBy(params: [
            'user_id' => auth('seller')->id(),
            'country_code' => $this['country_code'],
            'phone' => $this['phone'],
        ]) !== null;
    }

    private function apiRules(): array
    {
        return [
            'name' => 'required',
            'phone' => 'required',
        ];
    }
}
