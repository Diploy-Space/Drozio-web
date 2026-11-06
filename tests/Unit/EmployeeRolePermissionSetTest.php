<?php

namespace Tests\Unit;

use App\Enums\GlobalConstant;
use App\Rules\EmployeeRolePermissionSet;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeRolePermissionSetTest extends TestCase
{
    #[Test]
    public function it_rejects_an_empty_selection(): void
    {
        $this->assertFailure([]);
    }

    #[Test]
    public function it_rejects_a_permission_outside_the_vocabulary(): void
    {
        $this->assertFailure(['retail.dashboard', 'retail.invented']);
    }

    #[Test]
    public function it_rejects_a_business_permission_without_its_dashboard(): void
    {
        $this->assertFailure(['retail.pos']);
        $this->assertFailure(['wholesale.orders', 'retail.dashboard']);
        $this->assertFailure(['auction.inhouse']);
    }

    #[Test]
    public function it_accepts_a_dashboard_on_its_own(): void
    {
        $this->assertPasses(['retail.dashboard']);
        $this->assertPasses(['wholesale.dashboard']);
        $this->assertPasses(['auction.dashboard']);
    }

    #[Test]
    public function it_accepts_common_permissions_without_any_dashboard(): void
    {
        $this->assertPasses(['people.support']);
        $this->assertPasses(['reports.tax', 'settings.content']);
    }

    #[Test]
    public function it_accepts_a_full_business_group(): void
    {
        $this->assertPasses(array_keys(GlobalConstant::EMPLOYEE_ROLE_PERMISSION_GROUPS['retail']['permissions']));
        $this->assertPasses(GlobalConstant::employeeRolePermissionKeys());
    }

    #[Test]
    public function every_business_group_declares_an_anchor_that_belongs_to_it(): void
    {
        foreach (GlobalConstant::EMPLOYEE_ROLE_PERMISSION_GROUPS as $key => $group) {
            if ($group['requires'] === null) {
                continue;
            }

            $this->assertArrayHasKey(
                $group['requires'],
                $group['permissions'],
                "Group {$key} requires a permission it does not declare."
            );
        }
    }

    private function assertPasses(array $permissions): void
    {
        $this->assertSame([], $this->failures($permissions));
    }

    private function assertFailure(array $permissions): void
    {
        $this->assertNotSame([], $this->failures($permissions));
    }

    private function failures(array $permissions): array
    {
        $failures = [];

        (new EmployeeRolePermissionSet)->validate(
            'modules',
            $permissions,
            function (string $message) use (&$failures): void {
                $failures[] = $message;
            }
        );

        return $failures;
    }
}
