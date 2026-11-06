<?php

namespace App\Http\Requests\Admin\Category;

use App\Http\Requests\BaseRequest;
use App\Services\New\CategoryService;
use App\Services\New\ProductRetailService;
use Illuminate\Validation\Rule;

class DeleteRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    private ?array $categoryIds = null;

    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly ProductRetailService $productService,
    ) {}

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', Rule::in($this->categoryIds())],
            'category_id' => [
                Rule::requiredIf(fn (): bool => $this->hasProducts()),
                'nullable',
                'integer',
                'different:id',
                Rule::in($this->categoryIds()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate('Category not found.'),
            'id.integer' => translate('Category not found.'),
            'id.in' => translate('Category not found.'),
            'category_id.required' => translate('This category has products. Select a category to move them to before deleting it.'),
            'category_id.integer' => translate('Select a valid category to move the products to.'),
            'category_id.different' => translate('Select a different category to move the products to.'),
            'category_id.in' => translate('Select a valid category to move the products to.'),
        ];
    }

    private function hasProducts(): bool
    {
        return $this->productService->count(filters: ['category_id' => (int) $this['id']]) > 0;
    }

    private function categoryIds(): array
    {
        return $this->categoryIds ??= $this->categoryService->getIds(filters: ['position' => CategoryService::POSITION_CATEGORY]);
    }
}
