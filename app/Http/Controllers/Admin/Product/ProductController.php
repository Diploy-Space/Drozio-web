<?php

namespace App\Http\Controllers\Admin\Product;

use App\Enums\BusinessModule;
use App\Enums\WebConfigKey;
use App\Events\ProductRequestStatusUpdateEvent;
use App\Exports\ProductListExport;
use App\Exports\RestockProductListExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Product\BulkImportRequest;
use App\Http\Requests\Admin\Product\DenyRequest;
use App\Http\Requests\Shared\Product\AddRequest;
use App\Http\Requests\Shared\Product\EditImageRequest;
use App\Http\Requests\Shared\Product\UpdateRequest;
use App\Models\Product;
use App\Services\New\AttributeService;
use App\Services\New\AuthorService;
use App\Services\New\BannerService;
use App\Services\New\BrandService;
use App\Services\New\CartService;
use App\Services\New\CategoryService;
use App\Services\New\Clearance\StockClearanceProductService;
use App\Services\New\ColorService;
use App\Services\New\Deal\DealOfTheDayService;
use App\Services\New\Deal\FlashDealProductService;
use App\Services\New\DigitalProductVariationService;
use App\Services\New\ProductRetailService;
use App\Services\New\PublishingHouseService;
use App\Services\New\RestockProductCustomerService;
use App\Services\New\RestockProductService;
use App\Services\New\ReviewService;
use App\Services\New\ShopService;
use App\Services\New\VendorService;
use App\Services\New\WishlistService;
use App\Traits\FileManagerTrait;
use App\Utils\ProductGalleryVariations;
use Carbon\Carbon;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\TaxModule\app\Models\SystemTaxSetup;
use Modules\TaxModule\app\Models\Tax;
use Modules\TaxModule\app\Models\Taxable;
use Modules\TaxModule\app\Traits\VatTaxManagement;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends BaseController
{
    use FileManagerTrait {
        delete as deleteFile;
        update as updateFile;
    }
    use VatTaxManagement;

    private const PRODUCT_FILTER_KEYS = [
        'filter_sort_by',
        'product_status',
        'filter_product_types',
        'filter_brand_ids',
        'filter_shop_ids',
        'filter_category_ids',
        'filter_sub_category_ids',
        'filter_sub_sub_category_ids',
    ];

    public function __construct(
        private readonly AuthorService $authorService,
        private readonly PublishingHouseService $publishingHouseService,
        private readonly BrandService $brandService,
        private readonly RestockProductService $restockProductService,
        private readonly RestockProductCustomerService $restockProductCustomerService,
        private readonly DigitalProductVariationService $digitalProductVariationService,
        private readonly VendorService $vendorService,
        private readonly AttributeService $attributeService,
        private readonly WishlistService $wishlistService,
        private readonly FlashDealProductService $flashDealProductService,
        private readonly DealOfTheDayService $dealOfTheDayService,
        private readonly ReviewService $reviewService,
        private readonly BannerService $bannerService,
        private readonly ShopService $shopService,
        private readonly ProductRetailService $productService,
        private readonly StockClearanceProductService $stockClearanceProductService,
        private readonly ColorService $colorService,
        private readonly CategoryService $categoryService,
        private readonly CartService $cartService,
    ) {}

    private function getCategoryIdsForFilter(Request $request): array
    {
        $matchingIds = $this->categoryService->getMatchingCategoryIds(
            categoryIds: $request['filter_category_ids'] ?? [],
            subCategoryIds: $request['filter_sub_category_ids'] ?? [],
            subSubCategoryIds: $request['filter_sub_sub_category_ids'] ?? [],
        );

        return [
            'filter_category_ids' => $matchingIds['category_ids'],
            'filter_sub_category_ids' => $matchingIds['sub_category_ids'],
            'filter_sub_sub_category_ids' => $matchingIds['sub_sub_category_ids'],
            'filter_direct_category_ids' => $matchingIds['direct_category_ids'],
            'filter_direct_sub_category_ids' => $matchingIds['direct_sub_category_ids'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, mixed>  $dateRange
     * @return array<string, mixed>
     */
    private function restockFilters(array $filters, ?string $searchValue = null, array $dateRange = []): array
    {
        return array_filter([
            RestockProductService::FILTER_ADDED_BY => $filters['added_by'] ?? null,
            RestockProductService::FILTER_SELLER_ID => $filters['seller_id'] ?? null,
            RestockProductService::FILTER_BRAND_ID => $filters['brand_id'] ?? null,
            RestockProductService::FILTER_CATEGORY_ID => $filters['category_id'] ?? null,
            RestockProductService::FILTER_SUB_CATEGORY_ID => $filters['sub_category_id'] ?? null,
            RestockProductService::FILTER_DATE_RANGE => $dateRange,
            RestockProductService::FILTER_PRODUCT_IDS => $searchValue
                ? $this->productService->getIds(filters: $filters, searchValue: $searchValue)
                : null,
        ], static fn ($value) => $value !== null && $value !== '' && $value !== [] && $value !== 'all');
    }

    /** @return array<int, Carbon> */
    private function restockDateRange(Request $request): array
    {
        if ($request['date_type'] !== 'custom') {
            return [];
        }

        $from = Carbon::canBeCreatedFromFormat($request['from'] ?? '', 'Y-m-d') ? $request['from'] : null;
        $to = Carbon::canBeCreatedFromFormat($request['to'] ?? '', 'Y-m-d') ? $request['to'] : null;

        if ($from === null && $to === null) {
            return [];
        }

        return [
            Carbon::createFromFormat('Y-m-d', $from ?? $to)->startOfDay(),
            Carbon::createFromFormat('Y-m-d', $to ?? $from)->endOfDay(),
        ];
    }

    /** @return array<int, int> */
    private function digitalAuthorIds(Request $request): array
    {
        return $request['product_type'] === ProductRetailService::PRODUCT_TYPE_DIGITAL
            ? $this->authorService->resolveIds((array) ($request['authors'] ?? []))
            : [];
    }

    /** @return array<int, int> */
    private function digitalPublishingHouseIds(Request $request): array
    {
        return $request['product_type'] === ProductRetailService::PRODUCT_TYPE_DIGITAL
            ? $this->publishingHouseService->resolveIds((array) ($request['publishing_house'] ?? []))
            : [];
    }

    private function deactivateClearanceSaleBelowDiscount(object|array $product): void
    {
        $clearanceProduct = $this->stockClearanceProductService->findBy(
            params: ['product_id' => $product['id']],
            relations: ['setup', 'product'],
        );

        if ($clearanceProduct !== null && ! $this->stockClearanceProductService->isDiscountBelowProductPrice($clearanceProduct)) {
            $this->stockClearanceProductService->updateStatus(productId: $product['id'], status: false);
        }
    }

    /** @return array<int, array{name: string, value: string}> */
    private function preservedFilterInputs(Request $request): array
    {
        $inputs = [];

        foreach (self::PRODUCT_FILTER_KEYS as $key) {
            $value = $request->input($key);

            if ($value === null || $value === '') {
                continue;
            }

            foreach ((array) $value as $item) {
                $inputs[] = ['name' => is_array($value) ? $key.'[]' : $key, 'value' => (string) $item];
            }
        }

        return $inputs;
    }

    private function getListFilters(Request $request, string $state, ?string $addedBy = null, mixed $sellerId = null): array
    {
        return $this->productService->listFilters(
            state: $state,
            addedBy: $addedBy,
            sellerId: $sellerId,
            catalogFilters: [
                'brand_id' => array_values($request['filter_brand_ids'] ?? []),
                'shop_id' => array_values($request['filter_shop_ids'] ?? []),
                'product_type' => array_values($request['filter_product_types'] ?? []),
                ...$this->getCategoryIdsForFilter($request),
            ],
            statuses: array_values($request['product_status'] ?? []),
        );
    }

    public function index(?Request $request, ?string $type = null): View
    {
        $type = $type == 'vendor' ? ProductRetailService::ADDED_BY_SELLER : ProductRetailService::ADDED_BY_IN_HOUSE;

        $taxData = $this->getTaxSystemType();
        $productWiseTax = $taxData['productWiseTax'] && ! $taxData['is_included'];

        $subCategories = collect();
        $subSubCategories = collect();
        $paginationLimit = getWebConfig(name: WebConfigKey::PAGINATION_LIMIT) ? getWebConfig(name: WebConfigKey::PAGINATION_LIMIT) : 25;

        $products = $this->productService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $paginationLimit,
            relations: [
                'clearanceSale' => fn ($query) => $query->active(),
                'taxVats' => fn ($query) => $query->with(['tax'])->whereHas('tax', fn ($query) => $query->where('is_active', 1)),
            ],
            filters: $this->getListFilters(
                request: $request,
                state: $this->productService->resolveRequestState($request['request_status']),
                addedBy: $type,
                sellerId: $request['seller_id'],
            ),
            sortBy: $request['filter_sort_by'] ?? ProductRetailService::SORT_LATEST,
        )->appends($request->query());

        $sellers = $this->vendorService->getList(dataLimit: $paginationLimit, relations: ['shop'], filters: [VendorService::FILTER_STATUS_EXCEPT => VendorService::STATUS_PENDING]);
        $brands = $this->brandService->getList(dataLimit: 'all', sortBy: BrandService::SORT_NEWEST_ID);
        $categories = $this->categoryService->getList(dataLimit: 'all', relations: ['childes.childes'], position: CategoryService::POSITION_CATEGORY);

        return view('admin-views.product.list', compact(
            'products',
            'sellers',
            'brands',
            'categories',
            'subCategories',
            'subSubCategories',
            'type',
            'productWiseTax'
        ));
    }

    public function getAddView(): View
    {
        $taxData = $this->getTaxSystemType();
        $productWiseTax = $taxData['productWiseTax'] && ! $taxData['is_included'];
        $taxVats = $taxData['taxVats'];

        $categories = $this->categoryService->getList(dataLimit: 'all', position: CategoryService::POSITION_CATEGORY);
        $brands = $this->brandService->getList(dataLimit: 'all');
        $brandSetting = getWebConfig(name: 'product_brand');
        $digitalProductSetting = getWebConfig(name: 'digital_product');
        $colors = $this->colorService->getList(dataLimit: 'all', sortBy: ColorService::SORT_NAME_DESC);
        $attributes = $this->attributeService->getList(dataLimit: 'all', sortBy: AttributeService::SORT_NAME_DESC);
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $digitalProductFileTypes = ['audio', 'video', 'document', 'software'];
        $digitalProductAuthors = $this->authorService->getList(dataLimit: 'all');
        $publishingHouseList = $this->publishingHouseService->getList(dataLimit: 'all');

        return view('admin-views.product.add.index', compact('categories', 'brands', 'brandSetting', 'digitalProductSetting', 'colors', 'attributes', 'languages', 'defaultLanguage', 'digitalProductFileTypes', 'digitalProductAuthors', 'publishingHouseList', 'productWiseTax', 'taxVats'));
    }

    public function add(AddRequest $request): JsonResponse|RedirectResponse
    {
        if ($request->ajax()) {
            return response()->json([], 200);
        }
        $data = $request->all();
        $files = $request->allFiles();

        $this->productService->create(
            data: $data,
            files: $files,
            addedBy: ProductRetailService::ADDED_BY_ADMIN,
            userId: auth('admin')->id(),
            shopId: getInHouseShopConfig('id') ?? 0,
            colorNames: $this->colorService->getNameMap(),
            authorIds: $this->digitalAuthorIds($request),
            publishingHouseIds: $this->digitalPublishingHouseIds($request),
            taxIds: $request['tax_ids'] ?? [],
        );

        updateSetupGuideCacheKey(key: 'add_new_product', panel: 'admin');
        ToastMagic::success(translate(':item added successfully', ['item' => translate('Product')]));

        return redirect()->route('admin.retail.products.list', ['in_house']);
    }

    public function getUpdateView(string|int $id): View|RedirectResponse
    {
        $taxData = $this->getTaxSystemType();
        $productWiseTax = $taxData['productWiseTax'] && ! $taxData['is_included'];
        $taxVats = $taxData['taxVats'];

        $product = $this->productService->findUnscoped(id: $id, relations: ['digitalVariation', 'translations', 'seoInfo', 'digitalProductAuthors.author', 'digitalProductPublishingHouse.publishingHouse']);
        if (! $product) {
            ToastMagic::error(translate('Not found').'!');

            return redirect()->route('admin.retail.products.list', ['in_house']);
        }
        $productAuthorIds = $this->productService->getAuthorsInfo(product: $product)['ids'];
        $productPublishingHouseIds = $this->productService->getPublishingHouseInfo(product: $product)['ids'];

        $product['colors'] = json_decode($product['colors']);
        $categories = $this->categoryService->getList(dataLimit: 'all', position: CategoryService::POSITION_CATEGORY);
        $brands = $this->brandService->getList(dataLimit: 'all');
        $brandSetting = getWebConfig(name: 'product_brand');
        $digitalProductSetting = getWebConfig(name: 'digital_product');
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $colors = $this->colorService->getList(dataLimit: 'all', sortBy: ColorService::SORT_NAME_DESC);
        $attributes = $this->attributeService->getList(dataLimit: 'all', sortBy: AttributeService::SORT_NAME_DESC);
        $defaultLanguage = $languages[0];
        $digitalProductFileTypes = ['audio', 'video', 'document', 'software'];
        $digitalProductAuthors = $this->authorService->getList(dataLimit: 'all');
        $publishingHouseList = $this->publishingHouseService->getList(dataLimit: 'all');
        $taxVatIds = $product?->taxVats?->pluck('tax_id')->toArray() ?? [];

        return view('admin-views.product.update.index', compact('product', 'categories', 'brands', 'brandSetting', 'digitalProductSetting', 'colors', 'attributes', 'languages', 'defaultLanguage', 'digitalProductFileTypes', 'digitalProductAuthors', 'publishingHouseList', 'productAuthorIds', 'productPublishingHouseIds', 'productWiseTax', 'taxVats', 'taxVatIds'));
    }

    public function update(UpdateRequest $request, string|int $id): JsonResponse|RedirectResponse
    {
        if ($request->ajax()) {
            return response()->json([], 200);
        }
        $product = $this->productService->findUnscoped(id: $id, relations: ['digitalVariation', 'seoInfo', 'retailDetail']);

        if (! $product) {
            ToastMagic::error(translate('Invalid product'));

            return redirect()->route('admin.retail.products.list', ['in_house']);
        }

        $data = $request->all();
        $files = $request->allFiles();

        $this->productService->update(
            id: $id,
            data: $data,
            files: $files,
            updateBy: ProductRetailService::ADDED_BY_ADMIN,
            colorNames: $this->colorService->getNameMap(),
            authorIds: $this->digitalAuthorIds($request),
            publishingHouseIds: $this->digitalPublishingHouseIds($request),
            taxIds: $request['tax_ids'] ?? [],
        );

        $updatedProduct = $this->productService->find(id: $product['id'], relations: ['retailDetail']);
        $this->productService->updateRestockRequestListAndNotify(product: $product, updatedProduct: $updatedProduct);
        $this->deactivateClearanceSaleBelowDiscount(product: $updatedProduct);

        $cartList = $this->cartService->getCartsForProduct(productId: $product['id']);
        $this->cartService->updateShippingForCarts(cartList: $cartList);

        updateSetupGuideCacheKey(key: 'add_new_product', panel: 'admin');
        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Product')]));

        return redirect()->route('admin.retail.products.view', ['addedBy' => $product['added_by'] == 'admin' ? 'in-house' : $product['added_by'], 'id' => $product['id']]);
    }

    public function updateProductImages(EditImageRequest $request, int|string $id): RedirectResponse
    {
        $product = $this->productService->findUnscoped(id: $id);
        abort_if($product === null, 404);

        $this->productService->updateImages(
            id: $id,
            files: $request->allFiles(),
            removedImageNames: $request->removedImageNames(),
        );

        ToastMagic::success(translate('Product image updated successfully'));

        return back();
    }

    public function getView(string $addedBy, string|int $id): View|RedirectResponse
    {
        $productActive = $this->productService->find(id: $id, relations: ['digitalVariation', 'seoInfo']);
        if (! $productActive) {
            ToastMagic::error(translate('Not found').'!');

            return redirect()->route('admin.retail.products.list', ['in_house']);
        }

        $taxData = $this->getTaxSystemType();
        $productWiseTax = $taxData['productWiseTax'] && ! $taxData['is_included'];
        $productWiseTaxRelation = $productWiseTax ? ['taxVats' => function ($query) {
            return $query->with(['tax'])->wherehas('tax', function ($query) {
                return $query->where('is_active', 1);
            });
        }] : [];

        $isActive = $this->productService->findActiveBy(filters: ['id' => $id]);
        $relations = ['category', 'brand', 'reviews', 'rating', 'orderDetails', 'orderDelivered', 'digitalVariation', 'seoInfo', 'clearanceSale' => function ($query) {
            return $query->active();
        }];
        $relations = array_merge($productWiseTaxRelation, $relations);

        $product = $this->productService->findBy(filters: ['id' => $id], relations: ['translations', 'reviews', ...$relations]);

        $totalSellerProducts = $this->productService->getList(
            dataLimit: 'all',
            filters: ['added_by' => 'seller', 'seller_id' => $product['user_id']],
        );
        $totalAdminProducts = $this->productService->getList(dataLimit: 'all', filters: ['added_by' => 'in_house']);
        $product['priceSum'] = $product?->orderDelivered->sum('price') * $product?->orderDelivered->sum('qty');
        $product['total_qty_sold'] = $product?->orderDelivered->sum('qty');
        $product['discountSum'] = $product?->orderDelivered->sum('discount');
        $productColors = [];
        $colors = is_array($product['colors']) ? $product['colors'] : json_decode($product['colors'] ?? '', true);
        foreach ($colors as $color) {
            $getColor = $this->colorService->findBy(params: ['code' => $color]);
            if ($getColor) {
                $productColors[$getColor['name']] = $colors;
            }
        }
        $reviews = $this->reviewService->getList(dataLimit: getWebConfig(name: 'pagination_limit'), relations: ['customer', 'reply', 'product.seller.shop'], filters: ['product_id' => $id, 'delivery_man_id' => null]);

        return view('admin-views.product.view', compact('product', 'reviews', 'productActive', 'productColors', 'addedBy', 'isActive', 'totalSellerProducts', 'totalAdminProducts', 'productWiseTax'));
    }

    public function getSkuCombinationView(Request $request): JsonResponse
    {
        $product = $this->productService->find(id: $request['product_id'], relations: ['digitalVariation', 'seoInfo']);

        $combinations = $this->productService->getSkuCombinations(
            data: $request->all(),
            product: $product,
            colorNames: $this->colorService->getNameMap(),
        );

        $partial = $product ? '_edit-sku-combinations' : '_sku-combinations';

        return response()->json(['view' => view('admin-views.product.partials.'.$partial, [
            'combinations' => $combinations,
            'unitPrice' => $request['unit_price'],
            'colorsActive' => $request->has('colors_active') && $request->has('colors') && count($request['colors']) > 0 ? 1 : 0,
            'productName' => $request['name'][array_search('en', $request['lang'])],
        ])->render()]);
    }

    public function getColorWiseImageView(Request $request): JsonResponse
    {
        $product = $request['product_id']
            ? $this->productService->find(id: $request['product_id'], relations: ['retailDetail'])
            : null;

        return response()->json(['view' => view('admin-views.product.partials._color-wise-image-tiles', [
            'tiles' => $this->productService->getColorWiseImageTiles(
                colors: (array) ($request['colors'] ?? []),
                product: $product,
                removedImageNames: array_filter((array) ($request['removed_images'] ?? []), 'is_string'),
            ),
            'productId' => $request['product_id'],
            'isProductGallery' => (bool) $request['product_gallery'],
        ])->render()]);
    }

    public function getDigitalVariationCombinationView(Request $request): JsonResponse
    {
        $product = $this->productService->find(id: $request['product_id'], relations: ['digitalVariation', 'seoInfo']);
        $data = $request->all();

        $generateCombination = $this->productService->generateDigitalVariationCombination(
            data: $data,
            combinations: $this->productService->getDigitalVariationCombinations($data),
            product: $product,
        );

        return response()->json([
            'view' => view('admin-views.product.partials._digital-variation-combination', [
                'generateCombination' => $generateCombination,
                'unitPrice' => $request['unit_price'],
                'productName' => $request['name'][array_search('en', $request['lang'])],
                'digitalProductType' => $request['digital_product_type'],
                'product' => $product,
                'request' => $request,
            ])->render(),
            'combination_count' => count($generateCombination),
        ]);
    }

    public function deleteDigitalVariationFile(Request $request): JsonResponse
    {
        $variation = $this->digitalProductVariationService->findBy(params: ['product_id' => $request['product_id'], 'variant_key' => $request['variant_key']]);
        if ($variation) {
            $this->deleteFile(filePath: '/product/digital-product/'.$variation['file']);
            $this->digitalProductVariationService->updateBy(params: ['id' => $variation['id']], data: ['file' => null]);

            return response()->json([
                'status' => 1,
                'message' => translate('Deleted successfully'),
            ]);
        }

        return response()->json([
            'status' => 0,
            'message' => translate('Delete failed'),
        ]);
    }

    public function updateFeaturedStatus(Request $request): JsonResponse
    {
        abort_if($this->productService->find(id: $request['id']) === null, 404);

        $this->productService->toggleFeatured(productId: $request['id']);

        return response()->json([
            'status' => true,
            'message' => translate(':item updated successfully', ['item' => translate('Product featured status')]),
        ]);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $status = $request->get('status', 0);
        $productId = $request['id'];
        $success = (int) $this->productService->activate(id: $productId, status: (bool) $status);
        $data = $success ? true : null;

        return response()->json([
            'status' => $success,
            'data' => $data,
            'message' => $success ? translate(':item updated successfully', ['item' => translate('Status')]) : translate('Status update failed').' '.translate('Product must be approved'),
        ], 200);
    }

    public function getCategories(Request $request): JsonResponse
    {
        $parentId = $request['parent_id'];
        $businessModule = $this->productService->resolveModule($request['business_module'] ?? null);
        $filter = ['parent_id' => $parentId, 'business_module' => $businessModule];
        $categories = $this->categoryService->getList(dataLimit: 'all', filters: ['parent_id' => $parentId], position: null);
        $dropdown = $this->renderCategoryDropdown($categories, $request['sub_category']);

        $childCategories = '';
        if (count($categories) == 1) {
            $subCategories = $this->categoryService->getList(dataLimit: 'all', filters: ['parent_id' => $categories[0]['id']], position: null);
            $childCategories = $this->renderCategoryDropdown($subCategories, $request['sub_category']);
        }

        return response()->json([
            'select_tag' => $dropdown,
            'sub_categories' => count($categories) == 1 ? $childCategories : '',
        ]);
    }

    private function renderCategoryDropdown(object|array $categories, mixed $selectedId): string
    {
        return view('admin-views.product.partials._category-dropdown', [
            'categories' => $categories,
            'selectedId' => $selectedId,
        ])->render();
    }

    public function exportList(Request $request, string $type): BinaryFileResponse
    {
        $addedBy = $this->productService->resolveOwner($type);
        $sortBy = $request['filter_sort_by'] ?? ProductRetailService::SORT_LATEST;

        $taxData = $this->getTaxSystemType();
        $productWiseTax = $taxData['productWiseTax'] && ! $taxData['is_included'];
        $productWiseTaxRelation = $productWiseTax ? ['taxVats' => function ($query) {
            return $query->with(['tax'])->wherehas('tax', function ($query) {
                return $query->where('is_active', 1);
            });
        }] : [];

        $relations = ['category', 'brand', 'reviews', 'rating', 'orderDetails', 'orderDelivered', 'digitalVariation', 'seoInfo', 'translations', 'clearanceSale' => function ($query) {
            return $query->active();
        }];
        $relations = array_merge($productWiseTaxRelation, $relations);

        $products = $this->productService->getList(
            searchValue: $request['searchValue'],
            dataLimit: 'all',
            relations: $relations,
            filters: $this->getListFilters(
                request: $request,
                state: $this->productService->resolveRequestState($request['request_status']),
                addedBy: $addedBy,
                sellerId: $request['seller_id'],
            ),
            sortBy: $sortBy,
        );

        $category = (! empty($request['category_id']) && $request->has('category_id')) ? $this->categoryService->findBy(params: ['id' => $request['category_id']]) : 'all';
        $subCategory = (! empty($request->sub_category_id) && $request->has('sub_category_id')) ? $this->categoryService->findBy(params: ['id' => $request['sub_category_id']]) : 'all';
        $subSubCategory = (! empty($request->sub_sub_category_id) && $request->has('sub_sub_category_id')) ? $this->categoryService->findBy(params: ['id' => $request['sub_sub_category_id']]) : 'all';
        $brand = (! empty($request->brand_id) && $request->has('brand_id')) ? $this->brandService->findBy(params: ['id' => $request->brand_id]) : 'all';
        $seller = (! empty($request->seller_id) && $request->has('seller_id')) ? $this->vendorService->findBy(params: ['id' => $request->seller_id]) : '';
        $data = [
            'products' => $products,
            'category' => $category,
            'sub_category' => $subCategory,
            'sub_sub_category' => $subSubCategory,
            'brand' => $brand,
            'searchValue' => $request['searchValue'],
            'type' => $request->type ?? '',
            'seller' => $seller,
            'status' => $request->status ?? '',
            'productWiseTax' => $productWiseTax,
        ];

        return Excel::download(new ProductListExport($data), ucwords($request['type']).'-'.'product-list.xlsx');
    }

    public function getBarcodeView(Request $request, string|int $id): View|RedirectResponse
    {
        if ($request['limit'] > ProductRetailService::BARCODE_MAX_QUANTITY) {
            ToastMagic::warning(translate('You cannot generate more than 270 barcode'));

            return back();
        }

        $sheet = $this->productService->getBarcodeSheet(
            quantity: (int) ($request['limit'] ?? 4),
            layout: $request['layout'],
        );

        $product = $this->productService->find(id: $id, relations: ['seller.shop']);

        return view('admin-views.product.barcode', [
            'product' => $product,
            'barcodes' => $sheet['pages'],
            'layout' => $sheet['layout'],
            'defaultLayout' => ProductRetailService::BARCODE_LAYOUT_DEFAULT,
            'compactLayout' => ProductRetailService::BARCODE_LAYOUT_COMPACT,
            'shopName' => $product?->added_by === ProductRetailService::ADDED_BY_SELLER
                ? $product->seller?->shop?->name
                : getInHouseShopConfig(key: 'name'),
        ]);
    }

    public function getStockLimitListView(Request $request): View
    {
        $stockLimit = (int) getWebConfig(name: 'stock_limit');
        $searchValue = $request['searchValue'];

        $products = $this->productService->getStockLimitList(
            stockLimit: $stockLimit,
            searchValue: $searchValue,
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
            page: $request['page'],
            withCount: ['orderDetails'],
            filters: array_filter([
                'added_by' => ProductRetailService::ADDED_BY_IN_HOUSE,
                'product_type' => ProductRetailService::PRODUCT_TYPE_PHYSICAL,
                'brand_id' => array_values($request['filter_brand_ids'] ?? []),
                'status' => array_values($request['product_status'] ?? []),
                ...$this->getCategoryIdsForFilter($request),
            ], static fn ($value): bool => $value !== null && $value !== []),
            sortBy: $request['filter_sort_by'],
        )->appends($request->only([...self::PRODUCT_FILTER_KEYS, 'searchValue']));

        return view('admin-views.product.stock-limit-list', [
            'products' => $products,
            'productMeta' => $this->buildStockLimitMeta($products),
            'stockLimitNotice' => $this->stockLimitNotice($stockLimit),
            'searchValue' => $searchValue,
            'brands' => $this->brandService->getList(dataLimit: 'all', sortBy: BrandService::SORT_NEWEST_ID),
            'categories' => $this->categoryService->getList(dataLimit: 'all', relations: ['childes.childes'], position: CategoryService::POSITION_CATEGORY),
            'preservedFilterInputs' => $this->preservedFilterInputs($request),
            'hasActiveFilters' => $request->hasAny(self::PRODUCT_FILTER_KEYS),
        ]);
    }

    private function stockLimitNotice(int $stockLimit): string
    {
        return $stockLimit > 0
            ? translate('Products appear here when stock falls below the Business Settings stock limit of :limit.', ['limit' => $stockLimit])
            : translate('Products appear here once they are out of stock, because the Business Settings stock limit is zero.');
    }

    private function buildStockLimitMeta(iterable $products): array
    {
        $meta = [];

        foreach ($products as $product) {
            $meta[$product->id] = [
                'price' => setCurrencySymbol(
                    amount: usdToDefaultCurrency(amount: $product->unit_price),
                    currencyCode: getCurrencyCode(),
                ),
                'stockAlert' => (int) $product->current_stock <= 0 ? 'out-of-stock' : 'low-stock',
            ];
        }

        return $meta;
    }

    public function delete(string|int $id): RedirectResponse
    {
        $product = $this->productService->find(id: $id);
        $addedBy = $product['added_by'];

        if ($product) {
            $this->cartService->deleteBy(params: ['product_id' => $id]);
            $this->wishlistService->deleteBy(params: ['product_id' => $id]);
            $this->flashDealProductService->deleteBy(params: ['product_id' => $id]);
            $this->dealOfTheDayService->deleteBy(params: ['product_id' => $id]);
            $this->productService->delete(id: $id);
            $bannerIds = $this->bannerService->getList(filters: ['resource_type' => 'product', 'resource_id' => $product['id']])->pluck('id');
            $bannerIds->map(function ($bannerId) {
                $this->bannerService->update(id: $bannerId, data: ['published' => 0, 'resource_id' => null]);
            });
            ToastMagic::success(translate(':item removed successfully', ['item' => translate('Product')]));
        } else {
            ToastMagic::error(translate('Invalid product'));
        }
        if ($addedBy === 'admin') {
            return redirect()->route('admin.retail.products.list', ['type' => 'in_house']);
        } else {
            return redirect()->back();
        }
    }

    public function deleteRestock(string|int $id): RedirectResponse
    {
        $this->restockProductService->deleteBy(params: ['id' => $id]);
        $this->restockProductCustomerService->deleteBy(params: ['restock_product_id' => $id]);
        ToastMagic::success(translate(':item removed successfully', ['item' => translate('Product restock')]));

        return back();
    }

    public function getVariations(Request $request): JsonResponse
    {
        $product = $this->productService->find(id: $request['id']);

        abort_if($product === null, 404);

        $restockId = $request['restock_id'];
        $restockVariants = $this->restockProductService->getList(dataLimit: 'all', filters: ['product_id' => $request['id']])?->pluck('variant')->toArray() ?? [];

        return response()->json([
            'view' => view('admin-views.product.partials._update-stock', compact('product', 'restockId', 'restockVariants'))->render(),
        ]);
    }

    public function updateQuantity(Request $request): RedirectResponse
    {
        $stockCount = $request['current_stock'];

        if ($stockCount >= 0) {
            $product = $this->productService->find(id: $request['product_id'], relations: ['retailDetail']);

            DB::transaction(function () use ($request, $stockCount) {
                $this->productService->updateQuantity(
                    id: $request['product_id'],
                    currentStock: (int) $stockCount,
                );

                $this->productService->updateVariation(
                    productId: $request['product_id'],
                    variations: $this->productService->buildStockVariations($request->all()),
                );
            });

            $updatedProduct = $this->productService->find(id: $request['product_id'], relations: ['retailDetail']);
            $this->productService->updateRestockRequestListAndNotify(product: $product, updatedProduct: $updatedProduct);

            ToastMagic::success(translate('Product quantity updated successfully'));

            return back();
        }
        ToastMagic::warning(translate('Product quantity cannot be less than 0'));

        return back();
    }

    public function getBulkImportView(): View
    {
        return view('admin-views.product.bulk-import');
    }

    public function importBulkProduct(BulkImportRequest $request): RedirectResponse
    {
        $dataArray = $this->productService->getBulkImportData(
            file: $request->file('products_file'),
            addedBy: ProductRetailService::ADDED_BY_ADMIN,
            userId: auth('admin')->id(),
            shopId: getInHouseShopConfig('id') ?? 0,
        );

        if (! $dataArray['status']) {
            ToastMagic::error($this->bulkImportErrorMessage($dataArray['reason'], $dataArray['field']));

            return back();
        }

        $products = $this->productService->createMany(
            products: $dataArray['products'],
            codes: array_keys($dataArray['productsRetail']),
            retailByCode: $dataArray['productsRetail'],
        );
        $systemTaxSetup = SystemTaxSetup::where('is_active', 1)
            ->where('business_module', BusinessModule::RETAIL)
            ->where('is_default', 1)
            ->first();

        if ($systemTaxSetup) {
            $allTaxIds = collect($dataArray['productsTax'])->flatten()->map(function ($ids) {
                return explode(',', $ids);
            })->flatten()->unique();
            $validTaxIds = Tax::whereIn('id', $allTaxIds)->pluck('id')->toArray();
            $rowsToInsert = [];
            Taxable::where('tax_for', 'tax_for_orders')->where('taxable_type', Product::class)->whereIn('taxable_id', $products->pluck('id')->toArray() ?: [0])->delete();
            foreach ($products as $product) {
                $taxIds = explode(',', $dataArray['productsTax'][$product->code] ?? '');
                foreach ($taxIds as $taxId) {
                    if (in_array($taxId, $validTaxIds)) {
                        $rowsToInsert[] = [
                            'taxable_type' => Product::class,
                            'taxable_id' => $product->id,
                            'system_tax_setup_id' => $systemTaxSetup->id,
                            'tax_id' => $taxId,
                            'tax_for' => 'tax_for_orders',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
            if (! empty($rowsToInsert)) {
                Taxable::insert($rowsToInsert);
            }
        }
        ToastMagic::success(count($dataArray['products']).' - '.translate('Products imported successfully'));

        return back();
    }

    private function bulkImportErrorMessage(string $reason, ?string $field): string
    {
        return match ($reason) {
            ProductRetailService::REASON_INVALID_FILE => translate('You have uploaded a wrong format file').','.translate('Please upload the right file'),
            ProductRetailService::REASON_EMPTY_FILE => translate('You need to upload with proper data'),
            ProductRetailService::REASON_UNKNOWN_COLUMN => translate('Please upload the correct format file'),
            default => translate('Please fill :field fields', ['field' => $field]),
        };
    }

    public function updatedProductList(Request $request, ?string $type = null): View
    {
        $type = $type == 'vendor' ? ProductRetailService::ADDED_BY_SELLER : ProductRetailService::ADDED_BY_IN_HOUSE;
        $searchValue = $request['searchValue'];
        $taxData = $this->getTaxSystemType();
        $productWiseTax = $taxData['productWiseTax'] && ! $taxData['is_included'];

        $subCategories = collect();
        $subSubCategories = collect();
        $paginationLimit = getWebConfig(name: WebConfigKey::PAGINATION_LIMIT) ? getWebConfig(name: WebConfigKey::PAGINATION_LIMIT) : 25;
        $sellers = $this->vendorService->getList(dataLimit: $paginationLimit, relations: ['shop'], filters: [VendorService::FILTER_STATUS_EXCEPT => VendorService::STATUS_PENDING]);
        $brands = $this->brandService->getList(dataLimit: 'all', sortBy: BrandService::SORT_NEWEST_ID);
        $categories = $this->categoryService->getList(dataLimit: 'all', relations: ['childes.childes'], position: CategoryService::POSITION_CATEGORY);

        $products = $this->productService->getList(
            searchValue: $searchValue,
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
            filters: $this->getListFilters(
                request: $request,
                state: ProductRetailService::REQUEST_STATE_SHIPPING_UPDATE,
                addedBy: $type,
            ),
            sortBy: $request['filter_sort_by'] ?? ProductRetailService::SORT_LATEST,
        )->appends($request->query());

        return view('admin-views.product.updated-product-list', compact(
            'products',
            'searchValue',
            'sellers',
            'brands',
            'categories',
            'subCategories',
            'subSubCategories',
            'type',
            'productWiseTax'
        ));
    }

    public function updatedShipping(Request $request): JsonResponse
    {
        $this->productService->resolveShippingCostUpdate(id: $request['id'], approved: $request['status'] == 1);

        return response()->json(['message' => translate(':item updated successfully', ['item' => translate('Status')])], 200);
    }

    public function deny(DenyRequest $request): JsonResponse
    {
        $product = DB::transaction(function () use ($request) {
            $denied = $this->productService->deny(id: $request['id'], deniedNote: $request['denied_note']);

            if ($denied !== null) {
                $this->productService->updateFeatured(productId: $denied->id, featured: 0);
            }

            return $denied;
        });
        $vendor = $this->vendorService->findBy(params: ['id' => $product['user_id']]);
        if ($vendor['cm_firebase_token']) {
            ProductRequestStatusUpdateEvent::dispatch('product_request_rejected_message', 'seller', $vendor['app_language'] ?? getDefaultLanguage(), $vendor['cm_firebase_token']);
        }

        return response()->json(['message' => translate('Product request denied').'.']);
    }

    public function approveStatus(Request $request): JsonResponse
    {
        $product = $this->productService->toggleRequestStatus(id: $request['id']);
        $vendor = $this->vendorService->findBy(params: ['id' => $product['user_id']]);
        if ($vendor['cm_firebase_token']) {
            ProductRequestStatusUpdateEvent::dispatch('product_request_approved_message', 'seller', $vendor['app_language'] ?? getDefaultLanguage(), $vendor['cm_firebase_token']);
        }

        return response()->json(['message' => translate('Product request approved').'.']);
    }

    public function getSearchedProductsView(Request $request): JsonResponse
    {
        $searchValue = $request['searchValue'] ?? null;
        $products = $this->productService->getList(
            searchValue: $searchValue,
            dataLimit: 'all',
            filters: [
                'category_id' => $request['category_id'],
                'code' => $request['name'],
                'has_reviews' => true,
            ],
        );

        return response()->json([
            'count' => $products->count(),
            'result' => view('admin-views.partials._search-product', [
                'products' => $products,
                'showAllProducts' => $request->boolean('all_products_option', true),
            ])->render(),
        ]);
    }

    public function getSearchedAllProductsView(Request $request): JsonResponse
    {
        $searchValue = $request['searchValue'] ?? null;
        $products = $this->productService->getList(
            searchValue: $searchValue,
            dataLimit: 20,
            filters: [
                'status' => 1,
                'category_id' => $request['category_id'],
                'code' => $request['name'],
            ],
        );
        if (! empty($request['deal_id'])) {
            $flashDealProducts = $this->flashDealProductService->getList(dataLimit: 'all', filters: ['flash_deal_id' => $request['deal_id']])->pluck('product_id')->toArray();
            $filteredProducts = $products->filter(function ($product) use ($flashDealProducts) {
                return ! in_array($product->id, $flashDealProducts);
            });
            $products = $filteredProducts;
        }

        return response()->json([
            'count' => $products->count(),
            'result' => view('admin-views.partials._search-product', [
                'products' => $products,
                'showAllProducts' => $request->boolean('all_products_option', true),
            ])->render(),
        ]);
    }

    public function getProductGalleryView(Request $request): View
    {
        $searchValue = $request['searchValue'];
        $isInHouseGallery = $request['vendor_id'] == ProductRetailService::ADDED_BY_IN_HOUSE
            || $request['added_by'] == ProductRetailService::ADDED_BY_IN_HOUSE;
        $hasSelectedVendor = $request['vendor_id'] != 'all' && $request['vendor_id'] != null;

        $products = $this->productService->getList(
            searchValue: $searchValue,
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
            relations: [
                'clearanceSale' => fn ($query) => $query->active(),
                'taxVats' => fn ($query) => $query->with(['tax'])->whereHas('tax', fn ($query) => $query->where('is_active', 1)),
            ],
            filters: $this->getListFilters(
                request: $request,
                state: ProductRetailService::REQUEST_STATE_APPROVED,
                addedBy: match (true) {
                    ! $hasSelectedVendor => null,
                    $isInHouseGallery => ProductRetailService::ADDED_BY_IN_HOUSE,
                    default => ProductRetailService::ADDED_BY_SELLER,
                },
                sellerId: $isInHouseGallery ? null : $request['vendor_id'],
            ),
            sortBy: $request['filter_sort_by'] ?? ProductRetailService::SORT_LATEST,
        )->appends($request->query());

        $products->getCollection()->load(['brand', 'category', 'subCategory', 'digitalVariation', 'tags', 'retailDetail']);

        $galleryVariations = (new ProductGalleryVariations)->buildForProducts($products->getCollection());
        $vendors = $this->vendorService->getList(dataLimit: 'all', relations: ['shop'], filters: ['status' => VendorService::STATUS_APPROVED]);

        $productBrands = $this->brandService->getList(dataLimit: 'all', sortBy: BrandService::SORT_NEWEST_ID);

        $productStores = $this->shopService->getList(
            dataLimit: empty($request['filter_shop_ids']) ? 8 : 99,
            sortBy: ShopService::SORT_AUTHOR_TYPE_ASC,
        );

        $productCategories = $this->categoryService->getList(
            dataLimit: 'all',
            relations: ['childes.childes'],
            sortBy: CategoryService::SORT_NEWEST_ID,
            position: CategoryService::POSITION_CATEGORY,
        );

        return view('admin-views.product.product-gallery', [
            ...compact('products', 'vendors', 'productBrands', 'productCategories', 'searchValue', 'productStores', 'galleryVariations'),
            'hasActiveFilters' => $request->hasAny(self::PRODUCT_FILTER_KEYS),
        ]);
    }

    public function getStockLimitStatus(Request $request, string $type): JsonResponse
    {
        $products = $this->productService->getStockLimitList(
            stockLimit: getWebConfig(name: 'stock_limit'),
            dataLimit: 'all',
            filters: [
                'added_by' => $this->productService->resolveOwner($type),
                'product_type' => ProductRetailService::PRODUCT_TYPE_PHYSICAL,
            ],
        );
        if ($products->count() == 1) {
            $product = $products->first();
            $thumbnail = getStorageImages(path: $product->thumbnail_full_url, type: 'backend-product');

            return response()->json(['status' => 'one_product', 'product_count' => 1, 'product' => $product, 'thumbnail' => $thumbnail]);
        } else {
            return response()->json(['status' => 'multiple_product', 'product_count' => $products->count()]);
        }

    }

    public function getMultipleProductDetailsView(Request $request): JsonResponse
    {
        $selectedProducts = $this->productService->getList(
            dataLimit: 'all',
            filters: ['id' => $request['productIds']],
        );

        return response()->json([
            'result' => view('admin-views.partials._select-product', compact('selectedProducts'))->render(),
        ]);
    }

    public function deletePreviewFile(Request $request): JsonResponse
    {
        $this->productService->deletePreviewFile(productId: $request['product_id']);

        return response()->json([
            'status' => 1,
            'message' => translate('Preview file deleted'),
        ]);
    }

    public function getRequestRestockListView(Request $request): View|RedirectResponse
    {
        $filters = [
            'added_by' => 'in_house',
            'brand_id' => $request['brand_id'],
            'category_id' => $request['category_id'],
            'sub_category_id' => $request['sub_category_id'],
        ];

        $subCategories = collect();
        $dateRange = $this->restockDateRange($request);

        $restockProducts = $this->restockProductService->getList(
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
            relations: ['product'],
            withCount: ['restockProductCustomers'],
            filters: $this->restockFilters($filters, $request['searchValue'], $dateRange),
            sortBy: RestockProductService::SORT_RECENTLY_UPDATED,
        )->withQueryString();
        $brands = $this->brandService->getList(dataLimit: 'all', filters: ['status' => 1]);
        $categories = $this->categoryService->getList(dataLimit: 'all', position: CategoryService::POSITION_CATEGORY);
        $subCategory = $this->categoryService->findBy(params: ['id' => $request['sub_category_id']]);
        $totalRestockProducts = $this->restockProductService->getList(dataLimit: 'all', filters: $this->restockFilters($filters))->count();

        if ($request->has('category_id')) {
            $subCategories = $this->categoryService->getList(filters: ['parent_id' => $request['category_id']], position: null);
        }

        $isDateFiltered = $dateRange !== [];

        return view('admin-views.product.request-restock-list', compact(
            'restockProducts',
            'brands',
            'categories',
            'subCategory',
            'filters',
            'totalRestockProducts',
            'subCategories',
            'isDateFiltered',
        ));
    }

    public function exportRestockList(Request $request): BinaryFileResponse
    {
        $filters = [
            'added_by' => 'in_house',
            'brand_id' => $request['brand_id'],
            'category_id' => $request['category_id'],
            'sub_category_id' => $request['sub_category_id'],
        ];

        $dateRange = $this->restockDateRange($request);

        $restockProducts = $this->restockProductService->getList(
            dataLimit: 'all',
            relations: ['product'],
            withCount: ['restockProductCustomers'],
            filters: $this->restockFilters($filters, $request['searchValue'], $dateRange),
            sortBy: RestockProductService::SORT_RECENTLY_UPDATED,
        );
        $brand = (! empty($request->brand_id) && $request->has('brand_id')) ? $this->brandService->findBy(params: ['id' => $request->brand_id]) : 'all';
        $category = (! empty($request['category_id']) && $request->has('category_id')) ? $this->categoryService->findBy(params: ['id' => $request['category_id']]) : 'all';
        $subCategory = (! empty($request->sub_category_id) && $request->has('sub_category_id')) ? $this->categoryService->findBy(params: ['id' => $request['sub_category_id']]) : 'all';

        $data = [
            'products' => $restockProducts,
            'category' => $category,
            'subCategory' => $subCategory,
            'brand' => $brand,
            'searchValue' => $request['searchValue'],
            'startDate' => $dateRange[0] ?? '',
            'endDate' => $dateRange[1] ?? '',
        ];

        return Excel::download(new RestockProductListExport($data), 'restock-product-list.xlsx');
    }
}
