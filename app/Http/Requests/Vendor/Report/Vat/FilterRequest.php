<?php

namespace App\Http\Requests\Vendor\Report\Vat;

use App\Http\Requests\BaseRequest;
use Carbon\Carbon;
use Closure;
use Illuminate\Validation\Rule;

class FilterRequest extends BaseRequest
{
    public const EXPORT_EXCEL = 'excel';

    public const EXPORT_CSV = 'csv';

    private const DATE_RANGE_SEPARATOR = ' - ';

    private const DEFAULT_RANGE_DAYS = 6;

    public function rules(): array
    {
        return [
            'dates' => ['nullable', 'string', 'max:100', function (string $attribute, mixed $value, Closure $fail): void {
                if ($this->dateRangeParts($value) === null) {
                    $fail(translate('Choose a valid date range'));
                }
            }],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'export_type' => ['nullable', Rule::in([self::EXPORT_EXCEL, self::EXPORT_CSV])],
        ];
    }

    public function messages(): array
    {
        return [
            'dates.string' => translate('Choose a valid date range'),
            'dates.max' => translate('Choose a valid date range'),
            'search.string' => translate('Enter a valid search term'),
            'search.max' => translate('The search term is too long'),
            'page.integer' => translate('Choose a valid page'),
            'page.min' => translate('Choose a valid page'),
            'export_type.in' => translate('Choose a valid export type'),
        ];
    }

    public function startDate(): Carbon
    {
        $parts = $this->dateRangeParts($this->validated('dates'));

        return ($parts === null ? now()->subDays(self::DEFAULT_RANGE_DAYS) : Carbon::parse($parts[0]))->startOfDay();
    }

    public function endDate(): Carbon
    {
        $parts = $this->dateRangeParts($this->validated('dates'));

        return ($parts === null ? now() : Carbon::parse($parts[1]))->endOfDay();
    }

    public function exportExtension(): string
    {
        return $this->validated('export_type') === self::EXPORT_CSV ? 'csv' : 'xlsx';
    }

    /** @return array{0: string, 1: string}|null */
    private function dateRangeParts(mixed $dates): ?array
    {
        if (! is_string($dates) || ! str_contains($dates, self::DATE_RANGE_SEPARATOR)) {
            return null;
        }

        [$start, $end] = explode(self::DATE_RANGE_SEPARATOR, $dates);

        return strtotime(trim($start)) === false || strtotime(trim($end)) === false ? null : [trim($start), trim($end)];
    }
}
