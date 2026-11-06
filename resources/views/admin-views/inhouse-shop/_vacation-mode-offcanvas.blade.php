<form action="{{ route('admin.settings.business-settings.inhouse-shop-vacation-update') }}" method="post"
      id="vendor-vacation-offcanvas-form">
    @csrf
    <div class="offcanvas offcanvas-end w-sm-500" tabindex="-1" id="offcanvasVacationMode"
        aria-labelledby="offcanvasVacationModeLabel">
        <div class="offcanvas-header bg-body">
            <h2 class="mb-0" id="offcanvasVacationModeLabel">{{ translate('Vacation Mode Setup') }}</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}"></button>
        </div>
        <div class="offcanvas-body">
            <div class="mb-3 mb-sm-20 d-flex flex-column gap-3 gap-sm-20">
                <div class="p-12 p-sm-20 bg-section rounded">
                    <div class="mb-3">
                        <h4>{{ translate('Vacation Mode') }}</h4>
                        <p class="fs-12 mb-0">
                            {{ translate('If you turn on your shop will go to vacation mode.') }}
                            {{ translate("Customer can view your shop but can't order or bid") }}
                        </p>
                    </div>
                    <label
                        class="d-flex justify-content-between align-items-center gap-3 border rounded px-3 py-2 user-select-none">
                        <span class="fw-medium text-dark">{{ translate('Status') }}</span>
                        <label class="switcher">
                            <input type="checkbox" name="status" class="switcher_input" value="1"
                                id="vacation_close" {{ $vacation['status'] == 1 ? 'checked' : '' }}>
                            <span class="switcher_control"></span>
                        </label>
                    </label>
                </div>
                <div class="p-12 p-sm-20 bg-section rounded">
                    <div class="form-group m-0">
                        <label class="form-label">
                            {{ translate('Select Module') }}
                            <span class="text-danger">*</span>
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                aria-label="{{ translate('The businesses your shop stops taking orders for while on vacation.') }}"
                                data-bs-title="{{ translate('The businesses your shop stops taking orders for while on vacation.') }}">
                                <i class="fi fi-sr-info"></i>
                            </span>
                        </label>
                        <div
                            class="min-h-40 d-flex align-items-center flex-wrap gap-3 gap-sm-4 border rounded px-3 py-1 user-select-none">
                            @foreach ($vacationModules as $vacationModule)
                                <div class="d-flex align-items-center gap-5px p-0 pe-1">
                                    <input class="form-check-input checkbox--input mt-0" type="checkbox"
                                        name="modules[]" value="{{ $vacationModule['key'] }}"
                                        id="vacation-module-{{ $vacationModule['key'] }}"
                                        {{ $vacationModule['is_selected'] ? 'checked' : '' }}>
                                    <label class="form-check-label cursor-pointer"
                                        for="vacation-module-{{ $vacationModule['key'] }}">
                                        {{ $vacationModule['label'] }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <small id="vacationModuleError" class="form-text text-danger d-none">
                            {{ translate('Please choose at least one business for vacation mode.') }}
                        </small>
                    </div>
                </div>
                <div class="p-12 p-sm-20 bg-section rounded">
                    <div class="form-group m-0">
                        <label class="form-label">
                            {{ translate('Vacation Mode Duration') }}
                            <span class="text-danger">*</span>
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                aria-label="{{ translate('Dates you will be unavailable to fulfil orders.') }}"
                                data-bs-title="{{ translate('Dates you will be unavailable to fulfil orders.') }}">
                                <i class="fi fi-sr-info"></i>
                            </span>
                        </label>
                        <div
                            class="min-h-40 d-flex align-items-center flex-wrap gap-3 gap-sm-4 border rounded mb-2 px-3 py-1 user-select-none">
                            <div class="d-flex align-items-center gap-5px p-0 pe-1">
                                <input class="form-check-input radio--input mt-0" type="radio"
                                    name="vacation_duration_type" id="duration_type1" value="one_day"
                                    {{ isset($vacation['vacation_duration_type']) && $vacation['vacation_duration_type'] == 'one_day' ? 'checked' : '' }}>
                                <label class="form-check-label cursor-pointer" for="duration_type1">
                                    {{ translate('24 hours') }}
                                </label>
                            </div>
                            <div class="d-flex align-items-center gap-5px p-0 pe-1">
                                <input class="form-check-input radio--input mt-0" type="radio"
                                    name="vacation_duration_type" id="duration_type2" value="until_change"
                                    {{ isset($vacation['vacation_duration_type']) && $vacation['vacation_duration_type'] == 'until_change' ? 'checked' : '' }}>
                                <label class="form-check-label cursor-pointer" for="duration_type2">
                                    {{ translate('Until i Change') }}
                                </label>
                            </div>
                            <div class="d-flex align-items-center gap-5px p-0 pe-1">
                                <input class="form-check-input radio--input mt-0" type="radio"
                                    name="vacation_duration_type" id="duration_type3" value="custom"
                                    {{ !isset($vacation['vacation_duration_type']) || $vacation['vacation_duration_type'] == 'custom' ? 'checked' : '' }}>
                                <label class="form-check-label cursor-pointer" for="duration_type3">
                                    {{ translate('Custom') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-4 mb-0 custom-time-range-container">
                        <label class="form-label mb-1" for="vacation_date_range">
                            {{ translate('Select Date Range') }}
                            <span class="text-danger vacation-time-required">*</span>
                        </label>
                        <div class="position-relative">
                            <span class="fi fi-sr-calendar icon-absolute-on-right"></span>
                            <input type="text" class="form-control cursor-pointer" id="vacation_date_range"
                                placeholder="{{ translate('Select date range') }}" autocomplete="off">
                        </div>
                        <input type="hidden" name="vacation_start_date" id="custom_time_range_start"
                            value="{{ old('vacation_start_date', $vacation['vacation_start_date'] ?? '') }}">
                        <input type="hidden" name="vacation_end_date" id="custom_time_range_end"
                            value="{{ old('vacation_end_date', $vacation['vacation_end_date'] ?? '') }}">
                        <small id="dateError" class="form-text text-danger d-none">
                            {{ translate('Please select the date range vacation mode covers.') }}
                        </small>
                    </div>
                </div>
                <div class="p-12 p-sm-20 bg-section rounded">
                    <div class="form-group m-0">
                        <label class="form-label" for="vacation_note">
                            {{ translate('Vacation Note') }}
                        </label>
                        <textarea class="form-control" name="vacation_note" id="vacation_note" rows="5"
                            placeholder="{{ translate('Type about the description') }}">{{ $vacation['vacation_note'] }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer shadow-lg">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="reset"
                    class="btn btn-secondary px-3 px-sm-4 flex-grow-1">{{ translate('Reset') }}</button>
                <button type="submit" class="btn btn-primary px-3 px-sm-4 flex-grow-1">
                    <i class="fi fi-sr-disk"></i>
                    {{ translate('Save') }}
                </button>
            </div>
        </div>
    </div>
</form>
