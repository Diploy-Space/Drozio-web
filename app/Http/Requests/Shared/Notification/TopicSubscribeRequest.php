<?php

namespace App\Http\Requests\Shared\Notification;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class TopicSubscribeRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'topics' => 'required_without:topic|array|min:1',
            'topics.*' => 'required|string',
            'topic' => 'required_without:topics|string',
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => translate(':field is required', ['field' => translate('Token')]),
            'topics.required_without' => translate(':field is required', ['field' => translate('Topic')]),
            'topic.required_without' => translate(':field is required', ['field' => translate('Topic')]),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getTopics(): array
    {
        return array_values(array_unique($this->input('topics', array_filter([$this->input('topic')]))));
    }
}
