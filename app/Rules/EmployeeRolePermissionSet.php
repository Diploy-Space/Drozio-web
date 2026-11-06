<?php

namespace App\Rules;

use App\Enums\GlobalConstant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmployeeRolePermissionSet implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $permissions = array_values(array_filter((array) $value, 'is_string'));

        if ($permissions === []) {
            $fail(translate('Select at least one module permission'));

            return;
        }

        $unknown = array_diff($permissions, GlobalConstant::employeeRolePermissionKeys());

        if ($unknown !== []) {
            $fail(translate('One of the selected module permissions does not exist'));

            return;
        }

        foreach (GlobalConstant::EMPLOYEE_ROLE_PERMISSION_GROUPS as $group) {
            if ($group['requires'] === null) {
                continue;
            }

            $selected = array_intersect($permissions, array_keys($group['permissions']));

            if ($selected !== [] && ! in_array($group['requires'], $permissions, true)) {
                $fail(translate('Dashboard permission is required for :module', ['module' => translate($group['label'])]));

                return;
            }
        }
    }
}
