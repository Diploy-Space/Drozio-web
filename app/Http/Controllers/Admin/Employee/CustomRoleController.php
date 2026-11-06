<?php

namespace App\Http\Controllers\Admin\Employee;

use App\Enums\ExportFileNames\Admin\Employee;
use App\Enums\GlobalConstant;
use App\Enums\WebConfigKey;
use App\Exports\EmployeeRoleListExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\CustomRole\AddRequest;
use App\Http\Requests\Admin\CustomRole\UpdateRequest;
use App\Services\New\AdminRoleService;
use App\Services\New\BusinessSettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomRoleController extends BaseController
{
    public function __construct(
        private readonly AdminRoleService $adminRoleService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $roles = $this->getRoles($request, dataLimit: $this->getPaginationLimit());
        $statusCounts = $this->adminRoleService->getStatusCounts(
            filters: [AdminRoleService::FILTER_EXCLUDE_SUPER_ADMIN => true],
        );

        return view('admin-views.custom-role.list', [
            'roles' => $roles,
            'rolePermissions' => $this->groupedPermissionsOf($roles),
            'totalRoles' => array_sum($statusCounts),
            'activeRoles' => $statusCounts['active'],
            'inactiveRoles' => $statusCounts['inactive'],
        ]);
    }

    private function getPaginationLimit(): int|string
    {
        return $this->businessSettingService->getValueByType(type: WebConfigKey::PAGINATION_LIMIT) ?? DEFAULT_DATA_LIMIT;
    }

    public function getAddView(): View
    {
        return view('admin-views.custom-role.add-new', [
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function add(AddRequest $request): RedirectResponse
    {
        $this->adminRoleService->create(data: [
            'name' => $request['name'],
            'module_access' => json_encode(array_values($request['modules'])),
            'status' => 1,
        ]);
        ToastMagic::success(translate('Role added successfully'));

        return redirect()->route('admin.people.custom-role.list');
    }

    public function quickView(Request $request): JsonResponse
    {
        $role = $this->adminRoleService->find(id: $request['id']);

        if ($role === null) {
            return response()->json(['view' => '']);
        }

        return response()->json([
            'view' => view('admin-views.custom-role.partials._details-offcanvas', [
                'role' => $role,
                'assignedEmployees' => $this->adminRoleService->countEmployees(id: $role->id),
                'permissionGroups' => GlobalConstant::groupEmployeeRolePermissions(
                    (array) json_decode($role->module_access ?? '', true)
                ),
            ])->render(),
        ]);
    }

    public function getUpdateView(mixed $id): View
    {
        $role = $this->adminRoleService->find(id: $id);

        abort_if($role === null, 404);

        return view('admin-views.custom-role.edit', [
            'role' => $role,
            'assignedEmployees' => $this->adminRoleService->countEmployees(id: $id),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function update(UpdateRequest $request): RedirectResponse
    {
        $role = $this->adminRoleService->find(id: $request['id']);

        $this->adminRoleService->update(id: $request['id'], data: [
            'name' => $request['name'],
            'module_access' => json_encode(array_values(array_unique(array_merge(
                $request['modules'],
                $this->unlistedPermissions($role?->module_access),
            )))),
        ]);
        ToastMagic::success(translate(':item updated successfully', ['item' => translate('Role')]));

        return redirect()->route('admin.people.custom-role.list');
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $this->adminRoleService->updateStatus(id: $request['id'], status: (bool) $request->get('status', 0));

        return response()->json([
            'success' => 1,
            'message' => translate(':item updated successfully', ['item' => translate('Status')]),
        ], 200);
    }

    public function delete(Request $request): JsonResponse
    {
        $assignedEmployees = $this->adminRoleService->countEmployees(id: $request['id']);

        if ($assignedEmployees > 0) {
            return response()->json([
                'success' => 0,
                'message' => translate('This role is assigned to :count employee(s). Move them to another role before deleting it.', ['count' => $assignedEmployees]),
            ], 200);
        }

        $this->adminRoleService->delete(id: $request['id']);

        return response()->json([
            'success' => 1,
            'message' => translate('Role deleted successfully'),
        ], 200);
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $roles = $this->getRoles($request, dataLimit: 'all');

        return Excel::download(new EmployeeRoleListExport([
            'roles' => $roles,
            'rolePermissions' => $this->groupedPermissionsOf($roles),
            'searchValue' => $request['searchValue'],
            'active' => count($roles->where('status', 1)),
            'inActive' => count($roles->where('status', 0)),
        ]), Employee::EMPLOYEE_ROLE_LIST);
    }

    private function permissionGroups(): array
    {
        $manager = businessModuleManager();

        return array_filter(
            GlobalConstant::EMPLOYEE_ROLE_PERMISSION_GROUPS,
            static fn (array $group, string $key): bool => $manager->get($key) === null || $manager->isAvailableInPanel($key),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function unlistedPermissions(?string $moduleAccess): array
    {
        $listed = [];

        foreach ($this->permissionGroups() as $group) {
            $listed = array_merge($listed, array_keys($group['permissions']));
        }

        $stored = (array) json_decode($moduleAccess ?? '', true);

        return array_values(array_diff($stored, $listed));
    }

    private function groupedPermissionsOf(Collection|LengthAwarePaginator $roles): array
    {
        $grouped = [];

        foreach ($roles as $role) {
            $grouped[$role->id] = GlobalConstant::groupEmployeeRolePermissions(
                (array) json_decode($role->module_access ?? '', true)
            );
        }

        return $grouped;
    }

    private function getRoles(Request $request, int|string $dataLimit): Collection|LengthAwarePaginator
    {
        return $this->adminRoleService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $dataLimit,
            page: $request['page'],
            withCount: ['employees'],
            filters: [AdminRoleService::FILTER_EXCLUDE_SUPER_ADMIN => true],
            sortBy: AdminRoleService::SORT_NEWEST_ID,
        );
    }
}
