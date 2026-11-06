<?php

namespace App\Http\Requests\Admin\Vendor\Registration;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class SellWithUsSectionUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:50',
            'sub_title' => 'required|string|max:50',
            'image' => getRulesStringForImageValidation(
                rules: ['sometimes', 'file'],
                skipMimes: ['.svg', '.gif'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: 'true'
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => translate(':field is required', ['field' => translate('Title')]),
            'sub_title.required' => translate(':field is required', ['field' => translate('Sub Title')]),
            'image.required' => translate(':field is required', ['field' => translate('Image')]),
            'image.mimes' => translate('Image type must be').' '.getFileUploadFormats(skip: '.svg,.gif'),
            'image.max' => translate('Image max size is').' '.getFileUploadMaxSize().' MB',
        ];
    }
}
