<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'amount' => (float) $this->amount,
            'approved' => (int) $this->approved,
            'transaction_note' => $this->transaction_note,
            'withdrawal_method_id' => $this->withdrawal_method_id === null ? null : (int) $this->withdrawal_method_id,
            'withdrawal_method_fields' => $this->withdrawal_method_fields,
            'request_method_type' => $this->request_method_type,
            'created_at' => $this->created_at,
        ];
    }
}
