<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryManTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'credit' => (float) $this->credit,
            'debit' => (float) $this->debit,
            'balance' => (float) $this->balance,
            'transaction_type' => $this->transaction_type,
            'created_at' => $this->created_at,
        ];
    }
}
