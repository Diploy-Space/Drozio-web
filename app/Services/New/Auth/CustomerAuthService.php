<?php

namespace App\Services\New\Auth;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CustomerAuthService extends AuthService
{
    public const MEDIUM_OTP = 'otp';

    public const MEDIUM_SYSTEM = 'system';

    private const PASSPORT_TOKEN_NAME = 'LaravelAuthApp';

    private const TEMPORARY_TOKEN_LENGTH = 40;

    private const REFERRAL_CODE_LENGTH = 20;

    private const PENDING_PASSWORD_MIN = 11111111;

    private const PENDING_PASSWORD_MAX = 99999999;

    private const DEFAULT_APP_LANGUAGE = 'en';

    public function __construct(User $customer)
    {
        parent::__construct($customer);
    }

    protected function identityColumns(): array
    {
        return ['email', 'phone'];
    }

    public function userType(): string
    {
        return UserType::CUSTOMER;
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'name' => trim($data['f_name'].' '.($data['l_name'] ?? '')),
            'f_name' => $data['f_name'],
            'l_name' => $data['l_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => bcrypt($data['password'] ?? Str::random(self::TEMPORARY_TOKEN_LENGTH)),
            'temporary_token' => Str::random(self::TEMPORARY_TOKEN_LENGTH),
            'referral_code' => $this->generateReferralCode(),
            'referred_by' => $data['referred_by'] ?? null,
            'login_medium' => $data['login_medium'] ?? null,
            'is_phone_verified' => $data['is_phone_verified'] ?? 0,
            'is_email_verified' => $data['is_email_verified'] ?? 0,
            'email_verified_at' => ($data['is_email_verified'] ?? 0) === 1 ? now() : null,
            'is_active' => 1,
        ]);
    }

    public function findByReferralCode(string $code): ?Model
    {
        return $this->query()->where('referral_code', $code)->first();
    }

    public function findByTemporaryToken(string $token): ?Model
    {
        return $this->query()->where('temporary_token', $token)->first();
    }

    public function findBySocialAccount(string $medium, string $socialId): ?Model
    {
        return $this->query()->where('login_medium', $medium)->where('social_id', $socialId)->first();
    }

    public function createFromSocialProfile(array $profile, string $medium): ?Model
    {
        return $this->query()->create([
            'name' => trim($profile['f_name'].' '.$profile['l_name']),
            'f_name' => $profile['f_name'],
            'l_name' => $profile['l_name'],
            'email' => $profile['email'],
            'phone' => $profile['phone'] ?? '',
            'password' => bcrypt(Str::random(self::TEMPORARY_TOKEN_LENGTH)),
            'login_medium' => $medium,
            'social_id' => $profile['social_id'],
            'is_active' => 1,
            'is_email_verified' => $profile['email'] === null ? 0 : 1,
            'is_phone_verified' => 0,
            'referral_code' => $this->generateReferralCode(),
            'temporary_token' => Str::random(self::TEMPORARY_TOKEN_LENGTH),
        ]);
    }

    public function linkSocialAccount(mixed $id, string $medium, string $socialId): bool
    {
        return (bool) $this->query()->where('id', $id)->update([
            'login_medium' => $medium,
            'social_id' => $socialId,
        ]);
    }

    public function updatePhone(mixed $id, string $phone): bool
    {
        return (bool) $this->query()->where('id', $id)->update([
            'phone' => $phone,
            'is_phone_verified' => 0,
        ]);
    }

    public function markLoginMedium(mixed $id, string $medium): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['login_medium' => $medium]);
    }

    public function updateEmail(mixed $id, string $email): bool
    {
        return (bool) $this->query()->where('id', $id)->update([
            'email' => $email,
            'is_email_verified' => 0,
            'email_verified_at' => null,
        ]);
    }

    public function detachEmail(mixed $id): bool
    {
        return (bool) $this->query()->where('id', $id)->update([
            'email' => null,
            'is_email_verified' => 0,
            'email_verified_at' => null,
        ]);
    }

    public function findByPhone(string $phone): ?Model
    {
        return $this->query()->where('phone', $phone)->first();
    }

    public function issueTemporaryToken(mixed $id): string
    {
        $token = Str::random(self::TEMPORARY_TOKEN_LENGTH);

        $this->query()->where('id', $id)->update(['temporary_token' => $token]);

        return $token;
    }

    public function issueAccessToken(Model $account): string
    {
        $this->query()->where('id', $account->id)->update(['temporary_token' => null]);

        return $account->createToken(self::PASSPORT_TOKEN_NAME)->accessToken;
    }

    public function revokeAccessToken(Model $account): bool
    {
        $account->token()?->revoke();

        return (bool) $this->query()->where('id', $account->id)->update(['cm_firebase_token' => null]);
    }

    public function accountStatusReason(Model $account): ?string
    {
        return (int) $account->is_active === 1 ? null : self::REASON_INACTIVE;
    }

    public function pendingVerificationChannel(Model $account, ?string $requiredChannel): ?string
    {
        if ($requiredChannel === 'phone') {
            return (int) $account->is_phone_verified === 1 ? null : 'phone';
        }

        if ($requiredChannel === 'email') {
            return (int) $account->is_email_verified === 1 ? null : 'email';
        }

        return null;
    }

    public function markVerified(mixed $id, string $channel): bool
    {
        $columns = $channel === 'phone'
            ? ['is_phone_verified' => 1]
            : ['is_email_verified' => 1, 'email_verified_at' => now()];

        return (bool) $this->query()->where('id', $id)->update($columns);
    }

    public function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(self::REFERRAL_CODE_LENGTH));
        } while ($this->query()->where('referral_code', $code)->exists());

        return $code;
    }

    public function findByEmail(string $email): ?Model
    {
        return $this->query()->where('email', $email)->first();
    }

    public function markChannelVerified(mixed $id, string $channel): bool
    {
        $column = $channel === 'phone' ? 'is_phone_verified' : 'is_email_verified';

        return (bool) $this->query()->where('id', $id)->update([$column => 1]);
    }

    public function clearWebFirebaseToken(mixed $id): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['cm_firebase_token_web' => null]);
    }

    public function buildPendingPhoneAccount(string $phone): array
    {
        return [
            'phone' => $phone,
            'password' => bcrypt(rand(self::PENDING_PASSWORD_MIN, self::PENDING_PASSWORD_MAX)),
            'referral_code' => $this->generateReferralCode(),
        ];
    }

    public function updateOrCreateProfileByPhone(string $phone, ?string $name, ?string $email, array $pendingAccount = []): Model
    {
        $nameParts = explode(' ', trim((string) $name), 2);

        return $this->query()->updateOrCreate(['phone' => $phone], [
            'name' => $name,
            'f_name' => $nameParts[0] ?? '',
            'l_name' => $nameParts[1] ?? '',
            'email' => $email,
            'is_phone_verified' => 1,
            'referral_code' => $this->generateReferralCode(),
            ...$pendingAccount,
        ]);
    }

    public function buildPendingSocialAccount(string $medium, string $socialId, ?string $email, ?string $name): array
    {
        $nameParts = explode(' ', (string) $name);
        $firstName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 0, -1)) : implode(' ', $nameParts);
        $lastName = count($nameParts) > 1 ? end($nameParts) : '';

        return [
            'name' => $firstName.' '.$lastName,
            'f_name' => $firstName,
            'l_name' => $lastName,
            'email' => $email,
            'phone' => '',
            'password' => bcrypt($socialId),
            'is_active' => 1,
            'login_medium' => $medium,
            'social_id' => $socialId,
            'is_phone_verified' => 0,
            'is_email_verified' => 1,
            'referral_code' => $this->generateReferralCode(),
            'temporary_token' => Str::random(self::TEMPORARY_TOKEN_LENGTH),
        ];
    }

    public function buildPendingSocialAccountWithPhone(array $pendingAccount, ?string $name, string $phone): array
    {
        return [
            'name' => $name,
            'f_name' => $name,
            'l_name' => '',
            'email' => $pendingAccount['email'],
            'phone' => $phone,
            'social_id' => $pendingAccount['social_id'],
            'is_email_verified' => 1,
            'is_phone_verified' => 0,
            'password' => bcrypt(rand(self::PENDING_PASSWORD_MIN, self::PENDING_PASSWORD_MAX)),
            'temporary_token' => Str::random(self::TEMPORARY_TOKEN_LENGTH),
            'app_language' => self::DEFAULT_APP_LANGUAGE,
            'email_verified_at' => now(),
            'referral_code' => $this->generateReferralCode(),
            'login_medium' => $pendingAccount['login_medium'] ?? null,
        ];
    }

    public function updateOrCreateByEmail(string $email, array $account): Model
    {
        return $this->query()->updateOrCreate(['email' => $email], $account);
    }

    public function attachSocialAccountByEmail(string $email, ?string $medium, ?string $socialId): bool
    {
        return (bool) $this->query()->where('email', $email)->update([
            'is_email_verified' => 1,
            'login_medium' => $medium,
            'social_id' => $socialId,
            'temporary_token' => Str::random(self::TEMPORARY_TOKEN_LENGTH),
        ]);
    }

    public function releaseEmail(string $email): bool
    {
        return (bool) $this->query()->where('email', $email)->update([
            'email' => null,
            'is_email_verified' => 0,
        ]);
    }

    public function secondsUntilLoginAllowed(string $identity, ?Model $account, int $maxAttempts, int $blockSeconds): int
    {
        if ($account === null) {
            return parent::secondsUntilLoginAllowed($identity, $account, $maxAttempts, $blockSeconds);
        }

        if ((int) $account->is_temp_blocked !== 1 || $account->temp_block_time === null) {
            return 0;
        }

        $remaining = $blockSeconds - $account->temp_block_time->diffInSeconds(now(), false);

        if ($remaining > 0) {
            return (int) ceil($remaining);
        }

        $this->clearLoginFailures(identity: $identity, account: $account);

        return 0;
    }

    public function registerLoginFailure(string $identity, ?Model $account, int $maxAttempts, int $blockSeconds): void
    {
        if ($account === null) {
            parent::registerLoginFailure($identity, $account, $maxAttempts, $blockSeconds);

            return;
        }

        $this->query()->where('id', $account->id)->increment('login_hit_count');

        if ((int) $this->query()->where('id', $account->id)->value('login_hit_count') >= $maxAttempts) {
            $this->query()->where('id', $account->id)->update([
                'is_temp_blocked' => 1,
                'temp_block_time' => now(),
            ]);
        }
    }

    public function clearLoginFailures(string $identity, ?Model $account): void
    {
        if ($account === null) {
            parent::clearLoginFailures($identity, $account);

            return;
        }

        $this->query()->where('id', $account->id)->update([
            'login_hit_count' => 0,
            'is_temp_blocked' => 0,
            'temp_block_time' => null,
        ]);
    }
}
