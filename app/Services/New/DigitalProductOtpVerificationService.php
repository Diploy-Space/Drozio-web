<?php

namespace App\Services\New;

use App\Models\DigitalProductOtpVerification;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class DigitalProductOtpVerificationService extends BaseService
{
    private const RESEND_TIME_SETTING = 'otp_resend_time';

    private const TOKEN_TTL_MINUTES = 10;

    public function __construct(DigitalProductOtpVerification $digitalProductOtpVerification)
    {
        parent::__construct($digitalProductOtpVerification);
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
            ->when($searchValue, fn (Builder $builder) => $builder->where('identity', 'like', "%{$searchValue}%"))
            ->latest();

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
        $query = $this->query()->with($relations);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function findLatestByIdentity(int|string $orderDetailId, ?string $email, ?string $phone): ?Model
    {
        return $this->query()
            ->where('order_details_id', $orderDetailId)
            ->where(fn (Builder $inner) => $inner->where('identity', $email)->orWhere('identity', $phone))
            ->latest()
            ->first();
    }

    public function findValidToken(int|string $orderDetailId, int|string $token): ?Model
    {
        return $this->query()
            ->where('order_details_id', $orderDetailId)
            ->where('token', $token)
            ->where('created_at', '>=', now()->subMinutes(self::TOKEN_TTL_MINUTES))
            ->latest()
            ->first();
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

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function storeTokenForIdentities(int|string $orderDetailId, array $identities, int $token): void
    {
        $tokenData = [
            'order_details_id' => $orderDetailId,
            'token' => $token,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach ($identities as $identity) {
            $this->query()->updateOrInsert(['identity' => $identity, 'order_details_id' => $orderDetailId], $tokenData);
        }
    }

    public function getResendWaitSeconds(?Model $latestToken): float|int
    {
        $resendIntervalSeconds = getWebConfig(name: self::RESEND_TIME_SETTING) ?? 1;

        if ($latestToken === null || Carbon::parse($latestToken->created_at)->diffInSeconds() >= $resendIntervalSeconds) {
            return 0;
        }

        return $resendIntervalSeconds - Carbon::parse($latestToken->created_at)->diffInSeconds();
    }

    public function getResendCountdownSeconds(Model $storedToken): float|int
    {
        $resendTime = getWebConfig(name: self::RESEND_TIME_SETTING) > 0 ? getWebConfig(name: self::RESEND_TIME_SETTING) : 0;
        $resendAvailableAt = Carbon::parse($storedToken->created_at)->addSeconds((int) $resendTime);

        return $resendAvailableAt > Carbon::now() ? Carbon::now()->diffInSeconds($resendAvailableAt) : 0;
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) $record->delete();
    }

    public function deleteBy(array $params): int
    {
        $query = $this->query();

        $this->applyFilters($query, $params);

        return $query->delete();
    }
}
