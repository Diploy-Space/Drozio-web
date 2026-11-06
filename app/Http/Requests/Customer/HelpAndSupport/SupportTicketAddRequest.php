<?php

namespace App\Http\Requests\Customer\HelpAndSupport;

use App\Http\Requests\BaseRequest;
use App\Services\New\SupportTicket\SupportTicketService;
use Illuminate\Contracts\Validation\ValidationRule;

class SupportTicketAddRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this['ticket_description'])) {
            $this->merge(['ticket_description' => str_replace(search: "\r\n", replace: "\n", subject: $this['ticket_description'])]);
        }
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'ticket_subject' => 'required',
            'ticket_type' => 'required',
            'ticket_priority' => 'required',
            'ticket_description' => 'required_without_all:image.*|max:'.SupportTicketService::DESCRIPTION_MAX_LENGTH,
            'image.*' => 'required_without_all:ticket_description|image|mimes:jpeg,png,jpg,gif|max:6000',
        ];
    }

    public function messages(): array
    {
        return [
            'ticket_subject.required' => translate('The ticket subject is required'),
            'ticket_type.required' => translate('The ticket type is required'),
            'ticket_priority.required' => translate('The ticket priority is required'),
            'ticket_description.required_without_all' => translate('Either a ticket description or an image is required'),
            'ticket_description.max' => translate('The issue description must not exceed :limit characters', ['limit' => SupportTicketService::DESCRIPTION_MAX_LENGTH]),
            'image.*.required_without_all' => translate('Either a ticket description or an image is required'),
            'image.*.image' => translate('The file must be an image'),
            'image.*.mimes' => translate('The file must be of type: jpeg, png, jpg, gif'),
            'image.*.max' => translate('The image must not exceed 6 MB'),
        ];
    }
}
