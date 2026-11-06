<?php

namespace App\Http\Requests\Admin\Setting\System;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Validator;

class LanguageUpdateRequest extends BaseRequest
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

                // renaming to a name another language already uses is a clash; keeping your own name is not
                if (in_array($this['name'], $existingNames) && $languages->where('name', $this['name'])->first()['code'] != $this['code']) {
                    $validator->errors()->add(
                        'name', translate('The language name must be unique').'!'
                    );
                }
            },
        ];
    }
}
