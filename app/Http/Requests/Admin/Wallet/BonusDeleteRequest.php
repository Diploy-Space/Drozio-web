<?php

namespace App\Http\Requests\Admin\Wallet;

use App\Http\Requests\BaseRequest;

/**
 * Class Bonus
 *
 * @property int $id
 */
class BonusDeleteRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'id' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate('Bonus is required'),
        ];
    }
}
