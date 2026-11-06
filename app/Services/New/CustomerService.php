<?php

namespace App\Services\New;

use App\Enums\SessionKey;
use App\Models\User;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerService extends BaseService
{
    use FileManagerTrait {
        update as updateFile;
        delete as deleteFile;
    }

    public const SORT_NEWEST = 'desc';

    public const SORT_OLDEST = 'asc';

    public const SORT_ORDER_AMOUNT = 'order_amount';

    public const SORT_ORDER_COUNT = 'order_count';

    public const FILTER_JOINED_BETWEEN = 'joined_between';

    public const FILTER_ORDERED_BETWEEN = 'ordered_between';

    public const FILTER_EXCLUDE_WALKING_CUSTOMER = 'exclude_walking_customer';

    public const FILTER_EXCLUDE_IDS = 'exclude_ids';

    public const FILTER_LIMIT = 'limit';

    public const FILTER_ORDERED_FROM_SELLER = 'ordered_from_seller';

    public const FILTER_WITH_IMAGE = 'with_image';

    public const FILTER_ORDERED_IN_BUSINESS_MODULE = 'ordered_in_business_module';

    public const DEFAULT_IMAGE = 'def.png';

    private const IMAGE_DIRECTORY = 'profile/';

    private const WALKING_CUSTOMER_EMAIL = 'walking@customer.com';

    private const ACCESS_TOKEN_TABLE = 'oauth_access_tokens';

    private const PROFILE_PASSWORD_MIN_EXCLUSIVE_LENGTH = 5;

    private const SIGN_IN_CLEARED_SESSION_KEYS = [
        'coupon_code',
        'coupon_type',
        'coupon_bearer',
        'coupon_discount',
        'payment_method',
        'shipping_method_id',
        'billing_address_id',
        'order_id',
        'cart_group_id',
        'order_note',
        SessionKey::WISH_LIST,
        SessionKey::PRODUCT_COMPARE_LIST,
        self::CUSTOMER_FCM_TOPIC_SESSION_KEY,
    ];

    private const CUSTOMER_FCM_TOPIC_SESSION_KEY = 'customer_fcm_topic';

    public function __construct(User $customer)
    {
        parent::__construct($customer);
    }

    public function getList(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
    ): Collection|LengthAwarePaginatorContract {
        $joinedBetween = $filters[self::FILTER_JOINED_BETWEEN] ?? [];
        $orderedBetween = $filters[self::FILTER_ORDERED_BETWEEN] ?? [];
        $excludesWalkingCustomer = ! empty($filters[self::FILTER_EXCLUDE_WALKING_CUSTOMER]);
        $excludeIds = $filters[self::FILTER_EXCLUDE_IDS] ?? [];
        $limit = $filters[self::FILTER_LIMIT] ?? null;
        $requiresImage = ! empty($filters[self::FILTER_WITH_IMAGE]);
        $orderedInBusinessModule = $filters[self::FILTER_ORDERED_IN_BUSINESS_MODULE] ?? null;
        unset(
            $filters[self::FILTER_JOINED_BETWEEN],
            $filters[self::FILTER_ORDERED_BETWEEN],
            $filters[self::FILTER_EXCLUDE_WALKING_CUSTOMER],
            $filters[self::FILTER_EXCLUDE_IDS],
            $filters[self::FILTER_LIMIT],
            $filters[self::FILTER_WITH_IMAGE],
            $filters[self::FILTER_ORDERED_IN_BUSINESS_MODULE],
        );

        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('f_name', 'like', "%{$searchValue}%")
                    ->orWhere('l_name', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhereRaw("CONCAT(f_name, ' ', l_name) LIKE ?", ["%{$searchValue}%"]);
            }))
            ->when($excludeIds !== [], fn (Builder $builder) => $builder->whereNotIn('id', $excludeIds))
            ->when($requiresImage, fn (Builder $builder) => $builder->whereNotNull('image')->whereNotIn('image', ['', self::DEFAULT_IMAGE]))
            ->when($excludesWalkingCustomer, fn (Builder $builder) => $this->excludeWalkingCustomer($builder))
            ->when($orderedBetween, fn (Builder $builder) => $builder->whereHas(
                'orders',
                fn (Builder $inner) => $inner->whereBetween('created_at', $orderedBetween),
            ))
            ->when($orderedInBusinessModule, fn (Builder $builder) => $this->orderedInBusinessModule($builder, $orderedInBusinessModule))
            ->when($joinedBetween, fn (Builder $builder) => $builder->whereBetween('created_at', $joinedBetween));

        match ($sortBy) {
            self::SORT_OLDEST => $query->oldest()->oldest('id'),
            self::SORT_ORDER_AMOUNT => $query
                ->withSum(['orders' => fn (Builder $order) => $this->rankedOrders($order, $orderedInBusinessModule)], 'order_amount')
                ->orderBy('orders_sum_order_amount', 'desc')
                ->latest('id'),
            self::SORT_ORDER_COUNT => $query
                ->withCount(['orders as ranked_orders_count' => fn (Builder $order) => $this->rankedOrders($order, $orderedInBusinessModule)])
                ->orderBy('ranked_orders_count', 'desc')
                ->latest('id'),
            default => $query->latest()->latest('id'),
        };

        $this->applyFilters($query, $filters);

        if ($dataLimit === 'all') {
            return $limit ? $query->limit((int) $limit)->get() : $query->get();
        }

        return ($limit
            ? $this->paginateFirstRecords($query, (int) $limit, (int) $dataLimit, $page)
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
        )->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = [], array $withCount = []): ?Model
    {
        return $this->query()->with($relations)->withCount($withCount)->find($id);
    }

    public function findForWalletUpdate(mixed $id): ?Model
    {
        return $this->query()->lockForUpdate()->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function findByPhoneOrPartialPhone(?string $phone): ?Model
    {
        return $this->query()
            ->where('phone', $phone)
            ->orWhere('phone', 'like', "%{$phone}%")
            ->first();
    }

    public function findByEmailOrPhone(?string $email, ?string $phone): ?Model
    {
        return $this->query()
            ->where(['email' => $email])
            ->orWhere(['phone' => $phone])
            ->first();
    }

    public function existsByEmailOrPhone(?string $email, ?string $phone): bool
    {
        return $this->query()
            ->where(['email' => $email])
            ->orWhere(['phone' => $phone])
            ->exists();
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'f_name' => $data['f_name'],
            'l_name' => $data['l_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'country' => $data['country'] ?? null,
            'city' => $data['city'] ?? null,
            'zip' => $data['zip_code'] ?? null,
            'street_address' => $data['address'] ?? null,
            'password' => bcrypt($data['password'] ?? 'password'),
        ]);
    }

    public function update(mixed $id, array $data, ?UploadedFile $image = null): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $columns = array_filter([
            'f_name' => $data['f_name'] ?? null,
            'l_name' => $data['l_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'country' => $data['country'] ?? null,
            'city' => $data['city'] ?? null,
            'zip' => $data['zip'] ?? $data['zip_code'] ?? null,
            'street_address' => $data['street_address'] ?? $data['address'] ?? null,
            'referred_by' => $data['referred_by'] ?? null,
            'password' => isset($data['password']) ? bcrypt($data['password']) : null,
            'cm_firebase_token' => $data['cm_firebase_token'] ?? null,
            'cm_firebase_token_web' => $data['cm_firebase_token_web'] ?? null,
            'app_language' => $data['app_language'] ?? null,
            'image' => $image === null
                ? null
                : $this->updateFile(dir: self::IMAGE_DIRECTORY, oldImage: $record->image, format: 'webp', image: $image),
        ], static fn ($value) => $value !== null);

        $record->update($columns + $this->verificationResets(record: $record, data: $data));

        return $record->refresh();
    }

    public function updateProfile(mixed $id, array $data, ?UploadedFile $image = null): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $phone = $data['phone'] ?? null;
        $email = $data['email'] ?? null;
        $password = (string) ($data['password'] ?? '');

        $record->update([
            'f_name' => $data['f_name'] ?? null,
            'l_name' => $data['l_name'] ?? null,
            'phone' => $record->is_phone_verified ? $record->phone : $phone,
            'email' => $email,
            'is_phone_verified' => $phone == $record->phone ? $record->is_phone_verified : 0,
            'is_email_verified' => $email == $record->email ? $record->is_email_verified : 0,
            'image' => $image === null
                ? $record->image
                : $this->updateFile(dir: self::IMAGE_DIRECTORY, oldImage: $record->image, format: 'webp', image: $image),
            'password' => strlen($password) > self::PROFILE_PASSWORD_MIN_EXCLUSIVE_LENGTH ? bcrypt($password) : $record->password,
        ]);

        return $record->refresh();
    }

    /** @return array{forget: array<int, string>, put: array<string, array>} */
    public function buildSignInSessionState(array $wishListProductIds, array $compareListProductIds, array $restockFcmTopics): array
    {
        return [
            'forget' => self::SIGN_IN_CLEARED_SESSION_KEYS,
            'put' => [
                SessionKey::WISH_LIST => $wishListProductIds,
                SessionKey::PRODUCT_COMPARE_LIST => $compareListProductIds,
                self::CUSTOMER_FCM_TOPIC_SESSION_KEY => $restockFcmTopics,
            ],
        ];
    }

    public function updateWalletBalance(mixed $id, float $walletBalance): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        $record->wallet_balance = $walletBalance;

        return $record->save();
    }

    public function createFromCheckoutRegistration(array $registration): Model
    {
        return $this->query()->create([
            'name' => $registration['name'],
            'f_name' => $registration['name'],
            'l_name' => $registration['l_name'],
            'email' => $registration['email'],
            'phone' => $registration['phone'],
            'is_active' => 1,
            'password' => bcrypt($registration['password']),
            'referral_code' => $registration['referral_code'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function verificationResets(Model $record, array $data): array
    {
        $resets = [];

        if (isset($data['phone']) && $data['phone'] !== $record->phone) {
            $resets['is_phone_verified'] = 0;
        }

        if (isset($data['email']) && $data['email'] !== $record->email) {
            $resets['is_email_verified'] = 0;
            $resets['email_verified_at'] = null;
        }

        return $resets;
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['is_active' => $status]);
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrInsert($params, $data);
    }

    /** @return array{active: int, inactive: int} */
    public function getStatusCounts(?string $searchValue = null, array $filters = []): array
    {
        $excludesWalkingCustomer = ! empty($filters[self::FILTER_EXCLUDE_WALKING_CUSTOMER]);
        $orderedInBusinessModule = $filters[self::FILTER_ORDERED_IN_BUSINESS_MODULE] ?? null;
        unset($filters[self::FILTER_EXCLUDE_WALKING_CUSTOMER], $filters[self::FILTER_ORDERED_IN_BUSINESS_MODULE]);

        $query = $this->query()
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('f_name', 'like', "%{$searchValue}%")
                    ->orWhere('l_name', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%");
            }))
            ->when($excludesWalkingCustomer, fn (Builder $builder) => $this->excludeWalkingCustomer($builder))
            ->when($orderedInBusinessModule, fn (Builder $builder) => $this->orderedInBusinessModule($builder, $orderedInBusinessModule));

        $this->applyFilters($query, $filters);

        $counts = $query->select('is_active')
            ->selectRaw('count(*) as total')
            ->groupBy('is_active')
            ->pluck('total', 'is_active');

        return [
            'active' => (int) ($counts[1] ?? 0),
            'inactive' => (int) ($counts[0] ?? 0),
        ];
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        if ($record->image) {
            $this->deleteFile(filePath: self::IMAGE_DIRECTORY.$record->image);
        }

        return (bool) $record->delete();
    }

    public function exists(array $params): bool
    {
        return $this->query()->where($params)->exists();
    }

    public function count(array $filters = []): int
    {
        $joinedBetween = $filters[self::FILTER_JOINED_BETWEEN] ?? [];
        $excludesWalkingCustomer = ! empty($filters[self::FILTER_EXCLUDE_WALKING_CUSTOMER]);
        $orderedFromSellerId = $filters[self::FILTER_ORDERED_FROM_SELLER] ?? null;
        $orderedInBusinessModule = $filters[self::FILTER_ORDERED_IN_BUSINESS_MODULE] ?? null;
        unset(
            $filters[self::FILTER_JOINED_BETWEEN],
            $filters[self::FILTER_EXCLUDE_WALKING_CUSTOMER],
            $filters[self::FILTER_ORDERED_FROM_SELLER],
            $filters[self::FILTER_ORDERED_IN_BUSINESS_MODULE],
        );

        $query = $this->query()
            ->when($excludesWalkingCustomer, fn (Builder $builder) => $this->excludeWalkingCustomer($builder))
            ->when($joinedBetween, fn (Builder $builder) => $builder->whereBetween('created_at', $joinedBetween))
            ->when($orderedFromSellerId !== null, fn (Builder $builder) => $builder->whereHas(
                'orders',
                fn (Builder $order) => $order->where(['seller_is' => OrderService::SELLER_IS_SELLER, 'seller_id' => $orderedFromSellerId, 'is_guest' => 0]),
            ))
            ->when($orderedInBusinessModule, fn (Builder $builder) => $this->orderedInBusinessModule($builder, $orderedInBusinessModule));

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function deleteAuthAccessTokens(mixed $id): bool
    {
        DB::table(self::ACCESS_TOKEN_TABLE)->where('user_id', $id)->delete();

        return true;
    }

    public function getOptions(array $excludeIds = []): Collection
    {
        return $this->query()
            ->when($excludeIds, fn ($query) => $query->whereKeyNot($excludeIds))
            ->get(['id', 'f_name', 'l_name']);
    }

    /**
     * @return array<int, int>
     */
    public function getIdsMatchingAnyNameWord(string $searchValue): array
    {
        return $this->query()
            ->where(function (Builder $matched) use ($searchValue): void {
                foreach (explode(' ', $searchValue) as $word) {
                    $matched->orWhere('f_name', 'like', "%{$word}%")
                        ->orWhere('l_name', 'like', "%{$word}%");
                }
            })
            ->pluck('id')
            ->all();
    }

    public function getNameOptions(
        ?string $searchValue = null,
        array $excludeIds = [],
        bool $excludesWalkingCustomer = false,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
    ): Collection {
        $keywords = array_filter(explode(' ', (string) $searchValue));

        return $this->query()
            ->with('storage')
            ->when($excludeIds, fn (Builder $builder) => $builder->whereNotIn('id', $excludeIds))
            ->when($excludesWalkingCustomer, fn (Builder $builder) => $this->excludeWalkingCustomer($builder))
            ->when($keywords, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($keywords): void {
                foreach ($keywords as $keyword) {
                    $inner->orWhere('f_name', 'like', "%{$keyword}%")
                        ->orWhere('l_name', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");
                }
            }))
            ->when($dataLimit !== 'all', fn (Builder $builder) => $builder->limit((int) $dataLimit))
            ->get([DB::raw('id, IF(id <> "0", CONCAT(f_name, " ", COALESCE(l_name, ""), " (", phone ,")"), CONCAT(f_name, " ", COALESCE(l_name, ""))) as text')]);
    }

    private function rankedOrders(Builder $order, ?string $businessModule): Builder
    {
        return $businessModule === null ? $order : $order->businessModule($businessModule)->where('is_guest', 0);
    }

    private function orderedInBusinessModule(Builder $query, string $businessModule): Builder
    {
        return $query->whereHas(
            'orders',
            fn (Builder $order) => $order->businessModule($businessModule)->where('is_guest', 0),
        );
    }

    private function excludeWalkingCustomer(Builder $query): Builder
    {
        return $query->where(function (Builder $inner): void {
            $inner->whereNull('email')->orWhere('email', '!=', self::WALKING_CUSTOMER_EMAIL);
        });
    }

    private function paginateFirstRecords(Builder $query, int $limit, int $dataLimit, ?int $page): LengthAwarePaginator
    {
        $records = $query->limit($limit)->get();
        $currentPage = $page ?? LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            items: $records->forPage($currentPage, $dataLimit)->values(),
            total: $records->count(),
            perPage: $dataLimit,
            currentPage: $currentPage,
            options: ['path' => LengthAwarePaginator::resolveCurrentPath()],
        );
    }
}
