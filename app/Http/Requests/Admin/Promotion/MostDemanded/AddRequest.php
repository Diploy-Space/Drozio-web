<?php

namespace App\Http\Requests\Admin\Promotion\MostDemanded;

use App\Http\Requests\BaseRequest;

/**
 * @property string $product_id
 * @property string $image
 */
class AddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'product_id' => 'required',
            'image' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => translate('The product field is required'),
            'image.required' => translate('The image is required'),
        ];
    }
}
