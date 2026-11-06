<?php

namespace App\Http\Requests\Admin\SubCategory;

use App\Http\Requests\BaseRequest;
use App\Services\New\CategoryService;
use Illuminate\Validation\Validator;

class AddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = false;

    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    public function rules(): array
    {
        $rules = [
            'name' => 'required',
            'name.*' => ['nullable', 'string', 'max:'.CategoryService::NAME_MAX_LENGTH],
            'priority' => 'required',
            'parent_id' => 'required',
        ];

        if (theme_root_path() == 'theme_aster' && $this['position'] == 1) {
            $rules['image'] = getRulesStringForImageValidation(
                rules: ['required'],
                skipMimes: ['.svg'],
                maxSize: getFileUploadMaxSize(unit: 'kb'),
            );
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => translate(':field is required', ['field' => translate('Category Name')]),
            'name.*.max' => translate('Category name must not be longer than :max characters', ['max' => CategoryService::NAME_MAX_LENGTH]),
            'priority.required' => translate(':field is required', ['field' => translate('Category priority')]),
            'parent_id.required' => $this->parentRequiredMessage(),
            'image.required' => translate('Category image is required'),
            'image.mimes' => translate('Category image must be').getFileUploadFormats(skip: '.svg', asMessage: 'true'),
            'image.max' => translate('Category image must not exceed').getFileUploadMaxSize().'MB',
        ];
    }

    private function parentRequiredMessage(): string
    {
        if ((int) $this['position'] === CategoryService::POSITION_SUB_SUB_CATEGORY) {
            return translate(':field is required', ['field' => translate('Sub Category Name')]);
        }

        return translate('Main Category is required');
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $isDuplicate = $this->categoryService->isDuplicateName(
                    name: $this['name'][0] ?? '',
                    position: (int) ($this['position'] ?? 1),
                    parentId: $this['parent_id'] ?? null,
                );

                if (isset($this['name'][0]) && $isDuplicate) {
                    $validator->errors()->add(
                        'name.unique', translate('Name has been taken').'!'
                    );
                }
            },
        ];
    }
}
