<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Traits\SettingsTrait;
use App\Traits\UpdateClass;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class EnvironmentSettingsController extends BaseController
{
    use SettingsTrait, UpdateClass;

    /**
     * @return View Index function is the starting point of a controller
     *              Index function is the starting point of a controller
     */
    public function index(?Request $request, ?string $type = null): View
    {
        return view('admin-views.system-setup.environment-index');
    }

    public function update(Request $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('You cannot update this on demo mode'));

            return back();
        }

        try {
            $this->setEnvironmentValue(envKey: 'APP_DEBUG', envValue: $request['app_debug'] ?? envFileValue('APP_DEBUG'));
            $this->setEnvironmentValue(envKey: 'APP_MODE', envValue: $request['app_mode'] ?? config('app.mode'));
            ToastMagic::success(translate('Environment variables updated successfully'));
        } catch (Exception $exception) {
            ToastMagic::error(translate('Environment variables update failed'));
        }

        return back();
    }

    public function updateForceHttps(Request $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('You cannot update this on demo mode'));

            return back();
        }

        try {
            $this->setEnvironmentValue(envKey: 'FORCE_HTTPS', envValue: $request['force_https'] ?? envFileValue('FORCE_HTTPS', false));
            ToastMagic::success(translate('Environment variables updated successfully'));
        } catch (Exception $exception) {
            ToastMagic::error(translate('Environment variables update failed'));
        }

        return back();
    }

    public function optimizeSystem(Request $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('You cannot update this on demo mode'));

            return back();
        }

        if ($request['optimize_type'] == 'cache') {
            Artisan::call('optimize:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            ToastMagic::success(translate('Cache cleared successfully'));
        } elseif ($request['optimize_type'] == 'migrate') {
            Artisan::call('migrate');
            ToastMagic::success(translate('Database migrated successfully'));
        } elseif ($request['optimize_type'] == 'update') {
            $this->getProcessAllVersionsUpdates();
            ToastMagic::success(translate('Database updated successfully'));
        }

        return back();
    }

    public function installPassport(Request $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('You cannot update this on demo mode'));

            return back();
        }

        shell_exec('php ../artisan passport:install');
        ToastMagic::success(translate('Passport installed successfully'));

        return back();
    }
}
