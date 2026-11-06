<?php

namespace App\Http\Requests\Shared\Order;

use App\Http\Requests\BaseRequest;
use App\Services\New\OrderService;
use Illuminate\Validation\Rule;

class AddressUpdateRequest extends BaseRequest
{
    public const ADDRESS_FIELDS = ['contact_person_name', 'phone', 'city', 'zip', 'email', 'address', 'latitude', 'longitude'];

    private const PANEL_ADDRESS_INPUTS = [
        'contact_person_name' => 'name',
        'phone' => 'phone_number',
        'country' => 'country',
        'city' => 'city',
        'zip' => 'zip',
        'address' => 'address',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
    ];

    public function rules(): array
    {
        $rules = [
            'address_type' => ['required', Rule::in([OrderService::ADDRESS_TYPE_SHIPPING, OrderService::ADDRESS_TYPE_BILLING])],
        ];

        foreach ($this->getAddressInputs() as $input) {
            $rules[$input] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'address_type.required' => translate('Choose whether to update the shipping or the billing address'),
            'address_type.in' => translate('The address type must be shipping or billing'),
            'contact_person_name.string' => translate('Enter a valid contact person name'),
            'contact_person_name.max' => translate('The contact person name is too long'),
            'name.string' => translate('Enter a valid contact person name'),
            'name.max' => translate('The contact person name is too long'),
            'phone.string' => translate('Enter a valid phone number'),
            'phone.max' => translate('The phone number is too long'),
            'phone_number.string' => translate('Enter a valid phone number'),
            'phone_number.max' => translate('The phone number is too long'),
            'country.string' => translate('Enter a valid country'),
            'country.max' => translate('The country is too long'),
            'city.string' => translate('Enter a valid city'),
            'city.max' => translate('The city is too long'),
            'zip.string' => translate('Enter a valid zip code'),
            'zip.max' => translate('The zip code is too long'),
            'email.string' => translate('Enter a valid email'),
            'email.max' => translate('The email is too long'),
            'address.string' => translate('Enter a valid address'),
            'address.max' => translate('The address is too long'),
            'latitude.string' => translate('Enter a valid latitude'),
            'latitude.max' => translate('The latitude is too long'),
            'longitude.string' => translate('Enter a valid longitude'),
            'longitude.max' => translate('The longitude is too long'),
        ];
    }

    /** @return array<string, string|null> */
    public function getAddressData(): array
    {
        $address = [];
        foreach ($this->getAddressInputs() as $field => $input) {
            $address[$field] = $this->validated($input);
        }

        return $address;
    }

    /** @return array<string, string> */
    private function getAddressInputs(): array
    {
        return $this->isApiRequest() ? array_combine(self::ADDRESS_FIELDS, self::ADDRESS_FIELDS) : self::PANEL_ADDRESS_INPUTS;
    }
}
