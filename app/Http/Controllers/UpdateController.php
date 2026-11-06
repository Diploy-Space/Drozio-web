<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shared\System\PurchaseCodeUpdateRequest;
use App\Services\New\SoftwareUpdateService;
use App\Traits\ActivationClass;
use App\Traits\UpdateClass;
use App\Utils\Helpers;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\URL;

class UpdateController extends Controller
{
    use ActivationClass;
    use UpdateClass;

    private const ADMIN_SOFTWARE_UPDATE_PATH = 'admin/settings/system-setup/software-update';

    public function __construct(
        private readonly SoftwareUpdateService $softwareUpdateService,
    ) {}

    public function index(): View
    {
        return view('update.update-software', [
            'isUpdateUnfinished' => $this->softwareUpdateService->isUpdateUnfinished(installedVersion: envFileValue('SOFTWARE_VERSION')),
            'installedFilesVersion' => SOFTWARE_VERSION,
        ]);
    }

    public function updateSoftware(PurchaseCodeUpdateRequest $request): Redirector|RedirectResponse
    {
        $adminEmail = base64_encode(preg_replace('/\s+/', '', $request['email']));
        $username = preg_replace('/\s+/', '', $request['username']);
        $purchaseKey = preg_replace('/\s+/', '', $request['purchase_key']);

        Helpers::setEnvironmentValue('ADMIN_NAME', $request['name']);
        Helpers::setEnvironmentValue('ADMIN_IDENTIFIER', $adminEmail);
        Helpers::setEnvironmentValue('SOFTWARE_ID', 'MzE0NDg1OTc=');
        Helpers::setEnvironmentValue('BUYER_USERNAME', $username);
        Helpers::setEnvironmentValue('PURCHASE_CODE', $purchaseKey);
        Helpers::setEnvironmentValue('SOFTWARE_VERSION', SOFTWARE_VERSION);
        Helpers::setEnvironmentValue('APP_MODE', 'live');
        Helpers::setEnvironmentValue('APP_NAME', '6valley'.time());
        Helpers::setEnvironmentValue('SESSION_LIFETIME', '60');

        $appUrl = URL::to('/');
        $hostDomain = parse_url($appUrl, PHP_URL_HOST) ?: '';
        $publicIp = $request->server('SERVER_ADDR') ?: (filter_var($hostDomain, FILTER_VALIDATE_IP) ? $hostDomain : '');

        Helpers::setEnvironmentValue('APP_HOST_DOMAIN', $hostDomain);
        Helpers::setEnvironmentValue('APP_HOST_BASE_DOMAIN', Helpers::getHostBaseDomain($hostDomain));
        Helpers::setEnvironmentValue('APP_PUBLIC_IP', $publicIp);

        $response = $this->getRequestConfig(
            username: $username,
            purchaseKey: $purchaseKey,
            softwareId: SOFTWARE_ID,
            softwareType: base64_decode('cHJvZHVjdA=='),
            name: $request['name'],
            identifier: $request['email'],
        );
        $this->updateActivationConfig(app: 'admin_panel', response: $response);
        $status = $response['active'] ?? 0;

        if ((int) $status) {
            $this->finishUpdate();

            return redirect(url('/'));
        }

        if (! empty($response['errors'])) {
            foreach ($response['errors'] as $error) {
                $message = is_array($error) ? ($error[0] ?? 'Unknown error') : $error;
                ToastMagic::error($message);
            }
        } else {
            ToastMagic::error(translate('Verification failed try again'));
        }

        return back();
    }

    public function completeUploadedUpdate(string $token): Redirector|RedirectResponse
    {
        if (! $this->softwareUpdateService->consumeCompletionToken(token: $token)) {
            return redirect(url('/'));
        }

        $this->finishUpdate();

        return redirect(url(self::ADMIN_SOFTWARE_UPDATE_PATH));
    }

    private function finishUpdate(): void
    {
        $this->softwareUpdateService->completeUpdate();
        $this->moveQueueOffSynchronousDriver();
        $this->getProcessAllVersionsUpdates();
        Helpers::setEnvironmentValue('SOFTWARE_VERSION', SOFTWARE_VERSION);
        $this->cleanupEnvFileContext();
        $this->softwareUpdateService->discardCompiledPhpCache();
    }

    private function moveQueueOffSynchronousDriver(): void
    {
        $queueConnection = '';
        $envPath = base_path('.env');

        if (is_file($envPath)) {
            foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (preg_match('/^QUEUE_CONNECTION\s*=\s*(.*)$/i', trim($line), $matches)) {
                    $queueConnection = trim($matches[1], "\"' ");
                    break;
                }
            }
        }

        if (in_array($queueConnection, ['sync', ''], true)) {
            Helpers::setEnvironmentValue('QUEUE_CONNECTION', 'database');
        }
    }
}
