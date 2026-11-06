<?php

namespace App\Http\Requests\Shared\MapApi;

use App\Http\Requests\BaseRequest;

class PlaceApiDetailsRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->route('placeid') !== null) {
            $this->merge(['placeid' => $this->route('placeid')]);
        }
    }

    public function rules(): array
    {
        return [
            'placeid' => 'required|string|max:255|regex:/^[A-Za-z0-9_-]+$/',
            'session_token' => 'nullable|string|max:191',
        ];
    }

    public function messages(): array
    {
        return [
            'placeid.required' => translate('Please select a location'),
            'placeid.regex' => translate('The selected location is not valid'),
            'placeid.max' => translate('The selected location is not valid'),
        ];
    }
}
