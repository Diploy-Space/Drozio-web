<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Enums\SessionKey;
use App\Enums\UserRole;
use App\Enums\UserType;
use App\Events\AdminPasswordResetEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\ForgotPasswordRequest;
use App\Http\Requests\Admin\Auth\PasswordResetRequest;
use App\Services\New\Auth\AdminAuthService;
use App\Services\New\Auth\PasswordResetService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly AdminAuthService $adminAuthService,
        private readonly PasswordResetService $passwordResetService,
    ) {
        $this->middleware('guest:admin');
    }

    public function getForgotPasswordView(Request $request): View
    {
        if (in_array($request['role'], [UserRole::ADMIN, UserRole::EMPLOYEE], true)) {
            session()->put(SessionKey::ADMIN_FORGOT_PASSWORD_ROLE, $request['role']);
        }

        return view('admin-views.auth.forgot-password.index', ['loginUrl' => $this->loginUrl()]);
    }

    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = $request['email'];
        $admin = $this->adminAuthService->findByIdentity(identity: $email);

        if ($admin !== null && $this->adminAuthService->accountStatusReason(account: $admin) === null) {
            $token = $this->passwordResetService->issueResetLinkToken(identity: $email, userType: UserType::ADMIN);

            event(new AdminPasswordResetEvent(
                email: $email,
                resetUrl: route('admin.auth.forgot-password.reset-password', ['token' => $token]),
            ));
        }

        ToastMagic::success(translate('If that email belongs to an account, a password reset link is on its way'));

        return back();
    }

    public function getPasswordResetView(Request $request): View|RedirectResponse
    {
        $reset = $this->passwordResetService->findByResetToken(token: (string) $request['token'], userType: UserType::ADMIN);

        if ($reset === null || $this->passwordResetService->isLinkTokenExpired(reset: $reset)) {
            ToastMagic::error(translate('This password reset link is no longer valid'));

            return redirect($this->loginUrl());
        }

        return view('admin-views.auth.forgot-password.reset-password', ['token' => $request['token']]);
    }

    public function resetPassword(PasswordResetRequest $request): RedirectResponse
    {
        $reset = $this->passwordResetService->findByResetToken(token: $request['reset_token'], userType: UserType::ADMIN);

        if ($reset === null || $this->passwordResetService->isLinkTokenExpired(reset: $reset)) {
            ToastMagic::error(translate('This password reset link is no longer valid'));

            return redirect($this->loginUrl());
        }

        $admin = $this->adminAuthService->findByIdentity(identity: $reset->identity);

        if ($admin === null) {
            ToastMagic::error(translate('This password reset link is no longer valid'));

            return redirect($this->loginUrl());
        }

        $this->adminAuthService->updatePassword(id: $admin->id, password: $request['password']);
        $this->adminAuthService->clearLoginFailures(identity: $reset->identity, account: null);
        $this->passwordResetService->deleteByIdentity(identity: $reset->identity, userType: UserType::ADMIN);

        ToastMagic::success(translate('Password changed successfully'));

        return redirect($this->loginUrl(admin: $admin));
    }

    private function loginUrl(mixed $admin = null): string
    {
        $role = $admin === null
            ? session(SessionKey::ADMIN_FORGOT_PASSWORD_ROLE, UserRole::ADMIN)
            : $this->adminAuthService->loginRoleFor(account: $admin);

        return 'login/'.getWebConfig(name: $role === UserRole::EMPLOYEE ? 'employee_login_url' : 'admin_login_url');
    }
}
