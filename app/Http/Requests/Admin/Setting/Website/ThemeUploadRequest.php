<?php

namespace App\Http\Requests\Admin\Setting\Website;

use App\Http\Requests\BaseRequest;

/**
 * @property string $theme_upload
 */
class ThemeUploadRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'theme_upload' => 'required|mimes:zip',
        ];
    }

    public function messages(): array
    {
        return [
            'theme_upload.required' => translate('The theme upload is required.'),
            'theme_upload.mimes' => translate('The theme must be a ZIP file.'),
        ];
    }
}
