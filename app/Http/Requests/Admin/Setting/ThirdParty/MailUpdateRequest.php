<?php

namespace App\Http\Requests\Admin\Setting\ThirdParty;

use App\Http\Requests\BaseRequest;

class MailUpdateRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'name' => 'required',
            'host' => 'required',
            'driver' => 'required',
            'port' => 'required',
            'username' => 'required',
            'email' => 'required',
            'encryption' => 'required',
            'password' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate('The name field is required!'),
            'host.required' => translate('The host field is required!'),
            'driver.required' => translate('The driver field is required!'),
            'port.required' => translate('The port field is required!'),
            'username.required' => translate('The username field is required!'),
            'email.required' => translate('The email field is required!'),
            'encryption.required' => translate('The encryption field is required!'),
            'password.required' => translate('The password field is required!'),
        ];
    }
}
