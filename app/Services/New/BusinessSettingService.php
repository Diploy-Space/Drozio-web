<?php

namespace App\Services\New;

use App\Models\BusinessSetting;
use App\Services\BaseService;
use App\Utils\ImageManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use PDOException;

class BusinessSettingService extends BaseService
{
    public const COMPANY_IMAGE_DIRECTORY = 'company/';

    public const OTP_EXPIRY_TIME = 'otp_expiry_time';

    public const DEFAULT_OTP_EXPIRY_SECONDS = 300;

    public const MINIMUM_OTP_EXPIRY_SECONDS = 30;

    /** @var array<string, string> */
    public const IN_HOUSE_AVAILABILITY_SETTINGS = [
        'retail' => 'temporary_close',
        'wholesale' => 'wholesale_temporary_close',
        'auction' => 'auction_temporary_close',
    ];

    public const IN_HOUSE_VACATION_MODULES = 'vacation_modules';

    public const IN_HOUSE_VACATION = 'vacation_add';

    public const MINIMUM_ORDER_AMOUNT_BY_SELLER = 'minimum_order_amount_by_seller';

    public const VENDOR_LIST_PRIORITY = 'vendor_list_priority';

    public const TOP_VENDOR_LIST_PRIORITY = 'top_vendor_list_priority';

    /**
     * @var array<string, array{status: string, amount: string}>
     */
    public const IN_HOUSE_FREE_DELIVERY_SETTINGS = [
        'retail' => [
            'status' => 'inhouse_free_delivery_status',
            'amount' => 'free_delivery_over_amount',
        ],
        'wholesale' => [
            'status' => 'wholesale_inhouse_free_delivery_status',
            'amount' => 'wholesale_free_delivery_over_amount',
        ],
    ];

    public const MINIMUM_ORDER_AMOUNT = 'minimum_order_amount';

    public const MINIMUM_ORDER_AMOUNT_STATUS = 'minimum_order_amount_status';

    public const WHOLESALE_IN_HOUSE_MINIMUM_ORDER_AMOUNT = 'wholesale_inhouse_minimum_order_amount';

    /**
     * @var array<string, array{status: string, amount: string}>
     */
    public const IN_HOUSE_MINIMUM_ORDER_AMOUNT_SETTINGS = [
        'retail' => [
            'status' => self::MINIMUM_ORDER_AMOUNT_STATUS,
            'amount' => self::MINIMUM_ORDER_AMOUNT,
        ],
        'wholesale' => [
            'status' => 'wholesale_vendor_can_set_minimum_order_amount',
            'amount' => self::WHOLESALE_IN_HOUSE_MINIMUM_ORDER_AMOUNT,
        ],
    ];

    public const ORDER_VERIFICATION = 'order_verification';

    public const BILLING_INPUT_BY_CUSTOMER = 'billing_input_by_customer';

    public const GUEST_CHECKOUT = 'guest_checkout';

    public const FREE_DELIVERY_STATUS = 'free_delivery_status';

    public const FREE_DELIVERY_RESPONSIBILITY = 'free_delivery_responsibility';

    public const FREE_DELIVERY_OVER_AMOUNT_SELLER = 'free_delivery_over_amount_seller';

    public const FREE_DELIVERY_RESPONSIBILITY_ADMIN = 'admin';

    public const FREE_DELIVERY_RESPONSIBILITY_SELLER = 'seller';

    public const BANNER_FAQ_PAGE = 'banner_faq_page';

    public const JSON_STATUS_ACTIVE = '1';

    public const PRODUCT_BRAND = 'product_brand';

    public const COMPANY_NAME = 'company_name';

    public const MAXIMUM_LOGIN_HIT = 'maximum_login_hit';

    public const TEMPORARY_LOGIN_BLOCK_TIME = 'temporary_login_block_time';

    public const MAXIMUM_OTP_HIT = 'maximum_otp_hit';

    public const OTP_RESEND_TIME = 'otp_resend_time';

    public const TEMPORARY_BLOCK_TIME = 'temporary_block_time';

    public const FIREBASE_OTP_VERIFICATION = 'firebase_otp_verification';

    public const FCM_CREDENTIALS = 'fcm_credentials';

    public const FORGOT_PASSWORD_VERIFICATION = 'forgot_password_verification';

    public const REFERRAL_EARNING_CUSTOMER = 'ref_earning_customer';

    public const REFERRAL_EARNING_EXCHANGE_RATE = 'ref_earning_exchange_rate';

    public const MAIL_CONFIG = 'mail_config';

    public const MAIL_CONFIG_SENDGRID = 'mail_config_sendgrid';

    public const RECAPTCHA = 'recaptcha';

    public const WALLET_STATUS = 'wallet_status';

    public const REFERRAL_EARNING_STATUS = 'ref_earning_status';

    public function __construct(BusinessSetting $businessSetting)
    {
        parent::__construct($businessSetting);
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
            ->when($searchValue, fn (Builder $builder) => $builder->where('value', 'like', "%{$searchValue}%"));

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

    public function findByJsonValue(array $params, array $value): ?Model
    {
        return $this->query()->where($params)->whereJsonContains('value', $value)->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
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

        clearWebConfigCacheKeys();

        return $record->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return false;
    }

    public function updateWhere(array $params, array $data): int
    {
        $affected = $this->query()->where($params)->update($data);

        clearWebConfigCacheKeys();

        return $affected;
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        $this->query()->updateOrInsert($params, [...$data, 'updated_at' => now()]);

        clearWebConfigCacheKeys();

        return true;
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        clearWebConfigCacheKeys();

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

    public function isTableAvailable(): bool
    {
        try {
            return Schema::hasTable($this->model->getTable());
        } catch (PDOException) {
            return false;
        }
    }

    public function getOtpExpirySeconds(): int
    {
        $configured = (int) $this->getValueByType(type: self::OTP_EXPIRY_TIME, default: self::DEFAULT_OTP_EXPIRY_SECONDS);

        return max(self::MINIMUM_OTP_EXPIRY_SECONDS, $configured);
    }

    public function getValueByType(string $type, mixed $default = null): mixed
    {
        $value = $this->findBy(params: ['type' => $type])?->value;

        return $value !== null ? (json_decode($value, true) ?? $value) : $default;
    }

    /** @return array<string, mixed> */
    public function getValuesByTypes(array $types): array
    {
        return $this->query()->whereIn('type', $types)->pluck('value', 'type')->toArray();
    }

    /** @return array{image_name: string, storage: string} */
    public function storeImage(string $type, string $directory, string $format, UploadedFile $image): array
    {
        $storedName = $this->storedImageName(type: $type);

        return [
            'image_name' => $storedName === ''
                ? ImageManager::upload(dir: $directory, format: $format, image: $image)
                : ImageManager::update(dir: $directory, old_image: $storedName, format: $format, image: $image),
            'storage' => $this->storageDisk(),
        ];
    }

    public function storeFile(string $directory, string $format, UploadedFile $file): string
    {
        return ImageManager::file_upload(dir: $directory, format: $format, file: $file);
    }

    /** @return array{image_name: string, storage: string} */
    public function replaceStoredImage(?string $oldImageName, string $directory, string $format, UploadedFile $image): array
    {
        return [
            'image_name' => empty($oldImageName)
                ? ImageManager::upload(dir: $directory, format: $format, image: $image)
                : ImageManager::update(dir: $directory, old_image: $oldImageName, format: $format, image: $image),
            'storage' => $this->storageDisk(),
        ];
    }

    public function deleteStoredImage(string $directory, ?string $imageName): void
    {
        if (empty($imageName)) {
            return;
        }

        ImageManager::delete($directory.$imageName);
    }

    private function storedImageName(string $type): string
    {
        $storedValue = $this->findBy(params: ['type' => $type])?->value;

        if ($storedValue === null) {
            return '';
        }

        $decoded = json_decode((string) $storedValue, true);

        return is_array($decoded) ? (string) ($decoded['image_name'] ?? '') : (string) $storedValue;
    }
}
