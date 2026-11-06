<?php

namespace App\Http\Resources\V6\Deliveryman;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'is_saved' => (bool) $this->is_saved,
            'customer' => $this->whenLoaded('customer', fn (): ?array => $this->customer === null ? null : [
                'id' => $this->customer->id,
                'name' => trim(($this->customer->f_name ?? '').' '.($this->customer->l_name ?? '')),
                'image_full_url' => $this->customer->image_full_url,
            ]),
            'reply' => $this->whenLoaded('reply', fn (): ?string => $this->reply?->reply),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
