<?php

namespace App\Http\Requests\Admin\SidebarPin;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ToggleRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|string|max:190',
            'action' => 'required|in:add,remove',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate('Menu item is required'),
            'id.max' => translate('The selected menu item is not valid'),
            'action.required' => translate('Action is required'),
            'action.in' => translate('The selected action is invalid'),
        ];
    }
}
