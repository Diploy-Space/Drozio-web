<?php

namespace App\Http\Controllers\Admin\ThirdParty;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\ThirdParty\SocialMediaChatUpdateRequest;
use App\Services\New\BusinessSettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SocialMediaChatController extends BaseController
{
    private const SERVICE_MESSENGER = 'messenger';

    private const SERVICE_WHATSAPP = 'whatsapp';

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): RedirectResponse
    {
        return redirect()->route('admin.settings.third-party.social-login.view');
    }

    public function update(SocialMediaChatUpdateRequest $request, string $service): RedirectResponse
    {
        $setting = $this->resolveServiceSetting(service: $service, data: $request->all());

        if ($setting === null) {
            ToastMagic::warning(translate(':item update failed', ['item' => $this->serviceLabel(service: $service)]));

            return back();
        }

        $this->businessSettingService->updateOrInsert(
            params: ['type' => $service],
            data: ['value' => json_encode($setting)],
        );

        ToastMagic::success(translate(':item updated successfully', ['item' => $this->serviceLabel(service: $service)]));

        return back();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function resolveServiceSetting(string $service, array $data): ?array
    {
        return match ($service) {
            self::SERVICE_MESSENGER => [
                'status' => $data['status'] ?? 0,
                'script' => $data['script'] ?? null,
            ],
            self::SERVICE_WHATSAPP => [
                'status' => $data['status'] ?? 0,
                'phone' => $data['phone'] ?? null,
            ],
            default => null,
        };
    }

    private function serviceLabel(string $service): string
    {
        return match ($service) {
            self::SERVICE_MESSENGER => translate('Messenger'),
            self::SERVICE_WHATSAPP => translate('WhatsApp'),
            default => translate('Social media chat'),
        };
    }
}
