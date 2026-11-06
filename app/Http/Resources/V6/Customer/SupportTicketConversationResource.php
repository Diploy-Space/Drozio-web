<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'support_ticket_id' => $this->support_ticket_id,
            'message' => $this->customer_message ?? $this->admin_message,
            'sent_by' => $this->customer_message !== null ? 'customer' : 'admin',
            'attachment_full_url' => $this->attachment_full_url,
            'created_at' => $this->created_at,
        ];
    }
}
