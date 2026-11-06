<?php

namespace App\Services\New\Auth;

use App\Enums\UserType;
use App\Models\DeliveryMan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeliverymanAuthService extends AuthService
{
    private const TOKEN_LENGTH = 50;

    public function __construct(DeliveryMan $deliveryMan)
    {
        parent::__construct($deliveryMan);
    }

    protected function identityColumns(): array
    {
        return ['phone', 'email'];
    }

    public function userType(): string
    {
        return UserType::DELIVERY_MAN;
    }

    public function findByPhone(string $phone): ?Model
    {
        return $this->query()->where('phone', $phone)->first();
    }

    public function findByAccessToken(string $token): ?Model
    {
        return $this->query()->where('auth_token', $token)->first();
    }

    public function issueAccessToken(Model $account): string
    {
        $token = Str::random(self::TOKEN_LENGTH);

        $this->query()->where('id', $account->id)->update(['auth_token' => $token]);

        return $token;
    }

    public function revokeAccessToken(Model $account): bool
    {
        return (bool) $this->query()->where('id', $account->id)->update(['auth_token' => null]);
    }

    public function accountStatusReason(Model $account): ?string
    {
        return (int) $account->is_active === 1 ? null : self::REASON_INACTIVE;
    }
}
