<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Enums\SessionKey;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Services\New\Auth\AdminAuthService;
use App\Services\New\BusinessSettingService;
use App\Services\RecaptchaService;
use App\Traits\RecaptchaTrait;
use Carbon\CarbonInterval;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    use RecaptchaTrait;

    private const DEFAULT_MAX_LOGIN_HIT = 5;

    private const DEFAULT_BLOCK_SECONDS = 600;

    public function __construct(
        private readonly AdminAuthService $adminAuthService,
        private readonly BusinessSettingService $businessSettingService,
    ) {
        $this->middleware('guest:admin', ['except' => ['logout']]);
    }

    public function index(?Request $request, ?string $type = null): View
    {
        $loginTypes = [
            UserRole::ADMIN => getWebConfig(name: 'admin_login_url'),
            UserRole::EMPLOYEE => getWebConfig(name: 'employee_login_url'),
        ];

        $userType = array_search($type, $loginTypes);
        abort_if(! $userType, 404);

        $recaptchaBuilder = $this->generateDefaultReCaptcha(4);
        Session::put(SessionKey::ADMIN_RECAPTCHA_KEY, $recaptchaBuilder->getPhrase());

        $recaptcha = getWebConfig(name: 'recaptcha');

        return view('admin-views.auth.login', compact('recaptchaBuilder', 'recaptcha'))->with(['role' => $userType]);
    }

    public function generateReCaptcha(): Response
    {
        $recaptchaBuilder = $this->generateDefaultReCaptcha(4);
        if (Session::has(SessionKey::ADMIN_RECAPTCHA_KEY)) {
            Session::forget(SessionKey::ADMIN_RECAPTCHA_KEY);
        }
        Session::put(SessionKey::ADMIN_RECAPTCHA_KEY, $recaptchaBuilder->getPhrase());

        return response($recaptchaBuilder->get(), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $sessionKey = $request['role'] === UserRole::ADMIN ? SessionKey::ADMIN_RECAPTCHA_KEY : SessionKey::EMPLOYEE_RECAPTCHA_KEY;
        $result = RecaptchaService::verificationStatus(request: $request, session: $sessionKey, action: 'login');

        if ($result && ! $result['status']) {
            return $this->loginRejected(request: $request, message: $result['message']);
        }

        $identity = $request['email'];
        $maxAttempts = $this->maxLoginHit();
        $blockSeconds = $this->loginBlockSeconds();

        $retryAfter = $this->adminAuthService->secondsUntilLoginAllowed(
            identity: $identity,
            account: null,
            maxAttempts: $maxAttempts,
            blockSeconds: $blockSeconds,
        );

        if ($retryAfter > 0) {
            return $this->loginRejected(
                request: $request,
                message: translate('Too many attempts. Please try again after').CarbonInterval::seconds($retryAfter)->cascade()->forHumans(),
            );
        }

        $admin = $this->adminAuthService->findByIdentity(identity: $identity);

        if ($admin === null || ! $this->adminAuthService->verifyPassword(account: $admin, password: $request['password'])) {
            $this->adminAuthService->registerLoginFailure(
                identity: $identity,
                account: null,
                maxAttempts: $maxAttempts,
                blockSeconds: $blockSeconds,
            );

            return $this->credentialsRejected(request: $request);
        }

        if ($this->adminAuthService->accountStatusReason(account: $admin) !== null) {
            return $this->credentialsRejected(request: $request);
        }

        $expectedRole = $this->adminAuthService->loginRoleFor(account: $admin);

        if ($request['role'] !== $expectedRole) {
            return $this->loginRejected(
                request: $request,
                message: $expectedRole === UserRole::ADMIN
                    ? translate('Please login from the admin login page')
                    : translate('Please login from the employee login page'),
            );
        }

        auth('admin')->login($admin, (bool) $request['remember']);
        $this->adminAuthService->clearLoginFailures(identity: $identity, account: null);

        $dashboard = businessModuleDashboardUrl();

        return $request->expectsJson() || $request->ajax()
            ? response()->json(['redirect_url' => $dashboard])
            : redirect($dashboard);
    }

    public function logout(): RedirectResponse
    {
        $loginUrlKey = $this->adminAuthService->loginRoleFor(account: auth('admin')->user()) === UserRole::ADMIN
            ? 'admin_login_url'
            : 'employee_login_url';

        auth('admin')->logout();
        session()->invalidate();
        session()->flash('success', translate('Logged out successfully'));

        return redirect('login/'.getWebConfig(name: $loginUrlKey));
    }

    private function credentialsRejected(LoginRequest $request): RedirectResponse|JsonResponse
    {
        return $this->loginRejected(
            request: $request,
            message: translate('Credentials do not match or your account has been suspended'),
        );
    }

    private function loginRejected(LoginRequest $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message], 422);
        }

        ToastMagic::error($message);

        return redirect()->back()->withInput($request->only('email', 'remember'));
    }

    private function maxLoginHit(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: 'maximum_login_hit', default: self::DEFAULT_MAX_LOGIN_HIT);
    }

    private function loginBlockSeconds(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: 'temporary_login_block_time', default: self::DEFAULT_BLOCK_SECONDS);
    }
}
