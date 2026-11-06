<?php

namespace Modules\AI\app\Http\Requests\Shared\AuctionProduct;

use App\Http\Requests\BaseRequest;

class SeoSectionAutoFillRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate('Auction product name is required to generate the seo section'),
            'name.string' => translate('Auction product name is required to generate the seo section'),
            'name.max' => translate('Auction product name cannot be longer than 255 characters'),
            'description.string' => translate('Auction product description must be text to generate the seo section'),
        ];
    }
}
