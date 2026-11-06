<?php

namespace App\Http\Requests\Admin\SubCategory;

use App\Http\Requests\BaseRequest;
use App\Services\New\CategoryService;
use Illuminate\Validation\Validator;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class UpdateRequest extends BaseRequest
{
    use VatTaxManagement;

    protected $stopOnFirstFailure = false;

    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    public function rules(): array
    {
        return [
            'name' => [
                'required',
            ],
            'name.*' => ['nullable', 'string', 'max:'.CategoryService::NAME_MAX_LENGTH],
            'image' => getRulesStringForImageValidation(
                rules: ['nullable'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
            ),
            'priority' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate(':field is required', ['field' => translate('Category Name')]),
            'name.*.max' => translate('Category name must not be longer than :max characters', ['max' => CategoryService::NAME_MAX_LENGTH]),
            'image.mimes' => translate('Category image must be').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'image.max' => translate('Category image must not exceed').getFileUploadMaxSize().'MB',
            'priority.required' => translate(':field is required', ['field' => translate('Category priority')]),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $isDuplicate = $this->categoryService->isDuplicateName(
                    name: $this['name'][0] ?? '',
                    position: (int) ($this['position'] ?? 1),
                    parentId: $this['parent_id'] ?? null,
                    excludeId: $this['id'],
                );

                if (isset($this['name'][0]) && $isDuplicate) {
                    $validator->errors()->add(
                        'name.unique', translate('Name has been taken').'!'
                    );
                }

                if ($this->getTaxSystemType()['categoryWiseTax'] && empty($this['tax_ids'])) {
                    $validator->errors()->add(
                        'tax', translate('Please add your category tax').'!'
                    );
                }
            },
        ];
    }
}
