<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'transaction_id' => $this->transaction_id,
            'reference' => $this->reference,
            'transaction_type' => $this->transaction_type,
            'business_module' => $this->business_module,
            'payment_method' => $this->payment_method,
            'debit' => (float) $this->debit,
            'credit' => (float) $this->credit,
            'admin_bonus' => (float) $this->admin_bonus,
            'balance' => (float) $this->balance,
            'created_at' => $this->created_at,
        ];
    }
}
