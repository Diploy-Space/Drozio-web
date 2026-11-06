<?php

namespace App\Http\Resources\V6\Vendor;

use App\Services\New\ChattingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChattingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'last_message' => $this->message,
            'attachment_full_url' => ChattingMessageResource::withAttachmentTypes(attachments: $this->attachmentFullUrlWithSize()),
            'sent_by_seller' => (bool) $this->sent_by_seller,
            'seen_by_seller' => (bool) $this->seen_by_seller,
            'unseen_message_count' => (int) ($this->unseen_message_count ?? 0),
            'counterparty' => $this->counterparty(),
            'created_at' => $this->created_at,
        ];
    }

    private function counterparty(): array
    {
        if ($this->delivery_man_id !== null) {
            return [
                'type' => ChattingService::TYPE_DELIVERY_MAN,
                'id' => $this->delivery_man_id,
                'name' => trim(($this->deliveryMan?->f_name ?? '').' '.($this->deliveryMan?->l_name ?? '')),
                'image_full_url' => $this->deliveryMan?->image_full_url,
            ];
        }

        return [
            'type' => ChattingService::TYPE_CUSTOMER,
            'id' => $this->user_id,
            'name' => trim(($this->customer?->f_name ?? '').' '.($this->customer?->l_name ?? '')),
            'image_full_url' => $this->customer?->image_full_url,
        ];
    }
}
