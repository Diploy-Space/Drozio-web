<?php

namespace Modules\AI\app\Http\Requests\Shared\Product;

use App\Http\Requests\BaseRequest;

class TitleSuggestionRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $keywords = $this->input('keywords');

        if (is_string($keywords)) {
            $keywords = array_values(array_filter(
                array_map('trim', explode(',', $keywords)),
                fn ($keyword) => $keyword !== ''
            ));
        }

        $this->merge(['keywords' => $keywords]);
    }

    public function rules(): array
    {
        return [
            'keywords' => 'required|array|min:1|max:20',
            'keywords.*' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'keywords.required' => translate('Please add at least one keyword to generate title suggestions'),
            'keywords.array' => translate('Please add at least one keyword to generate title suggestions'),
            'keywords.min' => translate('Please add at least one keyword to generate title suggestions'),
            'keywords.max' => translate('Please add no more than 20 keywords to generate title suggestions'),
            'keywords.*.required' => translate('Please remove the empty keyword before generating title suggestions'),
            'keywords.*.string' => translate('Please remove the empty keyword before generating title suggestions'),
            'keywords.*.max' => translate('A keyword cannot be longer than 255 characters'),
            'description.string' => translate('Product description is required to generate title suggestions'),
        ];
    }
}
