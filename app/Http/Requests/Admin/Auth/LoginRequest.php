<?php

namespace App\Http\Requests\Admin\Auth;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;

/**
 * Class User
 *
 * @property string $email
 * @property string $password
 */
class LoginRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|min:6',
            'role' => 'required|in:'.UserRole::ADMIN.','.UserRole::EMPLOYEE,
        ];
    }

    public function messages(): array
    {
        return [
            'required' => translate('The').' :attribute '.translate('Field is required').'.',
        ];
    }
}
