@extends('layouts.admin.app')

@section('title', translate('Role update'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 d-flex align-items-center gap-3 min-w-0">
            <a class="btn btn-outline-secondary icon-btn flex-shrink-0"
               href="{{ route('admin.people.custom-role.list') }}"
               data-bs-toggle="tooltip" data-bs-title="{{ translate('Back to role list') }}">
                <i class="fi fi-rr-angle-left"></i>
            </a>
            <div class="min-w-0">
                <h2 class="h1 mb-1 text-capitalize d-flex align-items-center gap-2 flex-wrap min-w-0">
                    <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/add-new-employee.png') }}" width="20" alt="">
                    {{ $role['name'] }}
                    <span class="badge {{ $role['status'] == 1 ? 'badge-soft-success' : 'badge-soft-danger' }}">
                        {{ $role['status'] == 1 ? translate('Active') : translate('Inactive') }}
                    </span>
                </h2>
                <span class="fs-12 text-body-light d-flex align-items-center gap-2 flex-wrap">
                    {{ translate('Assigned employees') }}
                    <span class="badge badge-soft-info">{{ $assignedEmployees }}</span>
                    @if($assignedEmployees > 0)
                        <span>{{ translate('A permission removed here is removed from every one of them.') }}</span>
                    @endif
                </span>
            </div>
        </div>

        @include('admin-views.custom-role.partials._form', [
            'formAction' => route('admin.people.custom-role.update', [$role['id']]),
            'submitLabel' => translate('Update'),
            'roleId' => $role['id'],
            'roleName' => old('name', $role['name']),
            'selectedPermissions' => (array) old('modules', (array) json_decode($role['module_access'] ?? '', true)),
        ])
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/user-management/custom-role.js') }}"></script>
@endpush
