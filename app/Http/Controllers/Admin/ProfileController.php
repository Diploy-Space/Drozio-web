<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Profile\PasswordUpdateRequest;
use App\Http\Requests\Admin\Profile\UpdateRequest;
use App\Services\New\AdminService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProfileController extends BaseController
{
    public function __construct(
        private readonly AdminService $adminService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse {}

    public function getUpdateView(string|int $id): View|RedirectResponse
    {
        $admin = $this->adminService->find(id: $id);
        $shopBanner = getWebConfig('shop_banner');

        return view('admin-views.profile.update-view', compact('admin', 'shopBanner'));
    }

    public function update(UpdateRequest $request, string|int $id): RedirectResponse
    {
        $this->adminService->update(
            id: $id,
            data: [
                'name' => $request['name'],
                'email' => $request['email'],
                'phone' => $request['phone'],
            ],
            image: $request->file('image'),
        );
        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Profile')]));

        return redirect()->back();
    }

    public function updatePassword(PasswordUpdateRequest $request, string|int $id): RedirectResponse
    {
        $this->adminService->update(id: $id, data: ['password' => bcrypt($request['password'])]);
        ToastMagic::success(translate('Admin password updated successfully'));

        return redirect()->back();
    }
}
