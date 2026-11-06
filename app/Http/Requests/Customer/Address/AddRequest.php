<?php

namespace App\Http\Requests\Customer\Address;

use App\Http\Requests\BaseRequest;
use App\Services\New\ShippingAddressService;
use Illuminate\Validation\Rule;

class AddRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
                'guest_id' => 'nullable|integer',
                'contact_person_name' => 'required|string|max:50',
                'address_type' => ['required', Rule::in(ShippingAddressService::addressTypes())],
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:50',
                'zip' => 'required|string|max:10',
                'state' => 'nullable|string|max:191',
                'country' => 'required|string|max:191',
                'email' => 'nullable|email|max:255',
                'phone' => 'required|string|max:20',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'is_billing' => 'required|boolean',
            ];
        }

        $rules = [
            'name' => 'required',
            'phone' => 'required|max:20',
            'city' => 'required',
            'zip' => 'required',
            'country' => 'required',
            'address' => 'required',
        ];

        if (! $this->hasAcceptedPhoneDigitLength()) {
            $rules['phone'] = 'required|min:5|max:20';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'contact_person_name.required' => translate(':field is required', ['field' => translate('Contact person name')]),
            'address_type.in' => translate('Choose a valid address type'),
            'address.required' => translate(':field is required', ['field' => translate('Address')]),
            'city.required' => translate(':field is required', ['field' => translate('City')]),
            'zip.required' => translate(':field is required', ['field' => translate('Zip code')]),
            'country.required' => translate(':field is required', ['field' => translate('Country')]),
            'email.email' => translate('Enter a valid email address'),
            'latitude.between' => translate('Latitude must be between -90 and 90'),
            'longitude.between' => translate('Longitude must be between -180 and 180'),
            'phone.min' => translate('The phone number must be at least 4 characters'),
            'phone.max' => translate('The phone number may not be greater than 20 characters'),
        ];
    }

    private function hasAcceptedPhoneDigitLength(): bool
    {
        $digitCount = strlen(preg_replace('/[^0-9]/', '', (string) $this['phone']));

        return $digitCount >= 4 && $digitCount <= 20;
    }
}
