<?php

namespace App\Http\Controllers\Vendor\Coupon;

use App\Exports\CouponListExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\Coupon\AddRequest;
use App\Http\Requests\Vendor\Coupon\UpdateRequest;
use App\Services\New\CouponService;
use App\Services\New\CustomerService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CouponController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly CustomerService $customerService,
    ) {}

    public function index(Request $request): View
    {
        $searchValue = $request->get('searchValue');
        $coupons = $this->couponService->getList(
            searchValue: $searchValue,
            dataLimit: getWebConfig(name: 'pagination_limit'),
            withCount: ['order'],
            filters: $this->visibleFilters(),
        );

        return view('vendor-views.coupon.index', [
            'coupons' => $coupons,
            'searchValue' => $searchValue,
            'vendorId' => $this->vendorId(),
        ] + $this->getFormData());
    }

    public function add(AddRequest $request): RedirectResponse
    {
        $this->couponService->create(
            data: $request->validated(),
            sellerId: $this->vendorId(),
            addedBy: CouponService::ADDED_BY_SELLER,
        );

        ToastMagic::success(translate('Coupon added successfully'));

        return redirect()->route('vendor.retail.coupon.index');
    }

    public function getUpdateView(string|int $id): View|RedirectResponse
    {
        $coupon = $this->couponService->find(id: $id, filters: $this->ownedFilters());

        if ($coupon === null) {
            ToastMagic::warning(translate('Not found'));

            return redirect()->route('vendor.retail.coupon.index');
        }

        return view('vendor-views.coupon.update-view', ['coupon' => $coupon] + $this->getFormData());
    }

    public function update(UpdateRequest $request, string|int $id): RedirectResponse
    {
        $vendorId = $this->vendorId();
        $coupon = $this->couponService->find(id: $id, filters: $this->ownedFilters());

        if ($coupon === null) {
            ToastMagic::warning(translate('Not found'));

            return redirect()->route('vendor.retail.coupon.index');
        }

        $this->couponService->update(id: $coupon->id, data: $request->validated(), sellerId: $vendorId);

        ToastMagic::success(translate('Coupon updated successfully'));

        return redirect()->route('vendor.retail.coupon.index');
    }

    public function updateStatus(string|int $id, string|int $status): JsonResponse
    {
        $coupon = $this->couponService->find(id: $id, filters: $this->ownedFilters());

        if ($coupon === null) {
            return response()->json([
                'status' => 0,
                'message' => translate('Not found'),
            ]);
        }

        $this->couponService->updateStatus(id: $coupon->id, status: (bool) $status);

        return response()->json([
            'status' => 1,
            'message' => translate('Coupon status updated successfully'),
        ]);
    }

    public function delete(string|int $id): RedirectResponse
    {
        $coupon = $this->couponService->find(id: $id, filters: $this->ownedFilters());

        if ($coupon === null) {
            ToastMagic::warning(translate('Not found'));

            return redirect()->back();
        }

        $this->couponService->delete(id: $coupon->id);
        ToastMagic::success(translate('Coupon deleted successfully'));

        return redirect()->back();
    }

    public function getQuickView(Request $request): JsonResponse
    {
        $coupon = $this->couponService->find(id: $request['id'], filters: $this->visibleFilters());

        if ($coupon === null) {
            return response()->json(['view' => '']);
        }

        return response()->json([
            'view' => view('vendor-views.coupon.quick-view', compact('coupon'))->render(),
        ]);
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $coupons = $this->couponService->getList(
            searchValue: $request->get('searchValue'),
            dataLimit: 'all',
            withCount: ['order'],
            filters: $this->visibleFilters(),
        );

        return Excel::download(new CouponListExport([
            'data-from' => 'vendor',
            'vendor' => auth('seller')->user(),
            'coupon' => $coupons,
            'search' => $request->get('searchValue'),
        ]), 'Coupon-list.xlsx');
    }

    private function vendorId(): int
    {
        return auth('seller')->id();
    }

    private function ownedFilters(): array
    {
        return [
            'added_by' => CouponService::ADDED_BY_SELLER,
            'coupon_bearer' => CouponService::BEARER_SELLER,
            'seller_id' => $this->vendorId(),
        ];
    }

    private function visibleFilters(): array
    {
        return [CouponService::FILTER_VISIBLE_TO_VENDOR => $this->vendorId()];
    }

    private function getFormData(): array
    {
        return [
            'couponTypes' => $this->couponService->getCouponTypes(addedBy: CouponService::ADDED_BY_SELLER),
            'discountTypes' => $this->couponService->getDiscountTypes(),
            'fieldVisibility' => $this->couponService->getFieldVisibility(),
            'usageLimitRange' => ['min' => CouponService::MIN_USAGE_LIMIT, 'max' => CouponService::MAX_USAGE_LIMIT],
            'customers' => $this->customerService->getOptions(excludeIds: [CouponService::CUSTOMER_ALL]),
        ];
    }
}
