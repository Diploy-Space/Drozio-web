<?php

namespace App\Http\Requests\Admin\Report\Tax;

use App\Http\Requests\BaseRequest;
use App\Services\New\TaxReportService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class FilterRequest extends BaseRequest
{
    public const EXPORT_TYPE_EXCEL = 'excel';

    public const EXPORT_TYPE_CSV = 'csv';

    private const DATE_RANGE_SEPARATOR = ' - ';

    private const DEFAULT_RANGE_DAYS = 6;

    public function rules(): array
    {
        return [
            'date_range_type' => ['nullable', 'required_with:calculate_tax_on', Rule::in(TaxReportService::DATE_RANGE_TYPES)],
            'dates' => ['nullable', 'string', 'regex:/^.+'.self::DATE_RANGE_SEPARATOR.'.+$/'],
            'calculate_tax_on' => ['nullable', Rule::in(TaxReportService::CALCULATE_ON_OPTIONS)],
            'tax_rate' => ['nullable', 'array'],
            'tax_rate.*' => ['nullable', 'alpha_num'],
            'tax_on_order_commission' => ['nullable', 'array'],
            'tax_on_order_commission.*' => ['nullable', 'integer'],
            'tax_on_delivery_charge_commission' => ['nullable', 'array'],
            'tax_on_delivery_charge_commission.*' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
            'export_type' => ['nullable', Rule::in([self::EXPORT_TYPE_EXCEL, self::EXPORT_TYPE_CSV])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_range_type.required_with' => translate('Please select a date range type'),
            'date_range_type.in' => translate('Choose a valid date range'),
            'dates.string' => translate('Choose a valid date range'),
            'dates.regex' => translate('Choose a valid date range'),
            'calculate_tax_on.in' => translate('Choose how the tax should be calculated'),
            'tax_rate.array' => translate('Choose a valid tax rate'),
            'tax_rate.*.alpha_num' => translate('Choose a valid tax rate'),
            'tax_on_order_commission.array' => translate('Choose a valid tax rate for order commission'),
            'tax_on_order_commission.*.integer' => translate('Choose a valid tax rate for order commission'),
            'tax_on_delivery_charge_commission.array' => translate('Choose a valid tax rate for delivery charge'),
            'tax_on_delivery_charge_commission.*.integer' => translate('Choose a valid tax rate for delivery charge'),
            'search.string' => translate('Enter a valid search term'),
            'search.max' => translate('The search term is too long'),
            'export_type.in' => translate('Choose a valid export type'),
            'page.integer' => translate('Choose a valid page'),
            'page.min' => translate('Choose a valid page'),
        ];
    }

    public function canGenerateReport(): bool
    {
        return ! empty($this['date_range_type']) && ! empty($this['calculate_tax_on']);
    }

    public function calculateTaxOn(): string
    {
        return (string) $this['calculate_tax_on'];
    }

    /** @return array<int, int|string> */
    public function taxRateIds(): array
    {
        return $this['tax_rate'] ?? [];
    }

    /** @return array<int, int|string> */
    public function orderCommissionTaxIds(): array
    {
        return $this['tax_on_order_commission'] ?? [];
    }

    /** @return array<int, int|string> */
    public function deliveryChargeTaxIds(): array
    {
        return $this['tax_on_delivery_charge_commission'] ?? [];
    }

    /** @return array<int, int|string> */
    public function selectedTaxIds(): array
    {
        return collect([...$this->taxRateIds(), ...$this->orderCommissionTaxIds(), ...$this->deliveryChargeTaxIds()])
            ->unique()->filter()->values()->all();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function dateRange(): array
    {
        if ($this['date_range_type'] === TaxReportService::DATE_RANGE_THIS_FISCAL_YEAR) {
            return [now()->startOfYear()->startOfDay(), now()->endOfDay()];
        }

        if (! empty($this['dates'])) {
            [$start, $end] = explode(self::DATE_RANGE_SEPARATOR, $this['dates']);

            return [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()];
        }

        return [now()->subDays(self::DEFAULT_RANGE_DAYS)->startOfDay(), now()->endOfDay()];
    }

    public function exportExtension(): string
    {
        return $this['export_type'] === self::EXPORT_TYPE_CSV ? 'csv' : 'xlsx';
    }
}
