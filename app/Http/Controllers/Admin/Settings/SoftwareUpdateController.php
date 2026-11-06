<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\System\SoftwareUpdateRequest;
use App\Services\New\SoftwareUpdateService;
use App\Traits\ActivationClass;
use App\Traits\SettingsTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SoftwareUpdateController extends BaseController
{
    use ActivationClass;
    use SettingsTrait;

    private const ADMIN_PANEL = 'admin_panel';

    private const ENV_SOFTWARE_ID = 'MzE0NDg1OTc=';

    private const SOFTWARE_TYPE = 'cHJvZHVjdA==';

    private const LIVE_MODE = 'live';

    private const SESSION_LIFETIME = '60';

    private const COMPLETE_UPDATE_PATH = 'complete-update';

    private const DEBUGBAR_SERVICE = 'debugbar';

    public function __construct(
        private readonly SoftwareUpdateService $softwareUpdateService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return view('admin-views.system-setup.software-update');
    }

    public function update(SoftwareUpdateRequest $request): RedirectResponse|JsonResponse
    {
        if (config('app.mode') == 'demo') {
            return $this->updateResponse(
                request: $request,
                status: false,
                message: translate('Uploading ZIP files is currently unavailable in demo mode'),
            );
        }

        $this->storePurchaseCredentials(
            name: $request['name'],
            email: $request['email'],
            username: $this->withoutWhitespace(value: $request['username']),
            purchaseKey: $this->withoutWhitespace(value: $request['purchase_key']),
        );

        if (! $this->isActivatedPurchase(request: $request)) {
            return $this->updateResponse(request: $request, status: false, message: translate('Verification failed try again'));
        }

        $this->softwareUpdateService->storePackage(package: $request->file('update_file'));

        $installedVersion = envFileValue('SOFTWARE_VERSION');

        if (! $this->softwareUpdateService->isNewerPackage(installedVersion: $installedVersion)) {
            return $this->updateResponse(
                request: $request,
                status: false,
                message: $this->outdatedPackageMessage(packageVersion: $this->softwareUpdateService->getPackageVersion(), installedVersion: $installedVersion),
            );
        }

        $this->stopDebugbarCollecting();

        if (! $this->softwareUpdateService->applyPackage()) {
            return $this->updateResponse(request: $request, status: false, message: translate('The update file could not be extracted'));
        }

        $this->storeUpdatedEnvironment();

        return $this->updateResponse(
            request: $request,
            status: true,
            message: translate('Files updated, finishing the update'),
            redirectUrl: url(self::COMPLETE_UPDATE_PATH.'/'.$this->softwareUpdateService->issueCompletionToken()),
        );
    }

    private function outdatedPackageMessage(?string $packageVersion, ?string $installedVersion): string
    {
        if ($packageVersion === null) {
            return translate('This file is not a valid software update package');
        }

        return translate('This update file contains version :uploaded but version :installed is already installed. Upload a newer version.', [
            'uploaded' => $packageVersion,
            'installed' => $installedVersion,
        ]);
    }

    private function stopDebugbarCollecting(): void
    {
        if (app()->bound(self::DEBUGBAR_SERVICE)) {
            app(self::DEBUGBAR_SERVICE)->disable();
        }
    }

    private function isActivatedPurchase(SoftwareUpdateRequest $request): bool
    {
        $response = $this->getRequestConfig(
            username: $request['username'],
            purchaseKey: $request['purchase_key'],
            softwareId: SOFTWARE_ID,
            softwareType: base64_decode(self::SOFTWARE_TYPE),
            name: $request['name'],
            identifier: $request['email'],
        );

        $this->updateActivationConfig(app: self::ADMIN_PANEL, response: $response);

        return (bool) (int) ($response['active'] ?? 0);
    }

    private function storePurchaseCredentials(mixed $name, mixed $email, string $username, string $purchaseKey): void
    {
        if (! empty($name)) {
            $this->setEnvironmentValue(envKey: 'ADMIN_NAME', envValue: $name);
        }

        if (! empty($email)) {
            $this->setEnvironmentValue(envKey: 'ADMIN_IDENTIFIER', envValue: base64_encode($this->withoutWhitespace(value: $email)));
        }

        $this->setEnvironmentValue(envKey: 'SOFTWARE_ID', envValue: self::ENV_SOFTWARE_ID);
        $this->setEnvironmentValue(envKey: 'BUYER_USERNAME', envValue: $username);
        $this->setEnvironmentValue(envKey: 'PURCHASE_CODE', envValue: $purchaseKey);
    }

    private function storeUpdatedEnvironment(): void
    {
        $this->setEnvironmentValue(envKey: 'APP_MODE', envValue: self::LIVE_MODE);
        $this->setEnvironmentValue(envKey: 'SESSION_LIFETIME', envValue: self::SESSION_LIFETIME);
    }

    private function withoutWhitespace(mixed $value): string
    {
        return preg_replace('/\s+/', '', (string) $value);
    }

    private function updateResponse(SoftwareUpdateRequest $request, bool $status, string $message, ?string $redirectUrl = null): RedirectResponse|JsonResponse
    {
        if ($request->ajax()) {
            return response()->json([
                'status' => $status,
                'message' => $message,
                'redirect' => $redirectUrl,
            ]);
        }

        if ($redirectUrl !== null) {
            return redirect($redirectUrl);
        }

        $status ? ToastMagic::success($message) : ToastMagic::error($message);

        return back();
    }
}
