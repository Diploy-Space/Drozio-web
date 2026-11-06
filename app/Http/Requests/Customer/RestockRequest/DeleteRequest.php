<?php

namespace App\Http\Requests\Customer\RestockRequest;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class DeleteRequest extends BaseRequest
{
    public const TYPE_ALL = 'all';

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::in([self::TYPE_ALL])],
            'id' => ['required_unless:type,'.self::TYPE_ALL, 'nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.string' => translate('The removal type is not valid'),
            'type.in' => translate('The removal type is not valid'),
            'id.required_unless' => translate('Please select a restock request to remove'),
            'id.integer' => translate('The selected restock request is not valid'),
        ];
    }

    public function isRemovingAll(): bool
    {
        return $this->input('type') === self::TYPE_ALL;
    }
}
