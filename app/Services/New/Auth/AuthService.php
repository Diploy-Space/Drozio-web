<?php

namespace App\Services\New\Auth;

use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

abstract class AuthService extends BaseService
{
    public const REASON_INVALID_CREDENTIALS = 'invalid_credentials';

    public const REASON_INACTIVE = 'inactive';

    public const REASON_PENDING_APPROVAL = 'pending_approval';

    public const REASON_NOT_APPROVED = 'not_approved';

    private const THROTTLE_KEY_PREFIX = 'v6-login';

    /**
     * @return array<int, string>
     */
    abstract protected function identityColumns(): array;

    abstract public function userType(): string;

    abstract public function accountStatusReason(Model $account): ?string;

    public function findByIdentity(string $identity, array $relations = []): ?Model
    {
        $columns = $this->identityColumns();
        $matched = $this->columnForIdentity($identity, $columns);

        return $this->query()
            ->with($relations)
            ->where(function ($query) use ($identity, $matched, $columns): void {
                foreach ($matched === null ? $columns : [$matched] as $column) {
                    $query->orWhere($column, $identity);
                }
            })
            ->first();
    }

    public function identityExists(string $identity): bool
    {
        return $this->findByIdentity($identity) !== null;
    }

    public function verifyPassword(Model $account, string $password): bool
    {
        return Hash::check($password, (string) $account->password);
    }

    public function updatePassword(mixed $id, string $password): bool
    {
        return (bool) $this->query()->where('id', $id)->update([
            'password' => bcrypt(str_replace(' ', '', $password)),
        ]);
    }

    public function secondsUntilLoginAllowed(string $identity, ?Model $account, int $maxAttempts, int $blockSeconds): int
    {
        $key = $this->throttleKey($identity);

        return RateLimiter::tooManyAttempts($key, $maxAttempts) ? RateLimiter::availableIn($key) : 0;
    }

    public function registerLoginFailure(string $identity, ?Model $account, int $maxAttempts, int $blockSeconds): void
    {
        RateLimiter::hit($this->throttleKey($identity), $blockSeconds);
    }

    public function clearLoginFailures(string $identity, ?Model $account): void
    {
        RateLimiter::clear($this->throttleKey($identity));
    }

    protected function throttleKey(string $identity): string
    {
        return self::THROTTLE_KEY_PREFIX.':'.$this->userType().':'.hash('sha256', $identity);
    }

    private function columnForIdentity(string $identity, array $columns): ?string
    {
        $isEmail = filter_var($identity, FILTER_VALIDATE_EMAIL) !== false;

        foreach ($columns as $column) {
            if ($isEmail === ($column === 'email')) {
                return $column;
            }
        }

        return null;
    }
}
