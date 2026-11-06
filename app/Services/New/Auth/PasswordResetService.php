<?php

namespace App\Services\New\Auth;

use App\Enums\UserType;
use App\Models\PasswordReset;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PasswordResetService extends BaseService
{
    public const USER_TYPE_CUSTOMER = UserType::CUSTOMER;

    public const REASON_OTP_INVALID = 'otp_invalid';

    public const REASON_OTP_EXPIRED = 'otp_expired';

    public const REASON_OTP_BLOCKED = 'otp_blocked';

    public const LINK_TOKEN_LIFETIME = 3600;

    private const TOKEN_LENGTH = 120;

    private const OTP_DEV_TOKEN = '123456';

    public function __construct(PasswordReset $passwordReset)
    {
        parent::__construct($passwordReset);
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'identity' => $data['identity'],
            'token' => $data['token'] ?? Str::random(self::TOKEN_LENGTH),
            'user_type' => $data['user_type'],
            'created_at' => now(),
        ]);
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findByIdentity(string $identity, string $userType): ?Model
    {
        return $this->query()
            ->where('user_type', $userType)
            ->where('identity', $identity)
            ->latest('created_at')
            ->first();
    }

    public function issueOtp(string $identity, string $userType, ?string $token = null): string
    {
        $token ??= config('app.mode') === 'live' ? (string) rand(100000, 999999) : self::OTP_DEV_TOKEN;

        $this->deleteByIdentity(identity: $identity, userType: $userType);

        $this->query()->create([
            'identity' => $identity,
            'user_type' => $userType,
            'token' => $token,
            'created_at' => now(),
        ]);

        return $token;
    }

    public function secondsUntilResendAllowed(string $identity, string $userType, int $resendSeconds): int
    {
        $reset = $this->findByIdentity(identity: $identity, userType: $userType);

        if ($reset === null || $reset->created_at === null) {
            return 0;
        }

        $remaining = $resendSeconds - $reset->created_at->diffInSeconds(now(), false);

        return $remaining > 0 ? (int) ceil($remaining) : 0;
    }

    public function secondsUntilUnblocked(string $identity, string $userType, int $blockSeconds): int
    {
        $reset = $this->findByIdentity(identity: $identity, userType: $userType);

        if ($reset === null || ! $reset->is_temp_blocked || $reset->temp_block_time === null) {
            return 0;
        }

        $remaining = $blockSeconds - $reset->temp_block_time->diffInSeconds(now(), false);

        if ($remaining > 0) {
            return (int) ceil($remaining);
        }

        $this->query()->where('id', $reset->id)->update([
            'otp_hit_count' => 0,
            'is_temp_blocked' => 0,
            'temp_block_time' => null,
        ]);

        return 0;
    }

    public function verifyOtp(string $identity, string $userType, string $token, int $maxAttempts, int $expirySeconds): ?string
    {
        $reset = $this->findByIdentity(identity: $identity, userType: $userType);

        if ($reset === null) {
            return self::REASON_OTP_INVALID;
        }

        if ((string) $reset->token !== $token) {
            $this->applyOtpFailure(reset: $reset, maxAttempts: $maxAttempts);

            return self::REASON_OTP_INVALID;
        }

        if ($reset->created_at !== null && $reset->created_at->diffInSeconds(now(), false) > $expirySeconds) {
            return self::REASON_OTP_EXPIRED;
        }

        return null;
    }

    public function issueResetLinkToken(string $identity, string $userType): string
    {
        $token = Str::random(self::TOKEN_LENGTH);

        $this->deleteByIdentity(identity: $identity, userType: $userType);

        $this->query()->create([
            'identity' => $identity,
            'user_type' => $userType,
            'token' => $token,
            'created_at' => now(),
        ]);

        return $token;
    }

    public function isLinkTokenExpired(Model $reset, int $expirySeconds = self::LINK_TOKEN_LIFETIME): bool
    {
        return $reset->created_at !== null && $reset->created_at->diffInSeconds(now(), false) > $expirySeconds;
    }

    public function findByResetToken(string $token, string $userType): ?Model
    {
        return $this->query()
            ->where('user_type', $userType)
            ->where('token', $token)
            ->first();
    }

    public function registerOtpFailure(string $identity, string $userType, int $maxAttempts): void
    {
        $reset = $this->findByIdentity(identity: $identity, userType: $userType);

        if ($reset !== null) {
            $this->applyOtpFailure(reset: $reset, maxAttempts: $maxAttempts);
        }
    }

    public function deleteByIdentity(string $identity, string $userType): bool
    {
        return (bool) $this->query()
            ->where('user_type', $userType)
            ->where('identity', $identity)
            ->delete();
    }

    public function delete(mixed $id): bool
    {
        return (bool) $this->query()->where('id', $id)->delete();
    }

    private function applyOtpFailure(Model $reset, int $maxAttempts): void
    {
        $this->query()->where('id', $reset->id)->increment('otp_hit_count');

        $hitCount = (int) $this->query()->where('id', $reset->id)->value('otp_hit_count');

        if ($hitCount >= $maxAttempts) {
            $this->query()->where('id', $reset->id)->update([
                'is_temp_blocked' => 1,
                'temp_block_time' => now(),
            ]);
        }
    }
}
