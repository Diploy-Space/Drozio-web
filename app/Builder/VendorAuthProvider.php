<?php

namespace App\Builder;

use Illuminate\Support\Facades\Auth;
use Modules\Builder\app\Contracts\VendorAuthProvider as VendorAuthProviderContract;

class VendorAuthProvider implements VendorAuthProviderContract
{
    public function current(): ?array
    {
        $seller = Auth::guard('seller')->user();

        if (! $seller) {
            return null;
        }

        $name = \trim(($seller->f_name ?? '').' '.($seller->l_name ?? ''));

        return [
            'name' => $name !== '' ? $name : ($seller->email ?? 'Vendor'),
            'email' => $seller->email ?? null,
            'image_url' => $this->safeImageUrl($seller),
        ];
    }

    public function logoutUrl(): string
    {
        return \route('vendor.auth.logout');
    }

    private function safeImageUrl($seller): ?string
    {
        if (empty($seller->image)) {
            return null;
        }

        try {
            return getStorageImages(path: $seller->image_full_url, type: 'backend-profile');
        } catch (\Throwable) {
            return null;
        }
    }
}
