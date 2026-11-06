<?php

namespace Modules\AI\app\Http\Requests\Customer\ShoppingAssistant;

use App\Http\Requests\BaseRequest;

class StartSessionRequest extends BaseRequest
{
    private const MAX_GUEST_ID_LENGTH = 64;

    public function rules(): array
    {
        return [
            'guest_id' => [$this->guestIdPresenceRule(), 'string', 'max:'.self::MAX_GUEST_ID_LENGTH],
        ];
    }

    public function messages(): array
    {
        return [
            'guest_id.required' => translate('A guest id is required. Send it with every request.'),
            'guest_id.string' => translate('Please send a valid guest id'),
            'guest_id.max' => translate('The guest id is too long'),
        ];
    }

    private function guestIdPresenceRule(): string
    {
        return auth('api')->check() ? 'nullable' : 'required';
    }
}
