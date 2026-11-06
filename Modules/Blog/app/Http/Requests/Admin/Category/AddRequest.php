<?php

namespace Modules\Blog\app\Http\Requests\Admin\Category;

use Illuminate\Validation\Validator;
use Modules\Blog\app\Http\Requests\BaseRequest;
use Modules\Blog\app\Services\New\BlogCategoryService;

class AddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function __construct(
        private readonly BlogCategoryService $blogCategoryService,
    ) {
        parent::__construct();
    }

    public function rules(): array
    {
        $this->merge([
            'lang' => array_combine(array_keys($this['lang']), array_keys($this['lang'])),
        ]);

        $rules = [];
        foreach (array_keys($this['lang']) as $locale) {
            $rules["name.$locale"] = $locale == 'en' ? 'required|string|max:255' : 'nullable|string|max:255';
        }

        $rules += [
            'name' => 'required|array',
        ];

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'name.required' => translate('The name field is required'),
            'name.string' => translate('Please enter a valid name'),
            'name.max' => translate('The name may not be greater than 255 characters'),
        ];

        foreach (array_keys($this['lang']) as $locale) {
            $languageName = $this->getLanguageName(code: $locale);
            if ($locale == 'en') {
                $messages["name.$locale.required"] = translate('The name in :language is required', ['language' => $languageName]);
            }
            $messages["name.$locale.string"] = translate('The name in :language must be a string', ['language' => $languageName]);
            $messages["name.$locale.max"] = translate('The name in :language may not exceed 255 characters', ['language' => $languageName]);
        }

        return $messages;
    }

    public function getLanguageName($code): mixed
    {
        $name = 'english';
        foreach (getWebConfig('language') as $language) {
            if ($language['code'] == $code) {
                $name = $language['name'];
            }
        }

        return $name;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->blogCategoryService->nameExists(name: $this['name']['en'])) {
                    $validator->errors()->add(
                        'name', translate('The name must be unique')
                    );
                }
            },
        ];
    }
}
