<?php

namespace App\Http\Requests\Shared\MapApi;

use App\Http\Requests\BaseRequest;

class GeocodeApiRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => translate('Latitude is required'),
            'lat.numeric' => translate('Latitude must be a number'),
            'lat.between' => translate('Latitude must be between -90 and 90'),
            'lng.required' => translate('Longitude is required'),
            'lng.numeric' => translate('Longitude must be a number'),
            'lng.between' => translate('Longitude must be between -180 and 180'),
        ];
    }
}
