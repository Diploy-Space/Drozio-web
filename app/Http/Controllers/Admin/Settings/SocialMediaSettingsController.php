<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\Website\SocialMediaAddRequest;
use App\Http\Requests\Admin\Setting\Website\SocialMediaUpdateRequest;
use App\Services\New\SocialMediaService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SocialMediaSettingsController extends BaseController
{
    private const ICONS = [
        'google-plus' => 'fa fa-google-plus-square',
        'facebook' => 'fa fa-facebook',
        'twitter' => 'fa fa-twitter',
        'pinterest' => 'fa fa-pinterest',
        'instagram' => 'fa fa-instagram',
        'linkedin' => 'fa fa-linkedin',
    ];

    public function __construct(
        private readonly SocialMediaService $socialMediaService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $socialMediaLinks = $this->socialMediaService->getList(
            dataLimit: 10,
            filters: ['status' => 1],
            sortBy: SocialMediaService::SORT_NEWEST_ID,
        );

        return view('admin-views.pages-and-media.social-media.index', compact('socialMediaLinks'));
    }

    public function add(SocialMediaAddRequest $request): JsonResponse
    {
        $this->socialMediaService->create(data: [
            'name' => $request['name'],
            'link' => $request['link'],
            'icon' => $this->resolveIcon(name: $request['name']),
        ]);

        return response()->json(['status' => 'success']);
    }

    public function getUpdate(Request $request): JsonResponse
    {
        return response()->json($this->socialMediaService->find(id: $request['id']));
    }

    public function update(SocialMediaUpdateRequest $request): RedirectResponse
    {
        $this->socialMediaService->update(id: $request['id'], data: [
            'name' => $request['name'],
            'link' => $request['link'],
            'icon' => $this->resolveIcon(name: $request['name']),
        ]);
        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    public function delete(Request $request): RedirectResponse
    {
        $this->socialMediaService->delete(id: $request['id']);

        return back();
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $this->socialMediaService->updateStatus(id: $request['id'], status: (bool) $request['status']);
        ToastMagic::success(translate('Status updated successfully'));

        return back();
    }

    private function resolveIcon(string $name): string
    {
        return self::ICONS[$name] ?? '';
    }
}
