<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\Website\ThemeUploadRequest;
use App\Services\New\NotificationService;
use App\Services\New\ThemeService;
use App\Traits\PushNotificationTrait;
use Carbon\Carbon;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class ThemeController extends BaseController
{
    use PushNotificationTrait;

    private const DEFAULT_THEME = 'default';

    private const VENDOR_PUSH_TOPIC = 'six_valley_seller';

    public function __construct(
        private readonly ThemeService $themeService,
        private readonly NotificationService $notificationService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $themes = $this->themeService->getList();

        return view('admin-views.system-setup.themes.theme-setup', [
            'themes' => $themes,
            'totalThemes' => $this->installedThemeCount(themes: $themes),
            'currentThemeName' => $this->currentThemeName(themes: $themes),
        ]);
    }

    public function upload(ThemeUploadRequest $request): JsonResponse
    {
        $result = $this->themeService->create(archive: $request->file('theme_upload'));

        return response()->json([
            'status' => $result['status'] ? 'success' : 'error',
            'message' => $result['status'] ? translate('Theme uploaded successfully') : $this->uploadFailureMessage($result),
        ]);
    }

    public function publish(Request $request): JsonResponse
    {
        $theme = $request['theme'];
        $themeInfo = $this->themeService->find(id: $theme);

        if ($themeInfo === null) {
            return response()->json(['flag' => 'invalid', 'message' => translate('Invalid theme')]);
        }

        if ($this->needsActivation(theme: $theme, themeInfo: $themeInfo)) {
            return response()->json([
                'flag' => 'inactive',
                'view' => view('admin-views.system-setup.themes.theme-activate-modal-data', [
                    'themeInfo' => $themeInfo,
                    'theme' => $theme,
                ])->render(),
            ]);
        }

        $currentTheme = theme_root_path();
        $currentThemeInfo = $this->themeService->find(id: $currentTheme);

        $this->themeService->publish(id: $theme);

        return response()->json([
            'reload_action' => $themeInfo['routes'] === [] ? 1 : 0,
            'informationModal' => $themeInfo['routes'] === [] ? '' : view('admin-views.system-setup.themes.theme-information-modal-data', [
                'currentTheme' => $currentTheme,
                'themeInfo' => $themeInfo,
                'themeRoutes' => $themeInfo['routes'],
                'currentThemeRoutes' => $currentThemeInfo['routes'] ?? [],
            ])->render(),
        ]);
    }

    public function activation(Request $request): RedirectResponse
    {
        $isActivated = $this->themeService->activateByDomain(
            theme: $request['theme'],
            username: $request['username'],
            purchaseCode: $request['purchase_code'],
        );

        $isActivated
            ? ToastMagic::success(translate('Activated successfully'))
            : ToastMagic::error(translate('Activation failed'));

        return back();
    }

    public function delete(Request $request): JsonResponse
    {
        $theme = $this->themeService->find(id: $request['theme']);

        if ($theme === null) {
            return $this->respond(succeeded: false, message: translate('Invalid theme'));
        }

        if ($theme['is_published']) {
            return $this->respond(succeeded: false, message: translate('Cannot delete the active theme'));
        }

        $isDeleted = $this->themeService->delete(id: $request['theme']);

        return $this->respond(
            succeeded: $isDeleted,
            message: $isDeleted ? translate('Theme deleted successfully') : translate('Theme delete failed'),
        );
    }

    public function notifyAllTheVendors(Request $request): JsonResponse
    {
        $notification = $this->vendorNotificationData();

        try {
            $this->notificationService->create(data: $notification, image: $request->file('image'));
            $this->sendPushNotificationToTopic(data: $notification, topic: self::VENDOR_PUSH_TOPIC);
        } catch (Throwable) {
            return response()->json([
                'status' => 0,
                'message' => translate('Notification send to all vendors failed'),
            ]);
        }

        return response()->json([
            'status' => 1,
            'message' => translate('Notification Sent to All Vendors'),
        ]);
    }

    /** @param  array<string, array<string, mixed>>  $themes */
    private function installedThemeCount(array $themes): int
    {
        return count(array_filter($themes, static fn (array $theme) => isset($theme['software_id'])));
    }

    /** @param  array<string, array<string, mixed>>  $themes */
    private function currentThemeName(array $themes): string
    {
        $currentTheme = theme_root_path();

        $name = $currentTheme === self::DEFAULT_THEME
            ? 'default_theme'
            : ($themes[$currentTheme]['name'] ?? $currentTheme);

        return ucwords(str_replace('_', ' ', $name));
    }

    /** @param  array<string, mixed>  $themeInfo */
    private function needsActivation(string $theme, array $themeInfo): bool
    {
        return $theme !== self::DEFAULT_THEME
            && (empty($themeInfo['purchase_code']) || empty($themeInfo['username']) || $themeInfo['is_active'] == '0');
    }

    /** @return array<string, mixed> */
    private function vendorNotificationData(): array
    {
        return [
            'sent_to' => NotificationService::AUDIENCE_SELLER,
            'title' => 'Theme Changed to '.ucwords(str_replace('_', ' ', theme_root_path())),
            'description' => 'Theme Changed Description, time - '.Carbon::now(),
        ];
    }

    /** @param  array{status: bool, reason: ?string}  $result */
    private function uploadFailureMessage(array $result): string
    {
        return match ($result['reason']) {
            ThemeService::REASON_ARCHIVE_UNREADABLE => translate('Theme upload failed'),
            ThemeService::REASON_INVALID_ARCHIVE => translate('Invalid theme'),
            default => translate('Something went wrong'),
        };
    }

    private function respond(bool $succeeded, string $message): JsonResponse
    {
        return response()->json([
            'status' => $succeeded ? 'success' : 'error',
            'message' => $message,
        ]);
    }
}
