<?php

namespace App\Http\Requests\Deliveryman\Order;

use App\Http\Requests\BaseRequest;

class PauseUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'is_pause' => 'required|in:0,1',
            'cause' => 'nullable|string|max:191',
        ];
    }

    public function messages(): array
    {
        return [
            'is_pause.required' => translate('Choose whether the order is paused'),
            'is_pause.in' => translate('Choose a valid pause status'),
            'cause.string' => translate('The cause must be text'),
            'cause.max' => translate('The cause must not be longer than 191 characters'),
        ];
    }
}
