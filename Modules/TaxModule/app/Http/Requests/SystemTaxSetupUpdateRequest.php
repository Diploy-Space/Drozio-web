<?php

namespace Modules\TaxModule\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SystemTaxSetupUpdateRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this['tax_status'] != 'include') {
            return [
                'tax_ids' => 'required_if:tax_type,order_wise',
            ];
        }

        return [];
    }

    public function messages(): array
    {
        return [
            'tax_ids.required_if' => translate('Select at least one tax when the tax type is order-wise.'),
        ];
    }
}
