<?php

namespace App\Http\Resources\V6\Deliveryman;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'deliveryman_id' => $this->deliveryman_id,
            'time' => $this->time,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'location' => $this->location,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
