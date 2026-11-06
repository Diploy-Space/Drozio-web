<div class="offcanvas-header bg-body align-items-start gap-3">
    <div class="d-flex align-items-center gap-3 min-w-0">
        <img class="avatar avatar-lg rounded-circle object-fit-cover flex-shrink-0" width="56" height="56" alt=""
             src="{{ getStorageImages(path: $employee->image_full_url, type: 'backend-profile') }}">
        <div class="min-w-0">
            <h3 class="mb-1 text-capitalize text-break">{{ $employee->name }}</h3>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge badge-soft-info">
                    {{ $employee->role?->name ? ucwords($employee->role->name) : translate('No role') }}
                </span>
                <span class="badge {{ $employee->status ? 'badge-soft-success' : 'badge-soft-danger' }}">
                    {{ $employee->status ? translate('Active') : translate('Inactive') }}
                </span>
                <span class="fs-12 text-body-light">{{ '#EMP '.$employee->id }}</span>
            </div>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}"></button>
</div>

<div class="offcanvas-body d-flex flex-column gap-3">
    <div class="bg-section rounded-10 p-20">
        <h5 class="mb-3">{{ translate('Contact information') }}</h5>
        <ul class="d-flex flex-column gap-3 px-0 mb-0">
            <li class="d-flex gap-2 align-items-center">
                <i class="fi fi-rr-envelope text-body-light"></i>
                <a class="text-dark text-hover-primary text-break" href="mailto:{{ $employee->email }}">
                    {{ $employee->email }}
                </a>
            </li>
            <li class="d-flex gap-2 align-items-center">
                <i class="fi fi-rr-phone-flip text-body-light"></i>
                <a class="text-dark text-hover-primary" href="tel:{{ $employee->phone }}">{{ $employee->phone }}</a>
            </li>
            <li class="d-flex gap-2 align-items-center">
                <i class="fi fi-rr-calendar-day text-body-light"></i>
                <span class="text-dark">
                    {{ translate('Joined') }} {{ date('d M, Y', strtotime($employee->created_at)) }}
                </span>
            </li>
            @if(! empty($employee->identify_type))
                <li class="d-flex gap-2 align-items-center">
                    <i class="fi fi-rr-id-badge text-body-light"></i>
                    <span class="text-dark text-uppercase">
                        {{ $employee->identify_type }} {{ $employee->identify_number }}
                    </span>
                </li>
            @endif
        </ul>
    </div>

    <div class="bg-section rounded-10 p-20">
        <h5 class="mb-1">{{ translate('Module access') }}</h5>
        <span class="fs-12 text-body-light d-block mb-3">
            {{ translate('What this employee can open, from the role above.') }}
        </span>

        @forelse($permissionGroups as $groupLabel => $groupPermissions)
            <div class="{{ $loop->last ? '' : 'mb-3' }}">
                <h6 class="fs-12 fw-bold text-dark text-uppercase mb-2">{{ translate($groupLabel) }}</h6>
                <div class="d-flex flex-wrap gap-1">
                    @foreach($groupPermissions as $groupPermission)
                        <span class="badge badge-soft-primary fw-normal fs-12">{{ translate($groupPermission) }}</span>
                    @endforeach
                </div>
            </div>
        @empty
            <span class="text-body-light fs-12">{{ translate('No module access') }}</span>
        @endforelse
    </div>
</div>

<div class="offcanvas-footer d-flex gap-3 p-20 border-top">
    <button type="button" class="btn btn-secondary w-50" data-bs-dismiss="offcanvas">
        {{ translate('Close') }}
    </button>
    <a href="{{ route('admin.people.employee.update', [$employee->id]) }}" class="btn btn-primary w-50">
        <i class="fi fi-rr-pencil"></i>
        {{ translate('Edit') }}
    </a>
</div>
