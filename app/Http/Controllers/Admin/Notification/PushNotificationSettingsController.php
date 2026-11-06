<?php

namespace App\Http\Controllers\Admin\Notification;

use App\Enums\NotificationUserType;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Notification\PushMessageUpdateRequest;
use App\Http\Requests\Admin\Setting\ThirdParty\FirebaseConfigUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\NotificationMessageService;
use App\Services\New\PushNotificationService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PushNotificationSettingsController extends BaseController
{
    private const PUSH_NOTIFICATION_LANGUAGE_KEY = 'pnc_language';

    private const FIREBASE_OTP_VERIFICATION_KEY = 'firebase_otp_verification';

    private const PUSH_NOTIFICATION_KEY = 'push_notification_key';

    private const FCM_CREDENTIALS_KEY = 'fcm_credentials';

    private const FCM_PROJECT_ID_KEY = 'fcm_project_id';

    /** @var array<int, string> */
    private const FCM_CREDENTIAL_FIELDS = ['apiKey', 'authDomain', 'projectId', 'storageBucket', 'messagingSenderId', 'appId', 'measurementId'];

    /** @var array<int, string> */
    private const FCM_REQUIRED_FIELDS = ['apiKey', 'authDomain', 'projectId', 'storageBucket', 'messagingSenderId', 'appId'];

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
        private readonly NotificationMessageService $notificationMessageService,
        private readonly PushNotificationService $pushNotificationService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $request = $request ?? request();
        $businessModuleManager = businessModuleManager();
        $availableModules = $businessModuleManager->availableInPanel();

        $requestedModule = (string) $request->query('module', '');
        $commonModule = PushNotificationService::MODULE_COMMON;
        $businessModule = $requestedModule === $commonModule || $businessModuleManager->isAvailableInPanel($requestedModule)
            ? $requestedModule
            : $businessModuleManager->defaultKey();

        $availableUserTypes = $this->pushNotificationService->getAvailableUserTypes(businessModule: $businessModule);
        $userType = NotificationUserType::resolveFromRequest($request->query('user_type'));
        if ($userType === null || ! in_array($userType, $availableUserTypes, true)) {
            $userType = $availableUserTypes[0] ?? null;
        }

        $messages = $userType !== null
            ? $this->getPushNotificationMessageData(businessModule: $businessModule, userType: $userType)
            : new Collection;
        $labels = $userType !== null
            ? $this->pushNotificationService->getMessageLabels(businessModule: $businessModule, userType: $userType)
            : [];
        $language = $this->businessSettingService->findBy(params: ['type' => self::PUSH_NOTIFICATION_LANGUAGE_KEY]);

        return view('admin-views.push-notification.index', compact(
            'availableModules', 'commonModule', 'businessModule', 'availableUserTypes', 'userType', 'messages', 'labels', 'language'
        ));
    }

    public function updatePushNotificationMessage(PushMessageUpdateRequest $request): RedirectResponse
    {
        $businessModule = $request['module'];
        $userType = NotificationUserType::resolveFromRequest($request['user_type']);

        $pushNotificationMessages = $this->notificationMessageService->getList(
            dataLimit: 'all',
            filters: ['business_module' => $businessModule, 'user_type' => $userType],
        );

        foreach ($pushNotificationMessages as $pushNotificationMessage) {
            $this->notificationMessageService->update(
                id: $pushNotificationMessage['id'],
                data: $this->getMessageUpdateData(data: $request->all(), id: $pushNotificationMessage['id']),
            );
        }

        updateSetupGuideCacheKey(key: 'notification_configuration', panel: 'admin');
        ToastMagic::success(translate('Updated successfully'));

        return redirect()->route('admin.settings.system-setup.push-notification.index', ['module' => $businessModule, 'user_type' => $userType]);
    }

    public function getFirebaseConfigurationView(): View
    {
        $firebaseOTPVerification = $this->businessSettingService->findBy(params: ['type' => self::FIREBASE_OTP_VERIFICATION_KEY])?->value ?? '';
        $pushNotificationKey = $this->businessSettingService->findBy(params: ['type' => self::PUSH_NOTIFICATION_KEY])?->value ?? '';
        $configData = $this->businessSettingService->findBy(params: ['type' => self::FCM_CREDENTIALS_KEY])?->value ?? '';
        $projectId = $this->businessSettingService->findBy(params: ['type' => self::FCM_PROJECT_ID_KEY])?->value ?? '';

        return view('admin-views.third-party.firebase-configuration.index', [
            'firebaseOTPVerification' => json_decode($firebaseOTPVerification, true),
            'pushNotificationKey' => $pushNotificationKey,
            'projectId' => $projectId,
            'configData' => json_decode($configData),
            'configStatus' => $this->hasCompleteFirebaseConfig(config: json_decode($configData, true) ?? []),
        ]);
    }

    /** @throws Exception */
    public function getFirebaseConfigurationUpdate(FirebaseConfigUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return back();
        }

        $this->businessSettingService->updateOrInsert(params: ['type' => self::FCM_PROJECT_ID_KEY], data: ['value' => $request['fcm_project_id']]);
        $this->businessSettingService->updateOrInsert(params: ['type' => self::PUSH_NOTIFICATION_KEY], data: ['value' => $request['push_notification_key']]);

        $configData = $this->getFcmCredentials(data: $request->all());
        $this->pushNotificationService->firebaseConfigFileGenerate(config: $configData);
        $this->businessSettingService->updateOrInsert(params: ['type' => self::FCM_CREDENTIALS_KEY], data: ['value' => json_encode($configData)]);

        ToastMagic::success(translate('Settings updated'));

        return back();
    }

    private function getPushNotificationMessageData(string $businessModule, string $userType): Collection
    {
        $scope = ['business_module' => $businessModule, 'user_type' => $userType];
        $storedMessages = $this->notificationMessageService->getList(dataLimit: 'all', filters: $scope, sortBy: NotificationMessageService::SORT_KEY_ASC);
        $canonicalKeys = $this->pushNotificationService->getMessageKeyData(businessModule: $businessModule, userType: $userType);

        foreach (array_diff($canonicalKeys, $storedMessages->pluck('key')->toArray()) as $missingKey) {
            $this->notificationMessageService->create(data: [
                'business_module' => $businessModule,
                'user_type' => $userType,
                'key' => $missingKey,
                'message' => $this->pushNotificationService->getDefaultMessage(key: $missingKey),
            ]);
        }

        foreach ($storedMessages as $storedMessage) {
            if (! in_array($storedMessage['key'], $canonicalKeys)) {
                $this->notificationMessageService->delete(id: $storedMessage['id']);
            }
        }

        return $this->notificationMessageService->getList(
            dataLimit: 'all',
            relations: ['translations'],
            filters: $scope,
            sortBy: NotificationMessageService::SORT_KEY_ASC,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{lang: array<int, string>, message: array<int, ?string>, status: int}
     */
    private function getMessageUpdateData(array $data, mixed $id): array
    {
        return [
            'lang' => (array) ($data['lang'.$id] ?? []),
            'message' => (array) ($data['message'.$id] ?? []),
            'status' => (int) ($data['status'.$id] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function getFcmCredentials(array $data): array
    {
        $credentials = [];
        foreach (self::FCM_CREDENTIAL_FIELDS as $field) {
            $credentials[$field] = $data[$field] ?? null;
        }

        return $credentials;
    }

    /** @param  array<string, mixed>  $config */
    private function hasCompleteFirebaseConfig(array $config): bool
    {
        foreach (self::FCM_REQUIRED_FIELDS as $field) {
            if (empty($config[$field])) {
                return false;
            }
        }

        return true;
    }
}
