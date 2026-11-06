<?php

namespace Modules\TaxModule\app\Http\Requests\Vendor\Tax;

use App\Http\Requests\BaseRequest;

class ListRequest extends BaseRequest
{
    private const LIMIT_PATTERN = '/^(all|[1-9][0-9]*)$/';

    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'regex:'.self::LIMIT_PATTERN],
            'offset' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'limit.regex' => translate('Items per page must be a whole number of at least 1'),
            'offset.integer' => translate('Page number must be a whole number'),
            'offset.min' => translate('Page number must be a whole number'),
            'search.string' => translate('Enter valid search text'),
        ];
    }

    public function dataLimit(): int|string
    {
        return $this->validated('limit') ?? DEFAULT_DATA_LIMIT;
    }

    public function offset(): int
    {
        return (int) ($this->validated('offset') ?? 1);
    }
}
