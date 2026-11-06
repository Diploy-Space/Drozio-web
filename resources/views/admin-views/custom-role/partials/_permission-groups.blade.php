@php($direction = \Illuminate\Support\Facades\Session::get('direction'))

<div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
    <div class="min-w-0">
        <h4 class="mb-1">{{ translate('Module permission') }}</h4>
        <span class="fs-12 text-body-light">
            {{ translate('Turning on anything inside a business module turns its Dashboard on too, because the Dashboard is what opens that module.') }}
        </span>
    </div>
    <div class="form-group d-flex gap-2 mb-0 flex-shrink-0">
        <input type="checkbox" id="select-all-module"
               class="form-check-input checkbox--input checkbox--input_lg cursor-pointer">
        <label class="form-check-label cursor-pointer mb-0" for="select-all-module">
            {{ translate('Select all') }}
        </label>
    </div>
</div>

<div class="d-flex flex-column gap-3 mb-4">
    @foreach($permissionGroups as $permissionGroupKey => $permissionGroup)
        <div class="border rounded-10 overflow-hidden" data-permission-group="{{ $permissionGroupKey }}"
             data-permission-anchor="{{ $permissionGroup['requires'] }}">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap px-20 py-3 bg-section border-bottom">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <h5 class="mb-0 text-capitalize">{{ translate($permissionGroup['label']) }}</h5>
                    <span class="badge badge-soft-info permission-group-count">0</span>
                </div>
                <div class="form-group d-flex gap-2 mb-0 flex-shrink-0">
                    <input type="checkbox" class="form-check-input checkbox--input cursor-pointer select-all-group"
                           id="select-all-{{ $permissionGroupKey }}">
                    <label class="form-check-label cursor-pointer mb-0" for="select-all-{{ $permissionGroupKey }}">
                        {{ translate('Select all') }}
                    </label>
                </div>
            </div>

            <div class="p-20">
                <div class="row gy-3">
                    @foreach($permissionGroup['permissions'] as $permissionKey => $permissionLabel)
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <div class="d-flex gap-2 align-items-center">
                                <input type="checkbox" name="modules[]" value="{{ $permissionKey }}"
                                       class="form-check-input checkbox--input module-permission"
                                       id="{{ str_replace('.', '-', $permissionKey) }}-permission"
                                       {{ in_array($permissionKey, $selectedPermissions, true) ? 'checked' : '' }}>
                                <label class="cursor-pointer mb-0 d-flex align-items-center gap-2 flex-wrap"
                                       style="{{ $direction === 'rtl' ? 'margin-right: 1.25rem;' : '' }}"
                                       for="{{ str_replace('.', '-', $permissionKey) }}-permission">
                                    {{ translate($permissionLabel) }}
                                    @if($permissionGroup['requires'] === $permissionKey)
                                        <span class="badge badge-soft-primary fs-10" data-bs-toggle="tooltip"
                                              data-bs-title="{{ translate('Every other permission in this module needs it') }}">
                                            {{ translate('Required') }}
                                        </span>
                                    @endif
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
