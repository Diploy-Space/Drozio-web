<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Setting\Business\InvoiceUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Utils\ImageManager;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class InvoiceSettingsController extends Controller
{
    private const SETTINGS_KEY = 'invoice_settings';

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $invoiceSettings = json_decode((string) $this->businessSettingService->findBy(params: ['type' => self::SETTINGS_KEY])?->value, true);

        if (! $invoiceSettings) {
            $this->updateInvoiceSettings();
            $invoiceSettings = json_decode((string) $this->businessSettingService->findBy(params: ['type' => self::SETTINGS_KEY])?->value, true);
        }

        return view('admin-views.business-settings.invoice-settings.index', compact('invoiceSettings'));
    }

    public function update(InvoiceUpdateRequest $request): RedirectResponse
    {
        if ($request['business_identity_status'] && empty($request['business_identity_value'])) {
            ToastMagic::error(
                translate('Business identity value required'),
                translate('Cannot enable business identity status without identity value'),
            );

            return redirect()->back();
        }

        $this->updateInvoiceSettings(
            data: $request->all(),
            logo: $request->file('image'),
        );

        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Invoice Settings')]));

        return redirect()->back();
    }

    /**
     * Called with no arguments to seed the row this screen reads on first visit.
     *
     * @param  array<string, mixed>  $data
     */
    private function updateInvoiceSettings(array $data = [], ?UploadedFile $logo = null): void
    {
        $this->businessSettingService->updateOrInsert(params: ['type' => self::SETTINGS_KEY], data: ['value' => json_encode([
            'business_identity_status' => $data['business_identity_status'] ?? 0,
            'invoice_logo_status' => $data['invoice_logo_status'] ?? 0,
            'invoice_logo_type' => $data['invoice_logo_type'] ?? 'default',
            'terms_and_condition' => $data['terms_and_condition'] ?? null,
            'business_identity' => $data['business_identity'] ?? null,
            'business_identity_value' => $data['business_identity_value'] ?? null,
            'image' => $this->resolveInvoiceLogo($logo),
        ])]);
    }

    /** @return array{image_name: string, storage: string} */
    private function resolveInvoiceLogo(?UploadedFile $logo): array
    {
        $storedSettings = json_decode((string) $this->businessSettingService->findBy(params: ['type' => self::SETTINGS_KEY])?->value, true) ?: [];
        $storedImage = $storedSettings['image'] ?? null;
        $storedName = is_array($storedImage) ? ($storedImage['image_name'] ?? '') : (string) $storedImage;
        $storage = config('filesystems.disks.default') ?? 'public';

        if ($logo === null) {
            return ['image_name' => $storedName, 'storage' => $storage];
        }

        $imageName = isset($storedSettings['image'])
            ? ImageManager::update(dir: BusinessSettingService::COMPANY_IMAGE_DIRECTORY, old_image: $storedName, format: 'webp', image: $logo)
            : ImageManager::upload(dir: BusinessSettingService::COMPANY_IMAGE_DIRECTORY, format: 'webp', image: $logo);

        return ['image_name' => $imageName, 'storage' => $storage];
    }
}
