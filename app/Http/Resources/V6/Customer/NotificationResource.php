<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
            'image_full_url' => $this->image_full_url,
            'sent_to' => $this->sent_to,
            'notification_count' => $this->notification_count,
            'is_seen' => $this->relationLoaded('notificationSeenBy') && $this->notificationSeenBy !== null,
            'created_at' => $this->created_at,
        ];
    }
}
