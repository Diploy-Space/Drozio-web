<?php

namespace Modules\Blog\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\New\BusinessSettingService;
use App\Utils\ImageManager;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class BlogDownloadAppController extends Controller
{
    private const DOWNLOAD_APP_STATUS = 'blog_feature_download_app_status';

    private const DOWNLOAD_APP_TITLE = 'blog_feature_download_app_title';

    private const DOWNLOAD_APP_SUBTITLE = 'blog_feature_download_app_subtitle';

    private const DOWNLOAD_GOOGLE_APP_STATUS = 'blog_feature_download_google_app_button_status';

    private const DOWNLOAD_APPLE_APP_STATUS = 'blog_feature_download_apple_app_button_status';

    private const DOWNLOAD_APP_ICON = 'blog_feature_download_app_icon';

    private const DOWNLOAD_APP_BACKGROUND = 'blog_feature_download_app_background';

    private const LANGUAGE_SETTING = 'pnc_language';

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function appDownloadSetup(): View
    {
        return view('blog::admin-views.blog.app-download-setup', $this->getAppDownloadSetup());
    }

    /** @throws Throwable */
    public function updateDownloadAppButton(Request $request): RedirectResponse
    {
        $this->storeAppDownloadSetup(
            data: $request->all(),
            icon: $request->file('icon'),
            background: $request->file('image'),
        );
        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::DOWNLOAD_APP_STATUS],
            data: ['value' => $request->get('status', 0) ? 1 : 0],
        );

        return response()->json([
            'status' => true,
            'message' => translate(':item updated successfully', ['item' => translate('Status')]),
        ]);
    }

    public function deleteImage(Request $request): RedirectResponse
    {
        $this->deleteSetupImage(
            posted: $request['icon'],
            setting: self::DOWNLOAD_APP_ICON,
            deletedMessage: translate('App download icon removed successfully'),
        );

        $this->deleteSetupImage(
            posted: $request['image'],
            setting: self::DOWNLOAD_APP_BACKGROUND,
            deletedMessage: translate('App background removed successfully'),
        );

        return back();
    }

    private function deleteSetupImage(mixed $posted, string $setting, string $deletedMessage): void
    {
        if (empty($posted)) {
            ToastMagic::warning(translate('No image found to delete'));

            return;
        }

        $this->deleteStoredImage(setting: $setting)
            ? ToastMagic::success($deletedMessage)
            : ToastMagic::warning(translate('No image found to delete'));
    }

    /** @return array<string, mixed> */
    private function getAppDownloadSetup(): array
    {
        $settings = $this->businessSettingService->getList(dataLimit: 'all', filters: ['type' => [
            self::DOWNLOAD_APP_STATUS,
            self::DOWNLOAD_APP_TITLE,
            self::DOWNLOAD_APP_SUBTITLE,
            self::DOWNLOAD_GOOGLE_APP_STATUS,
            self::DOWNLOAD_APPLE_APP_STATUS,
            self::DOWNLOAD_APP_ICON,
            self::DOWNLOAD_APP_BACKGROUND,
            self::LANGUAGE_SETTING,
        ]])->pluck('value', 'type');
        $languages = json_decode((string) $settings->get(self::LANGUAGE_SETTING), true) ?? [];

        return [
            'downloadAppStatus' => $settings->get(self::DOWNLOAD_APP_STATUS),
            'googleAppStatus' => $settings->get(self::DOWNLOAD_GOOGLE_APP_STATUS),
            'appleAppStatus' => $settings->get(self::DOWNLOAD_APPLE_APP_STATUS),
            'titleData' => json_decode((string) $settings->get(self::DOWNLOAD_APP_TITLE), true) ?? [],
            'subTitleData' => json_decode((string) $settings->get(self::DOWNLOAD_APP_SUBTITLE), true) ?? [],
            'downloadAppIcon' => $this->imageLink($settings->get(self::DOWNLOAD_APP_ICON)),
            'downloadAppBackground' => $this->imageLink($settings->get(self::DOWNLOAD_APP_BACKGROUND)),
            'languages' => $languages,
            'defaultLanguage' => $languages[0] ?? 'en',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    private function storeAppDownloadSetup(array $data, ?UploadedFile $icon = null, ?UploadedFile $background = null): void
    {
        DB::transaction(function () use ($data, $icon, $background): void {
            $this->businessSettingService->updateOrInsert(params: ['type' => self::DOWNLOAD_APP_TITLE], data: ['value' => json_encode($data['title'] ?? [])]);
            $this->businessSettingService->updateOrInsert(params: ['type' => self::DOWNLOAD_APP_SUBTITLE], data: ['value' => json_encode($data['sub_title'] ?? [])]);
            $this->businessSettingService->updateOrInsert(params: ['type' => self::DOWNLOAD_GOOGLE_APP_STATUS], data: ['value' => $data['google_app_status'] ?? 0]);
            $this->businessSettingService->updateOrInsert(params: ['type' => self::DOWNLOAD_APPLE_APP_STATUS], data: ['value' => $data['apple_app_status'] ?? 0]);

            foreach ([self::DOWNLOAD_APP_ICON => $icon, self::DOWNLOAD_APP_BACKGROUND => $background] as $type => $image) {
                if ($image === null) {
                    continue;
                }

                $this->businessSettingService->updateOrInsert(params: ['type' => $type], data: ['value' => json_encode(
                    $this->businessSettingService->storeImage(
                        type: $type,
                        directory: BusinessSettingService::COMPANY_IMAGE_DIRECTORY,
                        format: 'webp',
                        image: $image,
                    )
                )]);
            }
        });
    }

    /** @return array<string, mixed>|null */
    private function imageLink(?string $storedValue): ?array
    {
        if ($storedValue === null) {
            return null;
        }

        $decoded = json_decode((string) $storedValue, true);
        $image = isset($decoded['image_name']) ? $decoded : ['image_name' => $storedValue, 'storage' => 'public'];

        return storageLink(
            rtrim(BusinessSettingService::COMPANY_IMAGE_DIRECTORY, '/'),
            $image['image_name'],
            $image['storage'],
        );
    }

    private function storedImageName(?string $storedValue): string
    {
        if ($storedValue === null) {
            return '';
        }

        $decoded = json_decode($storedValue, true);

        return is_array($decoded) ? (string) ($decoded['image_name'] ?? '') : $storedValue;
    }

    private function deleteStoredImage(string $setting): bool
    {
        $record = $this->businessSettingService->findBy(params: ['type' => $setting]);

        if ($record === null) {
            return false;
        }

        $imageName = $this->storedImageName($record->value);

        if ($imageName !== '') {
            ImageManager::delete(BusinessSettingService::COMPANY_IMAGE_DIRECTORY.$imageName);
        }

        return $this->businessSettingService->delete(id: $record->id);
    }
}
