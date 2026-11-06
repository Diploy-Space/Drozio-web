<?php

namespace App\Http\Requests\Admin\HelpAndSupport;

use App\Http\Requests\BaseRequest;

class HelpTopicAddRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'question' => 'required',
            'answer' => 'required',
            'ranking' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'question.required' => translate('The question field cannot be empty'),
            'answer.required' => translate('The answer field cannot be empty'),
            'ranking.required' => translate('The ranking field cannot be empty'),
        ];
    }
}
