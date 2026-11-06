<?php

namespace App\Http\Controllers\Admin\ThirdParty;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Erp\TokenAddRequest;
use App\Services\New\ErpApiTokenService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ErpController extends BaseController
{
    private const API_KEY_LENGTH = 48;

    private const API_SECRET_LENGTH = 64;

    private const CREDENTIALS_FLASH_KEY = 'new_credentials';

    public function __construct(
        private readonly ErpApiTokenService $erpApiTokenService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return view('admin-views.third-party.erp.index', [
            'tokens' => $this->erpApiTokenService->getList(
                dataLimit: 'all',
                sortBy: ErpApiTokenService::SORT_LATEST,
            ),
        ]);
    }

    public function store(TokenAddRequest $request): RedirectResponse
    {
        $demoResponse = $this->demoModeResponse();

        if ($demoResponse !== null) {
            return $demoResponse;
        }

        $credentials = $this->generateCredentials();

        $this->erpApiTokenService->create(data: [
            'name' => $request['name'],
            'webhook_url' => $request['webhook_url'],
            ...$credentials,
        ]);

        ToastMagic::success(translate('Token generated successfully'));

        return back()->with(self::CREDENTIALS_FLASH_KEY, $credentials);
    }

    public function revoke(Request $request): RedirectResponse
    {
        $demoResponse = $this->demoModeResponse();

        if ($demoResponse !== null) {
            return $demoResponse;
        }

        if ($this->erpApiTokenService->find(id: $request['id']) === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        $this->erpApiTokenService->updateStatus(id: $request['id'], status: false);
        ToastMagic::success(translate('Token revoked successfully'));

        return back();
    }

    public function delete(Request $request): RedirectResponse
    {
        $demoResponse = $this->demoModeResponse();

        if ($demoResponse !== null) {
            return $demoResponse;
        }

        $this->erpApiTokenService->delete(id: $request['id'])
            ? ToastMagic::success(translate('Token deleted successfully'))
            : ToastMagic::error(translate('Not found'));

        return back();
    }

    /** @return array{api_key: string, api_secret: string} */
    private function generateCredentials(): array
    {
        return [
            'api_key' => Str::random(self::API_KEY_LENGTH),
            'api_secret' => Str::random(self::API_SECRET_LENGTH),
        ];
    }

    private function demoModeResponse(): ?RedirectResponse
    {
        if (config('app.mode') != 'demo') {
            return null;
        }

        ToastMagic::error(translate('This action is not available in demo mode'));

        return back();
    }
}
