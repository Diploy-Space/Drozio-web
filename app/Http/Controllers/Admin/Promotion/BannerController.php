<?php

namespace App\Http\Controllers\Admin\Promotion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Promotion\Banner\BannerStoreRequest;
use App\Http\Requests\Admin\Promotion\Banner\BannerUpdateRequest;
use App\Services\New\BannerService;
use App\Services\New\BrandService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CategoryService;
use App\Services\New\ProductRetailService;
use App\Services\New\ShopService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    private const FILTER_KEYS = ['searchValue', 'banner_type', 'published', 'sortBy'];

    public function __construct(
        private readonly BannerService $bannerService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly CategoryService $categoryService,
        private readonly BrandService $brandService,
        private readonly ProductRetailService $productService,
        private readonly ShopService $shopService,
    ) {}

    public function index(Request $request): View
    {
        $formData = $this->getFormData();
        $banners = $this->bannerService->getList(
            searchValue: $request->get('searchValue'),
            dataLimit: $this->businessSettingService->findBy(params: ['type' => 'pagination_limit'])?->value,
            relations: ['storage'],
            filters: $this->listFilters($request, $formData['bannerTypes']),
            sortBy: $this->selectedSortBy($request),
        )->appends($request->only(self::FILTER_KEYS));

        return view('admin-views.banner.view', [
            'banners' => $banners,
            ...$formData,
            ...$this->filterPanelData($request),
        ]);
    }

    private function listFilters(Request $request, array $bannerTypes): array
    {
        $selectedType = $request->get('banner_type');

        return array_filter([
            'banner_type' => is_string($selectedType) && array_key_exists($selectedType, $bannerTypes) ? $selectedType : array_keys($bannerTypes),
            'published' => $this->selectedPublished($request),
        ], static fn ($value): bool => $value !== null && $value !== []);
    }

    private function selectedSortBy(Request $request): ?string
    {
        $sortBy = $request->get('sortBy');

        return is_string($sortBy) && array_key_exists($sortBy, $this->bannerService->getSortTypes()) ? $sortBy : null;
    }

    private function selectedPublished(Request $request): ?int
    {
        $published = $request->get('published');

        return in_array($published, ['0', '1'], true) ? (int) $published : null;
    }

    private function filterPanelData(Request $request): array
    {
        return [
            'sortTypes' => $this->bannerService->getSortTypes(),
            'publishedTypes' => $this->bannerService->getPublishedTypes(),
            'selectedSortBy' => $this->selectedSortBy($request),
            'selectedBannerType' => $request->get('banner_type'),
            'selectedPublished' => $this->selectedPublished($request),
            'hasActiveFilters' => $request->hasAny(self::FILTER_KEYS),
            'preservedFilterInputs' => $this->preservedFilterInputs($request),
            'filterAction' => url()->current(),
        ];
    }

    /** @return array<int, array{name: string, value: string}> */
    private function preservedFilterInputs(Request $request): array
    {
        $inputs = [];

        foreach (self::FILTER_KEYS as $key) {
            $value = $request->get($key);

            if ($value !== null && $value !== '') {
                $inputs[] = ['name' => $key, 'value' => (string) $value];
            }
        }

        return $inputs;
    }

    public function getUpdateView(string|int $id): View|RedirectResponse
    {
        $banner = $this->bannerService->find(id: $id);

        if ($banner === null) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route('admin.retail.banner.list');
        }

        return view('admin-views.banner.edit', ['banner' => $banner] + $this->getFormData());
    }

    public function add(BannerStoreRequest $request): RedirectResponse
    {
        $resource = $this->resolveResource($request);

        if ($resource['error'] !== null) {
            ToastMagic::error($resource['error']);

            return redirect()->route('admin.retail.banner.list');
        }

        $this->bannerService->create(
            data: $request->validated() + $request->all(),
            bannerUrl: $resource['url'],
            image: $request->file('image'),
        );

        ToastMagic::success(translate('Banner added successfully'));

        return redirect()->route('admin.retail.banner.list');
    }

    public function update(BannerUpdateRequest $request, string|int $id): RedirectResponse
    {
        $resource = $this->resolveResource($request);

        if ($resource['error'] !== null) {
            ToastMagic::error($resource['error']);

            return redirect()->route('admin.retail.banner.list');
        }

        $banner = $this->bannerService->update(
            id: $id,
            data: $request->validated() + $request->all(),
            bannerUrl: $resource['url'],
            image: $request->file('image'),
        );

        if ($banner === null) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route('admin.retail.banner.list');
        }

        ToastMagic::success(translate('Banner updated successfully'));

        return redirect()->route('admin.retail.banner.list');
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $status = (bool) $request->get('status', 0);
        $isUpdated = $this->bannerService->updateStatus(id: $request['id'], status: $status);

        if (! $isUpdated) {
            return response()->json(['message' => translate('Not found')], 404);
        }

        return response()->json([
            'message' => $status ? translate('Banner published successfully') : translate('Banner unpublished successfully'),
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $isDeleted = $this->bannerService->delete(id: $request['id']);

        if (! $isDeleted) {
            return response()->json(['message' => translate('Not found')], 404);
        }

        return response()->json(['message' => translate('Banner deleted successfully')]);
    }

    /** @return array{url: ?string, error: ?string} */
    private function resolveResource(Request $request): array
    {
        $resourceType = $request['resource_type'];
        $resourceId = $request[$resourceType.'_id'] ?? null;

        $resolved = $this->bannerService->resolveResource(
            resourceType: $resourceType,
            resourceId: $resourceId,
            resource: $this->findResource($resourceType, $resourceId),
            customUrl: $request['url'],
        );

        return [
            'url' => $resolved['url'],
            'error' => $resolved['reason'] === null ? null : $this->resourceErrorMessage($resourceType, $resolved['reason']),
        ];
    }

    private function resourceErrorMessage(?string $resourceType, string $reason): string
    {
        if ($reason === BannerService::REASON_RESOURCE_MISSING) {
            return match ($resourceType) {
                BannerService::RESOURCE_PRODUCT => translate('Please select a product.'),
                BannerService::RESOURCE_CATEGORY => translate('Please select a category.'),
                BannerService::RESOURCE_SHOP => translate('Please select a shop.'),
                BannerService::RESOURCE_BRAND => translate('Please select a brand.'),
                default => translate('Please select a resource.'),
            };
        }

        return match ($resourceType) {
            BannerService::RESOURCE_PRODUCT => translate('The product does not exist.'),
            BannerService::RESOURCE_CATEGORY => translate('The category does not exist.'),
            BannerService::RESOURCE_SHOP => translate('The shop does not exist.'),
            BannerService::RESOURCE_BRAND => translate('The brand does not exist.'),
            default => translate('The resource does not exist.'),
        };
    }

    private function findResource(?string $resourceType, mixed $resourceId): ?Model
    {
        if (empty($resourceId)) {
            return null;
        }

        return match ($resourceType) {
            BannerService::RESOURCE_PRODUCT => $this->productService->find(id: $resourceId),
            BannerService::RESOURCE_SHOP => $this->shopService->find(id: $resourceId),
            BannerService::RESOURCE_CATEGORY => $this->categoryService->find(id: $resourceId, relations: []),
            BannerService::RESOURCE_BRAND => $this->brandService->find(id: $resourceId, relations: []),
            default => null,
        };
    }

    private function getFormData(): array
    {
        return [
            'bannerTypes' => $this->bannerService->getBannerTypes(),
            'resourceTypes' => $this->bannerService->getResourceTypes(),
            'categories' => $this->categoryService->getListForParentSelect(),
            'brands' => $this->brandService->getList(dataLimit: 'all'),
            'products' => $this->productService->getActiveOptions(),
            'shops' => $this->shopService->getVendorOptions(),
        ];
    }
}
