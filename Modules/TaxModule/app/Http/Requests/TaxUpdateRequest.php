<?php

namespace Modules\TaxModule\app\Http\Requests;

use App\Enums\BusinessModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\TaxModule\app\Services\New\TaxService;

class TaxUpdateRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    private ?string $businessModule = null;

    public function __construct(private readonly TaxService $taxService)
    {
        parent::__construct();
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $businessModule = $this->businessModule();

        return [
            'name' => [
                'required', 'max:50',
                Rule::unique('taxes', 'name')->where('business_module', $businessModule)->ignore($this['id']),
            ],
            'country_code' => [
                'nullable', 'max:20',
                Rule::unique('taxes', 'country_code')->where('business_module', $businessModule)->ignore($this['id']),
            ],
            'tax_rate' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate(':field is required', ['field' => translate('Name')]),
            'name.max' => translate('Name must not exceed 50 characters!'),
            'name.unique' => translate('Name has been taken'),
            'country_code.max' => translate('Country code must not exceed 20 characters!'),
            'country_code.unique' => translate('This country code already exists!'),
            'tax_rate.required' => translate(':field is required', ['field' => translate('Tax rate')]),
            'tax_rate.numeric' => translate('Tax rate must be a valid number!'),
            'tax_rate.min' => translate('Tax rate must be at least 0!'),
        ];
    }

    private function businessModule(): string
    {
        if ($this->businessModule === null) {
            $taxVat = $this->taxService->find(id: $this['id']);
            $this->businessModule = $taxVat?->business_module ?? BusinessModule::RETAIL;
        }

        return $this->businessModule;
    }
}
