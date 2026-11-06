<?php

namespace App\Http\Requests\Admin\Setting\System;

use App\Http\Requests\BaseRequest;

/**
 * @property string $type
 * @property string $url
 */
class LoginSetupUpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'type' => 'required',
            'url' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => translate('The type is required'),
            'url.required' => translate(':field is required', ['field' => translate('The url')]),
        ];
    }
}
