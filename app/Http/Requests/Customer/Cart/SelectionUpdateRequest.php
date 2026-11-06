<?php

namespace App\Http\Requests\Customer\Cart;

use App\Http\Requests\BaseRequest;

class SelectionUpdateRequest extends BaseRequest
{
    public const ACTION_CHECKED = 'checked';

    public const ACTION_UNCHECKED = 'unchecked';

    public function rules(): array
    {
        return [
            'action' => 'required|in:'.self::ACTION_CHECKED.','.self::ACTION_UNCHECKED,
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => translate('Choose whether to select or unselect the cart items'),
            'action.in' => translate('Choose whether to select or unselect the cart items'),
            'ids.required' => translate('Choose at least one cart item'),
            'ids.array' => translate('Choose at least one cart item'),
            'ids.*.integer' => translate('A selected cart item could not be identified'),
        ];
    }

    public function isChecked(): bool
    {
        return $this->input('action') === self::ACTION_CHECKED;
    }
}
