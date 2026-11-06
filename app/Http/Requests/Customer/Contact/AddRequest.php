<?php

namespace App\Http\Requests\Customer\Contact;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class AddRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
                'mobile_number' => 'required',
                'subject' => 'required',
                'message' => 'required',
                'email' => 'required',
                'name' => 'required',
            ];
        }

        $rules = [
            'mobile_number' => 'required|max:20',
            'subject' => 'required',
            'message' => 'required',
            'email' => 'email',
        ];

        if (! $this->hasAcceptedPhoneDigitLength()) {
            $rules['mobile_number'] = 'required|min:5|max:20';
        }

        return $rules;
    }

    public function messages(): array
    {
        if ($this->isApiRequest()) {
            return [
                'name.required' => translate('Please enter your name'),
                'mobile_number.required' => translate('Please enter your phone number'),
                'subject.required' => translate('Please enter a subject'),
                'message.required' => translate('Please enter your message'),
                'email.required' => translate('Please enter your email address'),
            ];
        }

        return [
            'mobile_number.required' => translate(':field is required', ['field' => translate('Phone number')]),
            'mobile_number.min' => translate('The phone number must be at least 4 characters'),
            'mobile_number.max' => translate('The phone number may not be greater than 20 characters'),
            'subject.required' => translate('Subject is Empty'),
            'message.required' => translate('Message is Empty'),
        ];
    }

    private function hasAcceptedPhoneDigitLength(): bool
    {
        $digitCount = strlen(preg_replace('/[^0-9]/', '', (string) $this['mobile_number']));

        return $digitCount >= 4 && $digitCount <= 20;
    }
}
