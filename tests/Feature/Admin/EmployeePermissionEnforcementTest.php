<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Services\BusinessModule\BusinessModuleManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeePermissionEnforcementTest extends TestCase
{
    private const ROLE_ID = 900901;

    private const EMPLOYEE_ID = 900902;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/employee-permission-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private const GUARDED_ROUTES = [
        'retail.pos' => ['admin.retail.pos.index', []],
        'retail.catalog' => ['admin.retail.products.list', ['type' => 'in-house']],
        'retail.orders' => ['admin.retail.orders.list', ['status' => 'all']],
        'retail.marketing' => ['admin.retail.notification.index', []],
        'wholesale.dashboard' => ['admin.wholesale.dashboard.index', []],
        'people.customers' => ['admin.people.customer.list', []],
        'people.team' => ['admin.people.custom-role.list', []],
        'people.support' => ['admin.people.support-ticket.view', []],
        'reports.finance' => ['admin.report.retail.admin-earning', []],
        'reports.operations' => ['admin.report.retail.all-product', []],
        'settings.business' => ['admin.settings.business-settings.web-config.index', []],
        'settings.system' => ['admin.settings.system-setup.environment-setup', []],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (str_ends_with(DB::connection()->getDatabaseName(), '_snapshot_test')) {
            $this->markTestSkipped('Refusing to write fixtures into the API snapshot clone.');
        }

        $this->deleteFixtures();
        $this->stubAdminLayout();
    }

    protected function tearDown(): void
    {
        $this->deleteFixtures();

        if (is_file(self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php')) {
            unlink(self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php');
        }

        parent::tearDown();
    }

    #[Test]
    public function every_guarded_route_denies_an_employee_whose_role_lacks_its_key(): void
    {
        $employee = $this->employeeHolding(['retail.dashboard']);

        foreach (self::GUARDED_ROUTES as $permission => [$routeName, $parameters]) {
            $this->assertTrue(
                $this->hasRoute($routeName),
                "Route {$routeName} for {$permission} no longer exists."
            );

            $this->actingAs($employee, 'admin')
                ->get(route($routeName, $parameters))
                ->assertRedirect(route('admin.retail.dashboard.index'));
        }
    }

    #[Test]
    public function granting_the_key_opens_the_route_it_guards(): void
    {
        foreach (self::GUARDED_ROUTES as $permission => [$routeName, $parameters]) {
            $employee = $this->employeeHolding(array_unique([
                'retail.dashboard',
                $this->anchorFor($permission),
                $permission,
            ]));

            $response = $this->actingAs($employee, 'admin')->get(route($routeName, $parameters));

            $this->assertNotSame(
                route('admin.retail.dashboard.index'),
                $response->headers->get('Location'),
                "Route {$routeName} still denied an employee holding {$permission}."
            );
        }
    }

    #[Test]
    public function the_dashboard_key_is_what_opens_a_business_module(): void
    {
        $employee = $this->employeeHolding(['retail.dashboard', 'retail.pos']);

        $this->actingAs($employee, 'admin')
            ->get(route('admin.retail.dashboard.index'))
            ->assertOk();

        $this->actingAs($employee, 'admin')
            ->get(route('admin.wholesale.dashboard.index'))
            ->assertRedirect(route('admin.retail.dashboard.index'));
    }

    #[Test]
    public function the_business_switcher_offers_only_the_modules_the_role_can_open(): void
    {
        $this->actingAs($this->employeeHolding(['wholesale.dashboard', 'wholesale.orders']), 'admin');

        app()->forgetInstance(BusinessModuleManager::class);
        $manager = app(BusinessModuleManager::class);

        $this->assertSame(['wholesale'], array_keys($manager->permittedBusinessModules()));
    }

    #[Test]
    public function a_deactivated_role_locks_the_employee_out_of_everything(): void
    {
        $employee = $this->employeeHolding(['retail.dashboard', 'retail.pos'], roleStatus: 0);

        $this->actingAs($employee, 'admin')
            ->get(route('admin.retail.pos.index'))
            ->assertForbidden();
    }

    private function anchorFor(string $permission): string
    {
        [$prefix] = explode('.', $permission);

        return in_array($prefix, ['retail', 'wholesale', 'auction'], true) ? $prefix.'.dashboard' : $permission;
    }

    private function hasRoute(string $name): bool
    {
        return app('router')->has($name);
    }

    private function employeeHolding(array $permissions, int $roleStatus = 1): Admin
    {
        DB::table('admin_roles')->updateOrInsert(
            ['id' => self::ROLE_ID],
            [
                'name' => 'Permission enforcement fixture',
                'status' => $roleStatus,
                'module_access' => json_encode(array_values($permissions)),
            ]
        );

        DB::table('admins')->updateOrInsert(
            ['id' => self::EMPLOYEE_ID],
            [
                'name' => 'Permission enforcement employee',
                'email' => 'permission-enforcement@example.test',
                'password' => bcrypt('permission-enforcement'),
                'admin_role_id' => self::ROLE_ID,
                'status' => 1,
            ]
        );

        return Admin::query()->findOrFail(self::EMPLOYEE_ID)->load('role');
    }

    private function stubAdminLayout(): void
    {
        $path = self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php';

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, "@yield('content')@stack('script')");

        View::getFinder()->prependLocation(self::STUB_VIEW_ROOT);
        View::getFinder()->flush();
    }

    private function deleteFixtures(): void
    {
        DB::table('admins')->where('id', self::EMPLOYEE_ID)->delete();
        DB::table('admin_roles')->where('id', self::ROLE_ID)->delete();
    }
}
