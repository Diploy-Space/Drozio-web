<?php

namespace App\Http\Requests\Admin\Setting\Website;

use App\Http\Requests\BaseRequest;

class ErrorLogUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required|integer',
            'redirect_url' => 'nullable|string|max:500',
            'redirect_status' => 'nullable|in:301,302',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate(':field is required', ['field' => translate('404 log')]),
            'id.integer' => translate('Select a valid log to update'),
            'redirect_url.string' => translate('Redirection link must be a valid text'),
            'redirect_url.max' => translate('Redirection link cannot be longer than :max characters'),
            'redirect_status.in' => translate('Select a valid redirection status'),
        ];
    }
}
