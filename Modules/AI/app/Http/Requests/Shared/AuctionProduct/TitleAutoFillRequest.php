<?php

namespace Modules\AI\app\Http\Requests\Shared\AuctionProduct;

use App\Http\Requests\BaseRequest;

class TitleAutoFillRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'langCode' => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate('Auction product name is required to generate the title'),
            'name.string' => translate('Auction product name is required to generate the title'),
            'name.max' => translate('Auction product name cannot be longer than 255 characters'),
            'langCode.string' => translate('Please choose a valid language'),
            'langCode.max' => translate('Please choose a valid language'),
        ];
    }
}
