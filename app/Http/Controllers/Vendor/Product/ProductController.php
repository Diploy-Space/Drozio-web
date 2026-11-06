<?php

namespace App\Http\Controllers\Vendor\Product;

use App\Enums\WebConfigKey;
use App\Exports\ProductListExport;
use App\Exports\RestockProductListExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Shared\Product\AddRequest;
use App\Http\Requests\Shared\Product\EditImageRequest;
use App\Http\Requests\Shared\Product\UpdateRequest;
use App\Http\Requests\Vendor\Product\BulkImportRequest;
use App\Services\New\AttributeService;
use App\Services\New\AuthorService;
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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\AI\app\Services\AIUsageManagerService;
use Modules\TaxModule\app\Traits\VatTaxManagement;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends BaseController
{
    use FileManagerTrait {
        delete as deleteFile;
        update as updateFile;
    }
    use VatTaxManagement;

    public function __construct(
        private readonly AuthorService $authorService,
        private readonly PublishingHouseService $publishingHouseService,
        private readonly BrandService $brandService,
        private readonly DigitalProductVariationService $digitalProductVariationService,
        private readonly RestockProductService $restockProductService,
        private readonly RestockProductCustomerService $restockProductCustomerService,
        private readonly AttributeService $attributeService,
        private readonly ReviewService $reviewService,
        private readonly WishlistService $wishlistService,
        private readonly FlashDealProductService $flashDealProductService,
        private readonly DealOfTheDayService $dealOfTheDayService,
        private readonly VendorService $vendorService,
        private readonly ShopService $shopService,
        private readonly ProductRetailService $productService,
        private readonly StockClearanceProductService $stockClearanceProductService,
        private readonly ColorService $colorService,
        private readonly AIUsageManagerService $AIUsageManagerService,
        private readonly CategoryService $categoryService,
        private readonly CartService $cartService,
    ) {}

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

    private function getListFilters(Request $request, string $state, mixed $sellerId): array
    {
        return $this->productService->listFilters(
            state: $state,
            addedBy: ProductRetailService::ADDED_BY_SELLER,
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

    public function index(?Request $request, string|array|null $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        $vendorId = auth('seller')->id();
        $taxData = $this->getTaxSystemType();
        $productWiseTax = $taxData['productWiseTax'] && ! $taxData['is_included'];
        $searchValue = $request['searchValue'];

        $products = $this->productService->getList(
            searchValue: $searchValue,
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT) ?: DEFAULT_DATA_LIMIT,
            relations: [
                'translations',
                'seoInfo',
                'clearanceSale' => fn ($query) => $query->active(),
                'taxVats' => fn ($query) => $query->with(['tax'])->whereHas('tax', fn ($query) => $query->where('is_active', 1)),
            ],
            filters: $this->getListFilters(
                request: $request,
                state: $this->productService->resolveRequestState($type),
                sellerId: $vendorId,
            ),
            sortBy: $request['filter_sort_by'] ?? ProductRetailService::SORT_LATEST,
        )->appends($request->query());
        $brands = $this->brandService->getList(dataLimit: 'all', sortBy: BrandService::SORT_NEWEST_ID);
        $categories = $this->categoryService->getList(dataLimit: 'all', relations: ['childes.childes'], position: CategoryService::POSITION_CATEGORY);

        return view('vendor-views.product.list', compact('products', 'type', 'searchValue', 'brands',
            'categories', 'productWiseTax'));
    }

    public function getRequestRestockListView(Request $request): View|RedirectResponse
    {
        $filters = [
            'added_by' => 'seller',
            'seller_id' => auth('seller')->id(),
            'brand_id' => $request['brand_id'],
            'category_id' => $request['category_id'],
            'sub_category_id' => $request['sub_category_id'],
        ];

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
        $subCategoryList = $this->categoryService->getList(dataLimit: 'all', filters: ['parent_id' => $request['category_id']], position: null);
        $totalRestockProducts = $this->restockProductService->getList(dataLimit: 'all', filters: $this->restockFilters($filters))->count();

        $isDateFiltered = $dateRange !== [];

        return view('vendor-views.product.request-restock-list', compact('restockProducts', 'brands',
            'categories', 'subCategory', 'filters', 'totalRestockProducts', 'subCategoryList', 'isDateFiltered'));
    }

    public function deleteRestock(string|int $id): RedirectResponse
    {
        $this->restockProductService->deleteBy(params: ['id' => $id]);
        $this->restockProductCustomerService->deleteBy(params: ['restock_product_id' => $id]);
        ToastMagic::success(translate(':item removed successfully', ['item' => translate('Product restock')]));

        return back();
    }

    public function getAddView(): View
    {
        $taxData = $this->getTaxSystemType();
        $productWiseTax = $taxData['productWiseTax'] && ! $taxData['is_included'];
        $taxVats = $taxData['taxVats'];

        $categories = $this->categoryService->getList(dataLimit: 'all', position: CategoryService::POSITION_CATEGORY);
        $brands = $this->brandService->getList(dataLimit: 'all', filters: ['status' => 1]);
        $brandSetting = getWebConfig(name: 'product_brand');
        $digitalProductSetting = getWebConfig(name: 'digital_product');
        $colors = $this->colorService->getList(dataLimit: 'all', sortBy: ColorService::SORT_NAME_DESC);
        $attributes = $this->attributeService->getList(dataLimit: 'all', sortBy: AttributeService::SORT_NAME_DESC);
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $digitalProductFileTypes = ['audio', 'video', 'document', 'software'];
        $digitalProductAuthors = $this->authorService->getList(dataLimit: 'all');
        $publishingHouseList = $this->publishingHouseService->getList(dataLimit: 'all');
        $aiRemainingCount = $this->AIUsageManagerService->getGenerateRemainingCount();

        return view('vendor-views.product.add-new', compact('aiRemainingCount', 'categories', 'brands', 'brandSetting', 'digitalProductSetting', 'colors', 'attributes', 'languages', 'defaultLanguage', 'digitalProductFileTypes', 'digitalProductAuthors', 'publishingHouseList', 'productWiseTax', 'taxVats'));
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
            addedBy: ProductRetailService::ADDED_BY_SELLER,
            userId: auth('seller')->id(),
            shopId: $this->shopService->findBy(params: ['seller_id' => auth('seller')->id()])?->id ?? 0,
            colorNames: $this->colorService->getNameMap(),
            authorIds: $this->digitalAuthorIds($request),
            publishingHouseIds: $this->digitalPublishingHouseIds($request),
            taxIds: $request['tax_ids'] ?? [],
        );

        ToastMagic::success(translate(':item added successfully', ['item' => translate('Product')]));

        return redirect()->route('vendor.retail.products.list', ['type' => 'all']);
    }

    public function getUpdateView(string|int $id): RedirectResponse|View
    {
        $taxData = $this->getTaxSystemType();
        $productWiseTax = $taxData['productWiseTax'] && ! $taxData['is_included'];
        $taxVats = $taxData['taxVats'];

        $product = $this->productService->findBy(filters: ['id' => $id, 'user_id' => auth('seller')->id(), 'added_by' => 'seller'], relations: ['translations', 'reviews', 'seoInfo', 'digitalProductAuthors', 'digitalProductPublishingHouse']);
        if (! $product) {
            ToastMagic::error(translate('Invalid product'));

            return redirect()->route('vendor.retail.products.list', ['type' => 'all']);
        }
        $productAuthorIds = $this->productService->getAuthorsInfo(product: $product)['ids'];
        $productPublishingHouseIds = $this->productService->getPublishingHouseInfo(product: $product)['ids'];

        $product['colors'] = json_decode($product['colors']);
        $categories = $this->categoryService->getList(dataLimit: 'all', position: CategoryService::POSITION_CATEGORY);
        $brands = $this->brandService->getList(dataLimit: 'all', filters: ['status' => 1]);
        $brandSetting = getWebConfig(name: 'product_brand');
        $digitalProductSetting = getWebConfig(name: 'digital_product');
        $colors = $this->colorService->getList(dataLimit: 'all', sortBy: ColorService::SORT_NAME_DESC);
        $attributes = $this->attributeService->getList(dataLimit: 'all', sortBy: AttributeService::SORT_NAME_DESC);
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $digitalProductFileTypes = ['audio', 'video', 'document', 'software'];
        $digitalProductAuthors = $this->authorService->getList(dataLimit: 'all');
        $publishingHouseList = $this->publishingHouseService->getList(dataLimit: 'all');
        $taxVatIds = $product?->taxVats?->pluck('tax_id')->toArray() ?? [];
        $aiRemainingCount = $this->AIUsageManagerService->getGenerateRemainingCount();

        return view('vendor-views.product.edit', compact('product', 'categories', 'aiRemainingCount', 'brands', 'brandSetting', 'digitalProductSetting', 'colors', 'attributes', 'languages', 'defaultLanguage', 'digitalProductFileTypes', 'digitalProductAuthors', 'publishingHouseList', 'productAuthorIds', 'productPublishingHouseIds', 'productWiseTax', 'taxVats', 'taxVatIds'));
    }

    public function update(UpdateRequest $request, string|int $id): JsonResponse|RedirectResponse
    {
        if ($request->ajax()) {
            return response()->json([], 200);
        }

        $product = $this->productService->findBy(
            filters: ['id' => $id, 'user_id' => auth('seller')->id(), 'added_by' => ProductRetailService::ADDED_BY_SELLER],
            relations: ['translations', 'reviews', 'seoInfo', 'retailDetail'],
        );

        if (! $product) {
            ToastMagic::error(translate('Invalid product'));

            return redirect()->route('vendor.retail.products.list', ['type' => 'all']);
        }

        $data = $request->all();
        $files = $request->allFiles();

        $this->productService->update(
            id: $id,
            data: $data,
            files: $files,
            updateBy: ProductRetailService::ADDED_BY_SELLER,
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

        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Product')]));

        return redirect()->route('vendor.retail.products.view', ['id' => $product['id']]);
    }

    public function updateProductImages(EditImageRequest $request, int|string $id): RedirectResponse
    {
        $product = $this->productService->findBy(
            filters: ['id' => $id, 'user_id' => auth('seller')->id(), 'added_by' => ProductRetailService::ADDED_BY_SELLER],
            relations: ['translations', 'reviews'],
        );
        abort_if($product === null, 404);

        $this->productService->updateImages(
            id: $id,
            files: $request->allFiles(),
            removedImageNames: $request->removedImageNames(),
        );

        ToastMagic::success(translate('Product image updated successfully'));

        return back();
    }

    public function getView(string|int $id): View|RedirectResponse
    {
        $vendorId = auth('seller')->id();
        $productActive = $this->productService->findActiveBy(filters: ['id' => $id, 'user_id' => $vendorId]);

        $taxData = $this->getTaxSystemType();
        $productWiseTax = $taxData['productWiseTax'] && ! $taxData['is_included'];
        $productWiseTaxRelation = $productWiseTax ? ['taxVats' => function ($query) {
            return $query->with(['tax'])->wherehas('tax', function ($query) {
                return $query->where('is_active', 1);
            });
        }] : [];

        $relations = ['category', 'brand', 'reviews', 'rating', 'orderDetails', 'orderDelivered', 'translations', 'seoInfo', 'clearanceSale' => function ($query) {
            return $query->active();
        }];
        $relations = array_merge($productWiseTaxRelation, $relations);

        $product = $this->productService->findBy(filters: ['id' => $id, 'user_id' => $vendorId], relations: ['reviews', ...$relations]);
        if (! $product) {
            return redirect()->route('vendor.retail.products.list', ['type' => 'all']);
        }
        $isActive = $this->productService->findActiveBy(filters: ['id' => $id]);
        $product['priceSum'] = $product?->orderDelivered->sum('price');
        $product['total_qty_sold'] = $product?->orderDelivered->sum('qty');
        $product['discountSum'] = $product?->orderDelivered->sum('discount');
        $totalSellerProducts = $this->productService->getList(
            dataLimit: 'all',
            filters: ['added_by' => 'seller', 'seller_id' => $product['user_id']],
        );
        $productColors = [];
        $colors = json_decode($product['colors'], true);
        foreach ($colors as $color) {
            $getColor = $this->colorService->findBy(params: ['code' => $color]);
            if ($getColor) {
                $productColors[$getColor['name']] = $colors;
            }
        }

        $reviews = $this->reviewService->getList(dataLimit: getWebConfig(name: 'pagination_limit'), filters: ['product_id' => $id, 'delivery_man_id' => null], sortBy: ReviewService::SORT_LATEST);

        return view('vendor-views.product.view', compact('product', 'reviews', 'productActive', 'productColors', 'isActive', 'totalSellerProducts', 'productWiseTax'));
    }

    public function exportList(Request $request, string $type): BinaryFileResponse
    {
        $vendorId = auth('seller')->id();
        $vendor = $this->vendorService->findBy(params: ['id' => $vendorId]);
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

        $searchValue = $request['searchValue'];
        $products = $this->productService->getList(
            searchValue: $searchValue,
            dataLimit: 'all',
            relations: $relations,
            filters: $this->getListFilters(
                request: $request,
                state: $this->productService->resolveRequestState($type),
                sellerId: $vendorId,
            ),
            sortBy: $request['filter_sort_by'] ?? ProductRetailService::SORT_LATEST,
        );

        $category = (! empty($request['category_id']) && $request->has('category_id')) ? $this->categoryService->findBy(params: ['id' => $request['category_id']]) : 'all';
        $subCategory = (! empty($request->sub_category_id) && $request->has('sub_category_id')) ? $this->categoryService->findBy(params: ['id' => $request['sub_category_id']]) : 'all';
        $subSubCategory = (! empty($request->sub_sub_category_id) && $request->has('sub_sub_category_id')) ? $this->categoryService->findBy(params: ['id' => $request['sub_sub_category_id']]) : 'all';
        $brand = (! empty($request->brand_id) && $request->has('brand_id')) ? $this->brandService->findBy(params: ['id' => $request->brand_id]) : 'all';
        $seller = (! empty($request->seller_id) && $request->has('seller_id')) ? $this->vendorService->findBy(params: ['id' => $request->seller_id]) : '';
        $data = [
            'data-from' => 'vendor',
            'vendor' => $vendor,
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

    public function exportRestockList(Request $request): BinaryFileResponse
    {
        $vendorId = auth('seller')->id();
        $filters = [
            'added_by' => 'seller',
            'seller_id' => $vendorId,
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
            ? $this->productService->findBy(
                filters: ['id' => $request['product_id'], 'user_id' => auth('seller')->id(), 'added_by' => ProductRetailService::ADDED_BY_SELLER],
                relations: ['translations', 'reviews', 'retailDetail'],
            )
            : null;

        return response()->json(['view' => view('vendor-views.product.partials._color-wise-image-tiles', [
            'tiles' => $this->productService->getColorWiseImageTiles(
                colors: (array) ($request['colors'] ?? []),
                product: $product,
                removedImageNames: array_filter((array) ($request['removed_images'] ?? []), 'is_string'),
            ),
            'productId' => $product?->id,
            'isProductGallery' => (bool) $request['product_gallery'],
        ])->render()]);
    }

    public function getDigitalVariationCombinationView(Request $request): JsonResponse
    {
        $product = $this->productService->find(id: $request['product_id'], relations: ['digitalVariation']);
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

    public function updateStatus(Request $request): JsonResponse
    {
        $status = $request['status'];
        $productId = $request['id'];
        $product = $this->productService->findBy(filters: ['id' => $productId, 'user_id' => auth('seller')->id()]);
        $success = 0;

        if ($status == 1 && $product['request_status'] == 1) {
            $this->productService->activate(id: $productId, status: true);
            $success = 1;
        } elseif ($status != 1) {
            $this->productService->activate(id: $productId, status: (bool) ($status ?? 0));
            $success = 1;
        }

        return response()->json([
            'success' => $success,
            'message' => $success ? translate(':item updated successfully', ['item' => translate('Status')]) : translate('Status update failed').' '.translate('Product must be approved'),
        ], 200);
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

        return view('vendor-views.product.barcode', [
            'product' => $this->productService->findBy(filters: ['id' => $id, 'user_id' => auth('seller')->id()]),
            'barcodes' => $sheet['pages'],
            'layout' => $sheet['layout'],
            'defaultLayout' => ProductRetailService::BARCODE_LAYOUT_DEFAULT,
            'compactLayout' => ProductRetailService::BARCODE_LAYOUT_COMPACT,
        ]);
    }

    public function delete(string|int $id): RedirectResponse
    {
        $product = $this->productService->findBy(filters: ['id' => $id, 'user_id' => auth('seller')->id()]);

        if ($product) {
            $this->cartService->deleteBy(params: ['product_id' => $id]);
            $this->wishlistService->deleteBy(params: ['product_id' => $id]);
            $this->flashDealProductService->deleteBy(params: ['product_id' => $id]);
            $this->dealOfTheDayService->deleteBy(params: ['product_id' => $id]);

            $this->productService->delete(id: $id);

            ToastMagic::success(translate(':item removed successfully', ['item' => translate('Product')]));
        } else {
            ToastMagic::error(translate('Invalid product'));
        }

        return back();
    }

    public function getStockLimitListView(Request $request): View
    {
        $sortOrderQty = $request['sortOrderQty'];
        $searchValue = $request['searchValue'];
        $withCount = ['orderDetails'];
        $status = $request['status'];
        $vendor = auth('seller')->user();
        $stockLimit = $vendor['stock_limit'] <= 0 ? getWebConfig(name: 'stock_limit') : $vendor['stock_limit'];
        $products = $this->productService->getStockLimitList(
            stockLimit: $stockLimit,
            searchValue: $searchValue,
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT) ?: DEFAULT_DATA_LIMIT,
            relations: ['translations'],
            withCount: $withCount,
            filters: $this->productService->listFilters(
                state: ProductRetailService::REQUEST_STATE_APPROVED,
                addedBy: ProductRetailService::ADDED_BY_SELLER,
                sellerId: $vendor['id'],
                catalogFilters: ['product_type' => ProductRetailService::PRODUCT_TYPE_PHYSICAL],
            ),
            sortBy: $sortOrderQty,
        )->withQueryString();
        $sortUrlPrefix = route('vendor.retail.products.stock-limit-list', array_filter(['searchValue' => $searchValue, 'sortOrderQty' => ''], static fn ($value) => $value !== null));

        return view('vendor-views.product.stock-limit-list', compact('products', 'searchValue', 'status', 'sortOrderQty', 'stockLimit', 'sortUrlPrefix'));
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

    public function getVariations(Request $request): JsonResponse
    {
        $product = $this->productService->find(id: $request['id']);

        abort_if($product === null, 404);

        $restockId = $request['restock_id'];
        $restockVariants = $this->restockProductService->getList(dataLimit: 'all', filters: ['product_id' => $request['id']])?->pluck('variant')->toArray() ?? [];

        return response()->json([
            'view' => view('vendor-views.product.partials._update-stock', compact('product', 'restockId', 'restockVariants'))->render(),
        ]);
    }

    public function getBulkImportView(): View
    {
        return view('vendor-views.product.bulk-import');
    }

    public function importBulkProduct(BulkImportRequest $request): RedirectResponse
    {
        $dataArray = $this->productService->getBulkImportData(
            file: $request->file('products_file'),
            addedBy: ProductRetailService::ADDED_BY_SELLER,
            userId: auth('seller')->id(),
            shopId: $this->shopService->findBy(params: ['seller_id' => auth('seller')->id()])?->id ?? 0,
        );

        if (! $dataArray['status']) {
            ToastMagic::error($this->bulkImportErrorMessage($dataArray['reason'], $dataArray['field']));

            return back();
        }

        $this->productService->createMany(
            products: $dataArray['products'],
            codes: array_keys($dataArray['productsRetail']),
            retailByCode: $dataArray['productsRetail'],
        );

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

    public function getSearchedProductsView(Request $request): JsonResponse
    {
        $searchValue = $request['searchValue'] ?? null;
        $products = $this->productService->getList(
            searchValue: $searchValue,
            dataLimit: 'all',
            filters: [
                'added_by' => 'seller',
                'seller_id' => auth('seller')->id(),
                'category_id' => $request['category_id'],
                'code' => $request['name'] ?? null,
            ],
        );

        return response()->json([
            'count' => $products->count(),
            'result' => view('vendor-views.partials._search-product', compact('products'))->render(),
        ]);
    }

    public function getProductGalleryView(Request $request): View
    {
        $vendorId = auth('seller')->id();
        $searchValue = $request['searchValue'];

        $products = $this->productService->getList(
            searchValue: $searchValue,
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT) ?: DEFAULT_DATA_LIMIT,
            relations: [
                'clearanceSale' => fn ($query) => $query->active(),
                'taxVats' => fn ($query) => $query->with(['tax'])->whereHas('tax', fn ($query) => $query->where('is_active', 1)),
            ],
            filters: $this->getListFilters(
                request: $request,
                state: ProductRetailService::REQUEST_STATE_APPROVED,
                sellerId: $vendorId,
            ),
            sortBy: $request['filter_sort_by'] ?? ProductRetailService::SORT_LATEST,
        )->appends($request->query());

        $products->getCollection()->load(['brand', 'category', 'subCategory', 'digitalVariation', 'tags', 'retailDetail']);

        $galleryVariations = (new ProductGalleryVariations)->buildForProducts($products->getCollection());

        $brands = $this->brandService->getList(dataLimit: 'all', sortBy: BrandService::SORT_NEWEST_ID);
        $categories = $this->categoryService->getList(
            dataLimit: 'all',
            relations: ['childes.childes'],
            sortBy: CategoryService::SORT_NEWEST_ID,
            position: CategoryService::POSITION_CATEGORY,
        );

        return view('vendor-views.product.product-gallery', [
            ...compact('products', 'brands', 'categories', 'searchValue', 'galleryVariations'),
            'hasActiveFilters' => $request->hasAny(self::PRODUCT_FILTER_KEYS),
        ]);

    }

    public function getStockLimitStatus(Request $request): JsonResponse
    {
        $vendor = auth('seller')->user();
        $stockLimit = $vendor['stock_limit'] <= 0 ? getWebConfig(name: 'stock_limit') : $vendor['stock_limit'];
        $products = $this->productService->getStockLimitList(
            stockLimit: $stockLimit,
            dataLimit: 'all',
            filters: $this->productService->listFilters(
                state: ProductRetailService::REQUEST_STATE_APPROVED,
                addedBy: ProductRetailService::ADDED_BY_SELLER,
                sellerId: $vendor['id'],
                catalogFilters: ['product_type' => ProductRetailService::PRODUCT_TYPE_PHYSICAL],
            ),
        );
        if ($products->count() == 1) {
            $product = $products->first();
            $thumbnail = getStorageImages(path: $product->thumbnail_full_url, type: 'backend-product');

            return response()->json(['status' => 'one_product', 'product_count' => 1, 'product' => $product, 'thumbnail' => $thumbnail]);
        } else {
            return response()->json(['status' => 'multiple_product', 'product_count' => $products->count()]);
        }
    }

    public function deletePreviewFile(Request $request): JsonResponse
    {
        $this->productService->deletePreviewFile(productId: $request['product_id']);

        return response()->json([
            'status' => 1,
            'message' => translate('Preview file deleted'),
        ]);
    }

    public function loadMoreBrands(Request $request): JsonResponse
    {
        $oldBrands = $request['filter_brand_old_ids'] ? json_decode($request['filter_brand_old_ids']) : [];
        $page = $request->input('page', 1);
        $filterBrands = $this->brandService->getList(
            dataLimit: 8,
            page: $page,
            sortBy: BrandService::SORT_NAME_ASC,
        );

        $visibleLimit = $filterBrands->perPage();
        $totalBrands = $filterBrands->total();
        $hiddenCount = $totalBrands - ($page * $visibleLimit);

        return response()->json([
            'html' => view('vendor-views.partials.product-filters._filter-brands', [
                'filterBrands' => $filterBrands,
                'oldBrands' => $oldBrands,
            ])->render(),
            'visibleLimit' => $visibleLimit,
            'hiddenCount' => max(0, $hiddenCount),
            'totalBrands' => $totalBrands,
        ]);
    }
}
