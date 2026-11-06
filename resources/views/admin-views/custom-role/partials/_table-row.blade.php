<tr>
    <td>{{ $serial }}</td>
    <td>
        <a href="{{ route('admin.people.custom-role.update', [$role['id']]) }}"
           class="text-dark text-hover-primary text-capitalize fw-medium">
            {{ $role['name'] }}
        </a>
    </td>
    <td class="text-center">
        @if($role->employees_count > 0)
            <span class="badge badge-soft-info">{{ $role->employees_count }}</span>
        @else
            <span class="text-body-light">0</span>
        @endif
    </td>
    <td class="text-wrap module-access-cell">
        <div class="d-flex flex-column gap-2">
            @forelse($permissionGroups as $groupLabel => $groupPermissions)
                @php($groupBadge = \App\Enums\GlobalConstant::employeeRoleGroupBadge($groupLabel))
                <div class="d-flex gap-2 align-items-start">
                    <span class="module-access-group fs-10 fw-bold text-uppercase text-body-light text-nowrap">
                        {{ translate($groupLabel) }}
                    </span>
                    <span class="d-flex flex-wrap gap-2">
                        @foreach($groupPermissions as $groupPermission)
                            <span class="badge {{ $groupBadge }} rounded-pill fw-normal fs-10 module-access-chip">
                                {{ translate($groupPermission) }}
                            </span>
                        @endforeach
                    </span>
                </div>
            @empty
                <span class="text-body-light fs-12">{{ translate('No module access') }}</span>
            @endforelse
        </div>
    </td>
    <td class="text-nowrap">{{ date('d M, Y', strtotime($role['created_at'])) }}</td>
    <td class="text-center">
        <form action="{{ route('admin.people.custom-role.employee-role-status') }}" method="post"
              id="employee-role-status-{{ $role['id'] }}-form" class="no-reload-form">
            @csrf
            <input type="hidden" name="id" value="{{ $role['id'] }}">
            <label class="switcher mx-auto" for="employee-role-status-{{ $role['id'] }}">
                <input
                    class="switcher_input custom-modal-plugin"
                    type="checkbox" value="1" name="status"
                    id="employee-role-status-{{ $role['id'] }}"
                    {{ $role['status'] == 1 ? 'checked' : '' }}
                    data-modal-type="input-change-form"
                    data-modal-form="#employee-role-status-{{ $role['id'] }}-form"
                    data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/employee-on.png') }}"
                    data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/employee-off.png') }}"
                    data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Employee role Status')]).'?' }}"
                    data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Employee role Status')]).'?' }}"
                    data-on-message="<p>{{ translate('Employees holding this role can access the system.') }}</p>"
                    data-off-message="<p>{{ translate('Employees holding this role cannot access the system.') }}</p>"
                    data-on-button-text="{{ translate('Turn On') }}"
                    data-off-button-text="{{ translate('Turn Off') }}">
                <span class="switcher_control"></span>
            </label>
        </form>
    </td>
    <td>
        <div class="d-flex gap-10 justify-content-center">
            <a class="btn btn-outline-info icon-btn get-role-details"
               title="{{ translate('View') }}" data-bs-toggle="tooltip"
               href="javascript:" data-id="{{ $role['id'] }}">
                <i class="fi fi-rr-eye"></i>
            </a>
            <a href="{{ route('admin.people.custom-role.update', [$role['id']]) }}"
               class="btn btn-outline-primary icon-btn"
               title="{{ translate('Edit') }}" data-bs-toggle="tooltip">
                <i class="fi fi-rr-pencil"></i>
            </a>
            @if($role->employees_count > 0)
                <span data-bs-toggle="tooltip"
                      data-bs-title="{{ translate('Move its employees to another role before deleting it') }}">
                    <button type="button" class="btn btn-outline-danger icon-btn" disabled>
                        <i class="fi fi-rr-trash"></i>
                    </button>
                </span>
            @else
                <a href="javascript:"
                   class="btn btn-outline-danger icon-btn delete-role"
                   data-action="{{ route('admin.people.custom-role.delete') }}"
                   title="{{ translate('Delete') }}" data-bs-toggle="tooltip"
                   data-id="{{ $role['id'] }}">
                    <i class="fi fi-rr-trash"></i>
                </a>
            @endif
        </div>
    </td>
</tr>
