<?php

namespace App\Http\Requests\Admin\Setting\System;

use App\Http\Requests\BaseRequest;
use App\Services\New\AddonService;
use Illuminate\Validation\Rule;

class AddonInstallationCompleteRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function __construct(
        private readonly AddonService $addonService,
    ) {}

    public function rules(): array
    {
        return [
            'path' => ['required', 'string', Rule::in($this->addonService->getInstalledPaths())],
        ];
    }

    public function messages(): array
    {
        return [
            'path.required' => translate('Select the addon to finish updating'),
            'path.string' => translate('Select the addon to finish updating'),
            'path.in' => translate('This addon is not installed'),
        ];
    }
}
