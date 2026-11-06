@extends('layouts.admin.app')

@section('title', translate('Add new role'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 d-flex align-items-center gap-3 min-w-0">
            <a class="btn btn-outline-secondary icon-btn flex-shrink-0"
               href="{{ route('admin.people.custom-role.list') }}"
               data-bs-toggle="tooltip" data-bs-title="{{ translate('Back to role list') }}">
                <i class="fi fi-rr-angle-left"></i>
            </a>
            <div class="min-w-0">
                <h2 class="h1 mb-1 text-capitalize d-flex align-items-center gap-2 min-w-0">
                    <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/add-new-employee.png') }}" width="20" alt="">
                    {{ translate('Add new role') }}
                </h2>
                <span class="fs-12 text-body-light">
                    {{ translate('Name the role, then pick the modules an employee holding it can open.') }}
                </span>
            </div>
        </div>

        @include('admin-views.custom-role.partials._form', [
            'formAction' => route('admin.people.custom-role.store'),
            'submitLabel' => translate('Submit'),
            'roleName' => old('name'),
            'selectedPermissions' => (array) old('modules', []),
        ])
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/user-management/custom-role.js') }}"></script>
@endpush
