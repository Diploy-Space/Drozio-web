<?php

namespace App\Http\Resources\V6\Deliveryman;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'current_balance' => (float) $this['current_balance'],
            'cash_in_hand' => (float) $this['cash_in_hand'],
            'pending_withdraw' => (float) $this['pending_withdraw'],
            'total_withdraw' => (float) $this['total_withdraw'],
            'withdrawable_balance' => (float) $this['withdrawable_balance'],
            'total_earn' => (float) $this['total_earn'],
        ];
    }
}
