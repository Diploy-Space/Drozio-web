<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CustomerService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ReferEarnController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
        private readonly CustomerAuthService $customerAuthService,
    ) {}

    public function index(): View|RedirectResponse
    {
        if (! (getWebConfig(name: BusinessSettingService::REFERRAL_EARNING_STATUS) ?? 0)) {
            ToastMagic::error(translate('You have no permission'));

            return redirect('/');
        }

        $customerId = (int) auth('customer')->id();
        $customer = $this->customerService->find(id: $customerId);

        if (empty($customer['referral_code'])) {
            $this->customerService->updateWhere(
                params: ['id' => $customerId],
                data: ['referral_code' => $this->customerAuthService->generateReferralCode()],
            );
            $customer = $this->customerService->find(id: $customerId);
        }

        return view(themeViewName(key: 'refer_earn'), [
            'customer_detail' => $customer,
        ]);
    }
}
