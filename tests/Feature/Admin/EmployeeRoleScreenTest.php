<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeRoleScreenTest extends TestCase
{
    private const ROLE_ID = 900801;

    private const EMPLOYEE_ID = 900802;

    private const SUPER_ADMIN_ID = 900803;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/employee-role-screen-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

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

    #[Test]
    public function the_list_screen_shows_the_roles_and_an_add_button(): void
    {
        $this->createRole();

        $this->actingAs($this->superAdmin(), 'admin')
            ->get(route('admin.people.custom-role.list'))
            ->assertOk()
            ->assertSee('Screen fixture role')
            ->assertSee(route('admin.people.custom-role.add-new'))
            ->assertSee('Retail', false);
    }

    #[Test]
    public function the_list_screen_carries_no_role_form(): void
    {
        $response = $this->actingAs($this->superAdmin(), 'admin')
            ->get(route('admin.people.custom-role.list'))
            ->assertOk();

        $this->assertStringNotContainsString('name="modules[]"', $response->getContent());
    }

    #[Test]
    public function the_add_and_edit_screens_render_the_same_form(): void
    {
        $this->createRole();
        $admin = $this->superAdmin();

        $add = $this->actingAs($admin, 'admin')->get(route('admin.people.custom-role.add-new'))->assertOk();
        $edit = $this->actingAs($admin, 'admin')->get(route('admin.people.custom-role.update', [self::ROLE_ID]))->assertOk();

        foreach (['name="modules[]"', 'data-permission-group="retail"', 'data-permission-anchor="retail.dashboard"', 'select-all-module'] as $marker) {
            $this->assertStringContainsString($marker, $add->getContent());
            $this->assertStringContainsString($marker, $edit->getContent());
        }

        $this->assertStringContainsString('value="retail.pos"', $edit->getContent());
    }

    #[Test]
    public function a_role_with_employees_cannot_be_deleted(): void
    {
        $this->createRole();
        $this->assignEmployee();

        $this->actingAs($this->superAdmin(), 'admin')
            ->post(route('admin.people.custom-role.delete'), ['id' => self::ROLE_ID])
            ->assertOk()
            ->assertJsonPath('success', 0);

        $this->assertTrue(DB::table('admin_roles')->where('id', self::ROLE_ID)->exists());
    }

    #[Test]
    public function the_list_screen_disables_delete_for_an_assigned_role(): void
    {
        $this->createRole();
        $this->assignEmployee();

        $content = $this->actingAs($this->superAdmin(), 'admin')
            ->get(route('admin.people.custom-role.list'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Move its employees to another role before deleting it', $content);
        $this->assertDoesNotMatchRegularExpression('/delete-role[^>]*data-id="'.self::ROLE_ID.'"/', $content);
        $this->assertMatchesRegularExpression('/get-role-details[^>]*data-id="'.self::ROLE_ID.'"/s', $content);
    }

    #[Test]
    public function an_unassigned_role_is_deleted(): void
    {
        $this->createRole();

        $this->actingAs($this->superAdmin(), 'admin')
            ->post(route('admin.people.custom-role.delete'), ['id' => self::ROLE_ID])
            ->assertOk()
            ->assertJsonPath('success', 1);

        $this->assertFalse(DB::table('admin_roles')->where('id', self::ROLE_ID)->exists());
    }

    #[Test]
    public function submitting_the_add_form_stores_the_selected_permissions(): void
    {
        $this->actingAs($this->superAdmin(), 'admin')
            ->post(route('admin.people.custom-role.store'), [
                'name' => 'Screen fixture role',
                'modules' => ['retail.dashboard', 'retail.pos', 'people.support'],
            ])
            ->assertRedirect(route('admin.people.custom-role.list'));

        $stored = DB::table('admin_roles')->where('name', 'Screen fixture role')->value('module_access');

        $this->assertSame(['retail.dashboard', 'retail.pos', 'people.support'], json_decode($stored, true));

        DB::table('admin_roles')->where('name', 'Screen fixture role')->delete();
    }

    #[Test]
    public function submitting_the_edit_form_rewrites_the_selected_permissions(): void
    {
        $this->createRole();

        $this->actingAs($this->superAdmin(), 'admin')
            ->post(route('admin.people.custom-role.update', [self::ROLE_ID]), [
                'id' => self::ROLE_ID,
                'name' => 'Screen fixture role',
                'modules' => ['wholesale.dashboard', 'wholesale.orders'],
            ])
            ->assertRedirect(route('admin.people.custom-role.list'));

        $stored = json_decode(DB::table('admin_roles')->where('id', self::ROLE_ID)->value('module_access'), true);

        $this->assertSame(['wholesale.dashboard', 'wholesale.orders'], $stored);
    }

    #[Test]
    public function a_module_permission_without_its_dashboard_is_rejected(): void
    {
        $this->actingAs($this->superAdmin(), 'admin')
            ->from(route('admin.people.custom-role.add-new'))
            ->post(route('admin.people.custom-role.store'), [
                'name' => 'Screen fixture role',
                'modules' => ['retail.pos'],
            ])
            ->assertRedirect(route('admin.people.custom-role.add-new'))
            ->assertSessionHasErrors('modules');

        $this->assertFalse(DB::table('admin_roles')->where('name', 'Screen fixture role')->exists());
    }

    private function superAdmin(): Admin
    {
        DB::table('admins')->updateOrInsert(
            ['id' => self::SUPER_ADMIN_ID],
            [
                'name' => 'Screen fixture super admin',
                'email' => 'screen-fixture-super-admin@example.test',
                'password' => bcrypt('screen-fixture'),
                'admin_role_id' => 1,
                'status' => 1,
            ]
        );

        return Admin::query()->findOrFail(self::SUPER_ADMIN_ID);
    }

    private function createRole(): void
    {
        DB::table('admin_roles')->updateOrInsert(
            ['id' => self::ROLE_ID],
            [
                'name' => 'Screen fixture role',
                'status' => 1,
                'module_access' => json_encode(['retail.dashboard', 'retail.pos']),
            ]
        );
    }

    private function assignEmployee(): void
    {
        DB::table('admins')->updateOrInsert(
            ['id' => self::EMPLOYEE_ID],
            [
                'name' => 'Screen fixture employee',
                'email' => 'screen-fixture-employee@example.test',
                'password' => bcrypt('screen-fixture'),
                'admin_role_id' => self::ROLE_ID,
                'status' => 1,
            ]
        );
    }

    private function deleteFixtures(): void
    {
        DB::table('admins')->whereIn('id', [self::EMPLOYEE_ID, self::SUPER_ADMIN_ID])->delete();
        DB::table('admin_roles')->where('id', self::ROLE_ID)->delete();
    }
}
