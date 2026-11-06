<?php

namespace App\Http\Requests\Admin\Setting\ThirdParty;

use App\Http\Requests\BaseRequest;

class SocialMediaChatUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'status' => 'nullable|in:0,1',
            'phone' => 'nullable|string|max:20',
            'script' => 'nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => translate('Select a valid status for this social media chat option'),
            'phone.string' => translate('Whatsapp number must be a valid text'),
            'phone.max' => translate('Whatsapp number cannot be longer than :max characters'),
            'script.string' => translate('Messenger script must be a valid text'),
            'script.max' => translate('Messenger script cannot be longer than :max characters'),
        ];
    }
}
