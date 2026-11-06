<div class="offcanvas-header bg-body align-items-start gap-3">
    <div class="min-w-0">
        <h3 class="mb-1 text-capitalize text-break">{{ $role['name'] }}</h3>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge {{ $role['status'] == 1 ? 'badge-soft-success' : 'badge-soft-danger' }}">
                {{ $role['status'] == 1 ? translate('Active') : translate('Inactive') }}
            </span>
            <span class="fs-12 text-body-light">
                {{ translate('Created at') }} {{ date('d M, Y', strtotime($role['created_at'])) }}
            </span>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}"></button>
</div>

<div class="offcanvas-body d-flex flex-column gap-3">
    <div class="bg-section rounded-10 p-20 d-flex justify-content-between align-items-center gap-3">
        <div class="d-flex gap-2 align-items-center">
            <i class="fi fi-rr-users-alt text-body-light"></i>
            <h5 class="mb-0">{{ translate('Assigned employees') }}</h5>
        </div>
        <span class="badge badge-soft-info">{{ $assignedEmployees }}</span>
    </div>

    <div class="bg-section rounded-10 p-20">
        <h5 class="mb-1">{{ translate('Module access') }}</h5>
        <span class="fs-12 text-body-light d-block mb-3">
            {{ translate('What an employee holding this role can open.') }}
        </span>

        @forelse($permissionGroups as $groupLabel => $groupPermissions)
            @php($groupBadge = \App\Enums\GlobalConstant::employeeRoleGroupBadge($groupLabel))
            <div class="{{ $loop->last ? '' : 'mb-3' }}">
                <h6 class="fs-12 fw-bold text-dark text-uppercase mb-2">{{ translate($groupLabel) }}</h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($groupPermissions as $groupPermission)
                        <span class="badge {{ $groupBadge }} rounded-pill fw-normal fs-12">{{ translate($groupPermission) }}</span>
                    @endforeach
                </div>
            </div>
        @empty
            <span class="text-body-light fs-12">{{ translate('No module access') }}</span>
        @endforelse
    </div>

    @if($assignedEmployees > 0)
        <div class="d-flex gap-2 align-items-start fs-12 text-body-light">
            <i class="fi fi-rr-info pt-1"></i>
            <span>{{ translate('A permission removed here is removed from every employee holding this role.') }}</span>
        </div>
    @endif
</div>

<div class="offcanvas-footer d-flex gap-3 p-20 border-top">
    <button type="button" class="btn btn-secondary w-50" data-bs-dismiss="offcanvas">
        {{ translate('Close') }}
    </button>
    <a href="{{ route('admin.people.custom-role.update', [$role['id']]) }}" class="btn btn-primary w-50">
        <i class="fi fi-rr-pencil"></i>
        {{ translate('Edit') }}
    </a>
</div>
