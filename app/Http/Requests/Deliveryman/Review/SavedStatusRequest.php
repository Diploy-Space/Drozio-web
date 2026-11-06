<?php

namespace App\Http\Requests\Deliveryman\Review;

use App\Http\Requests\BaseRequest;

class SavedStatusRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'is_saved' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'is_saved.required' => translate('Choose whether to save this review'),
            'is_saved.boolean' => translate('The saved status must be either 0 or 1'),
        ];
    }
}
