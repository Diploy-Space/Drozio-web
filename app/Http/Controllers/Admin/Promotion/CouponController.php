<?php

namespace App\Http\Controllers\Admin\Promotion;

use App\Exports\CouponListExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Promotion\Coupon\CouponStoreRequest;
use App\Http\Requests\Admin\Promotion\Coupon\CouponUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\CouponService;
use App\Services\New\CustomerService;
use App\Services\New\VendorService;
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
        private readonly BusinessSettingService $businessSettingService,
        private readonly VendorService $vendorService,
        private readonly CustomerService $customerService,
    ) {}

    public function getAddListView(Request $request): View
    {
        $coupons = $this->couponService->getList(
            searchValue: $request->get('searchValue'),
            dataLimit: $this->businessSettingService->findBy(params: ['type' => 'pagination_limit'])?->value,
            withCount: ['order'],
        );

        return view('admin-views.coupon.add-new', ['coupons' => $coupons] + $this->getFormData());
    }

    public function getUpdateView(string|int $id): View|RedirectResponse
    {
        $coupon = $this->couponService->find(id: $id);

        if ($coupon === null) {
            ToastMagic::error(translate('Invalid Coupon'));

            return redirect()->route('admin.retail.coupon.add');
        }

        return view('admin-views.coupon.edit', [
            'coupon' => $coupon,
            'selectedVendor' => $coupon->seller_id ?? CouponService::VENDOR_INHOUSE,
        ] + $this->getFormData($coupon->coupon_bearer));
    }

    public function add(CouponStoreRequest $request): JsonResponse
    {
        $this->couponService->create(data: $request->validated());

        return response()->json([
            'status' => 1,
            'message' => translate('Coupon added successfully'),
            'redirect_url' => route('admin.retail.coupon.add'),
        ]);
    }

    public function update(CouponUpdateRequest $request, string|int $id): JsonResponse
    {
        $coupon = $this->couponService->update(id: $id, data: $request->validated());

        if ($coupon === null) {
            return response()->json([
                'status' => 0,
                'message' => translate('Invalid Coupon'),
            ]);
        }

        return response()->json([
            'status' => 1,
            'message' => translate('Coupon updated successfully'),
            'redirect_url' => route('admin.retail.coupon.add'),
        ]);
    }

    public function updateStatus(Request $request): JsonResponse|RedirectResponse
    {
        $isUpdated = $this->couponService->updateStatus(id: $request['id'], status: (bool) $request->get('status', 0));
        $message = $isUpdated ? translate('Coupon status updated') : translate('Invalid Coupon');

        if ($request->ajax()) {
            return response()->json(['status' => (int) $isUpdated, 'message' => $message]);
        }

        $isUpdated ? ToastMagic::success($message) : ToastMagic::error($message);

        return back();
    }

    public function quickView(Request $request): JsonResponse
    {
        $coupon = $this->couponService->find(id: $request['id']);

        if ($coupon === null) {
            return response()->json(['view' => '']);
        }

        return response()->json([
            'view' => view('admin-views.coupon.details-quick-view', compact('coupon'))->render(),
        ]);
    }

    public function delete(string|int $id): RedirectResponse
    {
        $this->couponService->delete(id: $id)
            ? ToastMagic::success(translate('Coupon deleted successfully'))
            : ToastMagic::error(translate('Invalid Coupon'));

        return redirect()->back();
    }

    public function getVendorList(Request $request): JsonResponse
    {
        $vendors = $this->couponService->getVendorOptions(
            vendors: $this->vendorService->getShopOptions(),
            bearer: $request['coupon_bearer'],
        );

        return response()->json(view('admin-views.coupon.partials._vendor-options', compact('vendors'))->render());
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $coupons = $this->couponService->getList(
            searchValue: $request->get('searchValue'),
            dataLimit: 'all',
            withCount: ['order'],
        );

        return Excel::download(new CouponListExport([
            'coupon' => $coupons,
            'search' => $request->get('searchValue'),
        ]), 'Coupon-list.xlsx');
    }

    private function getFormData(?string $bearer = null): array
    {
        return [
            'couponTypes' => $this->couponService->getCouponTypes(),
            'couponBearers' => $this->couponService->getCouponBearers(),
            'discountTypes' => $this->couponService->getDiscountTypes(),
            'fieldVisibility' => $this->couponService->getFieldVisibility(),
            'vendors' => $this->couponService->getVendorOptions(
                vendors: $this->vendorService->getShopOptions(),
                bearer: $bearer,
            ),
            'customers' => $this->customerService->getOptions(excludeIds: [CouponService::CUSTOMER_ALL]),
            'usageLimitRange' => ['min' => CouponService::MIN_USAGE_LIMIT, 'max' => CouponService::MAX_USAGE_LIMIT],
        ];
    }
}
