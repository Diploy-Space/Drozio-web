<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Services\New\BusinessSettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FirebaseOTPVerificationController extends BaseController
{
    private const FIREBASE_OTP_KEY = 'firebase_otp_verification';

    private const FCM_CREDENTIAL_KEY = 'fcm_credentials';

    private const FIREBASE_VALIDATION_KEY = 'firebase';

    /** @var array<int, string> */
    private const REQUIRED_FCM_CREDENTIALS = ['apiKey', 'authDomain', 'projectId', 'storageBucket', 'messagingSenderId', 'appId'];

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return view('admin-views.third-party.firebase-configuration.authentication', [
            'firebaseOTPVerification' => json_decode(
                (string) $this->businessSettingService->findBy(params: ['type' => self::FIREBASE_OTP_KEY])?->value,
                true,
            ),
            'configStatus' => $this->hasCompleteFirebaseConfig(),
        ]);
    }

    public function updateAuthentication(Request $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::error(translate('You cannot update this on demo mode'));

            return back();
        }

        $this->businessSettingService->updateOrInsert(params: ['type' => self::FIREBASE_OTP_KEY], data: ['value' => json_encode([
            'status' => $request->get('status', 0),
            'web_api_key' => $request['web_api_key'],
        ])]);
        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    public function getConfigValidation(Request $request): JsonResponse
    {
        return response()->json([
            'status' => $request['key'] == self::FIREBASE_VALIDATION_KEY ? $this->hasCompleteFirebaseConfig() : 1,
            'htmlView' => view('admin-views.third-party.firebase-configuration.config-validation', [
                'data' => ['type' => $request['key']],
            ])->render(),
        ]);
    }

    private function hasCompleteFirebaseConfig(): bool
    {
        $credential = $this->businessSettingService->getValueByType(type: self::FCM_CREDENTIAL_KEY, default: []);

        if (! is_array($credential) || $credential === []) {
            return false;
        }

        foreach (self::REQUIRED_FCM_CREDENTIALS as $key) {
            if (empty($credential[$key])) {
                return false;
            }
        }

        return true;
    }
}
