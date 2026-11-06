<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Product;
use App\Services\New\Clearance\StockClearanceProductService;
use App\Utils\Convert;
use App\Utils\FileExistenceCache;
use App\Utils\ProductManager;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\TaxModule\app\Traits\VatTaxManagement;
use Rap2hpoutre\FastExcel\FastExcel;

class ProductRetailService extends ProductService
{
    use VatTaxManagement;

    public const CATEGORY_ALL = 'all';

    public const DIGITAL_STOCK_DISPLAY = 999;

    public const FILTER_CATEGORY_IDS_JSON = 'category_ids_json';

    public const LIST_BRAND = 'brand';

    public const LIST_CATEGORY = 'category';

    public const LIST_FEATURED = 'featured';

    public const LIST_LATEST = 'latest';

    public const LIST_BEST_SELLING = 'best-selling';

    public const LIST_TOP_RATED = 'top-rated';

    public const LIST_MOST_FAVORITE = 'most-favorite';

    public const LIST_SEARCH = 'search';

    public const LIST_PUBLISHING_HOUSE = 'publishing_house';

    public const OFFER_DISCOUNTED = 'discounted';

    public const OFFER_CLEARANCE_SALE = 'clearance_sale';

    public const OFFER_FEATURED_DEAL = 'featured_deal';

    public const OFFER_FLASH_DEALS = 'flash-deals';

    public const SORT_PRICE_LOW_HIGH = 'low-high';

    public const SORT_PRICE_HIGH_LOW = 'high-low';

    public const SORT_NAME_A_TO_Z = 'a-z';

    public const SORT_NAME_Z_TO_A = 'z-a';

    public const SORT_RATING_LOW_HIGH = 'rating-low-high';

    public const SORT_RATING_HIGH_LOW = 'rating-high-low';

    public const STOREFRONT_OFFER_TYPES = [
        self::OFFER_DISCOUNTED,
        self::OFFER_CLEARANCE_SALE,
        self::OFFER_FEATURED_DEAL,
        self::OFFER_FLASH_DEALS,
    ];

    public const STOREFRONT_SORTS = [
        self::SORT_LATEST,
        self::SORT_PRICE_LOW_HIGH,
        self::SORT_PRICE_HIGH_LOW,
        self::SORT_NAME_A_TO_Z,
        self::SORT_NAME_Z_TO_A,
        self::SORT_RATING_LOW_HIGH,
        self::SORT_RATING_HIGH_LOW,
    ];

    private const RANKED_LISTS = [
        self::LIST_TOP_RATED,
        self::LIST_BEST_SELLING,
        self::LIST_MOST_FAVORITE,
    ];

    public const FILTER_DATA_FROM = 'data_from';

    public const FILTER_OFFER_TYPE = 'offer_type';

    public const FILTER_BRAND_ID = 'brand_id';

    public const FILTER_BRAND_IDS = 'brand_ids';

    public const FILTER_CATEGORY_ID = 'category_id';

    public const FILTER_SUB_CATEGORY_ID = 'sub_category_id';

    public const FILTER_SUB_SUB_CATEGORY_ID = 'sub_sub_category_id';

    public const FILTER_CATEGORY_IDS = 'category_ids';

    public const FILTER_SUB_CATEGORY_IDS = 'sub_category_ids';

    public const FILTER_SUB_SUB_CATEGORY_IDS = 'sub_sub_category_ids';

    public const FILTER_SEARCH_CATEGORY_VALUE = 'search_category_value';

    public const FILTER_PRODUCT_TYPE = 'product_type';

    public const FILTER_PUBLISHING_HOUSE_ID = 'publishing_house_id';

    public const FILTER_PUBLISHING_HOUSE_IDS = 'publishing_house_ids';

    public const FILTER_AUTHOR_ID = 'author_id';

    public const FILTER_AUTHOR_IDS = 'author_ids';

    public const FILTER_COLOR_IDS = 'color_ids';

    public const FILTER_MIN_PRICE = 'min_price';

    public const FILTER_MAX_PRICE = 'max_price';

    public const FILTER_RATING = 'rating';

    public const FILTER_RATINGS = 'ratings';

    public const FILTER_NAME = 'name';

    public const FILTER_PRODUCT_NAME = 'product_name';

    public const FILTER_SEARCH = 'search';

    public const FILTER_FLASH_DEALS_ID = 'flash_deals_id';

    public const FILTER_CLEARANCE_PRODUCT_IDS = 'clearance_product_ids';

    public const FILTER_FLASH_DEAL_PRODUCT_IDS = 'flash_deal_product_ids';

    public const FILTER_FEATURED_DEAL_PRODUCT_IDS = 'featured_deal_product_ids';

    public const FILTER_ATTACHED_PUBLISHER_PRODUCT_IDS = 'attached_publisher_product_ids';

    public const FILTER_ATTACHED_AUTHOR_PRODUCT_IDS = 'attached_author_product_ids';

    public const FILTER_SELECTED_PUBLISHER_PRODUCT_IDS = 'selected_publisher_product_ids';

    public const FILTER_SELECTED_AUTHOR_PRODUCT_IDS = 'selected_author_product_ids';

    public const FILTER_KEYWORD_AUTHOR_PRODUCT_IDS = 'keyword_author_product_ids';

    public const FILTER_KEYWORD_PUBLISHER_PRODUCT_IDS = 'keyword_publisher_product_ids';

    public const FILTER_TREE_CATEGORY_IDS = 'tree_category_ids';

    public const FILTER_TREE_SUB_CATEGORY_IDS = 'tree_sub_category_ids';

    public const FILTER_TREE_SUB_SUB_CATEGORY_IDS = 'tree_sub_sub_category_ids';

    /** @var array<int, string> */
    public const STOREFRONT_FILTER_KEYS = [
        self::FILTER_DATA_FROM,
        self::FILTER_OFFER_TYPE,
        self::FILTER_BRAND_ID,
        self::FILTER_BRAND_IDS,
        self::FILTER_CATEGORY_ID,
        self::FILTER_SUB_CATEGORY_ID,
        self::FILTER_SUB_SUB_CATEGORY_ID,
        self::FILTER_CATEGORY_IDS,
        self::FILTER_SUB_CATEGORY_IDS,
        self::FILTER_SUB_SUB_CATEGORY_IDS,
        self::FILTER_SEARCH_CATEGORY_VALUE,
        self::FILTER_PRODUCT_TYPE,
        self::FILTER_PUBLISHING_HOUSE_ID,
        self::FILTER_PUBLISHING_HOUSE_IDS,
        self::FILTER_AUTHOR_ID,
        self::FILTER_AUTHOR_IDS,
        self::FILTER_COLOR_IDS,
        self::FILTER_MIN_PRICE,
        self::FILTER_MAX_PRICE,
        self::FILTER_RATING,
        self::FILTER_RATINGS,
        self::FILTER_NAME,
        self::FILTER_PRODUCT_NAME,
        self::FILTER_SEARCH,
        self::FILTER_FLASH_DEALS_ID,
    ];

    private const FEATURED_PRIORITY_SETTING = 'featured_product_priority';

    private const TOP_RATED_PRIORITY_SETTING = 'top_rated_product_list_priority';

    private const BEST_SELLING_PRIORITY_SETTING = 'best_selling_product_list_priority';

    private const FEATURED_DEAL_PRIORITY_SETTING = 'feature_deal_priority';

    private const FLASH_DEAL_PRIORITY_SETTING = 'flash_deal_priority';

    private const CLEARANCE_PRIORITY_SETTING = 'stock_clearance_product_list_priority';

    private const CLEARANCE_VENDOR_PRIORITY_SETTING = 'stock_clearance_vendor_priority';

    private const VENDOR_LIST_PRIORITY_SETTING = 'vendor_product_list_priority';

    private const SEARCH_PRIORITY_SETTING = 'searched_product_list_priority';

    private const CATEGORY_PRIORITY_SETTING = 'category_wise_product_list_priority';

    public const PREVIEW_DIRECTORY = 'product/preview/';

    public const DIGITAL_DIRECTORY = 'product/digital-product/';

    private const IMPORT_COLUMNS = [
        'name', 'category_id', 'sub_category_id', 'sub_sub_category_id', 'brand_id', 'unit',
        'minimum_order_qty', 'status', 'refundable', 'youtube_video_url', 'unit_price', 'tax_ids',
        'discount', 'discount_type', 'current_stock', 'details', 'thumbnail',
    ];

    private const IMPORT_THUMBNAIL_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    private const IMPORT_OPTIONAL_COLUMNS = [
        'sub_category_id', 'sub_sub_category_id', 'brand_id', 'youtube_video_url', 'details', 'thumbnail',
    ];

    public const PRIORITY_SORTS = [
        'flash_deal_priority',
        'feature_deal_priority',
        'stock_clearance_product_list_priority',
        'featured_product_priority',
        'top_rated_product_list_priority',
        'best_selling_product_list_priority',
        'new_arrival_product_list_priority',
    ];

    public const SECTION_LATEST = 'latest';

    public const SECTION_NEW_ARRIVAL = 'new-arrival';

    public const SECTION_FEATURED = 'featured';

    public const SECTION_TOP_RATED = 'top-rated';

    public const SECTION_BEST_SELLING = 'best-selling';

    public const SECTION_DISCOUNTED = 'discounted';

    public const SECTION_JUST_FOR_YOU = 'just-for-you';

    public const SECTION_MOST_SEARCHED = 'most-searched';

    public const SECTION_SHOP_AGAIN = 'shop-again';

    public const SECTION_RELATED = 'related';

    public const SECTION_SEARCH = 'search';

    public const SECTION_FILTER = 'filter';

    public const SECTION_CATEGORY = 'category';

    public const SECTION_BRAND = 'brand';

    public const SECTION_SHOP = 'shop';

    public const SECTION_SHOP_BEST_SELLING = 'shop-best-selling';

    public const SECTION_SHOP_FEATURED = 'shop-featured';

    public const SECTION_SHOP_RECOMMENDED = 'shop-recommended';

    public const FILTER_ADDED_BY = 'added_by';

    public const FILTER_SELLER_ID = 'seller_id';

    public const FILTER_PRODUCT_IDS = 'product_ids';

    public const FILTER_EXACT_CATEGORY_IDS = 'exact_category_ids';

    public const FILTER_PREFERRED_CATEGORY_IDS = 'preferred_category_ids';

    public const FILTER_SEARCH_PRODUCT_IDS = 'search_product_ids';

    public const FILTER_SEARCH_TRANSLATED_ONLY = 'search_translated_only';

    public const FILTER_SORT_BY = 'sort_by';

    public const FILTER_PRICE_MIN = 'price_min';

    public const FILTER_PRICE_MAX = 'price_max';

    public const FILTER_PRODUCT_TYPES = 'product_types';

    public const FILTER_STATUS = 'status';

    public const FILTER_STATUSES = 'statuses';

    public const FILTER_REQUEST_STATUS = 'request_status';

    public const FILTER_START_DATE = 'start_date';

    public const FILTER_END_DATE = 'end_date';

    public const FILTER_CURRENCY_CODE = 'currency_code';

    public const FILTER_CATALOGUE_SORT = 'filter_sort_by';

    public const SHOP_AGAIN_LIMIT = 12;

    public const RELATED_LIMIT = 10;

    private const SHOP_AGAIN_COLUMNS = ['id', 'name', 'slug', 'thumbnail', 'unit_price', 'added_by', 'user_id'];

    private const NEW_ARRIVAL_PRIORITY_SETTING = 'new_arrival_product_list_priority';

    private const CATALOGUE_DATE_FORMAT = 'm/d/Y h:i:s A';

    protected function applyColumnFilters(Builder $query, array $filters): void
    {
        parent::applyColumnFilters($query, $filters);

        if (isset($filters['featured']) && $filters['featured'] !== '') {
            $query->whereHas('retailDetail', fn (Builder $builder) => $builder->where('featured', $filters['featured']));
        }

        if (isset($filters[self::FILTER_CATEGORY_IDS_JSON])) {
            $query->whereJsonContains('category_ids', [['id' => (string) $filters[self::FILTER_CATEGORY_IDS_JSON]]]);
        }
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    /** @return array<int, Model> */
    public function getAssistantKeywordMatches(
        string $keyword,
        ?int $categoryId,
        int $limit,
        array $authorProductIds = [],
        array $publishingHouseProductIds = [],
    ): array {
        return $this->searchForStorefront(
            keyword: $keyword,
            categoryId: $categoryId ?? self::CATEGORY_ALL,
            authorProductIds: $authorProductIds,
            publishingHouseProductIds: $publishingHouseProductIds,
            limit: $limit,
            page: 1,
        );
    }

    /** @return array<int, Model> */
    public function getAssistantDiscountedList(?string $keyword, int $limit, ?float $maxPriceUsd = null): array
    {
        $fetchLimit = $maxPriceUsd !== null ? min($limit * 3, 30) : $limit;

        $products = $this->query()
            ->active()
            ->with(['rating', 'tags', 'clearanceSale' => fn ($relation) => $relation->active()])
            ->withCount(['orderDetails', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->where(function (Builder $q) {
                $q->where('discount', '>', 0)
                    ->orWhereHas('clearanceSale', fn (Builder $c) => $c->active());
            })
            ->when($keyword, function (Builder $q) use ($keyword) {
                $q->where(function (Builder $inner) use ($keyword) {
                    $inner->where('name', 'like', "%{$keyword}%")
                        ->orWhereHas('tags', fn (Builder $t) => $t->where('tag', 'like', "%{$keyword}%"));
                });
            })
            ->orderByDesc('discount')
            ->limit($fetchLimit)
            ->get()
            ->all();

        return $this->finalizeAssistantList($products, $maxPriceUsd, null, null, rerankByPopularity: true, limit: $limit);
    }

    /** @return array<int, Model> */
    public function getAssistantDealList(int $dealId, ?string $keyword, int $limit, ?float $maxPriceUsd = null): array
    {
        $fetchLimit = $maxPriceUsd !== null ? min($limit * 3, 30) : $limit;

        $products = $this->query()
            ->active()
            ->flashDeal($dealId)
            ->with(['rating', 'tags', 'clearanceSale' => fn ($relation) => $relation->active()])
            ->withCount(['orderDetails', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->when($keyword, function (Builder $q) use ($keyword) {
                $q->where(function (Builder $inner) use ($keyword) {
                    $inner->where('name', 'like', "%{$keyword}%")
                        ->orWhereHas('tags', fn (Builder $t) => $t->where('tag', 'like', "%{$keyword}%"));
                });
            })
            ->limit($fetchLimit)
            ->get()
            ->all();

        return $this->finalizeAssistantList($products, $maxPriceUsd, null, null, rerankByPopularity: true, limit: $limit);
    }

    public function create(
        array $data,
        array $files = [],
        string $addedBy = self::ADDED_BY_ADMIN,
        ?int $userId = null,
        int|string $shopId = 0,
        array $colorNames = [],
        ?array $images = null,
        array $authorIds = [],
        array $publishingHouseIds = [],
        array $taxIds = [],
    ): ?Model {
        $images ??= $this->processImages($data, $files);

        return DB::transaction(function () use ($data, $files, $addedBy, $userId, $shopId, $colorNames, $images, $authorIds, $publishingHouseIds, $taxIds): Model {
            $product = parent::create(
                data: $data,
                files: $files,
                addedBy: $addedBy,
                userId: $userId,
                shopId: $shopId,
                colorNames: $colorNames,
                images: $images,
            );

            $this->writeRetailDetail($product->id, $this->buildRetailDetailAttributes($data, $files, $images, $colorNames));
            $this->syncTags(product: $product, tags: $data['tags'] ?? null);
            $this->syncTranslations(id: $product->id, data: $data, fields: ['name', 'description', 'title']);
            $this->syncSeoData(product: $product, data: $data, files: $files, action: 'add');
            $this->syncDigitalRelations(product: $product, data: $data, authorIds: $authorIds, publishingHouseIds: $publishingHouseIds);

            foreach ($this->getDigitalVariationFiles(data: $data, files: $files, product: $product) as $digitalFile) {
                $product->digitalVariation()->create($digitalFile);
            }

            $this->getAddTaxData(taxableType: Product::class, taxableId: $product->id, taxIds: $taxIds);

            return $product;
        });
    }

    public function update(
        mixed $id,
        array $data,
        array $files = [],
        string $updateBy = self::ADDED_BY_ADMIN,
        array $colorNames = [],
        ?array $images = null,
        array $authorIds = [],
        array $publishingHouseIds = [],
        array $taxIds = [],
    ): ?Model {
        $product = $this->findUnscoped($id, ['retailDetail']);

        if ($product === null) {
            return null;
        }

        $images ??= $this->processUpdateImages($data, $files, $product);
        $discardedImageNames = $this->getDiscardedImageNames($product, $images);

        $updatedProduct = DB::transaction(function () use ($id, $data, $files, $updateBy, $colorNames, $images, $product, $authorIds, $publishingHouseIds, $taxIds): ?Model {
            $updated = parent::update(
                id: $id,
                data: $data,
                files: $files,
                updateBy: $updateBy,
                colorNames: $colorNames,
                images: $images,
            );

            $this->writeRetailDetail($id, $this->buildRetailDetailUpdateAttributes($id, $data, $files, $images, $colorNames));
            $this->syncTags(product: $product, tags: $data['tags'] ?? null);
            $this->syncTranslations(id: $id, data: $data, fields: ['name', 'description', 'title']);
            $this->syncSeoData(product: $product, data: $data, files: $files, action: 'update');
            $this->syncDigitalVariations(product: $product, data: $data, files: $files);
            $this->syncDigitalRelations(product: $product, data: $data, authorIds: $authorIds, publishingHouseIds: $publishingHouseIds);

            $this->getUpdateTaxData(
                taxableType: Product::class,
                taxableId: $product->id,
                taxIds: $taxIds,
                oldTaxIds: $product->taxVats?->pluck('tax_id')->toArray() ?? [],
            );

            return $updated;
        });

        $this->deleteImageFiles($discardedImageNames);

        return $updatedProduct;
    }

    public function delete(mixed $id): bool
    {
        if ($this->findUnscoped($id) === null) {
            return false;
        }

        return DB::transaction(function () use ($id): bool {
            $this->deleteRetailDetail($id);

            return parent::delete($id);
        });
    }

    public function createMany(array $products, array $codes, array $retailByCode = []): Collection
    {
        return DB::transaction(function () use ($products, $codes, $retailByCode): Collection {
            $imported = parent::createMany($products, $codes);

            foreach ($imported as $product) {
                if (isset($retailByCode[$product->code])) {
                    $this->writeRetailDetail($product->id, $retailByCode[$product->code]);
                }
            }

            return $imported;
        });
    }

    public function updateVariation(mixed $productId, array $variations): Model
    {
        return $this->writeRetailDetail($productId, ['variation' => json_encode($variations)]);
    }

    public function toggleFeatured(mixed $productId): bool
    {
        return (bool) $this->writeRetailDetail(
            $productId,
            ['featured' => empty($this->findRetailDetail($productId)?->featured) ? 1 : 0],
        );
    }

    public function updateFeatured(mixed $productId, int $featured): Model
    {
        return $this->writeRetailDetail($productId, ['featured' => $featured]);
    }

    public function deletePreviewFile(mixed $productId): bool
    {
        $previewFile = $this->findRetailDetail($productId)?->preview_file;

        if ($previewFile) {
            $this->deleteFile(filePath: self::PREVIEW_DIRECTORY.$previewFile);
        }

        return (bool) $this->writeRetailDetail($productId, ['preview_file' => null]);
    }

    private function buildRetailDetailAttributes(array $data, array $files, array $images, array $colorNames): array
    {
        $digitalFile = $this->uploadDigitalFile($data, $files);
        $previewFile = $this->uploadPreviewFile($data, $files);
        $storage = $this->storageDisk();
        $hasUploadedPreviewFile = ($files['preview_file'] ?? null) instanceof UploadedFile;
        $hasUploadedDigitalFile = ($files['digital_file_ready'] ?? null) instanceof UploadedFile;

        return [
            'digital_product_type' => $this->isPhysical($data) ? null : ($data['digital_product_type'] ?? null),
            'digital_file_ready' => $digitalFile,
            'digital_file_ready_storage_type' => match (true) {
                $digitalFile === '' => null,
                $hasUploadedDigitalFile => $storage,
                default => $data['existing_digital_file_ready_storage_type'] ?? null,
            },
            'variation' => json_encode($this->isPhysical($data) ? $this->buildVariations($data, $colorNames) : []),
            'digital_product_file_types' => $data['extensions_type'] ?? [],
            'digital_product_extensions' => $this->getDigitalVariationCombinations($data),
            'purchase_price' => 0,
            'color_image' => json_encode($images['colored_image_names']),
            'preview_file' => $previewFile,
            'preview_file_storage_type' => match (true) {
                $previewFile === '' => null,
                $hasUploadedPreviewFile => $storage,
                default => $data['existing_preview_file_storage_type'] ?? null,
            },
        ];
    }

    private function buildRetailDetailUpdateAttributes(mixed $productId, array $data, array $files, array $images, array $colorNames): array
    {
        $storage = $this->storageDisk();
        $retail = $this->findRetailDetail($productId);

        $attributes = [
            'digital_product_type' => $this->isPhysical($data) ? null : ($data['digital_product_type'] ?? null),
            'variation' => json_encode($this->isPhysical($data) ? $this->buildVariations($data, $colorNames) : []),
            'digital_product_file_types' => $data['extensions_type'] ?? [],
            'digital_product_extensions' => $this->getDigitalVariationCombinations($data),
            'purchase_price' => 0,
            'color_image' => json_encode($images['colored_image_names']),
            'digital_file_ready' => $this->resolveDigitalFile($data, $files, $retail?->digital_file_ready),
            'digital_file_ready_storage_type' => isset($files['digital_file_ready'])
                ? $storage
                : $retail?->digital_file_ready_storage_type,
        ];

        if (isset($files['preview_file'])) {
            $attributes['preview_file'] = $this->updateFile(dir: self::PREVIEW_DIRECTORY, oldImage: $retail?->preview_file, format: $files['preview_file']->getClientOriginalExtension(), image: $files['preview_file'], fileType: 'file');
            $attributes['preview_file_storage_type'] = $storage;
        }

        if ($this->isPhysical($data) && $retail?->preview_file) {
            $this->deleteFile(filePath: self::PREVIEW_DIRECTORY.$retail->preview_file);
            $attributes['preview_file'] = null;
        }

        return $attributes;
    }

    private function findRetailDetail(mixed $productId): ?Model
    {
        return $this->query()->find($productId)?->retailDetail;
    }

    private function deleteRetailDetail(mixed $productId): bool
    {
        $retail = $this->findRetailDetail($productId);

        if ($retail?->preview_file) {
            $this->deleteFile(filePath: self::PREVIEW_DIRECTORY.$retail->preview_file);
        }

        return (bool) $this->query()->findOrFail($productId)->retailDetail()->delete();
    }

    private function writeRetailDetail(mixed $productId, array $attributes): Model
    {
        return $this->query()->findOrFail($productId)->retailDetail()->updateOrCreate(['product_id' => $productId], $attributes);
    }

    protected function storedVariations(?Model $product): array
    {
        return json_decode($product?->retailDetail?->variation ?: '[]', true) ?: [];
    }

    /** @return array{image_names: array, colored_image_names: array} */
    public function processImages(array $data, array $files): array
    {
        $storage = $this->storageDisk();
        $hasColors = $this->hasColors($data);

        $gallery = $this->applyColorUploads(gallery: [], data: $data, files: $files, acceptStoredNames: true);
        $gallery = $this->appendUploads($gallery, $files);

        foreach ($data['existing_images'] ?? [] as $image) {
            $gallery[] = ['image_name' => $image, 'storage' => $storage, 'color' => null];
        }

        $projected = $this->projectGallery($gallery, $hasColors);

        if (! $hasColors) {
            $projected['colored_image_names'] = array_map(
                static fn (string $image): array => ['color' => null, 'image_name' => $image, 'storage' => $storage],
                $data['existing_images'] ?? [],
            );
        }

        return $projected;
    }

    /** @return array{image_names: array, colored_image_names: array} */
    public function processUpdateImages(array $data, array $files, Model $product): array
    {
        $hasColors = $this->hasColors($data);

        $gallery = $this->rejectRemovedImages($this->getGalleryFromProduct($product), $data['removed_images'] ?? []);
        $gallery = $this->applyColorUploads(gallery: $gallery, data: $data, files: $files);
        $gallery = $this->untagRemovedColors($gallery, $this->postedColorCodes($data));
        $gallery = $this->appendUploads($gallery, $files);

        return $this->projectGallery($gallery, $hasColors);
    }

    /** @return array{image_names: array, colored_image_names: array} */
    public function processAdditionalImages(array $files, Model $product, array $removedImageNames = []): array
    {
        $stored = $this->rejectRemovedImages($this->getGalleryFromProduct($product), $removedImageNames);
        $emitColors = $this->storedColorImages($product) !== [] || ($stored !== [] && ! empty($files['images']));
        $gallery = $this->appendUploads($stored, $files);
        $colorSource = $this->isPhysicalProduct($product) ? $gallery : $stored;

        return [
            'image_names' => $this->projectGallery($gallery, false)['image_names'],
            'colored_image_names' => $emitColors
                ? $this->projectGallery($colorSource, true)['colored_image_names']
                : [],
        ];
    }

    private function isPhysicalProduct(Model $product): bool
    {
        return $product->product_type === self::PRODUCT_TYPE_PHYSICAL;
    }

    public function updateImages(mixed $id, array $files = [], ?array $images = null, array $removedImageNames = []): ?Model
    {
        $product = $this->findUnscoped($id);

        if ($product === null) {
            return null;
        }

        $images ??= $this->processAdditionalImages($files, $product, $removedImageNames);
        $discardedImageNames = $this->getDiscardedImageNames($product, $images);

        $attributes = ['images' => json_encode($images['image_names'])];

        if (isset($files['image'])) {
            $attributes['thumbnail'] = $this->updateFile(dir: self::THUMBNAIL_DIRECTORY, oldImage: $product->thumbnail, format: 'webp', image: $files['image'], fileType: 'image');
            $attributes['thumbnail_storage_type'] = $this->storageDisk();
        }

        $updatedProduct = DB::transaction(function () use ($product, $attributes, $images): Model {
            $product->update($attributes);

            $this->writeRetailDetail($product->id, ['color_image' => json_encode($images['colored_image_names'])]);

            cacheRemoveByType(type: 'products');

            return $product->refresh();
        });

        $this->deleteImageFiles($discardedImageNames);

        return $updatedProduct;
    }

    public function isRemovingEveryImage(Model $product, array $removedImageNames, int $uploadedImageCount): bool
    {
        $remainingImages = $this->rejectRemovedImages($this->storedGallery($product), $removedImageNames);

        return $remainingImages === [] && $uploadedImageCount === 0;
    }

    private function rejectRemovedImages(array $gallery, array $removedImageNames): array
    {
        return array_values(array_filter(
            $gallery,
            static fn (array $image): bool => ! in_array($image['image_name'], $removedImageNames, true),
        ));
    }

    private function getDiscardedImageNames(Model $product, array $images): array
    {
        $storedNames = array_column($this->getGalleryFromProduct($product), 'image_name');
        $keptNames = array_merge(
            array_column($images['image_names'], 'image_name'),
            array_column($images['colored_image_names'], 'image_name'),
        );

        return array_values(array_diff($storedNames, $keptNames));
    }

    private function deleteImageFiles(array $imageNames): void
    {
        foreach ($imageNames as $imageName) {
            $this->deleteFile(filePath: self::IMAGE_DIRECTORY.$imageName);
        }
    }

    public function deleteImage(mixed $id, string $imageName): bool
    {
        $product = $this->findUnscoped($id);

        if ($product === null) {
            return false;
        }

        $gallery = $this->getGalleryFromProduct($product);

        if (! collect($gallery)->contains('image_name', $imageName)) {
            return false;
        }

        $hasColors = (json_decode($product->getRawOriginal('colors'), true) ?: []) !== []
            && $this->storedColorImages($product) !== [];
        $gallery = $this->rejectImage($gallery, $imageName);
        $images = $this->projectGallery($gallery, $hasColors);

        $this->deleteFile(filePath: self::IMAGE_DIRECTORY.$imageName);

        return (bool) DB::transaction(function () use ($product, $images): bool {
            $product->update(['images' => json_encode($images['image_names'])]);

            $this->writeRetailDetail($product->id, ['color_image' => json_encode($images['colored_image_names'])]);

            cacheRemoveByType(type: 'products');

            return true;
        });
    }

    private function postedColorCodes(array $data): array
    {
        if (! $this->hasColors($data)) {
            return [];
        }

        return array_map(static fn (string $color): string => str_replace('#', '', $color), $data['colors']);
    }

    private function getGalleryFromProduct(Model $product): array
    {
        $storage = $this->storageDisk();
        $gallery = [];

        foreach ($this->storedGallery($product) as $image) {
            $gallery[$image['image_name']] = [
                'image_name' => $image['image_name'],
                'storage' => $image['storage'] ?? $storage,
                'color' => null,
            ];
        }

        foreach ($this->storedColorImages($product) as $image) {
            $gallery[$image['image_name']] = [
                'image_name' => $image['image_name'],
                'storage' => $image['storage'] ?? $storage,
                'color' => $image['color'] ?? null,
            ];
        }

        return array_values($gallery);
    }

    private function applyColorUploads(array $gallery, array $data, array $files, bool $acceptStoredNames = false): array
    {
        if (! $this->hasColors($data)) {
            return $gallery;
        }

        $storage = $this->storageDisk();

        foreach ($data['colors'] as $color) {
            $code = str_replace('#', '', $color);
            $uploaded = $files['color_image_'.$code] ?? null;
            $existing = $data['color_image_'.$code] ?? null;

            if ($uploaded instanceof UploadedFile) {
                $imageName = $this->upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $uploaded);
            } elseif ($acceptStoredNames && $existing) {
                $imageName = is_array($existing) ? $existing[0] : $existing;
            } else {
                continue;
            }

            $superseded = collect($gallery)->firstWhere('color', $code);

            if ($superseded !== null && $superseded['image_name'] !== $imageName) {
                $gallery = $this->rejectImage($gallery, $superseded['image_name']);
            }

            $gallery = $this->rejectImage($gallery, $imageName);
            $gallery[] = ['image_name' => $imageName, 'storage' => $storage, 'color' => $code];
        }

        return $gallery;
    }

    private function untagRemovedColors(array $gallery, array $colorCodes): array
    {
        return array_map(
            static function (array $image) use ($colorCodes): array {
                if ($image['color'] !== null && ! in_array($image['color'], $colorCodes, true)) {
                    $image['color'] = null;
                }

                return $image;
            },
            $gallery,
        );
    }

    private function appendUploads(array $gallery, array $files): array
    {
        $storage = $this->storageDisk();

        foreach ($files['images'] ?? [] as $file) {
            $gallery[] = [
                'image_name' => $this->upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $file),
                'storage' => $storage,
                'color' => null,
            ];
        }

        return $gallery;
    }

    /** @return array{image_names: array, colored_image_names: array} */
    private function projectGallery(array $gallery, bool $hasColors): array
    {
        $gallery = collect($gallery)->unique('image_name')->values();

        $imageNames = $gallery
            ->map(static fn (array $image): array => [
                'image_name' => $image['image_name'],
                'storage' => $image['storage'],
            ])
            ->all();

        if (! $hasColors) {
            return ['image_names' => $imageNames, 'colored_image_names' => []];
        }

        $coloredImageNames = $gallery
            ->sortByDesc(static fn (array $image): bool => $image['color'] !== null)
            ->values()
            ->all();

        return ['image_names' => $imageNames, 'colored_image_names' => $coloredImageNames];
    }

    private function storedColorImages(Model $product): array
    {
        return json_decode($product->retailDetail?->color_image ?: '[]', true) ?: [];
    }

    public function getColorWiseImageTiles(array $colors, ?Model $product = null, array $removedImageNames = []): array
    {
        $storedByColor = [];

        foreach ($product?->color_images_full_url ?? [] as $storedImage) {
            $isPendingRemoval = in_array($storedImage['image_name']['key'] ?? null, $removedImageNames, true);

            if (! empty($storedImage['color']) && ! $isPendingRemoval) {
                $storedByColor[$storedImage['color']] = (array) $storedImage['image_name'];
            }
        }

        $tiles = [];

        foreach ($colors as $color) {
            $key = str_replace('#', '', (string) $color);

            if ($key === '' || isset($tiles[$key])) {
                continue;
            }

            $tiles[$key] = [
                'key' => $key,
                'color_code' => '#'.$key,
                'input_name' => 'color_image_'.$key,
                'preview_id' => 'additional_Image_'.$key,
                'image_path' => $storedByColor[$key]['path'] ?? '',
                'stored_name' => $storedByColor[$key]['key'] ?? '',
            ];
        }

        return array_values($tiles);
    }

    public function getDigitalVariationOptions(array $data): array
    {
        $options = [];

        foreach ($data['extensions_type'] ?? [] as $type) {
            $options[$type] = array_map(
                static fn (string $option): string => str_replace('.', '_', removeSpecialCharacters($option)),
                explode(',', implode('|', $data['extensions_options_'.$type] ?? [])),
            );
        }

        return $options;
    }

    public function getDigitalVariationCombinations(array $data): array
    {
        $combinations = [];

        foreach ($this->getDigitalVariationOptions($data) as $type => $options) {
            $values = array_values(array_filter($options));

            if ($values !== []) {
                $combinations[$type] = $values;
            }
        }

        return $combinations;
    }

    public function generateDigitalVariationCombination(array $data, array $combinations, ?Model $product = null): array
    {
        $skuPrefix = $this->skuPrefix($this->baseLocaleValue($data, 'name'));
        $stored = collect($product?->digitalVariation ?? [])->keyBy('variant_key');

        $generated = [];

        foreach ($combinations as $type => $options) {
            foreach ($options as $option) {
                $variantKey = $type.'-'.preg_replace('/\s+/', '-', $option);
                $uniqueKey = strtolower(str_replace('-', '_', $variantKey));
                $existing = $stored->get($variantKey);

                $generated[] = [
                    'product_id' => $product?->id ?? '',
                    'unique_key' => $uniqueKey,
                    'variant_key' => $existing['variant_key'] ?? $variantKey,
                    'sku' => $existing['sku'] ?? $skuPrefix.'-'.$type.'-'.str_replace(' ', '', $option),
                    'price' => $existing['price'] ?? currencyConverter(amount: $data['unit_price']),
                    'file' => $existing['file'] ?? '',
                ];
            }
        }

        return $generated;
    }

    private function getDigitalVariationFiles(array $data, array $files, Model $product): array
    {
        $digitalFiles = [];

        foreach ($this->getDigitalVariationCombinations($data) as $type => $options) {
            foreach ($options as $option) {
                $uniqueKey = strtolower(str_replace('-', '_', $type.'-'.str_replace(' ', '', $option)));
                $file = $files['digital_files'][$uniqueKey] ?? null;

                $digitalFiles[] = [
                    'product_id' => $product->id,
                    'variant_key' => $data['digital_product_variant_key'][$uniqueKey] ?? null,
                    'sku' => $data['digital_product_sku'][$uniqueKey] ?? null,
                    'price' => currencyConverter(amount: $data['digital_product_price'][$uniqueKey] ?? 0),
                    'file' => $file instanceof UploadedFile
                        ? $this->fileUpload(dir: self::DIGITAL_DIRECTORY, format: $file->getClientOriginalExtension(), file: $file)
                        : (string) ($data['existing_digital_files'][$uniqueKey] ?? ''),
                ];
            }
        }

        return $digitalFiles;
    }

    private function syncDigitalRelations(Model $product, array $data, array $authorIds, array $publishingHouseIds): void
    {
        if (($data['product_type'] ?? null) !== self::PRODUCT_TYPE_DIGITAL || $authorIds === []) {
            $product->digitalProductAuthors()->delete();
        } else {
            foreach ($authorIds as $authorId) {
                $product->digitalProductAuthors()->updateOrCreate(['author_id' => $authorId], ['author_id' => $authorId]);
            }

            $product->digitalProductAuthors()->whereNotIn('author_id', $authorIds)->delete();
        }

        if (($data['product_type'] ?? null) !== self::PRODUCT_TYPE_DIGITAL || $publishingHouseIds === []) {
            $product->digitalProductPublishingHouse()->delete();

            return;
        }

        foreach ($publishingHouseIds as $houseId) {
            $product->digitalProductPublishingHouse()->updateOrCreate(['publishing_house_id' => $houseId], ['publishing_house_id' => $houseId]);
        }

        $product->digitalProductPublishingHouse()->whereNotIn('publishing_house_id', $publishingHouseIds)->delete();
    }

    private function syncDigitalVariations(Model $product, array $data, array $files): void
    {
        $readyFile = $files['digital_file_ready'] ?? null;

        if (! array_key_exists('digital_product_variant_key', $data) || ($readyFile instanceof UploadedFile && $readyFile->getPath() !== '')) {
            $product->digitalVariation()->delete();

            return;
        }

        $storedVariations = $product->digitalVariation()->get();
        $storedVariationKeys = $storedVariations->pluck('variant_key')->toArray();
        $requestedVariationKeys = $data['digital_product_variant_key'] ?? [];
        $missingFromRequest = array_diff($storedVariationKeys, $requestedVariationKeys);
        $missingFromStore = array_diff($requestedVariationKeys, $storedVariationKeys);
        $newCombinations = array_merge($missingFromRequest, $missingFromStore);

        foreach ($newCombinations as $newCombination) {
            if (in_array($newCombination, $requestedVariationKeys)) {
                $uniqueKey = strtolower(str_replace('-', '_', $newCombination));

                $fileItem = ($data['digital_product_type'] ?? null) === 'ready_product'
                    ? ($files['digital_files'][$uniqueKey] ?? null)
                    : null;

                $uploadedFile = $fileItem instanceof UploadedFile
                    ? $this->fileUpload(dir: self::DIGITAL_DIRECTORY, format: $fileItem->getClientOriginalExtension(), file: $fileItem)
                    : '';

                $product->digitalVariation()->create([
                    'variant_key' => $data['digital_product_variant_key'][$uniqueKey] ?? null,
                    'sku' => $data['digital_product_sku'][$uniqueKey] ?? null,
                    'price' => currencyConverter(amount: $data['digital_product_price'][$uniqueKey] ?? null),
                    'file' => $uploadedFile,
                ]);
            }
        }

        foreach ($missingFromRequest as $variantKey) {
            $variation = $product->digitalVariation()->where('variant_key', $variantKey)->first();

            if ($variation) {
                $variation->delete();
            }
        }

        foreach ($storedVariations as $variation) {
            $storedVariantKey = $variation['variant_key'];

            if (in_array($storedVariantKey, $requestedVariationKeys)) {
                $uniqueKey = strtolower(str_replace('-', '_', $storedVariantKey));

                $fileItem = ($data['digital_product_type'] ?? null) === 'ready_product'
                    ? ($files['digital_files'][$uniqueKey] ?? null)
                    : null;

                $uploadedFile = $variation['file'] ?? '';

                if ($fileItem instanceof UploadedFile) {
                    $uploadedFile = $this->fileUpload(dir: self::DIGITAL_DIRECTORY, format: $fileItem->getClientOriginalExtension(), file: $fileItem);
                }

                $product->digitalVariation()->where('variant_key', $storedVariantKey)->update([
                    'variant_key' => $data['digital_product_variant_key'][$uniqueKey] ?? null,
                    'sku' => $data['digital_product_sku'][$uniqueKey] ?? null,
                    'price' => currencyConverter(amount: $data['digital_product_price'][$uniqueKey] ?? null),
                    'file' => $uploadedFile,
                ]);
            }

            if (($data['product_type'] ?? null) === self::PRODUCT_TYPE_PHYSICAL || ($data['digital_product_type'] ?? null) === 'ready_after_sell') {
                $variation = $product->digitalVariation()->where('variant_key', $storedVariantKey)->first();

                if ($variation && $variation['file']) {
                    $product->digitalVariation()->where('id', $variation['id'])->update(['file' => '']);
                }

                if (($data['product_type'] ?? null) === self::PRODUCT_TYPE_PHYSICAL) {
                    $variation->delete();
                }
            }
        }
    }

    /** @return array{ids: array, names: array, data: array} */
    public function getAuthorsInfo(object|array $product): array
    {
        $authors = collect($product?->digitalProductAuthors ?? []);

        return [
            'ids' => $authors->pluck('author_id')->all(),
            'names' => $authors->pluck('author.name')->filter()->values()->all(),
            'data' => $authors->pluck('author')->all(),
        ];
    }

    /** @return array{ids: array, names: array, data: array} */
    public function getPublishingHouseInfo(object|array $product): array
    {
        $houses = collect($product?->digitalProductPublishingHouse ?? []);

        return [
            'ids' => $houses->pluck('publishing_house_id')->all(),
            'names' => $houses->pluck('publishingHouse.name')->filter()->values()->all(),
            'data' => $houses->pluck('publishingHouse')->all(),
        ];
    }

    /** @return array{status: bool, reason: ?string, field: ?string, products: array, productsTax: array, productsRetail: array} */
    public function getBulkImportData(UploadedFile $file, string $addedBy, ?int $userId, int|string $shopId): array
    {
        try {
            $rows = (new FastExcel)->import($file);
        } catch (\Exception) {
            return $this->bulkImportFailure(self::REASON_INVALID_FILE);
        }

        if (count($rows) <= 0) {
            return $this->bulkImportFailure(self::REASON_EMPTY_FILE);
        }

        $products = [];
        $productsTax = [];
        $productsRetail = [];

        foreach ($rows as $row) {
            foreach ($row as $column => $value) {
                if ($column === '') {
                    continue;
                }

                if (! in_array($column, self::IMPORT_COLUMNS, true)) {
                    return $this->bulkImportFailure(self::REASON_UNKNOWN_COLUMN, $column);
                }
            }

            $row = $this->normalizeImportRow($row);

            foreach (self::IMPORT_COLUMNS as $column) {
                if ($row[$column] === null && ! in_array($column, self::IMPORT_OPTIONAL_COLUMNS, true)) {
                    return $this->bulkImportFailure(self::REASON_MISSING_VALUE, $column);
                }
            }

            $code = $this->getUniqueCode();

            $productsTax[$code] = $row['tax_ids'];

            $products[] = [
                'name' => $row['name'],
                'shop_id' => $shopId,
                'slug' => Str::slug($row['name'], '-').'-'.Str::random(6),
                'category_ids' => json_encode($this->getCategoriesArray($row)),
                'category_id' => $row['category_id'],
                'sub_category_id' => $row['sub_category_id'],
                'sub_sub_category_id' => $row['sub_sub_category_id'],
                'brand_id' => $row['brand_id'],
                'unit' => $row['unit'],
                'minimum_order_qty' => $row['minimum_order_qty'],
                'unit_price' => currencyConverter(amount: $row['unit_price']),
                ...$this->buildDiscountAttributes($row),
                'shipping_cost' => 0,
                'current_stock' => $row['current_stock'],
                'details' => $row['details'],
                'video_provider' => 'youtube',
                'video_url' => $row['youtube_video_url'],
                'images' => json_encode(['def.png']),
                'thumbnail' => $this->importedThumbnailPath($row['thumbnail']),
                'thumbnail_storage_type' => $this->storageDisk(),
                'status' => $addedBy === self::ADDED_BY_ADMIN && $row['status'] == 1 ? 1 : 0,
                'request_status' => $this->resolveRequestStatus($addedBy),
                'colors' => json_encode([]),
                'attributes' => json_encode([]),
                'choice_options' => json_encode([]),
                'featured_status' => 0,
                'added_by' => $addedBy,
                'user_id' => $userId,
                'code' => $code,
                'created_at' => now(),
            ];

            $productsRetail[$code] = [
                'variation' => json_encode([]),
                'color_image' => json_encode([]),
                'purchase_price' => 0,
            ];
        }

        $importedThumbnails = [];

        foreach ($products as $index => $product) {
            $importedThumbnails[$product['thumbnail']] ??= $this->importThumbnail(path: $product['thumbnail']);
            $products[$index]['thumbnail'] = $importedThumbnails[$product['thumbnail']];
        }

        return [
            'status' => true,
            'reason' => null,
            'field' => null,
            'products' => $products,
            'productsTax' => $productsTax,
            'productsRetail' => $productsRetail,
        ];
    }

    private function importedThumbnailPath(mixed $thumbnail): string
    {
        $value = trim((string) $thumbnail);
        $path = parse_url($value, PHP_URL_PATH);

        if (! is_string($path)) {
            return '';
        }

        $path = trim(str_replace('\\', '/', rawurldecode($path)), '/');

        if (parse_url($value, PHP_URL_HOST) !== null) {
            $path = preg_replace('#^(.*/)?storage/(app/public/)?#', '', $path);
        }

        return in_array('..', explode('/', $path), true) ? basename($path) : $path;
    }

    private function importThumbnail(string $path): string
    {
        if ($path === '') {
            return 'def.png';
        }

        $sourcePath = $this->locateImportedThumbnail(path: $path);

        if ($sourcePath === null) {
            return basename($path);
        }

        if (dirname($sourcePath).'/' === self::THUMBNAIL_DIRECTORY) {
            return basename($sourcePath);
        }

        $disk = $this->storageDisk();
        $thumbnailName = Carbon::now()->toDateString().'-'.uniqid().'.'.strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        if (! Storage::disk($disk)->copy($sourcePath, self::THUMBNAIL_DIRECTORY.$thumbnailName)) {
            return basename($path);
        }

        if ($disk === 'public') {
            FileExistenceCache::remember(disk: $disk, path: self::THUMBNAIL_DIRECTORY.$thumbnailName);
        }

        cacheRemoveByType(type: 'file_manager');

        return $thumbnailName;
    }

    private function locateImportedThumbnail(string $path): ?string
    {
        if (! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::IMPORT_THUMBNAIL_EXTENSIONS, true)) {
            return null;
        }

        $disk = $this->storageDisk();
        $rootDirectories = once(fn (): array => Storage::disk($disk)->directories());

        $candidates = [
            $path,
            ...array_map(fn (string $directory): string => $directory.'/'.$path, $rootDirectories),
            self::THUMBNAIL_DIRECTORY.basename($path),
        ];

        foreach (array_unique($candidates) as $candidate) {
            if (Storage::disk($disk)->fileExists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeImportRow(array $row): array
    {
        $normalized = [];

        foreach (self::IMPORT_COLUMNS as $column) {
            $value = $row[$column] ?? null;
            $normalized[$column] = $value === '' ? null : $value;
        }

        return $normalized;
    }

    private function bulkImportFailure(string $reason, ?string $field = null): array
    {
        return [
            'status' => false,
            'reason' => $reason,
            'field' => $field,
            'products' => [],
            'productsTax' => [],
            'productsRetail' => [],
        ];
    }

    private function resolveDigitalFile(array $data, array $files, ?string $storedFile): ?string
    {
        if (isset($data['extensions_type'], $data['digital_product_variant_key'])) {
            return null;
        }

        if ($this->isPhysical($data)) {
            return null;
        }

        if (($data['digital_product_type'] ?? null) === 'ready_product' && isset($files['digital_file_ready'])) {
            return $this->updateFile(dir: self::DIGITAL_DIRECTORY, oldImage: $storedFile, format: $files['digital_file_ready']->getClientOriginalExtension(), image: $files['digital_file_ready'], fileType: 'file');
        }

        if (($data['digital_product_type'] ?? null) === 'ready_after_sell') {
            return null;
        }

        return $storedFile;
    }

    private function uploadDigitalFile(array $data, array $files): string
    {
        if ($this->isPhysical($data) || ($data['digital_product_type'] ?? null) !== 'ready_product') {
            return '';
        }

        $file = $files['digital_file_ready'] ?? null;

        if ($file instanceof UploadedFile) {
            return $this->fileUpload(dir: self::DIGITAL_DIRECTORY, format: $file->getClientOriginalExtension(), file: $file);
        }

        return (string) ($data['existing_digital_file_ready'] ?? '');
    }

    private function uploadPreviewFile(array $data, array $files): string
    {
        if ($this->isPhysical($data)) {
            return '';
        }

        $file = $files['preview_file'] ?? null;

        if ($file instanceof UploadedFile) {
            return $this->fileUpload(dir: self::PREVIEW_DIRECTORY, format: $file->getClientOriginalExtension(), file: $file);
        }

        return (string) ($data['existing_preview_file'] ?? '');
    }

    /**
     * @return array<int, Model>
     */
    public function searchForStorefront(
        string $keyword,
        int|string $categoryId = self::CATEGORY_ALL,
        array $authorProductIds = [],
        array $publishingHouseProductIds = [],
        int $limit = 10,
        int $page = 1,
    ): array {
        $matched = $this->getStorefrontNameMatches(
            keyword: $keyword,
            categoryId: $categoryId,
            authorProductIds: $authorProductIds,
            publishingHouseProductIds: $publishingHouseProductIds,
            limit: $limit,
            page: $page,
        );

        return $matched === []
            ? $this->getStorefrontTranslatedMatches(keyword: $keyword, categoryId: $categoryId, limit: $limit, page: $page)
            : $matched;
    }

    public function getShopMatchedProducts(array $productIds, string $keyword): Collection
    {
        return $this->query()
            ->active()
            ->withCount('reviews')
            ->whereIn('id', $productIds)
            ->orderByRaw('LOCATE(?, name), name', [$keyword])
            ->get();
    }

    public function getRelatedByCategoryIds(mixed $categoryIds, mixed $excludeId, int $limit): Collection
    {
        return $this->query()
            ->with(['reviews'])
            ->withCount('reviews')
            ->where('category_ids', $categoryIds)
            ->whereKeyNot($excludeId)
            ->limit($limit)
            ->get();
    }

    public function getFirstVariationQuantity(Model $product): int
    {
        if ($product->product_type !== self::PRODUCT_TYPE_PHYSICAL) {
            return self::DIGITAL_STOCK_DISPLAY;
        }

        $variations = $this->storedVariations($product);

        return (int) ($variations === [] ? $product->current_stock : ($variations[0]['qty'] ?? $product->current_stock));
    }

    public function getInitialVariant(Model $product, Collection $cartItems, array $colorNames): ?string
    {
        return $this->resolveInitialVariant($product, $cartItems, $colorNames)['variant'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getInitialConfig(
        Model $product,
        Collection $cartItems,
        array $colorNames,
        int $restockRequestStatus = 0,
    ): array {
        $initialVariant = $this->resolveInitialVariant($product, $cartItems, $colorNames);
        $variant = $initialVariant['variant'];
        $firstVariantInCart = $initialVariant['in_cart'];

        $price = $product['unit_price'];
        $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $product['unit_price']);

        if ($initialVariant['priced_from'] !== null) {
            $discount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $initialVariant['priced_from']);
            $price = $initialVariant['priced_from'] - $discount;
        }

        $cartRow = $cartItems->where('product_id', $product['id'])->where('variant', $variant)->first();
        $quantity = $cartRow['quantity'] ?? $product['minimum_order_qty'];
        $discountType = getProductPriceByType(product: $product, type: 'discount_type', result: 'string');

        return [
            'first_variant_in_cart' => $firstVariantInCart,
            'quantity' => $quantity,
            'price' => $price,
            'discount' => $discountType == self::DISCOUNT_TYPE_FLAT
                ? webCurrencyConverter($discount)
                : getProductPriceByType(product: $product, type: 'discount', result: 'value').'%',
            'discount_type' => $discountType,
            'total_quantity_price' => $price * $quantity,
            'variant' => $variant,
            'restock_request_status' => $restockRequestStatus,
        ];
    }

    /**
     * @return array{variant: ?string, in_cart: bool, priced_from: mixed}
     */
    private function resolveInitialVariant(Model $product, Collection $cartItems, array $colorNames): array
    {
        if ($product['product_type'] == self::PRODUCT_TYPE_DIGITAL) {
            $digitalVariation = $product['digitalVariation']?->first();

            if ($digitalVariation === null) {
                return ['variant' => '', 'in_cart' => false, 'priced_from' => null];
            }

            $variant = '';
            $inCart = false;

            foreach ($cartItems as $cartItem) {
                if ($cartItem['product_id'] == $product['id'] && $cartItem['variant'] == $digitalVariation['variant_key']) {
                    $variant = $cartItem['variant'];
                    $inCart = true;
                }
            }

            return ['variant' => $variant, 'in_cart' => $inCart, 'priced_from' => $digitalVariation['price']];
        }

        $variant = $this->getPossibleVariants($product, $colorNames)[0] ?? '';
        $storedVariant = collect($this->storedVariations($product))->where('type', $variant)->first();

        return [
            'variant' => $variant,
            'in_cart' => $storedVariant !== null,
            'priced_from' => $storedVariant === null ? null : $storedVariant['price'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getPossibleVariants(Model $product, array $colorNames): array
    {
        $combinations = $this->getCombinations(array_map(
            static fn ($choice): array => (array) ($choice['options'] ?? []),
            json_decode($product['choice_options'], true) ?? [],
        ));

        $variants = [];

        foreach (json_decode($product['colors'], true) ?? [] as $color) {
            foreach ($combinations as $combination) {
                $variants[] = ($colorNames[$color] ?? null).'-'.implode('-', $combination);
            }
        }

        return $variants;
    }

    public function getShopProductList(array $requestData, ?int $sellerId, ?string $addedBy): mixed
    {
        return ProductManager::getProductListData(new HttpRequest($requestData), $sellerId, $addedBy);
    }

    /**
     * @return array<string, mixed>
     */
    public function getStorefrontFilters(array $input): array
    {
        $filters = [];

        foreach (self::STOREFRONT_FILTER_KEYS as $key) {
            $filters[$key] = $input[$key] ?? null;
        }

        if (! array_key_exists(self::FILTER_CATEGORY_IDS, $input)) {
            $filters[self::FILTER_SEARCH_CATEGORY_VALUE] = null;

            return $filters;
        }

        $filters[self::FILTER_CATEGORY_IDS] = array_filter(
            (array) $input[self::FILTER_CATEGORY_IDS],
            static fn ($value): bool => ! is_null($value) && $value != self::CATEGORY_ALL,
        );

        return $filters;
    }

    public function getStorefrontSearchKeyword(array $filters): ?string
    {
        $search = $filters[self::FILTER_SEARCH] ?? null;
        $name = $filters[self::FILTER_NAME] ?? null;
        $productName = $filters[self::FILTER_PRODUCT_NAME] ?? null;
        $dataFrom = $filters[self::FILTER_DATA_FROM] ?? null;

        if (! (($dataFrom === self::LIST_SEARCH && ! empty($search)) || ! empty($name) || ! empty($productName))) {
            return null;
        }

        return (string) ($search ?: ($productName ?? $name));
    }

    public function getStorefrontList(
        array $filters = [],
        ?string $sortBy = null,
        ?int $sellerId = null,
        ?string $addedBy = null,
        ?string $currencyCode = null,
        ?string $type = null,
    ): Collection {
        $query = $this->buildStorefrontListQuery($filters, $sellerId, $addedBy, $currencyCode, $type);
        $products = $this->getStorefrontPriorityProducts($query, $filters, $sellerId, $addedBy);
        $products = $this->applyStorefrontSort(
            $products,
            $this->keepsRankedOrder($filters[self::FILTER_DATA_FROM] ?? null, $sortBy) ? null : $sortBy,
            $filters[self::FILTER_OFFER_TYPE] ?? null,
        );

        return $this->applyStorefrontRatingFilter($products, $filters[self::FILTER_RATING] ?? null);
    }

    /**
     * @return array<string, int>
     */
    public function getRatingCounts(Collection $products): array
    {
        $counts = ['rating_1' => 0, 'rating_2' => 0, 'rating_3' => 0, 'rating_4' => 0, 'rating_5' => 0];

        foreach ($products as $product) {
            if (! isset($product->rating[0]['average'])) {
                continue;
            }

            $average = $product->rating[0]['average'];

            $bucket = match (true) {
                $average > 0 && $average < 2 => 1,
                $average >= 2 && $average < 3 => 2,
                $average >= 3 && $average < 4 => 3,
                $average >= 4 && $average < 5 => 4,
                $average == 5 => 5,
                default => null,
            };

            if ($bucket !== null) {
                $counts['rating_'.$bucket]++;
            }
        }

        return $counts;
    }

    private function buildStorefrontListQuery(
        array $filters,
        ?int $sellerId,
        ?string $addedBy,
        ?string $currencyCode,
        ?string $type,
    ): Builder {
        $dataFrom = $filters[self::FILTER_DATA_FROM] ?? null;
        $offerType = $filters[self::FILTER_OFFER_TYPE] ?? null;
        $productType = $filters[self::FILTER_PRODUCT_TYPE] ?? null;
        $clearanceProductIds = $filters[self::FILTER_CLEARANCE_PRODUCT_IDS] ?? [];

        $query = $this->query()
            ->active()
            ->with([
                'category',
                'reviews' => fn ($relation) => $relation->active(),
                'rating',
                'seller.shop',
                'clearanceSale' => fn ($relation) => $relation->active()->with(['setup']),
            ])
            ->withAvg('reviews', 'rating')
            ->when($addedBy === self::ADDED_BY_ADMIN, fn (Builder $builder) => $builder->where(['added_by' => $addedBy]))
            ->when($sellerId && $addedBy === self::ADDED_BY_SELLER, fn (Builder $builder) => $builder->where(['added_by' => $addedBy, 'user_id' => $sellerId]))
            ->when(in_array($productType, [self::PRODUCT_TYPE_PHYSICAL, self::PRODUCT_TYPE_DIGITAL], true), fn (Builder $builder) => $builder->where(['product_type' => $productType]))
            ->withCount(['reviews' => fn ($relation) => $relation->active()])
            ->withSum(
                ['orderDetails as order_details_sum_qty' => fn (Builder $relation) => $relation->where('delivery_status', self::DELIVERY_STATUS_DELIVERED)],
                'qty',
            );

        if ($type === self::OFFER_FLASH_DEALS && ! empty($filters[self::FILTER_FLASH_DEALS_ID])) {
            $query->whereIn('id', $filters[self::FILTER_FLASH_DEAL_PRODUCT_IDS] ?? []);
        }

        if ($dataFrom === self::LIST_BRAND && ! empty($filters[self::FILTER_BRAND_ID])) {
            $query->where('brand_id', $filters[self::FILTER_BRAND_ID]);
        }

        if (! empty($filters[self::FILTER_BRAND_IDS]) && is_array($filters[self::FILTER_BRAND_IDS])) {
            $query->whereIn('brand_id', $filters[self::FILTER_BRAND_IDS]);
        }

        $this->applyStorefrontSearchCategoryMatch($query, $filters[self::FILTER_SEARCH_CATEGORY_VALUE] ?? null);

        if ($offerType === self::OFFER_CLEARANCE_SALE) {
            $query->whereIn('id', $clearanceProductIds);
        }

        if ($offerType === self::OFFER_CLEARANCE_SALE && $sellerId && $addedBy === self::ADDED_BY_SELLER) {
            $query->where(['added_by' => $addedBy, 'user_id' => $sellerId])->whereIn('id', $clearanceProductIds);
        }

        if ($this->hasCategoryTreeFilter($filters)) {
            $query->whereIn('id', $this->getCategoryTreeProductIds($filters, $sellerId, $addedBy));
        }

        $this->applyStorefrontPublisherFilters($query, $filters);
        $this->applyStorefrontAuthorFilters($query, $filters);

        if ($offerType === self::OFFER_DISCOUNTED) {
            $query->where(fn (Builder $outer) => $outer
                ->where(fn (Builder $inner) => $inner->where('discount', '!=', 0))
                ->orWhere(fn (Builder $inner) => $inner->whereIn('id', $clearanceProductIds)));
        }

        if ($dataFrom === self::LIST_MOST_FAVORITE) {
            $query->whereHas('wishList');
        }

        if ($dataFrom == self::LIST_LATEST || $dataFrom == '') {
            $query->orderBy('id', 'desc');
        }

        if (! empty($filters[self::FILTER_COLOR_IDS]) && is_array($filters[self::FILTER_COLOR_IDS])) {
            $query->where(function (Builder $inner) use ($filters): void {
                foreach ($filters[self::FILTER_COLOR_IDS] as $color) {
                    $inner->orWhere('colors', 'like', '%'.$color.'%');
                }
            });
        }

        if ($dataFrom === self::LIST_TOP_RATED) {
            $query->whereHas('reviews');
        }

        if ($dataFrom === self::LIST_BEST_SELLING) {
            $query->whereHas('orderDetails');
        }

        if ($offerType === self::OFFER_FEATURED_DEAL) {
            $query->whereIn('id', $filters[self::FILTER_FEATURED_DEAL_PRODUCT_IDS] ?? []);
        }

        $this->applyStorefrontKeywordOrder($query, $filters);
        $this->applyStorefrontKeywordMatch($query, $filters, $dataFrom);
        $this->applyStorefrontPriceRange($query, $filters, $currencyCode);

        if (($filters[self::FILTER_RATINGS] ?? null) != null) {
            $query->whereHas('rating');
        }

        return $query;
    }

    private function applyStorefrontSearchCategoryMatch(Builder $query, mixed $searchCategoryValue): void
    {
        if (empty($searchCategoryValue) || $searchCategoryValue === self::CATEGORY_ALL) {
            return;
        }

        $query->where(fn (Builder $outer) => $outer
            ->where('category_id', $searchCategoryValue)
            ->orWhere('sub_category_id', $searchCategoryValue)
            ->orWhere('sub_sub_category_id', $searchCategoryValue));
    }

    public function hasCategoryTreeFilter(array $filters): bool
    {
        $dataFrom = $filters[self::FILTER_DATA_FROM] ?? null;
        $hasSingleCategory = ($filters[self::FILTER_CATEGORY_ID] ?? null)
            || ($filters[self::FILTER_SUB_CATEGORY_ID] ?? null)
            || ($filters[self::FILTER_SUB_SUB_CATEGORY_ID] ?? null);

        return ($dataFrom === self::LIST_CATEGORY && $hasSingleCategory)
            || (! empty($filters[self::FILTER_CATEGORY_IDS]) && is_array($filters[self::FILTER_CATEGORY_IDS]));
    }

    /**
     * @return array<int, int>
     */
    private function getCategoryTreeProductIds(array $filters, ?int $sellerId, ?string $addedBy): array
    {
        $columns = [
            'category_id' => $filters[self::FILTER_TREE_CATEGORY_IDS] ?? [],
            'sub_category_id' => $filters[self::FILTER_TREE_SUB_CATEGORY_IDS] ?? [],
            'sub_sub_category_id' => $filters[self::FILTER_TREE_SUB_SUB_CATEGORY_IDS] ?? [],
        ];

        $productIds = [];

        foreach ($columns as $column => $categoryIds) {
            if ($categoryIds === []) {
                continue;
            }

            $productIds = array_merge($productIds, $this->query()
                ->whereIn($column, $categoryIds)
                ->when($addedBy === self::ADDED_BY_ADMIN, fn (Builder $builder) => $builder->where(['added_by' => $addedBy]))
                ->when($sellerId && $addedBy === self::ADDED_BY_SELLER, fn (Builder $builder) => $builder->where(['added_by' => $addedBy, 'user_id' => $sellerId]))
                ->when(in_array($filters[self::FILTER_PRODUCT_TYPE] ?? null, [self::PRODUCT_TYPE_PHYSICAL, self::PRODUCT_TYPE_DIGITAL], true), fn (Builder $builder) => $builder->where(['product_type' => $filters[self::FILTER_PRODUCT_TYPE]]))
                ->pluck('id')
                ->toArray());
        }

        return $productIds;
    }

    private function applyStorefrontPublisherFilters(Builder $query, array $filters): void
    {
        $publishingHouseId = $filters[self::FILTER_PUBLISHING_HOUSE_ID] ?? null;

        if ($publishingHouseId != '' && $publishingHouseId != 0) {
            $query->whereHas(
                'digitalProductPublishingHouse',
                fn (Builder $inner) => $inner->where('publishing_house_id', $publishingHouseId),
            );
        }

        if ($publishingHouseId != '' && $publishingHouseId == 0) {
            $query->where(['product_type' => self::PRODUCT_TYPE_DIGITAL])
                ->whereNotIn('id', $filters[self::FILTER_ATTACHED_PUBLISHER_PRODUCT_IDS] ?? []);
        }

        $publishingHouseIds = $filters[self::FILTER_PUBLISHING_HOUSE_IDS] ?? null;

        if (empty($publishingHouseIds)) {
            return;
        }

        $productIds = $filters[self::FILTER_SELECTED_PUBLISHER_PRODUCT_IDS] ?? [];

        if (in_array(0, $publishingHouseIds)) {
            $productIds = array_merge($productIds, $this->getUnattachedDigitalIds($filters[self::FILTER_ATTACHED_PUBLISHER_PRODUCT_IDS] ?? []));
        }

        $query->where(['product_type' => self::PRODUCT_TYPE_DIGITAL])->whereIn('id', $productIds);
    }

    private function applyStorefrontAuthorFilters(Builder $query, array $filters): void
    {
        $authorId = $filters[self::FILTER_AUTHOR_ID] ?? null;

        if ($authorId != '' && $authorId != 0) {
            $query->whereHas(
                'digitalProductAuthors',
                fn (Builder $inner) => $inner->where('author_id', $authorId),
            );
        }

        if ($authorId != '' && $authorId == 0) {
            $query->where(['product_type' => self::PRODUCT_TYPE_DIGITAL])
                ->whereNotIn('id', $filters[self::FILTER_ATTACHED_AUTHOR_PRODUCT_IDS] ?? []);
        }

        $authorIds = $filters[self::FILTER_AUTHOR_IDS] ?? null;

        if (empty($authorIds) || ! is_array($authorIds)) {
            return;
        }

        $productIds = $filters[self::FILTER_SELECTED_AUTHOR_PRODUCT_IDS] ?? [];

        if (in_array(0, $authorIds)) {
            $productIds = array_merge($productIds, $this->getUnattachedDigitalIds($filters[self::FILTER_ATTACHED_AUTHOR_PRODUCT_IDS] ?? []));
        }

        $query->where(['product_type' => self::PRODUCT_TYPE_DIGITAL])->whereIn('id', $productIds);
    }

    /**
     * @return array<int, int>
     */
    private function getUnattachedDigitalIds(array $attachedProductIds): array
    {
        return $this->query()
            ->active()
            ->where(['product_type' => self::PRODUCT_TYPE_DIGITAL])
            ->whereNotIn('id', $attachedProductIds)
            ->pluck('id')
            ->toArray();
    }

    private function applyStorefrontKeywordOrder(Builder $query, array $filters): void
    {
        if (empty($filters[self::FILTER_NAME])) {
            return;
        }

        $this->applyKeywordRelevanceOrder($query, (string) $filters[self::FILTER_NAME]);
    }

    private function applyStorefrontKeywordMatch(Builder $query, array $filters, ?string $dataFrom): void
    {
        $search = $filters[self::FILTER_SEARCH] ?? null;
        $name = $filters[self::FILTER_NAME] ?? null;
        $productName = $filters[self::FILTER_PRODUCT_NAME] ?? null;

        if (! (($dataFrom === self::LIST_SEARCH && ! empty($search)) || ! empty($name) || ! empty($productName))) {
            return;
        }

        $keyword = (string) ($search ?: ($productName ?? $name));
        $matchedIds = $this->getListSearchMatchIds($keyword, $filters);

        $query->whereIn('id', $matchedIds === [] ? [0] : $matchedIds);

        $this->applyKeywordRelevanceOrder($query, $keyword);
    }

    private function applyKeywordRelevanceOrder(Builder $query, string $keyword): void
    {
        $searchKeyword = $this->normalizeSearchKeyword($keyword);

        $query->orderByRaw(
            'CASE WHEN name LIKE ? THEN 1 ELSE 2 END, LOCATE(?, name), name',
            ["%{$searchKeyword}%", $searchKeyword],
        );
    }

    private function applyStorefrontPriceRange(Builder $query, array $filters, ?string $currencyCode): void
    {
        $minPrice = $filters[self::FILTER_MIN_PRICE] ?? null;
        $maxPrice = $filters[self::FILTER_MAX_PRICE] ?? null;

        if ($minPrice != null && $minPrice > 0) {
            $query->where('unit_price', '>=', Convert::usdPaymentModule($minPrice, $currencyCode));
        }

        if ($maxPrice != null) {
            $query->where('unit_price', '<=', Convert::usdPaymentModule($maxPrice, $currencyCode));
        }
    }

    /**
     * @return array<int, int>
     */
    private function getListSearchMatchIds(string $keyword, array $filters): array
    {
        $terms = explode(' ', $keyword);
        $authorProductIds = $filters[self::FILTER_KEYWORD_AUTHOR_PRODUCT_IDS] ?? [];
        $publisherProductIds = $filters[self::FILTER_KEYWORD_PUBLISHER_PRODUCT_IDS] ?? [];

        $query = $this->query()
            ->active()
            ->where(function (Builder $inner) use ($keyword, $terms): void {
                $this->applyNameSearch($inner, $keyword, $terms);

                $inner->orWhereHas('tags', function (Builder $tag) use ($terms): void {
                    $tag->where(function (Builder $group) use ($terms): void {
                        foreach ($terms as $term) {
                            $group->where('tag', 'like', "%{$term}%");
                        }
                    });
                });
            })
            ->when($authorProductIds !== [], fn (Builder $builder) => $builder->whereIn('id', $authorProductIds))
            ->when($publisherProductIds !== [], fn (Builder $builder) => $builder->whereIn('id', $publisherProductIds));

        $matched = $this->sortBySearchPriority($query, $keyword)->all();

        if ($matched !== [] && getDefaultLanguage() === self::BASE_LANGUAGE) {
            return $this->pluckIds($matched);
        }

        return array_merge($this->pluckIds($matched), $this->pluckIds($this->getListTranslatedMatches($keyword)));
    }

    /**
     * @return array<int, Model>
     */
    private function getListTranslatedMatches(string $keyword): array
    {
        $query = $this->query()->with(['tags'])->where(fn (Builder $inner) => $inner
            ->whereIn('id', $this->translatedIds($keyword))
            ->orWhereHas('tags', fn (Builder $tag) => $tag->where('tag', 'like', "%{$keyword}%")));

        return $this->sortBySearchPriority($query, $keyword, translated: true)->all();
    }

    /**
     * @return array<int, int>
     */
    private function pluckIds(array $products): array
    {
        return array_map(static fn (Model $product): int => $product->id, $products);
    }

    private function getStorefrontPriorityProducts(Builder $query, array $filters, ?int $sellerId, ?string $addedBy): Collection
    {
        $dataFrom = $filters[self::FILTER_DATA_FROM] ?? null;
        $offerType = $filters[self::FILTER_OFFER_TYPE] ?? null;

        if ($dataFrom === self::LIST_CATEGORY) {
            $this->applyCategoryPriority($query);

            return $query->get();
        }

        if ($dataFrom === self::LIST_TOP_RATED) {
            return $this->getTopRatedPriorityProducts($query);
        }

        if ($dataFrom === self::LIST_BEST_SELLING) {
            return $this->getBestSellingPriorityProducts($query);
        }

        if ($dataFrom === self::LIST_FEATURED) {
            return $this->getFeaturedPriorityProducts($query);
        }

        if ($dataFrom === self::LIST_MOST_FAVORITE) {
            return $this->getMostWishlistedProducts($query);
        }

        if ($offerType === self::OFFER_FEATURED_DEAL) {
            return $this->getFeaturedDealPriorityProducts($query);
        }

        if ($dataFrom === self::LIST_SEARCH) {
            return $this->sortBySearchPriority($query, (string) ($filters[self::FILTER_NAME] ?? ''));
        }

        if ($offerType === self::OFFER_CLEARANCE_SALE) {
            return $this->getClearancePriorityProducts($query);
        }

        if ($offerType === self::OFFER_FLASH_DEALS) {
            return $this->getFlashDealPriorityProducts($query);
        }

        if ($sellerId && $addedBy) {
            return $this->getVendorPriorityProducts($query);
        }

        return $query->get();
    }

    private function getFeaturedPriorityProducts(Builder $query): Collection
    {
        $priority = getWebConfig(name: self::FEATURED_PRIORITY_SETTING);

        $query->withCount(['orderDetails', 'reviews', 'wishList'])->withAvg('reviews', 'rating');

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            return $query->whereHas('retailDetail', fn (Builder $detail) => $detail->where('featured', 1))
                ->orderBy('id', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        }

        $this->applyPrioritySelection($query, $priority);
        $this->applyPrioritySortColumn($query, $priority['sort_by'] ?? null);

        $products = $query->whereHas('retailDetail', fn (Builder $detail) => $detail->where('featured', 1))->get();

        return $this->applyPriorityCollectionPasses($products, $priority);
    }

    private function getMostWishlistedProducts(Builder $query): Collection
    {
        return $query->withCount('wishList')
            ->orderBy($this->sortColumn('wish_list_count'), 'desc')
            ->orderBy('id')
            ->get();
    }

    private function getTopRatedPriorityProducts(Builder $query): Collection
    {
        $priority = getWebConfig(name: self::TOP_RATED_PRIORITY_SETTING);

        $query->withCount(['orderDetails', 'reviews', 'wishList'])->withAvg('reviews', 'rating');

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            return $this->orderByHighestRating($query)->get();
        }

        $this->applyPrioritySelection($query, $priority);
        $this->applyPrioritySortColumn($query, $priority['sort_by'] ?? null);

        $products = $query->get();

        if (($priority['out_of_stock_product'] ?? null) === 'desc') {
            $products = $this->mergeInStockFirst($products);
        }

        $minimumRating = $priority['minimum_rating_point'] ?? null;

        if (in_array($minimumRating, ['4', '3.5', '3', '2'], true)) {
            $products = $products->filter(
                fn (Model $product): bool => $product->reviews_avg_rating >= (float) $minimumRating
            );
        }

        if (($priority['temporary_close_sorting'] ?? null) === 'desc') {
            $products = $products->sortBy('is_shop_temporary_close');
        }

        return $products;
    }

    private function getBestSellingPriorityProducts(Builder $query): Collection
    {
        $priority = getWebConfig(name: self::BEST_SELLING_PRIORITY_SETTING);

        $query->withCount(['orderDetails', 'reviews', 'wishList'])->withAvg('reviews', 'rating');

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            return $query->orderBy($this->sortColumn('order_details_count'), 'desc')->get();
        }

        $this->applyPrioritySelection($query, $priority);
        $this->applyPrioritySortColumn($query, $priority['sort_by'] ?? null);

        return $this->applyPriorityCollectionPasses($query->get(), $priority);
    }

    private function getFeaturedDealPriorityProducts(Builder $query): Collection
    {
        $priority = getWebConfig(name: self::FEATURED_DEAL_PRIORITY_SETTING);
        $runningDeal = fn ($deal) => $deal
            ->whereDate('start_date', '<=', date('Y-m-d'))
            ->whereDate('end_date', '>=', date('Y-m-d'));

        $query->with([
            'seller.shop',
            'flashDealProducts.featureDeal',
            'flashDealProducts.featureDeal' => $runningDeal,
        ])
            ->whereHas('flashDealProducts.featureDeal', $runningDeal)
            ->withCount(['orderDetails', 'reviews', 'wishList'])
            ->withAvg('reviews', 'rating');

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            return $query->orderBy('id', 'desc')->get();
        }

        $this->applyTemporaryCloseHide($query, $priority['temporary_close_sorting'] ?? null);

        if (($priority['out_of_stock_product'] ?? null) === 'hide') {
            $this->applyInStockOnly($query);
        }

        $products = $this->sortPriorityCollection($query->get(), $priority['sort_by'] ?? null);

        if (($priority['out_of_stock_product'] ?? null) === 'desc') {
            $products = $this->mergeInStockFirst($products);
        }

        if (($priority['temporary_close_sorting'] ?? null) === 'desc') {
            $products = $products->sortBy('is_shop_temporary_close');
        }

        foreach ($products as $product) {
            $flashDealStatus = 0;
            $flashDealEndDate = 0;
            $flashDealEndAt = null;

            foreach ($product->flashDealProducts as $dealProduct) {
                $flashDealStatus = $dealProduct->flashDeal ? 1 : $flashDealStatus;
                $flashDealEndDate = isset($dealProduct->flashDeal->end_date)
                    ? date('Y-m-d H:i:s', strtotime($dealProduct->flashDeal->end_date))
                    : $flashDealEndDate;
                $flashDealEndAt = $dealProduct->flashDeal?->end_at ?? $flashDealEndAt;
            }

            $product['flash_deal_status'] = $flashDealStatus;
            $product['flash_deal_end_date'] = $flashDealEndDate;
            $product['flash_deal_end_at'] = $flashDealEndAt;
        }

        return $products;
    }

    private function getFlashDealPriorityProducts(Builder $query): Collection
    {
        $priority = getWebConfig(name: self::FLASH_DEAL_PRIORITY_SETTING);

        $query->withCount(['orderDetails', 'reviews'])->withAvg('reviews', 'rating');

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            return $query->get();
        }

        $this->applyPrioritySelection($query, $priority);

        return $this->applyPriorityCollectionPasses(
            $this->sortPriorityCollection($query->get(), $priority['sort_by'] ?? null),
            $priority,
        );
    }

    private function getClearancePriorityProducts(Builder $query): Collection
    {
        $priority = getWebConfig(name: self::CLEARANCE_PRIORITY_SETTING);
        $vendorPriority = getWebConfig(name: self::CLEARANCE_VENDOR_PRIORITY_SETTING);

        $query->withCount(['orderDetails', 'reviews', 'wishList'])->withAvg('reviews', 'rating');

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            return $query->get();
        }

        $this->applyPrioritySelection($query, $priority);

        $sortBy = $priority['sort_by'] ?? null;

        if ($sortBy === StockClearanceProductService::SORT_CLEARANCE_EXPIRATION) {
            $query->whereHas('clearanceSale.setup', fn (Builder $setup) => $setup->where('duration_end_date', '>=', now()));
        }

        $products = $this->sortPriorityCollection($query->get(), $sortBy);

        if ($sortBy === StockClearanceProductService::SORT_CLEARANCE_EXPIRATION) {
            $products = $products->sortBy(
                fn (Model $product) => $product?->clearanceSale?->setup?->duration_end_date ?? $product?->clearance_sale?->setup?->duration_end_date
            );
        }

        if (! empty($vendorPriority)) {
            $products = $products->sortBy(
                fn (Model $product) => array_search($product?->clearanceSale?->shop_id, $vendorPriority)
            );
        }

        return $this->applyPriorityCollectionPasses($products, $priority);
    }

    private function getVendorPriorityProducts(Builder $query): Collection
    {
        $priority = getWebConfig(name: self::VENDOR_LIST_PRIORITY_SETTING);

        $query->withCount(['orderDetails', 'reviews', 'wishList'])->withAvg('reviews', 'rating');

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            return $query->orderBy('id', 'desc')->get();
        }

        if (($priority['out_of_stock_product'] ?? null) === 'hide') {
            $this->applyInStockOnly($query);
        }

        $products = $this->sortPriorityCollection($query->get(), $priority['sort_by'] ?? null);

        return ($priority['out_of_stock_product'] ?? null) === 'desc'
            ? $this->mergeInStockFirst($products)
            : $products;
    }

    private function applyPrioritySelection(Builder $query, array $priority): void
    {
        $this->applyTemporaryCloseHide($query, $priority['temporary_close_sorting'] ?? null);

        if (($priority['out_of_stock_product'] ?? null) === 'hide') {
            $this->applyInStockOnly($query);
        }
    }

    private function applyPrioritySortColumn(Builder $query, ?string $sortBy): void
    {
        match ($sortBy) {
            'latest_created' => $query->orderBy('id', 'desc'),
            'first_created' => $query->orderBy('id', 'asc'),
            'most_order' => $query->orderBy($this->sortColumn('order_details_count'), 'desc'),
            'reviews_count' => $query->orderBy($this->sortColumn('reviews_count'), 'desc'),
            'rating' => $this->orderByHighestRating($query),
            'a_to_z' => $query->orderBy('name', 'asc'),
            'z_to_a' => $query->orderBy('name', 'desc'),
            default => null,
        };
    }

    private function sortPriorityCollection(Collection $products, ?string $sortBy): Collection
    {
        return match ($sortBy) {
            'latest_created' => $products->sortByDesc('id'),
            'first_created' => $products->sortBy('id'),
            'most_order' => $products->sortByDesc('order_details_count'),
            'reviews_count' => $products->sortByDesc('reviews_count'),
            'rating' => $products->sortByDesc('reviews_count')->sortByDesc('reviews_avg_rating'),
            'a_to_z' => $products->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE),
            'z_to_a' => $products->sortByDesc('name', SORT_NATURAL | SORT_FLAG_CASE),
            default => $products,
        };
    }

    private function applyPriorityCollectionPasses(Collection $products, array $priority): Collection
    {
        if (($priority['out_of_stock_product'] ?? null) === 'desc') {
            $products = $this->mergeInStockFirst($products);
        }

        return ($priority['temporary_close_sorting'] ?? null) === 'desc'
            ? $products->sortBy('is_shop_temporary_close')
            : $products;
    }

    private function keepsRankedOrder(?string $dataFrom, ?string $sortBy): bool
    {
        return $sortBy === self::SORT_LATEST && in_array($dataFrom, self::RANKED_LISTS, true);
    }

    private function applyStorefrontSort(Collection $products, ?string $sortBy, ?string $offerType): Collection
    {
        if (! $sortBy) {
            return $products;
        }

        if ($offerType === self::OFFER_CLEARANCE_SALE && $sortBy === self::SORT_LATEST) {
            $products = $products->filter(fn (Model $product): bool => isset($product->clearanceSale));
        }

        return match ($sortBy) {
            self::SORT_LATEST => $products->sortByDesc('id'),
            self::SORT_PRICE_LOW_HIGH => $products->sortBy('unit_price'),
            self::SORT_PRICE_HIGH_LOW => $products->sortByDesc('unit_price'),
            self::SORT_NAME_A_TO_Z => $products->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE),
            self::SORT_NAME_Z_TO_A => $products->sortByDesc('name', SORT_NATURAL | SORT_FLAG_CASE),
            self::SORT_RATING_LOW_HIGH => $products->sortBy('reviews_count')->sortBy('reviews_avg_rating'),
            self::SORT_RATING_HIGH_LOW => $products->sortByDesc('reviews_count')->sortByDesc('reviews_avg_rating'),
            default => $products,
        };
    }

    private function applyStorefrontRatingFilter(Collection $products, mixed $rating): Collection
    {
        if ($rating == null) {
            return $products;
        }

        return $products->filter(
            fn (Model $product): bool => in_array(floor($product?->reviews?->avg('rating') ?? 0), (array) $rating)
        );
    }

    /**
     * @return array<int, Model>
     */
    private function getStorefrontNameMatches(
        string $keyword,
        int|string $categoryId,
        array $authorProductIds,
        array $publishingHouseProductIds,
        int $limit,
        int $page,
    ): array {
        $query = $this->query()
            ->active()
            ->with(['rating', 'tags'])
            ->where(function (Builder $inner) use ($keyword): void {
                $this->applyNameSearch($inner, $keyword);

                $inner->orWhereHas('tags', fn (Builder $tag) => $tag->where('tag', 'like', "%{$keyword}%"));
            })
            ->when($authorProductIds !== [], fn (Builder $builder) => $builder->whereIn('id', $authorProductIds))
            ->when($publishingHouseProductIds !== [], fn (Builder $builder) => $builder->whereIn('id', $publishingHouseProductIds));

        if ($categoryId !== self::CATEGORY_ALL) {
            $this->applyStorefrontCategoryMatch($query, $categoryId);

            return $this->paginateByCategoryPriority($query, $limit, $page)->items();
        }

        return $this->paginateBySearchPriority($query, $keyword, $limit, $page)->items();
    }

    /**
     * @return array<int, Model>
     */
    private function getStorefrontTranslatedMatches(string $keyword, int|string $categoryId, int $limit, int $page): array
    {
        $query = $this->query()
            ->with(['tags', 'translations'])
            ->whereIn('id', $this->translatedIds($keyword));

        if ($categoryId !== self::CATEGORY_ALL) {
            $query->whereJsonContains('category_ids', [['id' => $categoryId]]);
        }

        return $this->paginateBySearchPriority($query, $keyword, $limit, $page, translated: true)->items();
    }

    private function applyStorefrontCategoryMatch(Builder $query, int|string $categoryId): void
    {
        $query->where(['category_id' => $categoryId])
            ->orWhere(['sub_category_id' => $categoryId])
            ->orWhere(['sub_sub_category_id' => $categoryId]);
    }

    private function paginateBySearchPriority(
        Builder $query,
        string $keyword,
        int $limit,
        int $page,
        bool $translated = false,
    ): LengthAwarePaginatorContract {
        $products = $this->sortBySearchPriority($query, $keyword, $translated);

        return new LengthAwarePaginator(
            items: $products->forPage($page, $limit),
            total: $products->count(),
            perPage: $limit,
            currentPage: $page,
            options: ['path' => Paginator::resolveCurrentPath()],
        );
    }

    private function sortBySearchPriority(Builder $query, string $keyword, bool $translated = false): Collection
    {
        $priority = getWebConfig(name: self::SEARCH_PRIORITY_SETTING);
        $customSorting = $priority && ($priority['custom_sorting_status'] ?? 0) == 1;

        $query->withCount(['orderDetails', 'reviews', 'wishList'])->withAvg('reviews', 'rating');

        if ($customSorting) {
            $this->applyTemporaryCloseHide($query, $priority['temporary_close_sorting'] ?? null);

            if (($priority['out_of_stock_product'] ?? null) === 'hide') {
                $this->applyInStockOnly($query);
            }
        }

        $searchKeyword = $this->normalizeSearchKeyword($keyword);

        $products = $query
            ->orderByRaw('CASE WHEN name LIKE ? THEN 1 ELSE 2 END, LOCATE(?, name), name', ["%{$searchKeyword}%", $searchKeyword])
            ->get();

        if ($customSorting && ($priority['out_of_stock_product'] ?? null) === 'desc') {
            $products = $this->mergeInStockFirst($products);
        }

        if ($translated) {
            $products = $products->sortBy(
                fn (Model $product) => strpos($product->translations->first()?->value ?? $product->name, $keyword)
            );
        }

        if ($customSorting && ($priority['temporary_close_sorting'] ?? null) === 'desc') {
            $products = $products->sortBy('is_shop_temporary_close');
        }

        return $products;
    }

    private function paginateByCategoryPriority(Builder $query, int $limit, int $page): LengthAwarePaginatorContract
    {
        $this->applyCategoryPriority($query);

        return $query->paginate(perPage: $limit, page: $page);
    }

    private function applyCategoryPriority(Builder $query): void
    {
        $priority = getWebConfig(name: self::CATEGORY_PRIORITY_SETTING);

        $query->withCount(['orderDetails', 'reviews', 'wishList'])->withAvg('reviews', 'rating');

        if (! $priority || ($priority['custom_sorting_status'] ?? 0) != 1) {
            $query->orderBy($this->sortColumn('order_details_count'), 'desc');

            return;
        }

        $this->applyTemporaryCloseHide($query, $priority['temporary_close_sorting'] ?? null);

        if (($priority['out_of_stock_product'] ?? null) === 'hide') {
            $query->where('product_type', '!=', self::PRODUCT_TYPE_DIGITAL)->where('current_stock', '>', 0);
        }

        if (($priority['temporary_close_sorting'] ?? null) === 'desc') {
            $query->orderByRaw($this->shopTemporaryCloseExpression());
        }

        if (($priority['out_of_stock_product'] ?? null) === 'desc') {
            $query->orderByRaw($this->inStockSortExpression());
        }

        match ($priority['sort_by'] ?? null) {
            'latest_created' => $query->orderBy('id', 'desc'),
            'first_created' => $query->orderBy('id'),
            'most_order' => $query->orderBy($this->sortColumn('order_details_count'), 'desc'),
            'reviews_count' => $query->orderBy($this->sortColumn('reviews_count'), 'desc'),
            'rating' => $query->orderBy($this->sortColumn('reviews_avg_rating'), 'desc'),
            'a_to_z' => $query->orderBy('name'),
            'z_to_a' => $query->orderBy('name', 'desc'),
            default => null,
        };
    }

    public function getSectionList(
        string $section,
        array $filters = [],
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        int $page = 1,
    ): Collection|LengthAwarePaginatorContract {
        return match ($section) {
            self::SECTION_LATEST => $this->getLatestSection($dataLimit, $page),
            self::SECTION_NEW_ARRIVAL => $this->getNewArrivalSection($dataLimit, $page),
            self::SECTION_FEATURED => $this->getFeaturedSection($dataLimit, $page),
            self::SECTION_TOP_RATED => $this->getTopRatedSection($dataLimit, $page),
            self::SECTION_BEST_SELLING, self::SECTION_MOST_SEARCHED => $this->getBestSellingSection($dataLimit, $page),
            self::SECTION_DISCOUNTED => $this->getDiscountedSection($filters, $dataLimit, $page),
            self::SECTION_JUST_FOR_YOU => $this->getJustForYouSection($filters, $dataLimit, $page),
            self::SECTION_SHOP_AGAIN => $this->getShopAgainSection($filters),
            self::SECTION_RELATED => $this->getRelatedSection($filters),
            self::SECTION_SEARCH => $this->getSearchSection($filters, $dataLimit, $page),
            self::SECTION_FILTER => $this->getFilterSection($filters, $dataLimit, $page),
            self::SECTION_CATEGORY => $this->getCategorySection($filters, $dataLimit, $page),
            self::SECTION_BRAND => $this->getBrandSection($filters, $dataLimit, $page),
            self::SECTION_SHOP => $this->getShopSection($filters, $dataLimit, $page),
            self::SECTION_SHOP_BEST_SELLING => $this->getShopBestSellingSection($filters, $dataLimit, $page),
            self::SECTION_SHOP_FEATURED => $this->getShopFeaturedSection($filters, $dataLimit, $page),
            self::SECTION_SHOP_RECOMMENDED => $this->getShopRecommendedSection($filters, $dataLimit, $page),
            default => throw new InvalidArgumentException($section),
        };
    }

    /** @return array<int, array{name: string, code: string, image: ?string}> */
    public function getFormattedColors(Model $product, array $colorNames): array
    {
        $codes = json_decode((string) $product->getRawOriginal('colors'), true);
        $codes = is_array($codes) ? $codes : [];
        $formatted = [];

        foreach (array_intersect_key($colorNames, array_flip($codes)) as $code => $name) {
            $image = collect($product->color_images_full_url ?? [])
                ->last(fn ($colorImage): bool => ! empty($colorImage['color']) && '#'.$colorImage['color'] == $code);

            $formatted[] = ['name' => $name, 'code' => $code, 'image' => $image['image_name']['key'] ?? null];
        }

        return $formatted;
    }

    public function getPosList(
        int $sellerId,
        array $categoryIds = [],
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        int $page = 1,
        array $relations = [],
    ): Collection|LengthAwarePaginatorContract {
        $query = $this->query()
            ->active()
            ->with(['digitalVariation', 'clearanceSale' => fn ($relation) => $relation->active()])
            ->with($relations)
            ->withCount('reviews')
            ->where(['added_by' => self::ADDED_BY_SELLER, 'user_id' => $sellerId, 'status' => 1])
            ->orderByDesc('id');

        $this->applyAnyCategoryLevel($query, $categoryIds);
        $this->applyNameTerms($query, $searchValue);

        return $this->paginateSectionQuery($query, $dataLimit, $page);
    }

    public function getPosTaxProducts(int $sellerId, array $productIds): Collection
    {
        $activeTaxes = fn ($relation) => $relation->with(['tax'])->whereHas('tax', fn (Builder $tax) => $tax->where('is_active', 1));

        return $this->query()
            ->where(['added_by' => self::ADDED_BY_SELLER, 'user_id' => $sellerId])
            ->whereIn('id', $productIds)
            ->with([
                'digitalVariation',
                'category' => fn ($category) => $category->with(['taxVats' => $activeTaxes]),
                'clearanceSale' => fn ($relation) => $relation->active(),
                'taxVats' => $activeTaxes,
            ])
            ->get();
    }

    public function adjustStockForEditedLine(?int $productId, ?string $variant, int $quantityChange): void
    {
        if (! $productId || $quantityChange === 0) {
            return;
        }

        $product = $this->model->newQuery()->find($productId);

        if ($product === null) {
            return;
        }

        $variations = [];
        foreach (json_decode($product->retailDetail?->variation ?? '[]', true) ?: [] as $variation) {
            if ($variant !== null && $variant === ($variation['type'] ?? null)) {
                $variation['qty'] = ($variation['qty'] ?? 0) + $quantityChange;
            }
            $variations[] = $variation;
        }

        DB::transaction(function () use ($product, $variations, $quantityChange): void {
            $this->model->newQuery()->whereKey($product->id)->update([
                'current_stock' => $product->current_stock + $quantityChange,
            ]);
            $product->retailDetail()->updateOrCreate(
                ['product_id' => $product->id],
                ['variation' => json_encode($variations)],
            );
        });
    }

    public function deductPosStock(Model $product, ?string $variant, int|float $quantity): void
    {
        DB::transaction(function () use ($product, $variant, $quantity): void {
            $lockedProduct = $this->findLockedStockRow(productId: $product->id);

            if ($lockedProduct !== null) {
                $this->writePosStockDeduction(lockedProduct: $lockedProduct, variant: $variant, quantity: $quantity);
            }
        });
    }

    public function deductPosStockIfAvailable(Model $product, ?string $variant, int|float $quantity): bool
    {
        return DB::transaction(function () use ($product, $variant, $quantity): bool {
            $lockedProduct = $this->findLockedStockRow(productId: $product->id);

            if ($lockedProduct === null) {
                return false;
            }

            if ($lockedProduct->product_type == self::PRODUCT_TYPE_PHYSICAL && $quantity > $this->getStockOnHand(product: $lockedProduct, variant: $variant)) {
                return false;
            }

            $this->writePosStockDeduction(lockedProduct: $lockedProduct, variant: $variant, quantity: $quantity);

            return true;
        });
    }

    public function getPosAvailableStock(int $productId, ?string $variant): int
    {
        $product = $this->stockRowQuery()->find($productId);

        return $product === null ? 0 : $this->getStockOnHand(product: $product, variant: $variant);
    }

    private function stockRowQuery(): Builder
    {
        return $this->model->newQuery()->withoutGlobalScopes()->with(['retailDetail']);
    }

    private function findLockedStockRow(int|string $productId): ?Model
    {
        return $this->stockRowQuery()->lockForUpdate()->find($productId);
    }

    private function writePosStockDeduction(Model $lockedProduct, ?string $variant, int|float $quantity): void
    {
        if ($variant != null) {
            $variations = array_map(static function (array $variation) use ($variant, $quantity): array {
                if ($variant == $variation['type']) {
                    $variation['qty'] -= $quantity;
                }

                return $variation;
            }, $this->storedVariations($lockedProduct));

            $this->writeRetailDetail($lockedProduct->id, ['variation' => json_encode($variations)]);
        }

        if ($lockedProduct->product_type == self::PRODUCT_TYPE_PHYSICAL) {
            $this->model->newQuery()->whereKey($lockedProduct->id)->update([
                'current_stock' => $lockedProduct->current_stock - $quantity,
            ]);
        }
    }

    private function getStockOnHand(Model $product, ?string $variant): int
    {
        $variationRow = $variant == null ? null : collect($this->storedVariations($product))->firstWhere('type', $variant);

        return (int) ($variationRow === null ? $product->current_stock : $variationRow['qty']);
    }

    public function getSellerCatalogueList(
        int $sellerId,
        array $filters = [],
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        int $page = 1,
        ?array $orderedQuantities = null,
    ): Collection|LengthAwarePaginatorContract {
        $isClearance = ($filters[self::FILTER_OFFER_TYPE] ?? null) === self::OFFER_CLEARANCE_SALE;
        $search = $filters[self::FILTER_SEARCH] ?? null;
        $activeTaxes = fn ($relation) => $relation->with(['tax'])->whereHas('tax', fn (Builder $tax) => $tax->where('is_active', 1));

        $query = $this->query()
            ->when($isClearance, fn (Builder $builder) => $builder->active())
            ->with([
                'brand', 'category', 'rating', 'tags', 'reviews', 'seoInfo', 'digitalVariation',
                'digitalProductAuthors' => fn ($relation) => $relation->with(['author']),
                'digitalProductPublishingHouse' => fn ($relation) => $relation->with(['publishingHouse']),
                'clearanceSale' => fn ($relation) => $relation->active(),
                'taxVats' => $activeTaxes,
            ])
            ->withCount('reviews')
            ->where(['user_id' => $sellerId, 'added_by' => self::ADDED_BY_SELLER]);

        $this->applyNameTerms($query, $search);

        $query->when($search && $isClearance, fn (Builder $builder) => $builder->whereNotIn('id', $filters[self::FILTER_CLEARANCE_PRODUCT_IDS] ?? []));

        $this->applyCatalogueColumnFilters($query, $filters, $sellerId);
        $this->applyCatalogueSort($query, $filters[self::FILTER_CATALOGUE_SORT] ?? null);
        $this->applyCatalogueRangeFilters($query, $filters);
        $this->applyStorefrontPublisherFilters($query, $filters);
        $this->applyStorefrontAuthorFilters($query, $filters);

        $products = $this->paginateSectionQuery($query->latest(), $dataLimit, $page);

        if ($orderedQuantities !== null) {
            foreach ($products as $product) {
                $orderedQuantity = $orderedQuantities[$product->id] ?? 0;
                $product->current_stock = max(0, $product->current_stock) + ($orderedQuantity > 0 ? $orderedQuantity : 0);
            }
        }

        return $products;
    }

    public function updateStockAndVariation(mixed $id, int $currentStock, string|array $variation): ?Model
    {
        if ($this->query()->find($id) === null) {
            return null;
        }

        DB::transaction(function () use ($id, $currentStock, $variation): void {
            $this->model->newQuery()->whereKey($id)->update(['current_stock' => $currentStock]);
            $this->writeRetailDetail($id, ['variation' => is_array($variation) ? json_encode($variation) : $variation]);
        });

        cacheRemoveByType(type: 'products');

        return $this->query()->with(['retailDetail'])->find($id);
    }

    public function clearDigitalVariationFile(mixed $productId, string $variantKey): bool
    {
        $variations = $this->query()->find($productId)?->digitalVariation()->where('variant_key', $variantKey);

        if ($variations === null || ! $variations->exists()) {
            return false;
        }

        $variations->update(['file' => null]);

        return true;
    }

    public function storeDigitalFile(UploadedFile $file): string
    {
        return $this->fileUpload(dir: self::DIGITAL_DIRECTORY, format: $file->getClientOriginalExtension(), file: $file);
    }

    private function getLatestSection(int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        $query = $this->sectionCardQuery()
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('id');

        return $this->stampFlashDealStatus($this->paginateSectionQuery($query, $dataLimit, $page));
    }

    private function getNewArrivalSection(int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        $query = $this->sectionCardQuery()
            ->withAvg('reviews', 'rating')
            ->withCount(['reviews', 'orderDetails']);

        $priority = $this->getCustomPriority(self::NEW_ARRIVAL_PRIORITY_SETTING);

        if ($priority === null) {
            $query->orderByDesc($this->sortColumn('order_details_count'));

            return $this->stampFlashDealStatus($this->paginateSectionQuery($query, $dataLimit, $page));
        }

        $this->applyPrioritySelection($query, $priority);
        $this->applyNewArrivalDuration($query, $priority);
        $this->applyPrioritySortColumn($query, $priority['sort_by'] ?? null);

        $products = $this->applyPriorityCollectionPasses($query->get(), $priority);

        return $this->stampFlashDealStatus($this->paginateCollection($products, $dataLimit, $page));
    }

    private function getFeaturedSection(int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        $query = $this->sectionCardQuery()
            ->withAvg('reviews', 'rating')
            ->withCount(['reviews', 'orderDetails']);

        return $this->stampFlashDealStatus($this->paginateFeatured($query, $dataLimit, $page));
    }

    private function getTopRatedSection(int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        $query = $this->rankedSectionQuery()->whereHas('reviews');

        $products = $this->getCustomPriority(self::TOP_RATED_PRIORITY_SETTING) === null
            ? $this->paginateSectionQuery(
                $this->orderByHighestRating($query->withCount('orderDetails')),
                $dataLimit,
                $page,
            )
            : $this->paginateCollection($this->getTopRatedPriorityProducts($query), $dataLimit, $page);

        return $this->stampFlashDealStatus($this->countLoadedReviews($products));
    }

    private function getBestSellingSection(int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        $query = $this->rankedSectionQuery()->whereHas('orderDetails');

        $products = $this->getCustomPriority(self::BEST_SELLING_PRIORITY_SETTING) === null
            ? $this->paginateSectionQuery(
                $query->withCount('orderDetails')->orderByDesc($this->sortColumn('order_details_count')),
                $dataLimit,
                $page,
            )
            : $this->paginateCollection($this->getBestSellingPriorityProducts($query), $dataLimit, $page);

        return $this->stampFlashDealStatus($this->countLoadedReviews($products));
    }

    private function getDiscountedSection(array $filters, int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        $clearanceProductIds = $filters[self::FILTER_CLEARANCE_PRODUCT_IDS] ?? [];

        $query = $this->query()
            ->active()
            ->with(['rating', 'reviews', 'tags', 'clearanceSale' => fn ($relation) => $relation->active()])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where(fn (Builder $outer) => $outer
                ->where('discount', '!=', 0)
                ->orWhereIn('id', $clearanceProductIds))
            ->orderByDesc('id');

        return $this->paginateSectionQuery($query, $dataLimit, $page);
    }

    private function getJustForYouSection(array $filters, int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        $query = $this->query()
            ->active()
            ->with(['clearanceSale' => fn ($relation) => $relation->active()])
            ->inRandomOrder();

        if (array_key_exists(self::FILTER_PREFERRED_CATEGORY_IDS, $filters)) {
            $categoryIds = (array) $filters[self::FILTER_PREFERRED_CATEGORY_IDS];

            $query->where(function (Builder $inner) use ($categoryIds): void {
                foreach ($categoryIds as $categoryId) {
                    $inner->orWhere('category_id', 'like', "%{$categoryId}%")
                        ->orWhere('sub_category_id', 'like', "%{$categoryId}%");
                }
            });
        }

        return $this->paginateSectionQuery($query, $dataLimit, $page);
    }

    private function getShopAgainSection(array $filters): Collection
    {
        $products = $this->query()
            ->active()
            ->with(['seller.shop', 'reviews', 'clearanceSale' => fn ($relation) => $relation->active()])
            ->whereIn('id', $filters[self::FILTER_PRODUCT_IDS] ?? [])
            ->select(self::SHOP_AGAIN_COLUMNS)
            ->inRandomOrder()
            ->limit(self::SHOP_AGAIN_LIMIT)
            ->get();

        return $this->countLoadedReviews($products);
    }

    private function getRelatedSection(array $filters): Collection
    {
        $products = $this->query()
            ->active()
            ->with(['rating', 'flashDealProducts.flashDeal', 'tags', 'seller.shop', 'clearanceSale' => fn ($relation) => $relation->active()])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('category_ids', $filters[self::FILTER_EXACT_CATEGORY_IDS] ?? '')
            ->whereNotIn('id', $filters[self::FILTER_EXCLUDE_IDS] ?? [])
            ->limit(self::RELATED_LIMIT)
            ->get();

        return $this->stampFlashDealStatus($products);
    }

    private function getSearchSection(array $filters, int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        $keyword = (string) ($filters[self::FILTER_SEARCH] ?? '');

        if (empty($filters[self::FILTER_SEARCH_TRANSLATED_ONLY])) {
            $matches = $this->paginateCollection(
                $this->sortBySearchPriority($this->searchMatchQuery($keyword, $filters), $keyword),
                $dataLimit,
                $page,
            );

            if ($this->hasRows($matches)) {
                return $matches;
            }
        }

        return $this->paginateCollection(
            $this->sortBySearchPriority($this->translatedSearchQuery($keyword), $keyword, translated: true),
            $dataLimit,
            $page,
        );
    }

    private function getFilterSection(array $filters, int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        $isClearance = ($filters[self::FILTER_OFFER_TYPE] ?? null) === self::OFFER_CLEARANCE_SALE;
        $categoryIds = $filters[self::FILTER_CATEGORY_IDS] ?? [];
        $subCategoryIds = $filters[self::FILTER_SUB_CATEGORY_IDS] ?? [];
        $subSubCategoryIds = $filters[self::FILTER_SUB_SUB_CATEGORY_IDS] ?? [];
        $priceMin = $filters[self::FILTER_PRICE_MIN] ?? null;
        $priceMax = $filters[self::FILTER_PRICE_MAX] ?? null;

        $query = $this->query()
            ->active()
            ->with(['rating', 'tags', 'clearanceSale' => fn ($relation) => $relation->active()])
            ->when(
                array_key_exists(self::FILTER_SEARCH_PRODUCT_IDS, $filters),
                fn (Builder $builder) => $builder->whereIn('id', [0, ...(array) $filters[self::FILTER_SEARCH_PRODUCT_IDS]]),
            )
            ->withCount(['reviews' => fn ($review) => $review->active()->whereNull('delivery_man_id')]);

        $this->applyProductTypeFilter($query, $filters);

        $query->when(! empty($filters[self::FILTER_BRAND_IDS]), fn (Builder $builder) => $builder->whereIn('brand_id', $filters[self::FILTER_BRAND_IDS]))
            ->when(! empty($categoryIds), fn (Builder $builder) => $builder
                ->whereIn('category_id', $categoryIds)
                ->when(! empty($subCategoryIds), fn (Builder $inner) => $inner->whereIn('sub_category_id', $subCategoryIds))
                ->when(! empty($subSubCategoryIds), fn (Builder $inner) => $inner->whereIn('sub_sub_category_id', $subSubCategoryIds)));

        $this->applyStorefrontPublisherFilters($query, $filters);
        $this->applyStorefrontAuthorFilters($query, $filters);

        match ($filters[self::FILTER_SORT_BY] ?? null) {
            self::SORT_PRICE_LOW_HIGH => $query->orderBy('unit_price'),
            self::SORT_PRICE_HIGH_LOW => $query->orderByDesc('unit_price'),
            self::SORT_NAME_A_TO_Z => $query->orderBy('name'),
            self::SORT_NAME_Z_TO_A => $query->orderByDesc('name'),
            self::SORT_LATEST => $query->latest(),
            default => null,
        };

        $query->when($isClearance, fn (Builder $builder) => $builder->whereIn('id', $filters[self::FILTER_CLEARANCE_PRODUCT_IDS] ?? []))
            ->when(! empty($priceMin) || ! empty($priceMax), fn (Builder $builder) => $builder->whereBetween('unit_price', [$priceMin, $priceMax]));

        return $isClearance
            ? $this->paginateClearance($query, $dataLimit, $page)
            : $this->paginateCollection(
                $this->sortBySearchPriority($query, (string) ($filters[self::FILTER_SEARCH] ?? '')),
                $dataLimit,
                $page,
            );
    }

    private function getCategorySection(array $filters, int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        $query = $this->query()
            ->active()
            ->with(['flashDealProducts.flashDeal', 'rating', 'seller.shop', 'tags', 'clearanceSale' => fn ($relation) => $relation->active()])
            ->withCount('reviews')
            ->where('category_ids', 'like', '%"'.($filters[self::FILTER_CATEGORY_ID] ?? '').'"%');

        $this->applyKeywordProductMatch($query, $filters);
        $this->applyProductTypeFilter($query, $filters);

        $query->when(! empty($filters[self::FILTER_BRAND_IDS]), fn (Builder $builder) => $builder->whereIn('brand_id', $filters[self::FILTER_BRAND_IDS]));

        $this->applyStorefrontPublisherFilters($query, $filters);
        $this->applyStorefrontAuthorFilters($query, $filters);
        $this->applyCategoryPriority($query);

        return $this->stampFlashDealStatus($this->paginateSectionQuery($query, $dataLimit, $page));
    }

    private function getBrandSection(array $filters, int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        $query = $this->query()
            ->active()
            ->with(['clearanceSale' => fn ($relation) => $relation->active()]);

        $this->applyKeywordProductMatch($query, $filters);

        $query->withCount('reviews')->where('brand_id', $filters[self::FILTER_BRAND_ID] ?? 0);

        return $this->paginateSectionQuery($query, $dataLimit, $page);
    }

    private function getShopSection(array $filters, int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        $isClearance = ($filters[self::FILTER_OFFER_TYPE] ?? null) === self::OFFER_CLEARANCE_SALE;

        $query = $this->query()
            ->active()
            ->with([
                'rating', 'flashDealProducts.flashDeal', 'tags', 'digitalProductAuthors.author',
                'digitalProductPublishingHouse.publishingHouse', 'clearanceSale' => fn ($relation) => $relation->active(),
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        $this->applyProductTypeFilter($query, $filters);
        $this->applyShopOwnership($query, $filters);
        $this->applyNameTerms($query, $filters[self::FILTER_SEARCH] ?? null);

        $query->when(! empty($filters[self::FILTER_BRAND_IDS]), fn (Builder $builder) => $builder->whereIn('brand_id', $filters[self::FILTER_BRAND_IDS]));

        $this->applyShopCategoryLevels($query, $filters);
        $this->applyShopPublisherFilter($query, $filters);
        $this->applyStorefrontAuthorFilters($query, $filters);

        $query->when($isClearance, fn (Builder $builder) => $builder->whereIn('id', $filters[self::FILTER_CLEARANCE_PRODUCT_IDS] ?? []))
            ->when(! empty($filters[self::FILTER_EXCLUDE_IDS]), fn (Builder $builder) => $builder->whereNotIn('id', $filters[self::FILTER_EXCLUDE_IDS]));

        $products = $isClearance
            ? $this->paginateClearance($query, $dataLimit, $page)
            : $this->paginateCollection($this->getVendorPriorityProducts($query), $dataLimit, $page);

        return $this->stampFlashDealStatus($products);
    }

    private function getShopBestSellingSection(array $filters, int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        $query = $this->query()
            ->active()
            ->with(['rating'])
            ->whereHas('orderDetails')
            ->withCount('orderDetails')
            ->orderByDesc('order_details_count');

        $this->applyShopOwnership($query, $filters);

        return $this->paginateSectionQuery($query, $dataLimit, $page);
    }

    private function getShopFeaturedSection(array $filters, int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        $query = $this->query()
            ->active()
            ->with(['reviews', 'rating', 'clearanceSale' => fn ($relation) => $relation->active()])
            ->whereHas('retailDetail', fn (Builder $detail) => $detail->where('featured', 1));

        $this->applyShopOwnership($query, $filters);

        return $this->countLoadedReviews($this->paginateFeatured($query, $dataLimit, $page));
    }

    private function getShopRecommendedSection(array $filters, int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        $query = $this->query()->active()->with(['category', 'reviews']);

        $this->applyShopOwnership($query, $filters);
        $this->applyShopCategoryLevels($query, $filters);

        $query->withCount('orderDelivered')
            ->withSum('tags', 'visit_count')
            ->orderByDesc('order_delivered_count')
            ->orderByDesc('tags_sum_visit_count');

        return $this->countLoadedReviews($this->paginateSectionQuery($query, $dataLimit, $page));
    }

    private function sectionCardQuery(): Builder
    {
        return $this->query()
            ->active()
            ->with(['rating', 'tags', 'seller.shop', 'flashDealProducts.flashDeal', 'clearanceSale' => fn ($relation) => $relation->active()]);
    }

    private function rankedSectionQuery(): Builder
    {
        return $this->query()
            ->active()
            ->withSum('orderDetails', 'qty')
            ->with([
                'seller.shop', 'category', 'reviews', 'rating', 'flashDealProducts.flashDeal',
                'clearanceSale' => fn ($relation) => $relation->active(),
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');
    }

    private function paginateFeatured(Builder $query, int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        if ($this->getCustomPriority(self::FEATURED_PRIORITY_SETTING) !== null) {
            return $this->paginateCollection($this->getFeaturedPriorityProducts($query), $dataLimit, $page);
        }

        $query->withCount(['orderDetails', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->whereHas('retailDetail', fn (Builder $detail) => $detail->where('featured', 1))
            ->orderByDesc('id');

        return $this->paginateSectionQuery($query, $dataLimit, $page);
    }

    private function paginateClearance(Builder $query, int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        if ($this->getCustomPriority(self::CLEARANCE_PRIORITY_SETTING) !== null) {
            return $this->paginateCollection($this->getClearancePriorityProducts($query), $dataLimit, $page);
        }

        $query->withCount(['orderDetails', 'reviews', 'wishList'])->withAvg('reviews', 'rating');

        return $this->paginateSectionQuery($query, $dataLimit, $page);
    }

    private function paginateSectionQuery(Builder $query, int|string $dataLimit, int $page): Collection|LengthAwarePaginatorContract
    {
        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    private function getCustomPriority(string $setting): ?array
    {
        $priority = getWebConfig(name: $setting);

        return is_array($priority) && ($priority['custom_sorting_status'] ?? 0) == 1 ? $priority : null;
    }

    private function applyNewArrivalDuration(Builder $query, array $priority): void
    {
        $duration = (int) ($priority['duration'] ?? 0);

        if ($duration === 0) {
            return;
        }

        match ($priority['duration_type'] ?? null) {
            'days' => $query->whereDate('created_at', '>=', Carbon::now()->subDays($duration)),
            'month' => $query->whereDate('created_at', '>=', Carbon::now()->subMonths($duration)),
            default => null,
        };
    }

    private function searchMatchQuery(string $keyword, array $filters): Builder
    {
        $terms = explode(' ', $keyword);
        $authorProductIds = $filters[self::FILTER_KEYWORD_AUTHOR_PRODUCT_IDS] ?? [];
        $publisherProductIds = $filters[self::FILTER_KEYWORD_PUBLISHER_PRODUCT_IDS] ?? [];

        return $this->query()
            ->active()
            ->with(['rating', 'tags', 'clearanceSale' => fn ($relation) => $relation->active()])
            ->where(function (Builder $inner) use ($keyword, $terms): void {
                $this->applyNameSearch($inner, $keyword, $terms);

                $inner->orWhereHas('tags', fn (Builder $tag) => $tag->where(function (Builder $group) use ($terms): void {
                    foreach ($terms as $term) {
                        $group->where('tag', 'like', "%{$term}%");
                    }
                }));
            })
            ->when($authorProductIds !== [], fn (Builder $builder) => $builder->whereIn('id', $authorProductIds))
            ->when($publisherProductIds !== [], fn (Builder $builder) => $builder->whereIn('id', $publisherProductIds));
    }

    private function translatedSearchQuery(string $keyword): Builder
    {
        return $this->query()
            ->with(['tags', 'clearanceSale' => fn ($relation) => $relation->active()])
            ->where(fn (Builder $inner) => $inner
                ->whereIn('id', $this->translatedIds($keyword))
                ->orWhereHas('tags', fn (Builder $tag) => $tag->where('tag', 'like', "%{$keyword}%")));
    }

    private function applyKeywordProductMatch(Builder $query, array $filters): void
    {
        if (empty($filters[self::FILTER_SEARCH])) {
            return;
        }

        $productIds = (array) ($filters[self::FILTER_SEARCH_PRODUCT_IDS] ?? []);

        $query->whereIn('id', $productIds === [] ? [0] : $productIds);

        $this->applyKeywordRelevanceOrder($query, (string) $filters[self::FILTER_SEARCH]);
    }

    private function applyProductTypeFilter(Builder $query, array $filters): void
    {
        $productType = $filters[self::FILTER_PRODUCT_TYPE] ?? null;

        if (in_array($productType, [self::PRODUCT_TYPE_PHYSICAL, self::PRODUCT_TYPE_DIGITAL], true)) {
            $query->where('product_type', $productType);
        }
    }

    private function applyShopOwnership(Builder $query, array $filters): void
    {
        if (($filters[self::FILTER_ADDED_BY] ?? null) === self::ADDED_BY_ADMIN) {
            $query->where('added_by', self::ADDED_BY_ADMIN);

            return;
        }

        $query->where('added_by', self::ADDED_BY_SELLER)->where('user_id', (int) ($filters[self::FILTER_SELLER_ID] ?? 0));
    }

    private function applyShopCategoryLevels(Builder $query, array $filters): void
    {
        $categoryIds = $filters[self::FILTER_CATEGORY_IDS] ?? [];
        $subCategoryIds = $filters[self::FILTER_SUB_CATEGORY_IDS] ?? [];
        $subSubCategoryIds = $filters[self::FILTER_SUB_SUB_CATEGORY_IDS] ?? [];

        $query->when(! empty($categoryIds), fn (Builder $builder) => $builder
            ->whereIn('category_id', $categoryIds)
            ->when(! empty($subCategoryIds), fn (Builder $inner) => $inner->whereIn('sub_category_id', $subCategoryIds))
            ->when(! empty($subSubCategoryIds), fn (Builder $inner) => $inner->whereIn('sub_sub_category_id', $subSubCategoryIds)));
    }

    private function applyShopPublisherFilter(Builder $query, array $filters): void
    {
        $publishingHouseIds = $filters[self::FILTER_PUBLISHING_HOUSE_IDS] ?? [];

        if (empty($publishingHouseIds)) {
            return;
        }

        $productIds = $filters[self::FILTER_SELECTED_PUBLISHER_PRODUCT_IDS] ?? [];

        if (in_array(0, $publishingHouseIds)) {
            $productIds = array_merge($productIds, $filters[self::FILTER_ATTACHED_PUBLISHER_PRODUCT_IDS] ?? []);
        }

        $query->where('product_type', self::PRODUCT_TYPE_DIGITAL)->whereIn('id', $productIds);
    }

    private function applyAnyCategoryLevel(Builder $query, array $categoryIds): void
    {
        if ($categoryIds === []) {
            return;
        }

        $query->where(fn (Builder $inner) => $inner
            ->whereIn('category_id', $categoryIds)
            ->orWhereIn('sub_category_id', $categoryIds)
            ->orWhereIn('sub_sub_category_id', $categoryIds));
    }

    private function applyNameTerms(Builder $query, ?string $searchValue): void
    {
        if ($searchValue === null || $searchValue === '') {
            return;
        }

        foreach (explode(' ', $searchValue) as $term) {
            $query->where('name', 'like', "%{$term}%");
        }
    }

    private function applyCatalogueColumnFilters(Builder $query, array $filters, int $sellerId): void
    {
        $this->applyProductTypeFilter($query, $filters);

        $query->when(is_array($filters[self::FILTER_PRODUCT_TYPES] ?? null), fn (Builder $builder) => $builder->whereIn('product_type', $filters[self::FILTER_PRODUCT_TYPES]))
            ->when(! empty($filters[self::FILTER_BRAND_IDS]), fn (Builder $builder) => $builder->whereIn('brand_id', $filters[self::FILTER_BRAND_IDS]));

        $this->applyAnyCategoryLevel($query, (array) ($filters[self::FILTER_CATEGORY_IDS] ?? []));

        if (array_key_exists(self::FILTER_TREE_CATEGORY_IDS, $filters)) {
            $query->whereIn('id', $this->getCategoryTreeProductIds($filters, $sellerId, self::ADDED_BY_SELLER));
        }

        $query->when(isset($filters[self::FILTER_STATUS]), fn (Builder $builder) => $builder->where('status', $filters[self::FILTER_STATUS]))
            ->when(is_array($filters[self::FILTER_STATUSES] ?? null), fn (Builder $builder) => $builder->whereIn('status', $filters[self::FILTER_STATUSES]))
            ->when(isset($filters[self::FILTER_REQUEST_STATUS]), fn (Builder $builder) => $builder->where('request_status', $filters[self::FILTER_REQUEST_STATUS]));
    }

    private function applyCatalogueSort(Builder $query, ?string $sortBy): void
    {
        $deliveredWithoutApprovedRefund = fn ($detail) => $detail
            ->where('delivery_status', self::DELIVERY_STATUS_DELIVERED)
            ->whereDoesntHave('refundStatus', fn (Builder $refund) => $refund->where('status', self::REFUND_STATUS_APPROVED));

        match ($sortBy) {
            self::SORT_LATEST => $query->orderByDesc('id'),
            self::SORT_OLDEST => $query->orderBy('id'),
            self::SORT_BEST_SELLING => $query->withCount([
                'orderDetails' => $deliveredWithoutApprovedRefund,
                'orderDetails as totalSoldAmount' => fn ($detail) => $deliveredWithoutApprovedRefund($detail->select(DB::raw('SUM(qty * price)'))),
            ])->orderByDesc('order_details_count'),
            self::SORT_MOST_FAVORITE => $query
                ->with(['reviews' => fn ($review) => $review->whereHas('product', fn (Builder $product) => $product->active())])
                ->withCount(['reviews' => fn ($review) => $review->whereNull('delivery_man_id')])
                ->withAvg('rating as ratings_average', 'rating')
                ->orderByDesc('reviews_count'),
            default => null,
        };
    }

    private function applyCatalogueRangeFilters(Builder $query, array $filters): void
    {
        $minPrice = $filters[self::FILTER_MIN_PRICE] ?? null;
        $maxPrice = $filters[self::FILTER_MAX_PRICE] ?? null;
        $currencyCode = $filters[self::FILTER_CURRENCY_CODE] ?? null;

        $query->when($minPrice != null && $minPrice > 0, fn (Builder $builder) => $builder->where('unit_price', '>=', Convert::usdPaymentModule($minPrice, $currencyCode)))
            ->when($maxPrice != null, fn (Builder $builder) => $builder->where('unit_price', '<=', Convert::usdPaymentModule($maxPrice, $currencyCode)));

        foreach ([self::FILTER_START_DATE => '>=', self::FILTER_END_DATE => '<='] as $key => $operator) {
            $date = $filters[$key] ?? null;

            if ($date !== null && checkDateFormatInMDYAndTime(dateTime: $date)) {
                $query->whereDate('created_at', $operator, Carbon::createFromFormat(self::CATALOGUE_DATE_FORMAT, $date));
            }
        }
    }

    private function countLoadedReviews(Collection|LengthAwarePaginatorContract $products): Collection|LengthAwarePaginatorContract
    {
        foreach ($products as $product) {
            $product->setAttribute('reviews_count', $product->reviews->count());
        }

        return $products;
    }

    private function stampFlashDealStatus(Collection|LengthAwarePaginatorContract $products): Collection|LengthAwarePaginatorContract
    {
        $now = date('Y-m-d H:i:s');

        foreach ($products as $product) {
            $flashDeal = $product->flashDealProducts->pluck('flashDeal')->filter()->last();

            $isRunning = $flashDeal !== null
                && $flashDeal->status == 1
                && $now >= date('Y-m-d H:i:s', strtotime($flashDeal->start_date))
                && $now <= date('Y-m-d H:i:s', strtotime($flashDeal->end_date));

            $product->setAttribute('flash_deal_status', $isRunning ? 1 : 0);
            $product->setAttribute('flash_deal_end_date', $flashDeal?->end_date ?? 0);
            $product->setAttribute('flash_deal_end_at', $flashDeal?->end_at);
        }

        return $products;
    }

    private function hasRows(Collection|LengthAwarePaginatorContract $products): bool
    {
        return $products instanceof Collection ? $products->isNotEmpty() : count($products->items()) > 0;
    }
}
