<?php

namespace App\Http\Requests\Shared\Profile;

use App\Http\Requests\BaseRequest;
use App\Services\New\BusinessSettingService;
use Illuminate\Validation\Rule;

class LanguageRequest extends BaseRequest
{
    public function __construct(private readonly BusinessSettingService $businessSettingService)
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'current_language' => ['required', 'string', Rule::in($this->enabledLanguageCodes())],
        ];
    }

    public function messages(): array
    {
        return [
            'current_language.required' => translate('Choose a language'),
            'current_language.in' => translate('This language is not available'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function enabledLanguageCodes(): array
    {
        $languages = $this->businessSettingService->getValueByType(type: 'language', default: []);

        if (! is_array($languages)) {
            return [];
        }

        return collect($languages)
            ->filter(static fn ($language): bool => (int) ($language['status'] ?? 0) === 1)
            ->pluck('code')
            ->filter()
            ->values()
            ->all();
    }
}
