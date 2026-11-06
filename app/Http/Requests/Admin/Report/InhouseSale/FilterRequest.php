<?php

namespace App\Http\Requests\Admin\Report\InhouseSale;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class FilterRequest extends BaseRequest
{
    private const CATEGORY_ALL = 'all';

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', Rule::when($this['category_id'] !== self::CATEGORY_ALL, ['integer', 'min:1'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.integer' => translate('Choose a valid category'),
            'category_id.min' => translate('Choose a valid category'),
            'page.integer' => translate('Choose a valid page'),
            'page.min' => translate('Choose a valid page'),
        ];
    }

    public function categoryId(): ?int
    {
        $categoryId = $this->validated('category_id');

        return $categoryId === null || $categoryId === self::CATEGORY_ALL ? null : (int) $categoryId;
    }
}
