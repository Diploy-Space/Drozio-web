<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyPointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'transaction_id' => $this->transaction_id,
            'business_module' => $this->business_module,
            'reference' => $this->reference,
            'transaction_type' => $this->transaction_type,
            'debit' => (float) $this->debit,
            'credit' => (float) $this->credit,
            'balance' => (float) $this->balance,
            'created_at' => $this->created_at,
        ];
    }
}
