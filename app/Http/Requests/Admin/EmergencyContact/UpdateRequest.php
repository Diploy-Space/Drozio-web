<?php

namespace App\Http\Requests\Admin\EmergencyContact;

use App\Http\Requests\BaseRequest;
use App\Services\New\EmergencyContactService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

class UpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->isPhoneStoredOnAnotherContact()) {
                    $validator->errors()->add(
                        'phone',
                        translate('This phone number already exists for you')
                    );
                }
            },
        ];
    }

    private function isPhoneStoredOnAnotherContact(): bool
    {
        return $this->emergencyContactService->getList(
            dataLimit: 'all',
            filters: [
                'user_id' => EmergencyContactService::ADMIN_USER_ID,
                'country_code' => $this['country_code'],
                'phone' => $this['phone'],
            ],
        )->contains(fn (Model $contact): bool => (int) $contact->id !== (int) $this->route('id'));
    }
}
