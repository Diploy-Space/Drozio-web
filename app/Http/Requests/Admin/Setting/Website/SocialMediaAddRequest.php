<?php

namespace App\Http\Requests\Admin\Setting\Website;

use App\Http\Requests\BaseRequest;

/**
 * @property string $name
 * @property string $link
 */
class SocialMediaAddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'name' => 'required|unique:social_medias',
            'link' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate('The name field is required'),
            'name.unique' => translate('This social media is already added to the list'),
            'link.required' => translate('The link field is required'),
        ];
    }
}
