<?php

namespace App\Http\Controllers\Admin\ThirdParty;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\ThirdParty\MailUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\MailService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MailController extends BaseController
{
    private const SMTP_CONFIG_KEY = 'mail_config';

    private const SENDGRID_CONFIG_KEY = 'mail_config_sendgrid';

    private const SEND_STATUS_INACTIVE = 0;

    private const SEND_STATUS_SENT = 1;

    private const SEND_STATUS_FAILED = 2;

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
        private readonly MailService $mailService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return view('admin-views.third-party.mail.index');
    }

    public function update(MailUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return back();
        }

        if ($request['status'] == 1) {
            $this->deactivateMailConfig(type: self::SENDGRID_CONFIG_KEY);
        }

        $this->storeMailConfig(type: self::SMTP_CONFIG_KEY, config: $this->mailConfigFromRequest(request: $request));
        ToastMagic::success(translate('Configuration updated successfully'));

        return back();
    }

    public function updateSendGrid(MailUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return back();
        }

        if ($request['status'] == 1) {
            $this->deactivateMailConfig(type: self::SMTP_CONFIG_KEY);
        }

        $this->storeMailConfig(type: self::SENDGRID_CONFIG_KEY, config: $this->mailConfigFromRequest(request: $request));
        ToastMagic::success(translate('SendGrid Configuration updated successfully'));

        return back();
    }

    public function send(Request $request): JsonResponse
    {
        if (config('app.mode') == 'demo') {
            return response()->json([
                'status' => 0,
                'message' => translate('This option is disabled for demo'),
            ]);
        }

        if (! $this->hasActiveMailConfig()) {
            return response()->json([
                'status' => self::SEND_STATUS_INACTIVE,
                'message' => 'success',
            ]);
        }

        $response = $this->mailService->sendTestMail(email: (string) $request['email']);

        return response()->json([
            'status' => $response['sent'] ? self::SEND_STATUS_SENT : self::SEND_STATUS_FAILED,
            'message' => $response['sent'] ? 'success' : $response['error'],
        ]);
    }

    private function hasActiveMailConfig(): bool
    {
        $smtp = $this->businessSettingService->getValueByType(type: self::SMTP_CONFIG_KEY, default: []);

        return ($smtp['status'] ?? 0) == 1
            || ($this->businessSettingService->getValueByType(type: self::SENDGRID_CONFIG_KEY, default: [])['status'] ?? 0) == 1;
    }

    /** @return array<string, mixed> */
    private function mailConfigFromRequest(MailUpdateRequest $request): array
    {
        return [
            'status' => $request->get('status', 0),
            'name' => $request['name'],
            'host' => $request['host'],
            'driver' => $request['driver'],
            'port' => $request['port'],
            'username' => $request['username'],
            'email_id' => $request['email'],
            'encryption' => $request['encryption'],
            'password' => $request['password'],
        ];
    }

    private function deactivateMailConfig(string $type): void
    {
        $config = $this->businessSettingService->getValueByType(type: $type, default: []);

        if (! is_array($config) || $config === []) {
            return;
        }

        $this->storeMailConfig(type: $type, config: array_merge($config, ['status' => 0]));
    }

    /** @param  array<string, mixed>  $config */
    private function storeMailConfig(string $type, array $config): void
    {
        $this->businessSettingService->updateWhere(params: ['type' => $type], data: ['value' => json_encode($config)]);
    }
}
