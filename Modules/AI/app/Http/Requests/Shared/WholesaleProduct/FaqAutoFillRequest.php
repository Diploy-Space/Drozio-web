<?php

namespace Modules\AI\app\Http\Requests\Shared\WholesaleProduct;

use App\Http\Requests\BaseRequest;

class FaqAutoFillRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'langCode' => 'nullable|string|max:10',
            'existing_questions' => 'nullable|array',
            'existing_questions.*' => 'nullable|string|max:255',
            'translation_languages' => 'nullable|array',
            'translation_languages.*' => 'nullable|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate('Product name is required to generate FAQs'),
            'name.string' => translate('Product name is required to generate FAQs'),
            'name.max' => translate('Product name cannot be longer than 255 characters'),
            'description.required' => translate('Product description is required to generate FAQs'),
            'description.string' => translate('Product description is required to generate FAQs'),
            'langCode.string' => translate('Please choose a valid language to generate FAQs.'),
            'langCode.max' => translate('Please choose a valid language to generate FAQs.'),
            'existing_questions.array' => translate('Please send the questions already added as a list to generate a new FAQ.'),
            'existing_questions.*.string' => translate('Each question already added must be written as text.'),
            'existing_questions.*.max' => translate('Each question already added must be 255 characters or fewer.'),
            'translation_languages.array' => translate('Please send the languages to translate the FAQ into as a list.'),
            'translation_languages.*.string' => translate('Please choose a valid language to translate the FAQ into.'),
            'translation_languages.*.max' => translate('Please choose a valid language to translate the FAQ into.'),
        ];
    }

    public function existingQuestions(): array
    {
        return array_values(array_filter(
            (array) $this->input('existing_questions', []),
            fn ($question): bool => is_string($question) && trim($question) !== '',
        ));
    }

    public function translationLanguages(): array
    {
        return array_values(array_unique(array_filter(
            (array) $this->input('translation_languages', []),
            fn ($language): bool => is_string($language) && trim($language) !== '',
        )));
    }
}
