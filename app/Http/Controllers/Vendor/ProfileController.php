<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\Profile\BankInfoUpdateRequest;
use App\Http\Requests\Vendor\Profile\PasswordUpdateRequest;
use App\Http\Requests\Vendor\Profile\UpdateRequest;
use App\Services\New\VendorService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProfileController extends BaseController
{
    public function __construct(
        private readonly VendorService $vendorService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        return $this->getListView();
    }

    public function getListView(): View
    {
        return view('vendor-views.profile.index', [
            'vendor' => $this->vendor(),
        ]);
    }

    public function getUpdateView(string|int $id): View|RedirectResponse
    {
        if (auth('seller')->id() != $id) {
            ToastMagic::warning(translate('You cannot change others profile'));

            return redirect()->back();
        }

        return view('vendor-views.profile.update-view', [
            'vendor' => $this->vendor(),
        ]);
    }

    public function update(UpdateRequest $request, string|int $id): JsonResponse
    {
        $this->vendorService->update(
            id: auth('seller')->id(),
            data: $request->validated(),
            image: $request->file('image'),
        );

        return response()->json(['message' => translate(':item updated successfully', ['item' => translate('Profile')])]);
    }

    public function updatePassword(PasswordUpdateRequest $request, string|int $id): JsonResponse
    {
        $this->vendorService->updatePassword(id: auth('seller')->id(), password: $request['password']);

        return response()->json(['message' => translate('Password updated successfully')]);
    }

    public function updateBankInfo(BankInfoUpdateRequest $request, string|int $id): RedirectResponse
    {
        if (auth('seller')->id() != $id) {
            ToastMagic::warning(translate('You cannot change others info'));

            return redirect()->back();
        }

        $this->vendorService->updateBankInfo(id: auth('seller')->id(), data: $request->validated());
        ToastMagic::success(translate('Bank info updated successfully').'!!');

        return redirect()->route('vendor.profile.index');
    }

    private function vendor(): mixed
    {
        return $this->vendorService->find(id: auth('seller')->id());
    }
}
