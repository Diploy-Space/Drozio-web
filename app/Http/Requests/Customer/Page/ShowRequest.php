<?php

namespace App\Http\Requests\Customer\Page;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ShowRequest extends BaseRequest
{
    public const SOURCE_ADMIN_PREVIEW = 'admin';

    public function rules(): array
    {
        return [
            'source' => ['nullable', Rule::in([self::SOURCE_ADMIN_PREVIEW])],
        ];
    }

    public function messages(): array
    {
        return [
            'source.in' => translate('Please select a valid page source'),
        ];
    }

    public function isAdminPreview(): bool
    {
        return $this['source'] === self::SOURCE_ADMIN_PREVIEW;
    }
}
