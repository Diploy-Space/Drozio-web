<?php

namespace App\Http\Requests\Shared\AdvancedSearch;

use App\Enums\BusinessModule;
use App\Http\Requests\BaseRequest;
use App\Services\BusinessModule\BusinessModuleManager;
use Illuminate\Validation\Rule;

class SearchRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'business_module' => ['nullable', Rule::in(BusinessModule::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string' => translate('Please enter a valid search keyword'),
            'keyword.max' => translate('A keyword cannot be longer than 255 characters'),
            'business_module.in' => translate('Invalid business module selected!'),
        ];
    }

    public function searchKeyword(): string
    {
        return trim((string) $this->validated('keyword'));
    }

    public function resolvedBusinessModule(BusinessModuleManager $manager): string
    {
        $permittedModules = $manager->permittedBusinessModules();

        return $this->namedBusinessModule(permittedModules: $permittedModules)['key']
            ?? $this->postedBusinessModule(permittedModules: $permittedModules)
            ?? $manager->activeKey();
    }

    public function isBusinessModuleNamed(BusinessModuleManager $manager): bool
    {
        return $this->namedBusinessModule(permittedModules: $manager->permittedBusinessModules())['key'] !== null;
    }

    public function recordSearchKeyword(BusinessModuleManager $manager): string
    {
        $moduleName = $this->namedBusinessModule(permittedModules: $manager->permittedBusinessModules())['name'];

        if ($moduleName === null) {
            return $this->searchKeyword();
        }

        $remainder = trim((string) preg_replace('/(^|\s)'.preg_quote($moduleName, '/').'(?=\s|$)/iu', ' ', $this->normalizedKeyword(), 1));

        return $remainder === '' ? $this->searchKeyword() : (string) preg_replace('/\s+/u', ' ', $remainder);
    }

    private function namedBusinessModule(array $permittedModules): array
    {
        $keyword = mb_strtolower($this->normalizedKeyword());
        $moduleNames = [];

        foreach ($permittedModules as $key => $module) {
            $moduleNames[$key] = array_unique([mb_strtolower($key), mb_strtolower($module->label)]);
        }

        foreach ($moduleNames as $key => $names) {
            foreach ($names as $name) {
                if (str_starts_with($keyword.' ', $name.' ')) {
                    return ['key' => $key, 'name' => $name];
                }
            }
        }

        foreach ($moduleNames as $key => $names) {
            foreach ($names as $name) {
                if (str_contains(' '.$keyword.' ', ' '.$name.' ')) {
                    return ['key' => $key, 'name' => $name];
                }
            }
        }

        return ['key' => null, 'name' => null];
    }

    private function normalizedKeyword(): string
    {
        return (string) preg_replace('/\s+/u', ' ', $this->searchKeyword());
    }

    private function postedBusinessModule(array $permittedModules): ?string
    {
        $postedModule = $this->validated('business_module');

        return is_string($postedModule) && array_key_exists($postedModule, $permittedModules) ? $postedModule : null;
    }
}
