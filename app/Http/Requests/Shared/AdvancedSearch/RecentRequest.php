<?php

namespace App\Http\Requests\Shared\AdvancedSearch;

use App\Enums\BusinessModule;
use App\Http\Requests\BaseRequest;
use App\Services\New\AdvancedSearchService;
use Closure;
use Illuminate\Validation\Rule;

class RecentRequest extends BaseRequest
{
    private const IMAGE_URL_MAX_LENGTH = 2048;

    private const ABSOLUTE_URI_PATTERN = '/^\s*([a-z][a-z0-9+.\-]*:|[\/\\\\]{2})/i';

    private const VENDOR_PATH_PATTERN = 'vendor/*';

    private ?AdvancedSearchService $panelSearchService = null;

    public function __construct(
        private readonly AdvancedSearchService $advancedSearchService,
    ) {}

    public function rules(): array
    {
        $searchService = $this->panelSearchService();

        return [
            'routeName' => ['required', 'string', 'max:255'],
            'routeUri' => [
                'required',
                'string',
                'max:255',
                'not_regex:'.self::ABSOLUTE_URI_PATTERN,
                function (string $attribute, mixed $value, Closure $fail) use ($searchService): void {
                    if (! is_string($value) || preg_match(self::ABSOLUTE_URI_PATTERN, $value) === 1) {
                        return;
                    }

                    if (! str_starts_with(ltrim($value, '/'), $searchService->getUriPrefix())) {
                        $fail($this->messages()['routeUri.not_regex']);

                        return;
                    }

                    if (! $searchService->isReachable(uri: $value)) {
                        $fail($this->messages()['routeUri.reachable']);
                    }
                },
            ],
            'searchKeyword' => ['nullable', 'string', 'max:255'],
            'business_module' => ['required', Rule::in([...BusinessModule::values(), BusinessModule::COMMON])],
            'type' => ['required', 'string', Rule::in($searchService->getResultTypes())],
            'image' => ['nullable', 'string', 'max:'.self::IMAGE_URL_MAX_LENGTH, 'url:http,https'],
        ];
    }

    public function messages(): array
    {
        return [
            'routeName.required' => translate('The search result has no title'),
            'routeName.string' => translate('The search result title is invalid'),
            'routeName.max' => translate('The search result title cannot be longer than 255 characters'),
            'routeUri.required' => translate('The search result has no link'),
            'routeUri.string' => translate('The search result link is invalid'),
            'routeUri.max' => translate('The search result link cannot be longer than 255 characters'),
            'routeUri.not_regex' => translate('The search result link must point to a page in this panel'),
            'routeUri.reachable' => translate('The search result link points to a page that does not exist'),
            'searchKeyword.string' => translate('Please enter a valid search keyword'),
            'searchKeyword.max' => translate('A keyword cannot be longer than 255 characters'),
            'business_module.required' => translate('The search result has no business module'),
            'business_module.in' => translate('Invalid business module selected!'),
            'type.required' => translate('The search result has no type'),
            'type.string' => translate('The search result type is invalid'),
            'type.in' => translate('The search result type is invalid'),
            'image.string' => translate('The search result image is invalid'),
            'image.max' => translate('The search result image link is too long'),
            'image.url' => translate('The search result image is invalid'),
        ];
    }

    public function searchKeyword(): string
    {
        return trim((string) $this->validated('searchKeyword'));
    }

    private function panelSearchService(): AdvancedSearchService
    {
        return $this->panelSearchService ??= $this->advancedSearchService->forPanel(
            panel: $this->is(self::VENDOR_PATH_PATTERN) ? AdvancedSearchService::PANEL_VENDOR : AdvancedSearchService::PANEL_ADMIN,
        );
    }
}
