@extends('layouts.admin.app')

@section('title', translate('Role and permission'))

@push('css_or_js')
    <style>
        #roleTable > thead > tr > th,
        #roleTable > tbody > tr > td {
            padding-top: 0.9rem;
            padding-bottom: 0.9rem;
        }

        .module-access-cell .module-access-group {
            min-width: 64px;
            padding-top: 3px;
        }

        .module-access-chip {
            padding: 4px 10px;
            line-height: 1.2;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-4">
            <h2 class="h1 mb-1 text-capitalize d-flex align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/add-new-employee.png') }}" width="20" alt="">
                {{ translate('Role and permission') }}
            </h2>
            <span class="fs-12 text-body-light">
                {{ translate('A role is a set of modules. Every employee holds one, and can open exactly what it allows.') }}
            </span>
        </div>

        @include('admin-views.custom-role.partials._list-overview')

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                    <h3 class="mb-0 text-capitalize gap-2">
                        {{ translate('Role table') }}
                        <span class="badge badge-info text-bg-info">{{ $roles->total() }}</span>
                    </h3>

                    <div class="d-flex flex-wrap gap-3 align-items-center justify-content-start justify-content-md-end min-w-100-mobile">
                        <div class="flex-grow-1 max-w-300 min-w-100-mobile">
                            <form action="{{ url()->current() }}" method="GET">
                                <div class="form-group mb-0">
                                    <div class="input-group">
                                        <input type="search" name="searchValue" class="form-control"
                                               placeholder="{{ translate('Search by role name') }}"
                                               value="{{ request('searchValue') }}">
                                        <div class="input-group-append search-submit">
                                            <button type="submit">
                                                <i class="fi fi-rr-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <a type="button" class="btn btn-outline-primary text-nowrap"
                           href="{{ route('admin.people.custom-role.export', ['searchValue' => request('searchValue')]) }}">
                            <i class="fi fi-sr-inbox-in"></i>
                            <span class="fs-12">{{ translate('Export') }}</span>
                        </a>

                        <a href="{{ route('admin.people.custom-role.add-new') }}" class="btn btn-primary text-nowrap">
                            <i class="fi fi-rr-plus-small"></i>
                            <span class="text">{{ translate('Add new role') }}</span>
                        </a>
                    </div>
                </div>

                <div class="table-responsive datatable-custom">
                    <table id="roleTable" class="table table-hover table-borderless table-thead-bordered align-middle card-table w-100">
                        <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>{{ translate('Serial') }}</th>
                                <th>{{ translate('Role name') }}</th>
                                <th class="text-center">{{ translate('Employees') }}</th>
                                <th class="min-w-300">{{ translate('Module access') }}</th>
                                <th>{{ translate('Created at') }}</th>
                                <th class="text-center">{{ translate('Status') }}</th>
                                <th class="text-center text-nowrap">{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($roles as $key => $role)
                            @include('admin-views.custom-role.partials._table-row', [
                                'role' => $role,
                                'serial' => $roles->firstItem() + $key,
                                'permissionGroups' => $rolePermissions[$role['id']] ?? [],
                            ])
                        @endforeach
                        </tbody>
                    </table>
                </div>

                @if($roles->total() > 0)
                    <div class="mt-4">
                        <div class="px-4 d-flex justify-content-center justify-content-md-end">
                            {{ $roles->links() }}
                        </div>
                    </div>
                @else
                    <div class="w-100 text-center py-5">
                        @include('layouts.admin.partials._empty-state', ['text' => 'no_data_found'], ['image' => 'default'])
                        @if(request('searchValue'))
                            <a class="btn btn-outline-primary mt-3" href="{{ route('admin.people.custom-role.list') }}">
                                {{ translate('Clear') }}
                            </a>
                        @else
                            <a class="btn btn-primary mt-3" href="{{ route('admin.people.custom-role.add-new') }}">
                                <i class="fi fi-rr-plus-small"></i>
                                {{ translate('Add new role') }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRoleDetails"
         aria-labelledby="offcanvasRoleDetailsLabel" style="--bs-offcanvas-width: 520px;"></div>

    <span id="get-role-details-url" data-url="{{ route('admin.people.custom-role.quick-view') }}"></span>
    <span id="role-not-found-message" data-text="{{ translate('Role not found') }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/user-management/custom-role.js') }}"></script>
@endpush
