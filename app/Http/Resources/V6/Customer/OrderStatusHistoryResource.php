<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'user_id' => $this->user_id,
            'user_type' => $this->user_type,
            'status' => $this->status,
            'cause' => $this->cause,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
