<?php

namespace App\Http\Requests\Admin\Setting\System;

use App\Http\Requests\BaseRequest;

/**
 * @property string $pixel_analytics
 */
class SoftwareUpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'update_file' => 'required|mimes:zip',
            'username' => 'required',
            'purchase_key' => 'required|uuid',
        ];
    }

    public function messages(): array
    {
        return [
            'update_file.required' => translate('Update file is required'),
            'update_file.mimes' => translate('Update file must be a zip file'),
            'username.required' => translate('Username is required'),
            'purchase_key.required' => translate(':field is required', ['field' => translate('Purchase key')]),
            'purchase_key.uuid' => translate('Purchase key must be a valid uuid'),
        ];
    }
}
