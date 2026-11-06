<?php

namespace App\Http\Controllers\Vendor\Auth;

use App\Enums\SessionKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\Auth\LoginRequest;
use App\Services\New\Auth\VendorAuthService;
use App\Services\New\VendorWalletService;
use App\Services\RecaptchaService;
use App\Traits\RecaptchaTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    use RecaptchaTrait;

    public function __construct(
        private readonly VendorAuthService $vendorAuthService,
        private readonly VendorWalletService $sellerWalletService,
    ) {
        $this->middleware('guest:seller', ['except' => ['logout']]);
    }

    public function getLoginView(): View
    {
        $recaptchaBuilder = $this->generateDefaultReCaptcha(4);
        $recaptcha = getWebConfig(name: 'recaptcha');
        Session::put(SessionKey::VENDOR_RECAPTCHA_KEY, $recaptchaBuilder->getPhrase());
        $isVendorRegistrationOpen = getWebConfig(name: 'business_mode') !== 'single' && (bool) getWebConfig(name: 'seller_registration');

        return view('vendor-views.auth.login', compact('recaptchaBuilder', 'recaptcha', 'isVendorRegistrationOpen'));
    }

    public function login(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $result = RecaptchaService::verificationStatus(request: $request, session: SessionKey::VENDOR_RECAPTCHA_KEY, action: 'login');

        if ($result && ! $result['status']) {
            return $this->loginRejected(request: $request, message: $result['message']);
        }

        $vendor = $this->vendorAuthService->findByIdentity(identity: $request['email']);

        if ($vendor === null || ! $this->vendorAuthService->verifyPassword(account: $vendor, password: $request['password'])) {
            return $this->loginRejected(request: $request, message: translate('Your login details are not correct').'!');
        }

        if ($this->vendorAuthService->accountStatusReason(account: $vendor) !== null) {
            return $this->loginRejected(request: $request, message: translate('Not approve yet').'!');
        }

        auth('seller')->login($vendor, (bool) $request['remember']);
        $this->sellerWalletService->createForSeller(sellerId: $vendor->id);

        ToastMagic::info(translate('Welcome to your dashboard').'.');

        return $this->loginAccepted(request: $request);
    }

    private function loginAccepted(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $dashboard = businessModuleDashboardUrl();

        return $request->expectsJson() || $request->ajax()
            ? response()->json(['redirect_url' => $dashboard])
            : redirect($dashboard);
    }

    private function loginRejected(LoginRequest $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message], 422);
        }

        ToastMagic::error($message);

        return back();
    }

    public function logout(): RedirectResponse
    {
        $vendor = auth('seller')->user();

        if ($vendor !== null) {
            $this->vendorAuthService->clearWebPushToken(id: $vendor->id);
        }

        auth('seller')->logout();
        session()->invalidate();

        ToastMagic::success(translate('Logged out successfully').'.');

        return redirect()->route('vendor.auth.login');
    }
}
