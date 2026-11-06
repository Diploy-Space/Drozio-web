<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\Storage\S3CredentialUpdateRequest;
use App\Services\New\BusinessSettingService;
use Aws\S3\S3Client;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class StorageConnectionSettingsController extends BaseController
{
    private const STORAGE_CONNECTION_TYPE_KEY = 'storage_connection_type';

    private const S3_CREDENTIAL_KEY = 'storage_connection_s3_credential';

    private const STORAGE_TYPE_LOCAL = 'local';

    private const STORAGE_TYPE_PUBLIC = 'public';

    private const STORAGE_TYPE_S3 = 's3';

    private const S3_TEST_FILE_NAME = 'test_file.txt';

    private const S3_TEST_FILE_CONTENT = 'This is a test file uploaded to S3.';

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $settings = $this->settingValues(settingKeys: [self::STORAGE_CONNECTION_TYPE_KEY, self::S3_CREDENTIAL_KEY]);
        $storageConnectionType = $settings[self::STORAGE_CONNECTION_TYPE_KEY] ?? null;

        if (! $storageConnectionType) {
            $storageConnectionType = self::STORAGE_TYPE_LOCAL;
            $this->businessSettingService->updateOrInsert(
                params: ['type' => self::STORAGE_CONNECTION_TYPE_KEY],
                data: ['value' => $storageConnectionType],
            );
        }

        return view('admin-views.third-party.storage-connection-settings.index', [
            'storageConnectionType' => $storageConnectionType,
            'storageConnectionS3Credential' => $this->decodedSetting(value: $settings[self::S3_CREDENTIAL_KEY] ?? null),
        ]);
    }

    public function updateStorageType(Request $request): JsonResponse|RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            if ($request->ajax()) {
                return response()->json([
                    'message' => translate('You cannot update this on demo mode.'),
                ]);
            }
            ToastMagic::warning(translate('You cannot update this on demo mode'));

            return back();
        }

        $type = $this->resolveStorageType(request: $request);

        $this->updateStorageConnectionType(type: $type);
        if ($type == self::STORAGE_TYPE_S3) {
            try {
                $this->checkS3Credential();
            } catch (Exception) {
                $this->updateStorageConnectionType(type: self::STORAGE_TYPE_PUBLIC);
                if ($request->ajax()) {
                    return response()->json([
                        'message' => translate('Storage connection type cannot be changed due to wrong s3 credentials.'),
                    ]);
                } else {
                    ToastMagic::error(translate('Storage connection type cannot be changed due to wrong s3 credentials'));
                }
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'status' => 1,
                'message' => translate('Storage connection type changed successfully'),
            ]);
        }

        ToastMagic::success(translate('Storage connection type changed successfully'));

        return back();
    }

    public function updateS3Credential(S3CredentialUpdateRequest $request): JsonResponse
    {
        if (config('app.mode') == 'demo') {
            return response()->json([
                'status' => 0,
                'error' => translate('You cannot update this on demo mode').'.',
            ]);
        }

        $credential = $this->s3Credential(request: $request);

        try {
            (new S3Client($this->s3ClientConfig(credential: $credential)))->listBuckets();
        } catch (Exception $exception) {
            return response()->json([
                'status' => 0,
                'error' => translate('S3 wrong credentials are not acceptable').'.',
                'exception' => $exception->getMessage(),
            ]);
        }

        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::S3_CREDENTIAL_KEY],
            data: ['value' => json_encode($credential)],
        );
        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Credential')]));

        return response()->json([
            'status' => 1,
            'message' => translate(':item updated successfully', ['item' => translate('Credential')]).'.',
        ]);
    }

    /**
     * @param  array<int, string>  $settingKeys
     * @return array<string, mixed>
     */
    private function settingValues(array $settingKeys): array
    {
        return $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => $settingKeys])
            ->pluck('value', 'type')
            ->toArray();
    }

    /** @return array<string, mixed> */
    private function decodedSetting(mixed $value): array
    {
        return $value === null ? [] : (json_decode((string) $value, true) ?: []);
    }

    private function resolveStorageType(Request $request): string
    {
        if ($request['status'] == '1') {
            return $request['type'];
        }

        return $request['type'] == self::STORAGE_TYPE_PUBLIC ? self::STORAGE_TYPE_S3 : self::STORAGE_TYPE_PUBLIC;
    }

    private function updateStorageConnectionType(string $type): void
    {
        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::STORAGE_CONNECTION_TYPE_KEY],
            data: ['value' => $type],
        );
        Session::forget(self::STORAGE_CONNECTION_TYPE_KEY);
    }

    private function checkS3Credential(): void
    {
        $this->applyS3StorageEnvironment();
        Storage::disk(self::STORAGE_TYPE_S3)->put(self::S3_TEST_FILE_NAME, self::S3_TEST_FILE_CONTENT);
        if (Storage::disk(self::STORAGE_TYPE_S3)->exists(self::S3_TEST_FILE_NAME)) {
            Storage::disk(self::STORAGE_TYPE_S3)->delete(self::S3_TEST_FILE_NAME);
        }
    }

    private function applyS3StorageEnvironment(): void
    {
        $credential = $this->decodedSetting(
            value: $this->businessSettingService->findBy(params: ['type' => self::S3_CREDENTIAL_KEY])?->value,
        );

        Config::set('filesystems.disks.default', self::STORAGE_TYPE_S3);

        if ($credential !== []) {
            Config::set('filesystems.disks.'.self::STORAGE_TYPE_S3, $credential);
        }
    }

    /** @return array<string, mixed> */
    private function s3Credential(S3CredentialUpdateRequest $request): array
    {
        return [
            'driver' => self::STORAGE_TYPE_S3,
            'key' => $request['s3_key'],
            'secret' => $request['s3_secret'],
            'region' => $request['s3_region'],
            'bucket' => $request['s3_bucket'],
            'url' => $request['s3_url'],
            'visibility' => self::STORAGE_TYPE_PUBLIC,
            'endpoint' => $request['s3_endpoint'],
        ];
    }

    /**
     * @param  array<string, mixed>  $credential
     * @return array<string, mixed>
     */
    private function s3ClientConfig(array $credential): array
    {
        $config = [
            'version' => 'latest',
            'region' => $credential['region'],
            'credentials' => [
                'key' => $credential['key'],
                'secret' => $credential['secret'],
                'region' => $credential['region'],
            ],
        ];

        if (! empty($credential['endpoint']) && ! str_contains($credential['endpoint'], 'amazonaws.com')) {
            $config['endpoint'] = $credential['endpoint'];
        }

        return $config;
    }
}
