<?php

namespace App\Http\Requests\Admin\Category;

use App\Http\Requests\BaseRequest;
use App\Services\New\CategoryService;
use Illuminate\Validation\Validator;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class AddRequest extends BaseRequest
{
    use VatTaxManagement;

    protected $stopOnFirstFailure = false;

    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    public function rules(): array
    {
        return [
            'name' => 'required|array',
            'name.0' => 'required',
            'name.*' => ['nullable', 'string', 'max:'.CategoryService::NAME_MAX_LENGTH],
            'image' => getRulesStringForImageValidation(
                rules: ['required', 'image'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
                isDisallowed: true
            ),
            'priority' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate(':field is required', ['field' => translate('Category Name')]),
            'name.*.max' => translate('Category name must not be longer than :max characters', ['max' => CategoryService::NAME_MAX_LENGTH]),
            'name.0.required' => translate('The name field is required'),
            'image.required' => translate('Category image is required'),
            'image.mimes' => translate('Category image must be').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'image.max' => translate('Category image must not exceed').getFileUploadMaxSize().'MB',
            'priority.required' => translate(':field is required', ['field' => translate('Category priority')]),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (isset($this['name'][0]) && $this->categoryService->isDuplicateName($this['name'][0], (int) ($this['position'] ?? 0))) {
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
