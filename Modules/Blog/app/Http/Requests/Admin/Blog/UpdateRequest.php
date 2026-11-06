<?php

namespace Modules\Blog\app\Http\Requests\Admin\Blog;

use Illuminate\Validation\Validator;
use Modules\Blog\app\Http\Requests\BaseRequest;

/**
 * @property string $about_us
 */
class UpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = false;

    public function rules(): array
    {
        return [
            'title' => 'required|array',
            'description' => 'required|array',
            'image' => 'mimes:jpeg,jpg,png,gif,webp|max:10000',
            'writer' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:blog_categories,id',
            'publish_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        $messages = [
            'title.required' => translate('The title field is required'),
            'title.string' => translate('Please enter a valid title'),
            'title.max' => translate('The title may not be greater than 255 characters'),
            'description.required' => translate('The description field is required'),
            'description.array' => translate('The description is not in a valid format'),
            'image.mimes' => translate('The image must be a file of type jpeg,jpg,png,gif,webp'),
            'image.max' => translate('The image may not be greater than 10000 kilobytes'),
            'writer.string' => translate('Please enter a valid writer name'),
            'writer.max' => translate('The writer may not be greater than 255 characters'),
            'category_id.integer' => translate('Please select a valid category'),
            'category_id.exists' => translate('The selected category is not valid'),
            'publish_date.date' => translate('The publish date is not a valid date'),
        ];

        foreach (array_keys($this['lang']) as $locale) {
            $languageName = $this->getLanguageName(code: $locale);
            if ($locale == 'en') {
                $messages["title.$locale.required"] = translate('The title in :language is required', ['language' => $languageName]);
                $messages["description.$locale.required"] = translate('The description in :language is required', ['language' => $languageName]);
            }
            $messages["title.$locale.string"] = translate('The title in :language must be a string', ['language' => $languageName]);
            $messages["title.$locale.max"] = translate('The title in :language may not exceed 255 characters', ['language' => $languageName]);
            $messages["description.$locale.string"] = translate('The description in :language must be a string', ['language' => $languageName]);
        }

        return $messages;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $description = $this->input('description');

                if (is_array($description)) {
                    $first = reset($description);
                    $cleanedDescription = is_string($first) ? trim(strip_tags($first)) : null;
                } else {
                    $cleanedDescription = is_string($description) ? trim(strip_tags($description)) : null;
                }
                if (is_null($this['title'][array_search('en', $this['lang'])])) {
                    $validator->errors()->add(
                        'title', translate('Title field is required').'!'
                    );
                }
                if (empty($cleanedDescription)) {
                    $validator->errors()->add(
                        'description',
                        translate(':field is required', ['field' => translate('Description')]).'!'
                    );
                }
            },
        ];
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
}
