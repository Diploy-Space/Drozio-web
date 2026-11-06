<?php

namespace Modules\TaxModule\app\Services\New;

use App\Enums\BusinessModule;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\TaxModule\app\Models\SystemTaxSetup;

class SystemTaxSetupService extends BaseService
{
    public const TAX_PAYER_VENDOR = 'vendor';

    private const DEFAULT_TAX_TYPE = 'order_wise';

    private const TAX_STATUS_INCLUDED = 'include';

    private const COUNTRY_TYPE_SINGLE = 'single';

    public function __construct(SystemTaxSetup $systemTaxSetup)
    {
        parent::__construct($systemTaxSetup);
    }

    public function find(mixed $id, array $relations = ['additionalData']): ?Model
    {
        return $this->query()->with($relations)->find($id);
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
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('tax_payer', 'like', "%{$searchValue}%")
                    ->orWhere('business_module', 'like', "%{$searchValue}%")
                    ->orWhere('tax_type', 'like', "%{$searchValue}%")
                    ->orWhere('country_code', 'like', "%{$searchValue}%");
            }))
            ->latest('id');

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function findBy(array $params, array $relations = ['additionalData']): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function findByBusinessModule(string $businessModule, string $taxPayer = self::TAX_PAYER_VENDOR, array $relations = ['additionalData']): ?Model
    {
        return $this->query()
            ->with($relations)
            ->where('business_module', $businessModule)
            ->where('tax_payer', $taxPayer)
            ->first();
    }

    public function findDefaultForBusinessModule(string $businessModule, string $taxPayer = self::TAX_PAYER_VENDOR, ?string $countryCode = null, array $relations = ['additionalData']): ?Model
    {
        return $this->query()
            ->with($relations)
            ->where('business_module', $businessModule)
            ->where('tax_payer', $taxPayer)
            ->when(
                $this->isSingleCountry(),
                fn (Builder $builder) => $builder->where('is_default', true),
                fn (Builder $builder) => $builder->where('country_code', $countryCode),
            )
            ->first();
    }

    public function findOrCreateByBusinessModule(string $businessModule, string $taxPayer = self::TAX_PAYER_VENDOR, array $relations = ['additionalData']): Model
    {
        return $this->findByBusinessModule(businessModule: $businessModule, taxPayer: $taxPayer, relations: $relations)
            ?? $this->query()->create([
                'tax_type' => self::DEFAULT_TAX_TYPE,
                'country_code' => null,
                'tax_payer' => $taxPayer,
                'business_module' => $businessModule,
                'is_default' => true,
                'is_active' => 0,
            ]);
    }

    public function create(array $data): ?Model
    {
        $isSingleCountry = $this->isSingleCountry();

        return $this->query()->create([
            'tax_type' => $data['tax_type'] ?? self::DEFAULT_TAX_TYPE,
            'country_code' => $isSingleCountry ? null : ($data['country_code'] ?? null),
            'tax_payer' => $data['tax_payer'] ?? self::TAX_PAYER_VENDOR,
            'business_module' => $data['business_module'] ?? BusinessModule::RETAIL,
            'is_default' => $isSingleCountry,
            'is_included' => $data['is_included'] ?? 0,
            'is_active' => $data['is_active'] ?? 0,
        ]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $countryCode = $this->isSingleCountry() ? null : ($data['country_code'] ?? null);

        $record->update(($data['tax_status'] ?? null) === self::TAX_STATUS_INCLUDED
            ? [
                'is_included' => 1,
                'country_code' => $countryCode,
            ]
            : [
                'tax_type' => $data['tax_type'] ?? self::DEFAULT_TAX_TYPE,
                'tax_ids' => $data['tax_ids'] ?? null,
                'is_included' => 0,
                'country_code' => $countryCode,
            ]);

        return $record->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) $record->update(['is_active' => $status]);
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrInsert($params, $data);
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) $record->delete();
    }

    public function exists(array $params): bool
    {
        return $this->query()->where($params)->exists();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function syncAdditionalSetups(mixed $id, array $names, array $selectedTaxIds, array $activeFlags): void
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return;
        }

        foreach ($names as $name) {
            $additionalSetup = $record->additionalData()->firstOrNew(['name' => $name]);
            $additionalSetup->is_active = ! empty($activeFlags[$name]) && ! empty($selectedTaxIds[$name]) ? 1 : 0;
            $additionalSetup->tax_ids = $selectedTaxIds[$name] ?? [];
            $additionalSetup->save();
        }
    }

    private function isSingleCountry(): bool
    {
        return config('taxmodule.country_type') === self::COUNTRY_TYPE_SINGLE;
    }
}
