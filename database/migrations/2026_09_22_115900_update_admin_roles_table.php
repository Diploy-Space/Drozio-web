<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FORWARD_MAP = [
        'dashboard' => ['retail.dashboard', 'wholesale.dashboard', 'auction.dashboard'],
        'marketing_section' => ['retail.marketing', 'wholesale.marketing'],
        'business_section' => ['settings.business', 'settings.content'],
        'pos' => ['retail.pos'],
        'catalog' => ['retail.catalog', 'wholesale.catalog', 'auction.catalog'],
        'orders' => ['retail.orders', 'wholesale.orders'],
        'marketing' => ['retail.marketing', 'wholesale.marketing'],
        'people' => ['people.customers', 'people.vendors', 'people.delivery_men', 'people.team', 'people.support'],
        'reports' => ['reports.finance', 'reports.operations', 'reports.tax'],
        'business_settings' => ['settings.business', 'settings.content'],
        'system_settings' => ['settings.system'],
        '3rd_party_setup' => ['settings.third_party'],
        'themes_and_addons' => ['settings.themes'],
    ];

    private const BACKWARD_MAP = [
        'retail.dashboard' => ['dashboard'],
        'wholesale.dashboard' => ['dashboard'],
        'auction.dashboard' => ['dashboard'],
        'retail.pos' => ['pos'],
        'retail.catalog' => ['catalog'],
        'wholesale.catalog' => ['catalog'],
        'auction.catalog' => ['catalog'],
        'auction.inhouse' => ['catalog'],
        'auction.vendor' => ['catalog'],
        'auction.finance' => ['reports'],
        'retail.orders' => ['orders'],
        'wholesale.orders' => ['orders'],
        'retail.marketing' => ['marketing'],
        'wholesale.marketing' => ['marketing'],
        'people.customers' => ['people'],
        'people.vendors' => ['people'],
        'people.delivery_men' => ['people'],
        'people.team' => ['people'],
        'people.support' => ['people'],
        'reports.finance' => ['reports'],
        'reports.operations' => ['reports'],
        'reports.tax' => ['reports'],
        'settings.business' => ['business_settings'],
        'settings.content' => ['business_settings'],
        'settings.system' => ['system_settings'],
        'settings.third_party' => ['3rd_party_setup'],
        'settings.themes' => ['themes_and_addons'],
    ];

    private const BUSINESS_DASHBOARDS = ['retail.dashboard', 'wholesale.dashboard', 'auction.dashboard'];

    public function up(): void
    {
        Schema::table('admin_roles', function (Blueprint $table): void {
            $table->text('module_access')->nullable()->change();
        });

        $this->rewriteModuleAccess(self::FORWARD_MAP, true);
    }

    public function down(): void
    {
        $this->rewriteModuleAccess(self::BACKWARD_MAP, false);
    }

    private function rewriteModuleAccess(array $map, bool $grantDashboards): void
    {
        foreach (DB::table('admin_roles')->get(['id', 'module_access']) as $role) {
            if (empty($role->module_access)) {
                continue;
            }

            $modules = json_decode($role->module_access, true);

            if (! is_array($modules)) {
                continue;
            }

            $rewritten = [];

            foreach ($modules as $module) {
                foreach ($map[$module] ?? [$module] as $replacement) {
                    $rewritten[] = $replacement;
                }
            }

            if ($grantDashboards && $this->holdsBusinessPermission($rewritten)) {
                $rewritten = array_merge($rewritten, self::BUSINESS_DASHBOARDS);
            }

            $rewritten = array_values(array_unique($rewritten));

            if ($rewritten !== $modules) {
                DB::table('admin_roles')
                    ->where('id', $role->id)
                    ->update(['module_access' => json_encode($rewritten)]);
            }
        }
    }

    private function holdsBusinessPermission(array $modules): bool
    {
        foreach ($modules as $module) {
            if (preg_match('/^(retail|wholesale|auction)\./', (string) $module)) {
                return true;
            }
        }

        return false;
    }
};
