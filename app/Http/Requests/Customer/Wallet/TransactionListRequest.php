<?php

namespace App\Http\Requests\Customer\Wallet;

use App\Http\Requests\BaseRequest;
use App\Services\New\WalletTransactionService;
use Illuminate\Validation\Rule;

class TransactionListRequest extends BaseRequest
{
    private const JSON_LIST_FIELDS = ['transaction_types'];

    protected function prepareForValidation(): void
    {
        if ($this->isApiRequest()) {
            $this->decodeJsonEncodedArrays(fieldPatterns: self::JSON_LIST_FIELDS);
        }
    }

    public function rules(): array
    {
        if ($this->isApiRequest()) {
            return [
                'limit' => 'nullable|integer|min:1',
                'offset' => 'nullable|integer|min:1',
                'search' => 'nullable|string',
                'entry_type' => ['nullable', Rule::in(WalletTransactionService::ENTRY_TYPES)],
                'transaction_types' => 'nullable|array',
                'transaction_types.*' => ['string', Rule::in(WalletTransactionService::CUSTOMER_TRANSACTION_TYPE_FILTERS)],
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ];
        }

        return [
            'types' => 'nullable|array',
            'transaction_range' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'limit.integer' => translate('Limit must be a positive whole number'),
            'limit.min' => translate('Limit must be a positive whole number'),
            'offset.integer' => translate('Offset must be a positive whole number'),
            'offset.min' => translate('Offset must be a positive whole number'),
            'search.string' => translate('Search must be text'),
            'entry_type.in' => translate('Choose a valid entry type'),
            'transaction_types.array' => translate('Please select valid transaction types'),
            'transaction_types.*.string' => translate('Please select valid transaction types'),
            'transaction_types.*.in' => translate('Please select valid transaction types'),
            'start_date.date' => translate('Start date must be a valid date'),
            'end_date.date' => translate('End date must be a valid date'),
            'types.array' => translate('Please select valid transaction types'),
            'transaction_range.string' => translate('Invalid date range format'),
        ];
    }
}
