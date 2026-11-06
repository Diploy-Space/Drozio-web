<?php

namespace App\Http\Requests\Admin\Setting\Delivery;

use App\Http\Requests\BaseRequest;

class ZipCodeAddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'zipcode' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'zipcode.required' => translate(':field is required', ['field' => translate('Zip Code')]),
            'zipcode.string' => translate('The zip code field is invalid'),
        ];
    }

    /**
     * The tags input posts one comma-separated string.
     *
     * @return array<int, string>
     */
    public function zipCodes(): array
    {
        return array_values(array_unique(array_filter(
            array_map('trim', explode(',', (string) $this['zipcode'])),
            static fn (string $zipCode) => $zipCode !== '',
        )));
    }
}
