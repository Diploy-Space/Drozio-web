<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BusinessModule\BusinessModuleManager;
use Illuminate\Http\RedirectResponse;

/**
 * Handles switching the admin's active business module (Retail / Auction /
 * Wholesale). State lives in the session; see BusinessModuleManager.
 */
class BusinessModuleController extends Controller
{
    public function __construct(
        private readonly BusinessModuleManager $businessModuleManager
    ) {
    }

    /**
     * Switch the active business module and land on that module's home.
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
     * Legacy admin/dashboard entry point: forward to the active module's
     * dashboard (admin/{module}/dashboard) so old links and bookmarks keep
     * working.
     */
    public function dashboard(): RedirectResponse
    {
        return redirect(businessModuleDashboardUrl());
    }
}
