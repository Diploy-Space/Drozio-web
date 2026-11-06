<?php

namespace App\Http\Resources\V6\Erp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    private const STATUS_APPROVED = 'approved';

    private const UNKNOWN_VENDOR_NAME = 'Unknown Vendor';

    public function toArray(Request $request): array
    {
        $shop = $this->shop;
        $orderCount = (int) $this->orders_count;

        return [
            'id' => (string) $this->id,
            'name' => $shop->name ?? self::UNKNOWN_VENDOR_NAME,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status === self::STATUS_APPROVED ? 1 : 0,
            'address' => $shop->address ?? null,
            'logo_full_url' => $this->imagePath(fullUrl: $shop?->image_full_url),
            'cover_photo_full_url' => $this->imagePath(fullUrl: $shop?->banner_full_url),
            'rating' => $this->rating,
            'total_order' => $orderCount,
            'order_count' => $orderCount,
            'comission' => $this->sales_commission_percentage,
            'zone_id' => null,
            'vendor' => [
                'f_name' => $this->f_name,
                'l_name' => $this->l_name,
                'email' => $this->email,
                'phone' => $this->phone,
            ],
            'module' => null,
            'zone' => null,
        ];
    }

    private function imagePath(array|string|null $fullUrl): ?string
    {
        return is_array($fullUrl) ? ($fullUrl['path'] ?? null) : $fullUrl;
    }
}
