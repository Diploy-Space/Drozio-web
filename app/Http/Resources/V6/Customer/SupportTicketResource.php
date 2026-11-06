<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'type' => $this->type,
            'priority' => $this->priority,
            'description' => $this->description,
            'status' => $this->status,
            'attachment_full_url' => $this->attachment_full_url,
            'conversations_count' => $this->whenCounted('conversations'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
