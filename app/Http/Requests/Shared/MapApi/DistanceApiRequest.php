<?php

namespace App\Http\Requests\Shared\MapApi;

use App\Http\Requests\BaseRequest;

class DistanceApiRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'origin_lat' => 'required|numeric|between:-90,90',
            'origin_lng' => 'required|numeric|between:-180,180',
            'destination_lat' => 'required|numeric|between:-90,90',
            'destination_lng' => 'required|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'origin_lat.required' => translate('The origin latitude is required'),
            'origin_lng.required' => translate('The origin longitude is required'),
            'destination_lat.required' => translate('The destination latitude is required'),
            'destination_lng.required' => translate('The destination longitude is required'),
            'origin_lat.between' => translate('Latitude must be between -90 and 90'),
            'destination_lat.between' => translate('Latitude must be between -90 and 90'),
            'origin_lng.between' => translate('Longitude must be between -180 and 180'),
            'destination_lng.between' => translate('Longitude must be between -180 and 180'),
        ];
    }
}
