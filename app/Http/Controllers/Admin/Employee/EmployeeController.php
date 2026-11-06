<?php

namespace App\Http\Controllers\Admin\Employee;

use App\Enums\GlobalConstant;
use App\Enums\WebConfigKey;
use App\Exports\EmployeeListExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Employee\AddRequest;
use App\Http\Requests\Admin\Employee\UpdateRequest;
use App\Services\New\AdminRoleService;
use App\Services\New\AdminService;
use App\Services\New\BusinessSettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeController extends BaseController
{
    private const STATUS_ACTIVE = 1;

    private const STATUS_INACTIVE = 0;

    public function __construct(
        private readonly AdminService $adminService,
        private readonly AdminRoleService $adminRoleService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $employees = $this->adminService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $this->getPaginationLimit(),
            relations: ['role'],
            filters: [AdminService::FILTER_EXCLUDE_SUPER_ADMIN => true],
            sortBy: AdminService::SORT_NEWEST_ID,
        );

        $statusCounts = $this->adminService->getStatusCounts(
            filters: [AdminService::FILTER_EXCLUDE_SUPER_ADMIN => true],
        );

        return view('admin-views.employee.list', [
            'employees' => $employees,
            'totalEmployees' => array_sum($statusCounts),
            'activeEmployees' => $statusCounts['active'],
            'inactiveEmployees' => $statusCounts['inactive'],
            'totalRoles' => $this->getEmployeeRoles()->count(),
        ]);
    }

    public function getAddView(): View
    {
        return view('admin-views.employee.add-new', ['employee_roles' => $this->getEmployeeRoles()]);
    }

    public function add(AddRequest $request): RedirectResponse
    {
        if ((int) $request['role_id'] === AdminRoleService::SUPER_ADMIN_ROLE_ID) {
            ToastMagic::warning(translate('Access denied'));

            return back();
        }

        $this->adminService->create(
            data: $this->getEmployeeData(request: $request) + ['status' => self::STATUS_ACTIVE],
            image: $request->file('image'),
            identityImages: $this->getIdentityImages(request: $request),
        );
        ToastMagic::success(translate(':item added successfully', ['item' => translate('Employee')]));

        return redirect()->route('admin.people.employee.list');
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $employees = $this->adminService->getList(
            searchValue: $request['searchValue'],
            dataLimit: 'all',
            relations: ['role'],
            filters: [AdminService::FILTER_EXCLUDE_SUPER_ADMIN => true],
            sortBy: AdminService::SORT_NEWEST_ID,
        );

        return Excel::download(new EmployeeListExport([
            'employees' => $employees,
            'search' => $request['searchValue'],
            'active' => $employees->where('status', self::STATUS_ACTIVE)->count(),
            'inactive' => $employees->where('status', self::STATUS_INACTIVE)->count(),
        ]), 'Employees.xlsx');
    }

    public function quickView(Request $request): JsonResponse
    {
        $employee = $this->adminService->find(id: $request['id'], relations: ['role']);

        if ($employee === null) {
            return response()->json(['view' => '']);
        }

        return response()->json([
            'view' => view('admin-views.employee.partials._details-offcanvas', [
                'employee' => $employee,
                'permissionGroups' => GlobalConstant::groupEmployeeRolePermissions(
                    (array) json_decode($employee->role?->module_access ?? '', true)
                ),
            ])->render(),
        ]);
    }

    public function getUpdateView(string $id): View|RedirectResponse
    {
        $employee = $this->adminService->find(id: $id);

        if ($employee === null) {
            ToastMagic::error(translate('Employee not found'));

            return redirect()->route('admin.people.employee.list');
        }

        return view('admin-views.employee.edit', [
            'employee' => $employee,
            'adminRoles' => $this->getEmployeeRoles(),
        ]);
    }

    public function update(UpdateRequest $request, string $id): RedirectResponse
    {
        if ((int) $request['role_id'] === AdminRoleService::SUPER_ADMIN_ROLE_ID) {
            ToastMagic::warning(translate('Access denied'));

            return back();
        }

        $updated = $this->adminService->update(
            id: $id,
            data: $this->getEmployeeData(request: $request),
            image: $request->file('image'),
            identityImages: $this->getIdentityImages(request: $request),
        );

        $updated === null
            ? ToastMagic::error(translate('Employee not found'))
            : ToastMagic::success(translate(':item updated successfully', ['item' => translate('Employee')]));

        return redirect()->route('admin.people.employee.list');
    }

    public function updateStatus(Request $request): RedirectResponse|JsonResponse
    {
        $this->adminService->updateStatus(id: $request['id'], status: (bool) $request->get('status', 0));
        $message = translate('Employee status updated successfully');

        if ($request->ajax()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        ToastMagic::success($message);

        return back();
    }

    /** @return array<string, mixed> */
    private function getEmployeeData(AddRequest|UpdateRequest $request): array
    {
        return array_filter([
            'name' => $request['name'],
            'phone' => $request['phone'],
            'email' => $request['email'],
            'admin_role_id' => $request['role_id'],
            'identify_type' => $request['identify_type'],
            'identify_number' => $request['identify_number'],
            'password' => $request['password'] ? bcrypt($request['password']) : null,
        ], static fn ($value) => $value !== null);
    }

    /** @return array<int, UploadedFile> */
    private function getIdentityImages(AddRequest|UpdateRequest $request): array
    {
        return array_values(array_filter((array) $request->file('identity_image')));
    }

    private function getEmployeeRoles(): Collection
    {
        return $this->adminRoleService->getList(
            dataLimit: 'all',
            filters: [AdminRoleService::FILTER_EXCLUDE_SUPER_ADMIN => true],
        );
    }

    private function getPaginationLimit(): int|string
    {
        return $this->businessSettingService->getValueByType(type: WebConfigKey::PAGINATION_LIMIT) ?? DEFAULT_DATA_LIMIT;
    }
}
