<?php

namespace App\Services\New;

use App\Models\LoginSetup;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class LoginSetupService extends BaseService
{
    private const CACHE_TYPE = 'login_setups';

    /** @var array<int, string> */
    public const SOCIAL_MEDIUMS = ['google', 'facebook', 'apple'];

    public const CHANNEL_PHONE = 'phone';

    public const CHANNEL_EMAIL = 'email';

    public const LOGIN_OPTION_MANUAL = 'manual_login';

    public const LOGIN_OPTION_OTP = 'otp_login';

    public const LOGIN_OPTION_SOCIAL = 'social_login';

    private const KEY_PHONE_VERIFICATION = 'phone_verification';

    private const KEY_EMAIL_VERIFICATION = 'email_verification';

    private const KEY_LOGIN_OPTIONS = 'login_options';

    public function __construct(LoginSetup $loginSetup)
    {
        parent::__construct($loginSetup);
    }

    public function getList(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where('key', 'like', "%{$searchValue}%"));

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create($data);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($data);

        return $record->refresh();
    }

    public function updateWhere(array $params, array $data): int
    {
        $affected = $this->query()->where($params)->update($data);

        cacheRemoveByType(type: self::CACHE_TYPE);

        return $affected;
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        $this->query()->updateOrInsert($params, [...$data, 'updated_at' => now()]);

        cacheRemoveByType(type: self::CACHE_TYPE);

        return true;
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) $record->delete();
    }

    public function getValueByKey(string $key, mixed $default = null): mixed
    {
        $value = $this->query()->where('key', $key)->value('value');

        return $value !== null ? (json_decode($value, true) ?? $value) : $default;
    }

    public function requiredVerificationChannel(): ?string
    {
        if ($this->getValueByKey(key: self::KEY_PHONE_VERIFICATION, default: 0)) {
            return self::CHANNEL_PHONE;
        }

        return $this->getValueByKey(key: self::KEY_EMAIL_VERIFICATION, default: 0) ? self::CHANNEL_EMAIL : null;
    }

    public function isVerificationRequired(string $channel): bool
    {
        $key = $channel === self::CHANNEL_PHONE ? self::KEY_PHONE_VERIFICATION : self::KEY_EMAIL_VERIFICATION;

        return (bool) $this->getValueByKey(key: $key, default: 0);
    }

    public function getLoginOptions(): array
    {
        $options = $this->getValueByKey(key: self::KEY_LOGIN_OPTIONS, default: []);

        return is_array($options) ? $options : [];
    }

    public function isLoginOptionEnabled(string $option): bool
    {
        $options = $this->getValueByKey(key: self::KEY_LOGIN_OPTIONS, default: []);

        if (! is_array($options) || array_filter($options) === []) {
            return $option === self::LOGIN_OPTION_MANUAL;
        }

        return (bool) ($options[$option] ?? 0);
    }

    /** @return array<string, int> */
    public function getSocialMediumAvailability(): array
    {
        $availability = array_fill_keys(self::SOCIAL_MEDIUMS, 0);

        foreach (getWebConfig(name: 'social_login') ?? [] as $medium) {
            if (! empty($medium['client_id']) && ! empty($medium['client_secret'])) {
                $availability[$medium['login_medium']] = 1;
            }
        }

        foreach (getWebConfig(name: 'apple_login') ?? [] as $medium) {
            if (! empty($medium['client_id']) && ! empty($medium['team_id']) && ! empty($medium['key_id']) && ! empty($medium['service_file'])) {
                $availability[$medium['login_medium']] = 1;
            }
        }

        return $availability;
    }
}
