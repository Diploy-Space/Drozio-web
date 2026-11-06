<?php

namespace App\Services\New\Auth;

use App\Models\PasswordReset;
use App\Models\PhoneOrEmailVerification;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VerificationService extends BaseService
{
    public const REASON_OTP_INVALID = 'otp_invalid';

    public const REASON_OTP_EXPIRED = 'otp_expired';

    public const REASON_OTP_BLOCKED = 'otp_blocked';

    public const REASON_OTP_BLOCK_LIFTED = 'otp_block_lifted';

    public const REASON_OTP_TOO_MANY_ATTEMPTS = 'otp_too_many_attempts';

    private const OTP_DEV_TOKEN = '123456';

    private const REGISTRATION_TOKEN_LENGTH = 40;

    public function __construct(PhoneOrEmailVerification $verification)
    {
        parent::__construct($verification);
    }

    public function findByIdentity(string $identity): ?Model
    {
        return $this->query()->where('phone_or_email', $identity)->latest('created_at')->first();
    }

    public function issueOtp(string $identity, ?string $token = null): string
    {
        $token ??= config('app.mode') === 'live' ? (string) rand(100000, 999999) : self::OTP_DEV_TOKEN;

        $this->deleteByIdentity(identity: $identity);

        $this->query()->create([
            'phone_or_email' => $identity,
            'token' => $token,
            'created_at' => now(),
        ]);

        PasswordReset::create([
            'identity' => $identity,
            'token' => $token,
            'created_at' => now(),
        ]);

        return $token;
    }

    public function issueRegistrationToken(string $identity): string
    {
        return $this->issueOtp(identity: $identity, token: Str::random(self::REGISTRATION_TOKEN_LENGTH));
    }

    public function secondsUntilResendAllowed(string $identity, int $resendSeconds): int
    {
        $verification = $this->findByIdentity(identity: $identity);

        if ($verification === null || $verification->created_at === null) {
            return 0;
        }

        $remaining = $resendSeconds - $verification->created_at->diffInSeconds(now(), false);

        return $remaining > 0 ? (int) ceil($remaining) : 0;
    }

    public function secondsUntilUnblocked(string $identity, int $blockSeconds): int
    {
        $verification = $this->findByIdentity(identity: $identity);

        if ($verification === null || (int) $verification->is_temp_blocked !== 1 || $verification->temp_block_time === null) {
            return 0;
        }

        $remaining = $blockSeconds - $verification->temp_block_time->diffInSeconds(now(), false);

        if ($remaining > 0) {
            return (int) ceil($remaining);
        }

        $this->query()->where('id', $verification->id)->update([
            'otp_hit_count' => 0,
            'is_temp_blocked' => 0,
            'temp_block_time' => null,
        ]);

        return 0;
    }

    public function verifyOtp(string $identity, string $token, int $maxAttempts, int $expirySeconds): ?string
    {
        $verification = $this->findByIdentity(identity: $identity);

        if ($verification === null) {
            return self::REASON_OTP_INVALID;
        }

        if ((string) $verification->token !== $token) {
            $this->registerOtpFailure(verification: $verification, maxAttempts: $maxAttempts);

            return self::REASON_OTP_INVALID;
        }

        if ($verification->created_at !== null && $verification->created_at->diffInSeconds(now(), false) > $expirySeconds) {
            return self::REASON_OTP_EXPIRED;
        }

        return null;
    }

    public function deleteByIdentity(string $identity): bool
    {
        return (bool) $this->query()->where('phone_or_email', $identity)->delete();
    }

    public function findByIdentityAndToken(string $identity, string $token): ?Model
    {
        return $this->query()->where('phone_or_email', $identity)->where('token', $token)->first();
    }

    public function updateOrCreateToken(string $identity, string $token): Model
    {
        return $this->query()->updateOrCreate(
            ['phone_or_email' => $identity],
            ['phone_or_email' => $identity, 'token' => $token, 'otp_hit_count' => 0, 'created_at' => now()],
        );
    }

    public function secondsRemainingInBlock(Model $verification, int $blockSeconds): int
    {
        if ($verification->temp_block_time === null) {
            return 0;
        }

        $remaining = $blockSeconds - $this->elapsedSeconds($verification->temp_block_time);

        return $remaining > 0 ? (int) ceil($remaining) : 0;
    }

    /** @return array{reason: ?string, retry_after: int} */
    public function registerLoginOtpAttempt(string $identity, int $maxAttempts, int $resendSeconds, int $blockSeconds): array
    {
        $verification = $this->findByIdentity(identity: $identity);

        if ($verification === null) {
            return $this->attemptResult(reason: self::REASON_OTP_INVALID);
        }

        $result = $this->attemptResult(reason: null);

        if ($verification->temp_block_time !== null && $this->elapsedSeconds($verification->temp_block_time) <= $blockSeconds) {
            $result = $this->attemptResult(
                reason: self::REASON_OTP_BLOCKED,
                retryAfter: $blockSeconds - $this->elapsedSeconds($verification->temp_block_time),
            );
        } elseif ((int) $verification->is_temp_blocked === 1 && $this->elapsedSeconds($verification->updated_at) >= $blockSeconds) {
            $this->liftBlock(verification: $verification);
            $result = $this->attemptResult(reason: self::REASON_OTP_BLOCK_LIFTED);
        } elseif ((int) $verification->otp_hit_count >= $maxAttempts && $this->elapsedSeconds($verification->updated_at) < $resendSeconds && (int) $verification->is_temp_blocked === 0) {
            $this->applyBlock(verification: $verification);
            $result = $this->attemptResult(
                reason: self::REASON_OTP_TOO_MANY_ATTEMPTS,
                retryAfter: $blockSeconds - $this->elapsedSeconds($verification->temp_block_time),
            );
        }

        $hitCount = (int) $this->query()->where('id', $verification->id)->value('otp_hit_count');
        $this->query()->where('id', $verification->id)->update([
            'otp_hit_count' => $hitCount + 1,
            'updated_at' => now(),
        ]);

        return $result;
    }

    /** @return array{reason: ?string, retry_after: int} */
    public function checkRegistrationOtpBlock(Model $verification, int $maxAttempts, int $resendSeconds, int $blockSeconds): array
    {
        if ($verification->temp_block_time !== null && $this->elapsedSeconds($verification->temp_block_time) <= $blockSeconds) {
            return $this->attemptResult(
                reason: self::REASON_OTP_BLOCKED,
                retryAfter: $blockSeconds - $this->elapsedSeconds($verification->temp_block_time),
            );
        }

        if ((int) $verification->is_temp_blocked === 1 && $this->elapsedSeconds($verification->updated_at) >= $blockSeconds) {
            $this->liftBlock(verification: $verification);
        }

        if ((int) $verification->otp_hit_count >= $maxAttempts && $this->elapsedSeconds($verification->updated_at) < $resendSeconds && (int) $verification->is_temp_blocked === 0) {
            $this->applyBlock(verification: $verification);

            return $this->attemptResult(
                reason: self::REASON_OTP_TOO_MANY_ATTEMPTS,
                retryAfter: $blockSeconds - $this->elapsedSeconds($verification->temp_block_time),
            );
        }

        return $this->attemptResult(reason: null);
    }

    /** @return array{reason: ?string, retry_after: int} */
    public function registerOtpMismatch(Model $verification, int $maxAttempts, int $blockSeconds): array
    {
        if ($verification->temp_block_time !== null && $this->elapsedSeconds($verification->temp_block_time) <= $blockSeconds) {
            return $this->attemptResult(
                reason: self::REASON_OTP_BLOCKED,
                retryAfter: $blockSeconds - $this->elapsedSeconds($verification->temp_block_time),
            );
        }

        if ((int) $verification->is_temp_blocked === 1 && $verification->created_at !== null && $this->elapsedSeconds($verification->created_at) >= $blockSeconds) {
            $this->query()->where('id', $verification->id)->update([
                'otp_hit_count' => 1,
                'is_temp_blocked' => 0,
                'temp_block_time' => null,
                'updated_at' => now(),
            ]);

            return $this->attemptResult(reason: self::REASON_OTP_BLOCK_LIFTED);
        }

        if ((int) $verification->otp_hit_count >= $maxAttempts && (int) $verification->is_temp_blocked === 0) {
            $this->applyBlock(verification: $verification);

            return $this->attemptResult(
                reason: self::REASON_OTP_TOO_MANY_ATTEMPTS,
                retryAfter: $blockSeconds - $this->elapsedSeconds($verification->temp_block_time),
            );
        }

        $this->query()->where('id', $verification->id)->update([
            'otp_hit_count' => (int) $verification->otp_hit_count + 1,
            'updated_at' => now(),
        ]);

        return $this->attemptResult(reason: self::REASON_OTP_INVALID);
    }

    public function registerFirebaseOtpFailure(Model $verification): void
    {
        $this->query()->where('id', $verification->id)->update([
            'otp_hit_count' => (int) $verification->otp_hit_count + 1,
            'updated_at' => now(),
            'temp_block_time' => null,
        ]);
    }

    private function liftBlock(Model $verification): void
    {
        $this->query()->where('id', $verification->id)->update([
            'otp_hit_count' => 0,
            'is_temp_blocked' => 0,
            'temp_block_time' => null,
        ]);
    }

    private function applyBlock(Model $verification): void
    {
        $this->query()->where('id', $verification->id)->update([
            'is_temp_blocked' => 1,
            'temp_block_time' => now(),
        ]);
    }

    private function elapsedSeconds(mixed $moment): float
    {
        return Carbon::parse($moment)->diffInSeconds(now());
    }

    /** @return array{reason: ?string, retry_after: int} */
    private function attemptResult(?string $reason, float $retryAfter = 0): array
    {
        return ['reason' => $reason, 'retry_after' => (int) ceil($retryAfter)];
    }

    private function registerOtpFailure(Model $verification, int $maxAttempts): void
    {
        $this->query()->where('id', $verification->id)->increment('otp_hit_count');

        if ((int) $this->query()->where('id', $verification->id)->value('otp_hit_count') >= $maxAttempts) {
            $this->query()->where('id', $verification->id)->update([
                'is_temp_blocked' => 1,
                'temp_block_time' => now(),
            ]);
        }
    }
}
