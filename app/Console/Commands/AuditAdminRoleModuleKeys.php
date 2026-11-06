<?php

namespace App\Console\Commands;

use App\Enums\GlobalConstant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditAdminRoleModuleKeys extends Command
{
    protected $signature = 'admin-roles:audit-module-keys
                            {--fix : Rewrite stale keys in place using the same map as the migration}';

    protected $description = 'Find admin_roles whose module_access JSON holds keys outside the current permission vocabulary, or a business module selected without its dashboard.';

    private const FORWARD_MAP = [
        'pos_management' => ['retail.pos'],
        'pos' => ['retail.pos'],
        'promotion_management' => ['retail.marketing', 'wholesale.marketing'],
        'blog_management' => ['settings.content'],
        'user_section' => ['people.customers', 'people.vendors', 'people.delivery_men'],
        'support_section' => ['people.support'],
        'marketing_section' => ['retail.marketing', 'wholesale.marketing'],
        'business_section' => ['settings.business', 'settings.content'],
        'order_management' => ['retail.orders', 'wholesale.orders'],
        'product_management' => ['retail.catalog', 'wholesale.catalog', 'auction.catalog'],
        'report' => ['reports.finance', 'reports.operations', 'reports.tax'],
        'dashboard' => ['retail.dashboard', 'wholesale.dashboard', 'auction.dashboard'],
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

    public function handle(): int
    {
        $stale = [];

        foreach (DB::table('admin_roles')->get(['id', 'name', 'status', 'module_access']) as $role) {
            if (empty($role->module_access)) {
                continue;
            }

            $modules = json_decode($role->module_access, true);

            if (! is_array($modules)) {
                $this->warn("Role #{$role->id} ({$role->name}): module_access is not valid JSON — skipped.");

                continue;
            }

            $problems = $this->problemsIn($modules);

            if ($problems !== []) {
                $stale[] = [
                    'id' => $role->id,
                    'name' => $role->name,
                    'status' => $role->status,
                    'problem' => implode('; ', $problems),
                    'current' => implode(', ', $modules),
                ];
            }
        }

        if ($stale === []) {
            $this->info('All admin_roles use the current permission vocabulary. Nothing to fix.');

            return self::SUCCESS;
        }

        $this->warn(sprintf('%d role(s) need rewriting:', count($stale)));
        $this->table(['ID', 'Name', 'Status', 'Problem', 'Full module_access'], $stale);

        if (! $this->option('fix')) {
            $this->line('');
            $this->line('Re-run with --fix to rewrite these rows using the same mapping as');
            $this->line('migration 2026_09_22_115900_update_admin_roles_table.');

            return self::FAILURE;
        }

        foreach ($stale as $row) {
            $modules = json_decode(
                DB::table('admin_roles')->where('id', $row['id'])->value('module_access'),
                true
            ) ?: [];

            DB::table('admin_roles')
                ->where('id', $row['id'])
                ->update(['module_access' => json_encode($this->rewrite($modules))]);
        }

        $this->info(sprintf('Rewrote module_access for %d role(s).', count($stale)));
        $this->line('Tip: clear caches so middleware/sidebar see the new values immediately:');
        $this->line('     php artisan optimize:clear');

        return self::SUCCESS;
    }

    private function problemsIn(array $modules): array
    {
        $problems = [];
        $unknown = array_diff($modules, GlobalConstant::employeeRolePermissionKeys());

        if ($unknown !== []) {
            $problems[] = 'unknown keys: '.implode(', ', $unknown);
        }

        foreach (GlobalConstant::EMPLOYEE_ROLE_PERMISSION_GROUPS as $group) {
            if ($group['requires'] === null) {
                continue;
            }

            $selected = array_intersect($modules, array_keys($group['permissions']));

            if ($selected !== [] && ! in_array($group['requires'], $modules, true)) {
                $problems[] = $group['requires'].' missing';
            }
        }

        return $problems;
    }

    private function rewrite(array $modules): array
    {
        $rewritten = [];

        foreach ($modules as $module) {
            foreach (self::FORWARD_MAP[$module] ?? [$module] as $replacement) {
                $rewritten[] = $replacement;
            }
        }

        $rewritten = array_values(array_intersect(
            array_unique($rewritten),
            GlobalConstant::employeeRolePermissionKeys()
        ));

        foreach (GlobalConstant::EMPLOYEE_ROLE_PERMISSION_GROUPS as $group) {
            if ($group['requires'] === null) {
                continue;
            }

            if (array_intersect($rewritten, array_keys($group['permissions'])) !== []) {
                $rewritten[] = $group['requires'];
            }
        }

        return array_values(array_unique($rewritten));
    }
}
