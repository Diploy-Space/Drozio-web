<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\BusinessModule\BusinessModuleManager;
use Illuminate\Http\RedirectResponse;

/**
 * Handles switching the vendor's active business module (Retail / Auction /
 * Wholesale). State lives in the vendor-scoped session key; see
 * BusinessModuleManager (the panel is resolved from the vendor/* URL).
 */
class BusinessModuleController extends Controller
{
    public function __construct(
        private readonly BusinessModuleManager $businessModuleManager
    ) {
    }

    /**
     * Switch the active business module and land on that module's vendor home.
     * Unavailable/unknown targets change nothing and bounce back.
     */
    public function switch(string $module): RedirectResponse
    {
        if (!$this->businessModuleManager->setActive($module)) {
            return back();
        }

        return redirect(url($this->businessModuleManager->active()->landing));
    }

    /**
     * Legacy vendor/dashboard entry point: forward to the active module's
     * dashboard (vendor/{module}/dashboard) so old links and bookmarks keep
     * working.
     */
    public function dashboard(): RedirectResponse
    {
        return redirect(businessModuleDashboardUrl());
    }
}
