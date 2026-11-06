<?php

namespace App\Http\Requests\Admin\Product;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class BulkImportRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'products_file' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'products_file.required' => translate('Products file is required'),
        ];
    }
}
