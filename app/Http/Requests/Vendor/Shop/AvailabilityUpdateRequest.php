<?php

namespace App\Http\Requests\Vendor\Shop;

use App\Http\Requests\BaseRequest;
use App\Services\New\ShopService;
use Illuminate\Validation\Rule;

class AvailabilityUpdateRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->isApiRequest() || ! $this->has('status') || is_array($this['status'])) {
            return;
        }

        $isClosed = filter_var($this['status'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($isClosed === null) {
            return;
        }

        $this->merge(['status' => $isClosed ? [] : $this->availableModuleKeys()]);
    }

    public function rules(): array
    {
        return [
            'status' => [$this->isApiRequest() ? 'present' : 'nullable', 'array'],
            'status.*' => ['string', Rule::in($this->availableModuleKeys())],
        ];
    }

    public function messages(): array
    {
        return [
            'status.present' => translate('Send whether the shop stays open or closed.'),
            'status.array' => translate('Please choose which businesses your shop stays open for.'),
            'status.*.string' => translate('One of the selected businesses is not available on your shop.'),
            'status.*.in' => translate('One of the selected businesses is not available on your shop.'),
        ];
    }

    /** @return array<string, int> */
    public function temporaryCloseColumns(): array
    {
        $openModuleKeys = $this['status'] ?? [];
        $columns = [];

        foreach (array_intersect_key(ShopService::TEMPORARY_CLOSE_COLUMNS, array_flip($this->availableModuleKeys())) as $moduleKey => $column) {
            $columns[$column] = in_array($moduleKey, $openModuleKeys, true) ? 0 : 1;
        }

        return $columns;
    }

    /** @return string[] */
    public function availableModuleKeys(): array
    {
        return array_keys(businessModuleManager()->availableInPanel());
    }
}
