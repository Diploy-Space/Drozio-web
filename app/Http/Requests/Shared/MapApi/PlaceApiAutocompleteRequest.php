<?php

namespace App\Http\Requests\Shared\MapApi;

use App\Http\Requests\BaseRequest;

class PlaceApiAutocompleteRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'search_text' => 'required|string|max:255',
            'session_token' => 'nullable|string|max:191',
        ];
    }

    public function messages(): array
    {
        return [
            'search_text.required' => translate('Type a place to search for'),
            'search_text.max' => translate('The search text must not be longer than 255 characters'),
        ];
    }
}
