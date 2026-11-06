<?php

namespace App\Services\Admin\ThirdParty;

use App\Models\VendorWithdrawMethodInfo;
use App\Models\WithdrawalMethod;

class WithdrawMethodService
{
    /** Null distinguishes "no such withdrawal method" from a method that simply declares no fields. */
    public function findMethodFields(mixed $withdrawMethodId): ?array
    {
        $method = WithdrawalMethod::where(['id' => $withdrawMethodId])->first();

        return $method ? (array) $method['method_fields'] : null;
    }

    public function methodNameExistsForUser(?string $methodName, mixed $userId, mixed $excludeId = null): bool
    {
        return VendorWithdrawMethodInfo::where([
            'user_id' => $userId,
            'method_name' => $methodName,
        ])->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))->exists();
    }
}
