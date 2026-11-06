<?php

namespace App\Services\Vendor\Shop;

use App\Models\Shop;

class ShopService
{
    public function tinExpireDateForSeller(mixed $sellerId): ?string
    {
        return Shop::where(['seller_id' => $sellerId])->first()?->tin_expire_date;
    }
}
