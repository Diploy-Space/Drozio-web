<?php

namespace App\Http\Requests\Admin\Setting\Website;

use App\Http\Requests\BaseRequest;

/**
 * @property int $id
 * @property string $name
 * @property string $image
 * @property int $status
 */
class FeatureSectionUpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = false;

    public function rules(): array
    {
        $rules = [];

        for ($i = 1; $i <= 4; $i++) {
            $rules["title_{$i}"] = ['required', 'string', 'max:40'];
            $rules["image_{$i}"] = ['sometimes', 'image', getFileUploadFormats(skip: '.svg', asRule: 'true'), 'max:'.getFileUploadMaxSize(unit: 'kb')];
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [];
        for ($i = 1; $i <= 4; $i++) {
            $messages["title_{$i}.required"] = translate('The title for item :number is required', ['number' => $i]);
            $messages["image_{$i}.sometimes"] = translate('The image for item :number is required', ['number' => $i]);
            $messages["image_{$i}.mimes"] = translate('The image for item :number must be a file of type :formats', ['number' => $i, 'formats' => getFileUploadFormats('skip: .svg', asMessage: 'true')]);
        }

        return $messages;
    }
}
