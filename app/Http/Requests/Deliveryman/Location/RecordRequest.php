<?php

namespace App\Http\Requests\Deliveryman\Location;

use App\Http\Requests\BaseRequest;

class RecordRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'order_id' => 'required|integer',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => translate('The order is required'),
            'latitude.required' => translate('Latitude is required'),
            'latitude.between' => translate('Latitude must be between -90 and 90'),
            'longitude.required' => translate('Longitude is required'),
            'longitude.between' => translate('Longitude must be between -180 and 180'),
            'location.required' => translate('The location name is required'),
            'location.max' => translate('The location must not be longer than 255 characters'),
        ];
    }
}
