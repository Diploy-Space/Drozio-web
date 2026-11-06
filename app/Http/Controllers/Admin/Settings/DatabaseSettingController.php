<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatabaseSettingController extends BaseController
{
    /**
     * System/installation tables that must never be cleaned. Shown in the UI with a disabled
     * checkbox, and also stripped server-side in delete() as defense in depth.
     */
    private const PROTECTED_TABLES = [
        'addon_settings',
        'admin_roles',
        'admins',
        'business_settings',
        'colors',
        'currencies',
        'failed_jobs',
        'migrations',
        'oauth_access_tokens',
        'oauth_auth_codes',
        'oauth_clients',
        'oauth_personal_access_clients',
        'oauth_refresh_tokens',
        'password_resets',
        'personal_access_tokens',
        'phone_or_email_verifications',
        'social_medias',
        'soft_credentials',
        'users',
    ];

    /**
     * @return View Index function is the starting point of a controller
     *              Index function is the starting point of a controller
     */
    public function index(?Request $request, ?string $type = null): View
    {
        $allTables = DB::table('information_schema.tables')
            ->where('table_schema', DB::getDatabaseName())
            ->pluck('TABLE_NAME')
            ->toArray();
        sort($allTables);

        $tables = [];
        foreach ($allTables as $table) {
            $tables[] = [
                'name' => $table,
                'count' => DB::table($table)->count(),
                'protected' => in_array($table, self::PROTECTED_TABLES, true),
            ];
        }

        return view('admin-views.business-settings.db-index', compact('tables'));
    }

    public function delete(Request $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::error(translate('Uploading ZIP files is currently unavailable in demo mode'));

            return back();
        }

        $tables = array_values(array_diff((array) $request['tables'], self::PROTECTED_TABLES));

        if (count($tables) == 0) {
            ToastMagic::error(translate('No Table Updated'));

            return back();
        }

        try {
            DB::transaction(function () use ($tables) {
                foreach ($tables as $table) {
                    DB::table($table)->delete();
                }
            });
        } catch (Exception $exception) {
            ToastMagic::error(translate('Failed to update'));

            return back();
        }

        ToastMagic::success(translate('Updated successfully'));

        return back();
    }
}
