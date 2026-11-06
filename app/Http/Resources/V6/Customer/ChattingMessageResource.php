<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChattingMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'attachment_full_url' => $this->attachmentFullUrlWithSize(),
            'sent_by_customer' => (bool) $this->sent_by_customer,
            'sent_by_seller' => (bool) $this->sent_by_seller,
            'sent_by_delivery_man' => (bool) $this->sent_by_delivery_man,
            'sent_by_admin' => (bool) $this->sent_by_admin,
            'seen_by_customer' => (bool) $this->seen_by_customer,
            'seen_by_seller' => (bool) $this->seen_by_seller,
            'seen_by_delivery_man' => (bool) $this->seen_by_delivery_man,
            'seen_by_admin' => (bool) $this->seen_by_admin,
            'created_at' => $this->created_at,
        ];
    }
}
