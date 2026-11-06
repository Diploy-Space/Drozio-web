<?php

namespace App\Http\Resources\V6\Deliveryman;

use App\Services\New\WithdrawRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'status' => match ((int) $this->approved) {
                WithdrawRequestService::APPROVAL_APPROVED => WithdrawRequestService::STATUS_APPROVED,
                WithdrawRequestService::APPROVAL_DENIED => WithdrawRequestService::STATUS_DENIED,
                default => WithdrawRequestService::STATUS_PENDING,
            },
            'transaction_note' => $this->transaction_note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
