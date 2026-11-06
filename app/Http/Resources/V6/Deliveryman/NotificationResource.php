<?php

namespace App\Http\Resources\V6\Deliveryman;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'description' => $this->description,
            'order_status' => $this->whenLoaded('order', fn (): ?string => $this->order?->order_status),
            'business_module' => $this->whenLoaded('order', fn (): ?string => $this->order?->business_module),
            'created_at' => $this->created_at,
        ];
    }
}
