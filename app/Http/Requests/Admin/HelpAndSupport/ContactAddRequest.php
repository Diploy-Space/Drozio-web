<?php

namespace App\Http\Requests\Admin\HelpAndSupport;

use App\Http\Requests\BaseRequest;

class ContactAddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'mobile_number' => 'required',
            'subject' => 'required',
            'message' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'mobile_number.required' => translate('Mobile number is required'),
            'subject.required' => translate('Subject is empty'),
            'message.required' => translate('Message is empty'),
        ];
    }
}
