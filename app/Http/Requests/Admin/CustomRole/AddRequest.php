<?php

namespace App\Http\Requests\Admin\CustomRole;

use App\Http\Requests\BaseRequest;
use App\Rules\EmployeeRolePermissionSet;
use Illuminate\Validation\Rule;

class AddRequest extends BaseRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                Rule::unique('admin_roles', 'name'),
            ],
            'modules' => ['required', 'array', new EmployeeRolePermissionSet],
            'modules.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate('The Role field is required!'),
            'modules.required' => translate('Select at least one module permission'),
            'modules.array' => translate('Select at least one module permission'),
        ];
    }
}
