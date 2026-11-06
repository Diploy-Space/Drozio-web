<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AdminRole;
use App\Services\BusinessModule\BusinessModuleManager;
use App\Utils\Helpers;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeRolePermissionTest extends TestCase
{
    private const ROLE_ID = 900701;

    protected function tearDown(): void
    {
        DB::table('admin_roles')->where('id', self::ROLE_ID)->delete();

        parent::tearDown();
    }

    #[Test]
    public function super_admin_holds_every_permission(): void
    {
        $this->actAsEmployee(['retail.dashboard'], adminRoleId: 1);

        $this->assertTrue(Helpers::module_permission_check('wholesale.orders'));
        $this->assertTrue(Helpers::module_permission_check('settings.themes'));
    }

    #[Test]
    public function an_employee_only_holds_the_keys_its_role_stores(): void
    {
        $this->actAsEmployee(['retail.dashboard', 'retail.pos']);

        $this->assertTrue(Helpers::module_permission_check('retail.pos'));
        $this->assertFalse(Helpers::module_permission_check('wholesale.orders'));
    }

    #[Test]
    public function any_of_the_listed_keys_grants_access(): void
    {
        $this->actAsEmployee(['auction.dashboard', 'auction.vendor']);

        $this->assertTrue(Helpers::module_permission_check('auction.inhouse', 'auction.vendor'));
        $this->assertFalse(Helpers::module_permission_check('auction.catalog', 'auction.finance'));
    }

    #[Test]
    public function a_disabled_role_holds_nothing(): void
    {
        $this->actAsEmployee(['retail.dashboard'], status: 0);

        $this->assertFalse(Helpers::module_permission_check('retail.dashboard'));
    }

    #[Test]
    public function the_switcher_only_offers_modules_whose_dashboard_is_granted(): void
    {
        $this->actAsEmployee(['wholesale.dashboard', 'wholesale.orders']);

        $manager = app(BusinessModuleManager::class);
        $permitted = array_keys($manager->permittedBusinessModules());

        $this->assertNotContains('retail', $permitted);
        $this->assertNotContains('auction', $permitted);

        if (array_key_exists('wholesale', $manager->available())) {
            $this->assertContains('wholesale', $permitted);
            $this->assertSame('wholesale', $manager->defaultKey());
        }
    }

    #[Test]
    public function the_migration_expands_legacy_keys_without_removing_access(): void
    {
        $this->skipWhenNotOnTheContractDatabase();

        DB::table('admin_roles')->updateOrInsert(
            ['id' => self::ROLE_ID],
            ['name' => 'Permission split fixture', 'status' => 1, 'module_access' => json_encode(['catalog', 'orders', 'people', 'business_settings'])]
        );

        $this->migration()->up();

        $stored = json_decode(DB::table('admin_roles')->where('id', self::ROLE_ID)->value('module_access'), true);

        foreach ([
            'retail.catalog', 'wholesale.catalog', 'auction.catalog',
            'retail.orders', 'wholesale.orders',
            'people.customers', 'people.vendors', 'people.delivery_men', 'people.team', 'people.support',
            'settings.business', 'settings.content',
            'retail.dashboard', 'wholesale.dashboard', 'auction.dashboard',
        ] as $expected) {
            $this->assertContains($expected, $stored);
        }

        $this->assertNotContains('catalog', $stored);
        $this->assertNotContains('business_settings', $stored);
    }

    #[Test]
    public function the_migration_grants_no_dashboard_to_a_common_only_role(): void
    {
        $this->skipWhenNotOnTheContractDatabase();

        DB::table('admin_roles')->updateOrInsert(
            ['id' => self::ROLE_ID],
            ['name' => 'Permission split fixture', 'status' => 1, 'module_access' => json_encode(['reports'])]
        );

        $this->migration()->up();

        $stored = json_decode(DB::table('admin_roles')->where('id', self::ROLE_ID)->value('module_access'), true);

        $this->assertSame(['reports.finance', 'reports.operations', 'reports.tax'], $stored);
    }

    private function skipWhenNotOnTheContractDatabase(): void
    {
        if (str_ends_with(DB::connection()->getDatabaseName(), '_snapshot_test')) {
            $this->markTestSkipped('Refusing to write fixtures into the API snapshot clone.');
        }
    }

    private function migration(): object
    {
        return require base_path('database/migrations/2026_09_22_115900_update_admin_roles_table.php');
    }

    private function actAsEmployee(array $permissions, int $adminRoleId = 2, int $status = 1): void
    {
        $role = new AdminRole([
            'name' => 'Permission fixture',
            'module_access' => json_encode($permissions),
            'status' => $status,
        ]);
        $role->id = self::ROLE_ID;

        $admin = new Admin;
        $admin->id = 900702;
        $admin->admin_role_id = $adminRoleId;
        $admin->setRelation('role', $role);

        $this->actingAs($admin, 'admin');

        app()->forgetInstance(BusinessModuleManager::class);
    }
}
