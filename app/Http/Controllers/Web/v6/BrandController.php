<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Brand\ListRequest;
use App\Services\New\BrandService;
use App\Services\New\RobotsMetaContentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BrandController extends Controller
{
    private const BRAND_STATUS_SETTING = 'product_brand';

    private const BRAND_STATUS_ACTIVE = 1;

    /** @var array<string, int> */
    private const PAGE_SIZES = ['theme_aster' => 14, 'theme_fashion' => 10];

    private const DEFAULT_PAGE_SIZE = 14;

    public function __construct(
        private readonly BrandService $brandService,
        private readonly RobotsMetaContentService $robotsMetaContentService,
    ) {}

    public function index(ListRequest $request): View|RedirectResponse
    {
        $robotsMetaContentData = $this->robotsMetaContentService->findByPageNameOrDefault(
            pageName: RobotsMetaContentService::PAGE_NAME_BRANDS,
        );

        $brandStatus = getWebConfig(name: self::BRAND_STATUS_SETTING);
        session()->put(self::BRAND_STATUS_SETTING, $brandStatus);

        if ($brandStatus != self::BRAND_STATUS_ACTIVE) {
            return redirect()->route('home');
        }

        $searchKeyword = $request['search'];
        $selectedOrder = $request['order_by'];
        $orderBy = $selectedOrder ?? ListRequest::ORDER_DESCENDING;
        $sortLinks = $this->getSortLinks(searchKeyword: $searchKeyword, selectedOrder: $selectedOrder);

        return view(themeViewName(key: 'all_brands'), [
            'brands' => $this->brandService->getList(
                dataLimit: self::PAGE_SIZES[theme_root_path()] ?? self::DEFAULT_PAGE_SIZE,
                withCount: ['brandProducts'],
                filters: [
                    'status' => self::BRAND_STATUS_ACTIVE,
                    BrandService::FILTER_NAME_LIKE => $searchKeyword,
                ],
                sortBy: $this->resolveSort($request, $orderBy),
                appends: ['order_by' => $orderBy, 'search' => $searchKeyword],
            ),
            'robotsMetaContentData' => $robotsMetaContentData,
            'searchKeyword' => $searchKeyword,
            'selectedOrder' => $selectedOrder,
            'sortLinks' => $sortLinks,
            'activeSortLabel' => collect($sortLinks)->firstWhere('active', true)['label'],
        ]);
    }

    private function resolveSort(ListRequest $request, string $orderBy): string
    {
        if (empty($request['order_by']) && $this->brandService->hasCustomListPriority()) {
            return BrandService::SORT_PRIORITY;
        }

        return $orderBy === ListRequest::ORDER_ASCENDING
            ? BrandService::SORT_NAME_ASC_LATEST
            : BrandService::SORT_NAME_DESC_LATEST;
    }

    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    private function getSortLinks(?string $searchKeyword, ?string $selectedOrder): array
    {
        $orderLabels = [
            null => translate('Default'),
            ListRequest::ORDER_ASCENDING => translate('A-Z'),
            ListRequest::ORDER_DESCENDING => translate('Z-A'),
        ];

        return collect($orderLabels)->map(fn (string $label, string $order): array => [
            'label' => $label,
            'url' => route('brands', array_filter(['search' => $searchKeyword, 'order_by' => $order])),
            'active' => $order === (string) $selectedOrder,
        ])->values()->all();
    }
}
