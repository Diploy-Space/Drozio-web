<form id="submit-create-role" method="post" action="{{ $formAction }}"
      class="text-start form-advance-validation form-advance-inputs-validation non-ajax-form-validate" novalidate>
    @csrf
    @isset($roleId)
        <input type="hidden" name="id" value="{{ $roleId }}">
    @endisset

    <div class="card mb-3">
        <div class="card-body">
            <h4 class="mb-3">{{ translate('Role information') }}</h4>
            <div class="row">
                <div class="col-lg-6">
                    <div class="form-group mb-0">
                        <label for="name" class="form-label">{{ translate('Role name') }}</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ $roleName }}"
                               data-required-msg="{{ translate('Name field is required') }}"
                               placeholder="{{ translate('Ex').': '.translate('Store manager') }}" required>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @include('admin-views.custom-role.partials._permission-groups', [
                'permissionGroups' => $permissionGroups,
                'selectedPermissions' => $selectedPermissions,
            ])

            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap border-top pt-4">
                <span class="fs-12 text-body-light">
                    {{ translate('Selected') }}
                    <span class="badge badge-soft-info" id="selected-permission-count">0</span>
                </span>
                <div class="d-flex gap-3">
                    <a href="{{ route('admin.people.custom-role.list') }}" class="btn btn-secondary">
                        {{ translate('Cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
                </div>
            </div>
        </div>
    </div>
</form>
