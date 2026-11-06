<?php

namespace App\Http\Requests\Admin\Setting\System;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Validator;

class LanguageAddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'name' => 'required',
            'code' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate('Language name field is required!'),
            'code.required' => translate('Language code field is required!'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {

                $languages = collect(getWebConfig('language') ?? []);
                $existingNames = $languages->pluck('name')->toArray();
                $existingCodes = $languages->pluck('code')->toArray();

                if (in_array($this['name'], $existingNames)) {
                    $validator->errors()->add(
                        'name', translate('The language name must be unique').'!'
                    );
                }

                if (in_array($this['code'], $existingCodes)) {
                    $validator->errors()->add(
                        'code', translate('The language code must be unique').'!'
                    );
                }
            },
        ];
    }
}
