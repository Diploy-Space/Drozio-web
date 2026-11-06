<?php

namespace App\Http\Resources\V6\Customer;

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
            'attachment_full_url' => $this->attachmentFullUrlWithSize(),
            'sent_by_customer' => (bool) $this->sent_by_customer,
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

        $shop = $this->counterparty_shop;

        if ($this->seller_id !== null) {
            return [
                'type' => ChattingService::TYPE_SELLER,
                'id' => $this->seller_id,
                'name' => $shop?->name,
                'image_full_url' => $shop?->image_full_url,
                ...$this->availabilityFields(),
            ];
        }

        return [
            'type' => ChattingService::TYPE_ADMIN,
            'id' => ChattingService::ADMIN_THREAD_ID,
            'name' => $shop?->name,
            'image_full_url' => $shop?->image_full_url,
            ...$this->availabilityFields(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function availabilityFields(): array
    {
        return $this->availability ?? [
            'temporary_close' => false,
            'vacation_status' => false,
            'is_vacation_mode_now' => false,
            'vacation_duration_type' => null,
            'vacation_start_date' => null,
            'vacation_end_date' => null,
        ];
    }
}
