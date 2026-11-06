<?php

namespace App\Http\Resources\V6\Deliveryman;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashCollectionListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'credit' => (float) $this->credit,
            'debit' => (float) $this->debit,
            'transaction_type' => $this->transaction_type,
            'created_at' => $this->created_at,
        ];
    }
}
