<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\BusinessModule;
use App\Enums\WebConfigKey;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\Shop\AvailabilityUpdateRequest;
use App\Http\Requests\Vendor\Shop\OtherSetupUpdateRequest;
use App\Http\Requests\Vendor\Shop\UpdateRequest;
use App\Http\Requests\Vendor\Shop\VacationUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\ReviewService;
use App\Services\New\ShopService;
use App\Services\New\ShopTinDocumentService;
use App\Services\New\VendorService;
use App\Utils\VendorVerificationCriteria;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

class ShopController extends BaseController
{
    private const FREE_DELIVERY_ON = 'on';

    private const FREE_DELIVERY_BY_SELLER = 'seller';

    private const CERTIFICATE_TYPE_PDF = 'pdf';

    private const CERTIFICATE_TYPE_IMAGE = 'image';

    private const CERTIFICATE_TYPE_DOCUMENT = 'document';

    private const CERTIFICATE_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        private readonly ShopService $shopService,
        private readonly VendorService $vendorService,
        private readonly ProductRetailService $productService,
        private readonly OrderService $orderService,
        private readonly ReviewService $reviewService,
        private readonly ShopTinDocumentService $shopTinDocumentService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        $vendorId = $this->vendorId();
        $vendor = $this->vendorService->find(id: $vendorId);

        if ($this->shouldAnnounceVerification(vendorId: $vendorId, isVerified: (bool) $vendor?->is_verified)) {
            ToastMagic::success(translate('Verified'), translate('Congratulations! Your vendor account is now verified.'));

            return redirect()->route('vendor.shop.index');
        }

        $shop = $this->shop() ?? $this->shopService->create(data: [
            'seller_id' => $vendorId,
            'name' => $vendor['f_name'],
            'contact' => $vendor['phone'],
        ]);

        $productIds = $this->vendorProductIds(vendorId: $vendorId);
        $availabilityModules = $this->getAvailabilityModules();
        $shopTinDocuments = $this->shopTinInfo(shopId: $shop?->id);
        $vacationModules = $this->shopService->getVacationModules(
            moduleLabels: $this->moduleLabels(),
            selectedModuleKeys: $shop['vacation_modules'],
        );

        return view('vendor-views.shop.index', [
            'shop' => $shop,
            'vendor' => $vendor,
            'vacationModules' => $vacationModules,
            'vacationSchedule' => $this->shopService->getVacationSchedule(
                isOnVacation: (bool) $shop['vacation_status'],
                durationType: $shop['vacation_duration_type'],
                startDate: $shop['vacation_start_date'],
                endDate: $shop['vacation_end_date'],
                vacationModules: $vacationModules,
            ),
            'availabilityModules' => $availabilityModules,
            'closedBusinesses' => $this->shopService->getClosedBusinessesLabel(availabilityModules: $availabilityModules),
            'storefrontLinks' => $this->shopService->getStorefrontLinks(slug: $shop?->slug),
            'shopTinDocuments' => $shopTinDocuments,
            'retailOverview' => $this->retailOverview(
                isActive: ! (bool) $shop?->temporary_close,
                vendorId: $vendorId,
                productIds: $productIds,
                tin: $shopTinDocuments[BusinessModule::RETAIL] ?? null,
            ),
            'verification' => $this->verificationOverview(
                vendorId: $vendorId,
                isVerified: (bool) $vendor?->is_verified,
            ),
        ]);
    }

    /** @return array<string, mixed> */
    private function retailOverview(bool $isActive, int $vendorId, array $productIds, ?array $tin): array
    {
        $rating = $this->reviewService->getRatingSummary(filters: ['product_id' => $productIds], precision: 1, includeInactive: true);

        return [
            'statLabel' => translate('Retail'),
            'statIsActive' => $isActive,
            'statMetrics' => [
                translate('Products') => count($productIds),
                translate('Orders') => $this->orderService->count(filters: [
                    'seller_id' => $vendorId,
                    'seller_is' => OrderService::SELLER_IS_SELLER,
                ]),
            ],
            'statRating' => [
                'average' => $rating['avg_rating'],
                'ratings' => $rating['total_review'],
                'reviews' => $rating['total_comment'],
            ],
            'statTin' => $tin,
        ];
    }

    /** @return array<string, array{number: string, expire_date: ?string}> */
    private function shopTinInfo(?int $shopId): array
    {
        if ($shopId === null) {
            return [];
        }

        $info = [];

        foreach ($this->shopTinDocumentService->getByShop(shopId: $shopId) as $businessModule => $document) {
            if (empty($document->tax_identification_number)) {
                continue;
            }

            $info[$businessModule] = [
                'number' => $document->tax_identification_number,
                'expire_date' => $document->tin_expire_date?->format('m/d/Y'),
            ];
        }

        return $info;
    }

    /** @return array{is_verified: bool, is_badge_enabled: bool, is_qualified: bool, show_prompt: bool, criteria: array<int, array<string, mixed>>} */
    private function verificationOverview(int|string $vendorId, bool $isVerified): array
    {
        return VendorVerificationCriteria::overview(
            metrics: $this->vendorService->getVerificationMetrics(id: $vendorId),
            isVerified: $isVerified,
            isBadgeEnabled: (bool) $this->businessSettingService->getValueByType(type: WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS, default: 0),
        );
    }

    private function shouldAnnounceVerification(int|string $vendorId, bool $isVerified): bool
    {
        return $isVerified
            && $this->businessSettingService->getValueByType(type: WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS, default: 0)
            && $this->vendorService->markVerifiedNoticeSeen(id: $vendorId);
    }

    public function getUpdateView(string|int $id): View
    {
        return view('vendor-views.shop.update-view', ['shop' => $this->shop()]);
    }

    public function update(UpdateRequest $request, string|int $id): RedirectResponse
    {
        $this->shopService->update(
            id: $this->shop()?->id,
            data: $request->all(),
            images: [
                'image' => $request->file('image'),
                'banner' => $request->file('banner'),
                'bottom_banner' => $request->file('bottom_banner'),
                'offer_banner' => $request->file('offer_banner'),
            ],
        );
        updateSetupGuideCacheKey(key: 'shop_setup', panel: 'vendor');
        ToastMagic::info(translate(':item updated successfully', ['item' => translate('Shop')]));

        return redirect()->route('vendor.shop.index');
    }

    public function updateVacation(VacationUpdateRequest $request): RedirectResponse
    {
        $this->shopService->updateBy(
            params: ['id' => $this->shop()?->id],
            data: $request->vacationColumns(),
        );
        updateSetupGuideCacheKey(key: 'shop_setup', panel: 'vendor');
        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Vacation Mode')]));

        return back();
    }

    public function updateAvailability(AvailabilityUpdateRequest $request): RedirectResponse
    {
        $this->shopService->updateBy(
            params: $this->shopParams(),
            data: $request->temporaryCloseColumns(),
        );
        cacheRemoveByType(type: 'shops');
        updateSetupGuideCacheKey(key: 'shop_setup', panel: 'vendor');
        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Status')]));

        return back();
    }

    public function getPaymentInformationView(Request $request): View
    {
        return view('vendor-views.shop.payment-information');
    }

    public function getOtherSetupView(Request $request): View
    {
        $shop = $this->shop();
        $vendor = $this->vendorService->find(id: $this->vendorId());

        return view('vendor-views.shop.other-setup', [
            'shop' => $shop,
            'vendor' => $vendor,
            'showMinimumOrderAmount' => (bool) getWebConfig(name: 'minimum_order_amount_status')
                && (bool) getWebConfig(name: 'minimum_order_amount_by_seller'),
            'showRetailFreeDelivery' => $this->isRetailFreeDeliveryBySeller(),
            'showWholesaleMinimumOrderAmount' => $this->vendorService->canSetWholesaleMinimumOrderAmount(),
            'showWholesaleFreeDelivery' => $this->isWholesaleFreeDeliveryBySeller(),
            'retailFreeDelivery' => $this->freeDeliveryFormState(
                storedStatus: $vendor?->free_delivery_status,
                storedAmount: $vendor?->free_delivery_over_amount,
                statusField: 'free_delivery_status',
                amountField: 'free_delivery_over_amount',
            ),
            'wholesaleFreeDelivery' => $this->freeDeliveryFormState(
                storedStatus: $vendor?->wholesale_free_delivery_status,
                storedAmount: $vendor?->wholesale_free_delivery_over_amount,
                statusField: 'wholesale_free_delivery_status',
                amountField: 'wholesale_free_delivery_over_amount',
            ),
            'tinDocuments' => $this->tinDocuments(shopId: $shop?->id),
            'tinMaxLength' => OtherSetupUpdateRequest::TIN_MAX_LENGTH,
        ]);
    }

    public function updateOtherSettings(OtherSetupUpdateRequest $request): RedirectResponse
    {
        $vendorColumns = $this->vendorSetupColumns($request->all());

        if ($vendorColumns !== []) {
            $this->vendorService->updateBy(params: ['id' => $this->vendorId()], data: $vendorColumns);
        }

        $shop = $this->shop();
        $tinDocuments = $this->tinDocumentColumns(data: $request['tin'] ?? []);

        if ($shop !== null) {
            $this->shopService->update(
                id: $shop->id,
                data: $tinDocuments[BusinessModule::RETAIL] ?? [],
                tinCertificate: $request->file('tin.'.BusinessModule::RETAIL.'.tin_certificate'),
            );

            $this->shopTinDocumentService->syncForShop(
                shopId: $shop->id,
                documents: $tinDocuments,
                certificates: $this->tinCertificates(request: $request),
            );
        }

        updateSetupGuideCacheKey(key: 'order_setup', panel: 'vendor');
        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    /** @return array<string, array{label: string, tax_identification_number: string|null, tin_expire_date: string|null, certificate_name: string|null, certificate_url: string|null, certificate_type: string|null}> */
    private function tinDocuments(?int $shopId): array
    {
        $storedDocuments = $shopId === null ? [] : $this->shopTinDocumentService->getByShop(shopId: $shopId);

        $documents = [];
        foreach ($this->moduleLabels() as $moduleKey => $moduleLabel) {
            $document = $storedDocuments[$moduleKey] ?? null;
            $certificate = $document?->tin_certificate_full_url;
            $certificateUrl = ($certificate['status'] ?? null) === 200 ? $certificate['path'] : null;

            $documents[$moduleKey] = [
                'label' => $moduleLabel,
                'tax_identification_number' => $document?->tax_identification_number,
                'tin_expire_date' => $document?->tin_expire_date?->format('m/d/Y'),
                'certificate_name' => $certificateUrl === null ? null : $document->tin_certificate,
                'certificate_url' => $certificateUrl,
                'certificate_type' => $certificateUrl === null ? null : $this->certificateType(certificate: $document->tin_certificate),
            ];
        }

        return $documents;
    }

    private function certificateType(string $certificate): string
    {
        $extension = strtolower(pathinfo($certificate, PATHINFO_EXTENSION));

        return match (true) {
            $extension === 'pdf' => self::CERTIFICATE_TYPE_PDF,
            in_array($extension, self::CERTIFICATE_IMAGE_EXTENSIONS, true) => self::CERTIFICATE_TYPE_IMAGE,
            default => self::CERTIFICATE_TYPE_DOCUMENT,
        };
    }

    /**
     * @param  array<string, array<string, string|null>>  $data
     * @return array<string, array<string, string|null>>
     */
    private function tinDocumentColumns(array $data): array
    {
        return array_map(static fn (array $document): array => [
            'tax_identification_number' => $document['tax_identification_number'] ?? null,
            'tin_expire_date' => $document['tin_expire_date'] ?? null,
            'remove_certificate' => filter_var($document['remove_certificate'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ], array_intersect_key($data, $this->moduleLabels()));
    }

    /** @return array<string, UploadedFile|null> */
    private function tinCertificates(Request $request): array
    {
        $certificates = [];

        foreach (array_keys($this->moduleLabels()) as $businessModule) {
            $certificates[$businessModule] = $request->file("tin.{$businessModule}.tin_certificate");
        }

        return $certificates;
    }

    /** @return array<int, array{key: string, label: string, description_on: string, description_off: string, is_open: bool}> */
    private function getAvailabilityModules(): array
    {
        return $this->shopService->getAvailabilityModules(
            moduleLabels: $this->moduleLabels(),
            availability: $this->shopAvailability(moduleKeys: array_keys(businessModuleManager()->availableInPanel())),
        );
    }

    /** @return array<string, string> */
    private function moduleLabels(): array
    {
        return array_map(fn ($module) => $module->label, businessModuleManager()->availableInPanel());
    }

    /** @return array<string, mixed> */
    private function shopParams(): array
    {
        return [
            'seller_id' => $this->vendorId(),
            'author_type' => ShopService::AUTHOR_TYPE_VENDOR,
        ];
    }

    private function shop(array $relations = []): mixed
    {
        return $this->shopService->findBy(params: $this->shopParams(), relations: $relations);
    }

    /** @return array<int, int> */
    private function vendorProductIds(int $vendorId): array
    {
        return $this->productService->getList(
            dataLimit: 'all',
            filters: [
                'added_by' => ProductRetailService::ADDED_BY_SELLER,
                'user_id' => $vendorId,
            ],
        )->pluck('id')->all();
    }

    /**
     * @param  string[]  $moduleKeys
     * @return array<string, bool>
     */
    private function shopAvailability(array $moduleKeys): array
    {
        $columns = array_intersect_key(ShopService::TEMPORARY_CLOSE_COLUMNS, array_flip($moduleKeys));
        $shop = $this->shop();

        $availability = [];
        foreach ($columns as $moduleKey => $column) {
            $availability[$moduleKey] = ! (bool) ($shop?->{$column} ?? false);
        }

        return $availability;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function vendorSetupColumns(array $data): array
    {
        $columns = [];

        if (array_key_exists('minimum_order_amount', $data)) {
            $columns['minimum_order_amount'] = currencyConverter($data['minimum_order_amount'], 'usd');
        }

        if (array_key_exists('stock_limit', $data)) {
            $columns['stock_limit'] = $data['stock_limit'] ?? 1;
        }

        if ($this->isRetailFreeDeliveryBySeller()) {
            $columns = [...$columns, ...$this->freeDeliveryColumns(
                data: $data,
                statusField: 'free_delivery_status',
                amountField: 'free_delivery_over_amount',
            )];
        }

        if ($this->isWholesaleFreeDeliveryBySeller()) {
            $columns = [...$columns, ...$this->freeDeliveryColumns(
                data: $data,
                statusField: 'wholesale_free_delivery_status',
                amountField: 'wholesale_free_delivery_over_amount',
            )];
        }

        if (array_key_exists('wholesale_minimum_order_amount', $data) && $this->vendorService->canSetWholesaleMinimumOrderAmount()) {
            $columns['wholesale_minimum_order_amount'] = currencyConverter($data['wholesale_minimum_order_amount'] ?? 0, 'usd');
        }

        return $columns;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function freeDeliveryColumns(array $data, string $statusField, string $amountField): array
    {
        return [
            $statusField => ($data[$statusField] ?? null) === self::FREE_DELIVERY_ON ? 1 : 0,
            $amountField => blank($data[$amountField] ?? null) ? 0 : currencyConverter($data[$amountField], 'usd'),
        ];
    }

    /** @return array{is_on: bool, amount: mixed} */
    private function freeDeliveryFormState(mixed $storedStatus, mixed $storedAmount, string $statusField, string $amountField): array
    {
        $isOn = session()->hasOldInput()
            ? old($statusField) === self::FREE_DELIVERY_ON
            : (int) $storedStatus === 1;

        $storedAmountValue = (float) $storedAmount > 0
            ? usdToDefaultCurrency(amount: $storedAmount)
            : '';

        return [
            'is_on' => $isOn,
            'amount' => old($amountField, $storedAmountValue),
        ];
    }

    private function isRetailFreeDeliveryBySeller(): bool
    {
        return (bool) getWebConfig(name: 'free_delivery_status')
            && getWebConfig(name: 'free_delivery_responsibility') === self::FREE_DELIVERY_BY_SELLER;
    }

    private function isWholesaleFreeDeliveryBySeller(): bool
    {
        return $this->isWholesaleAvailable()
            && getWebConfig(name: 'wholesale_free_delivery_status')
            && getWebConfig(name: 'wholesale_free_delivery_responsibility') === self::FREE_DELIVERY_BY_SELLER;
    }

    private function isWholesaleAvailable(): bool
    {
        return function_exists('businessModuleManager')
            && businessModuleManager()->isAvailableInPanel(BusinessModule::WHOLESALE);
    }

    private function vendorId(): int
    {
        return auth('seller')->id();
    }
}
