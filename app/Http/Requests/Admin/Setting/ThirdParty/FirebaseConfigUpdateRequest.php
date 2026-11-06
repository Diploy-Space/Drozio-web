<?php

namespace App\Http\Requests\Admin\Setting\ThirdParty;

use App\Http\Requests\BaseRequest;

class FirebaseConfigUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'push_notification_key' => 'nullable|json',
            'fcm_project_id' => 'nullable|string|max:191',
            'apiKey' => 'nullable|string|max:500',
            'authDomain' => 'nullable|string|max:500',
            'projectId' => 'nullable|string|max:500',
            'storageBucket' => 'nullable|string|max:500',
            'messagingSenderId' => 'nullable|string|max:500',
            'appId' => 'nullable|string|max:500',
            'measurementId' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'push_notification_key.json' => translate('The service account content must be valid json'),
        ];
    }
}
