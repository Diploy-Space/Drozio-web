<?php

namespace App\Http\Requests\Admin\Setting\System;

use App\Http\Requests\BaseRequest;

class AddonUploadRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'file_upload' => 'required|mimes:zip',
        ];
    }

    public function messages(): array
    {
        return [
            'file_upload.required' => translate('The file is required'),
            'file_upload.mimes' => translate('The file must be a zip file'),
        ];
    }
}
