<?php

namespace App\Traits;

use App\Enums\AuthErrorCode;
use App\Services\New\GuestUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait CustomerOwnerTrait
{
    abstract protected function guestUserService(): GuestUserService;

    /**
     * @return array{id: string, is_guest: bool}|null
     */
    protected function resolveCustomerOwner(Request $request): ?array
    {
        $customer = auth('api')->user();

        if ($customer !== null) {
            return ['id' => (string) $customer->id, 'is_guest' => false];
        }

        $guestId = $request->input('guest_id');

        if ($guestId === null || $this->guestUserService()->find(id: $guestId) === null) {
            return null;
        }

        return ['id' => (string) $guestId, 'is_guest' => true];
    }

    /**
     * @return array<string, mixed>
     */
    protected function customerOwnerFilters(array $owner): array
    {
        return [
            'customer_id' => $owner['id'],
            'is_guest' => (int) $owner['is_guest'],
        ];
    }

    protected function customerOwnerNotResolved(): JsonResponse
    {
        return $this->authError(
            config: config('response.guest_identity_required'),
            errorCode: AuthErrorCode::UNAUTHENTICATED,
        );
    }
}
