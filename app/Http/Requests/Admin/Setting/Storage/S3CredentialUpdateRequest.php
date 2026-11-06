<?php

namespace App\Http\Requests\Admin\Setting\Storage;

use App\Http\Requests\BaseRequest;

class S3CredentialUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            's3_key' => 'required',
            's3_secret' => 'required',
            's3_region' => 'required',
            's3_bucket' => 'required',
            's3_url' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            's3_key.required' => translate('The key field is required'),
            's3_secret.required' => translate('The secret field is required'),
            's3_region.required' => translate('The region field is required'),
            's3_bucket.required' => translate('The bucket field is required'),
            's3_url.required' => translate('The url field is required'),
        ];
    }
}
