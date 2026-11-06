<?php

namespace App\Http\Controllers\Web\v6;

use App\Enums\BusinessModule;
use App\Http\Controllers\Controller;
use App\Services\New\CouponService;
use App\Services\New\OrderService;
use Illuminate\Contracts\View\View;

class CustomerCouponController extends Controller
{
    private const LIST_LIMIT = 8;

    private const BUSINESS_MODE_SINGLE = 'single';

    public function __construct(
        private readonly CouponService $couponService,
        private readonly OrderService $orderService,
    ) {}

    public function index(): View
    {
        $customerId = (int) auth('customer')->id();

        $coupons = $this->couponService->getList(
            dataLimit: self::LIST_LIMIT,
            relations: ['seller.shop'],
            filters: [
                'status' => CouponService::STATUS_ACTIVE,
                CouponService::FILTER_RUNNING => true,
                'customer_id' => [$customerId, CouponService::CUSTOMER_ALL],
                CouponService::FILTER_FIRST_ORDER_EXCLUDED => $this->orderService->count(filters: ['customer_id' => $customerId]) > 0,
            ] + $this->businessModeFilters(),
            sortBy: CouponService::SORT_OLDEST,
        );

        return view(themeViewName(key: 'user_coupons'), [
            'coupons' => $coupons,
            'moduleTabs' => customerProfileModuleTabs(menuKey: 'coupons', activeModule: BusinessModule::RETAIL),
        ]);
    }

    private function businessModeFilters(): array
    {
        return getWebConfig(name: 'business_mode') == self::BUSINESS_MODE_SINGLE
            ? ['added_by' => CouponService::ADDED_BY_ADMIN]
            : [];
    }
}
