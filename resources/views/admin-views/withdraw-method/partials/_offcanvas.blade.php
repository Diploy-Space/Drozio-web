@php
    $wm = $withdrawalMethod ?? null;
    $isEdit = (bool) $wm;
    $ocId = 'offcanvasWithdraw-' . ($isEdit ? $wm['id'] : 'add');
    $fields = $isEdit && count($wm['method_fields']) ? $wm['method_fields'] : [null];
@endphp

<div class="offcanvas offcanvas-end withdraw-method-offcanvas" tabindex="-1" id="{{ $ocId }}"
     aria-labelledby="{{ $ocId }}-label" style="--bs-offcanvas-width: 640px;">
    <div class="offcanvas-header bg-body">
        <h3 class="mb-0 d-flex align-items-center gap-2" id="{{ $ocId }}-label">
            <i class="fi fi-sr-{{ $isEdit ? 'pencil' : 'add' }} text-primary d-flex"></i>
            {{ $isEdit ? $editTitle : $addTitle }}
        </h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}"></button>
    </div>

    <form action="{{ $isEdit ? route($routePrefix . '.update') : route($routePrefix . '.add') }}"
          method="POST" data-oc="{{ $ocId }}"
          class="withdraw-method-form non-ajax-form-validate d-flex flex-column h-100 overflow-hidden" novalidate="novalidate">
        @csrf
        @if($isEdit)
            <input type="hidden" name="id" value="{{ $wm['id'] }}">
        @endif

        <div class="offcanvas-body d-flex flex-column gap-3 gap-sm-4">
            <div class="bg-section rounded-10 p-12 p-sm-20">
                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="{{ $ocId }}-method_name" class="form-label fw-medium">{{ translate('Method name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control method-name-input" name="method_name" id="{{ $ocId }}-method_name"
                                   placeholder="{{ translate('Ex') }}: PayPal" value="{{ $isEdit ? $wm['method_name'] : '' }}"
                                   data-required-msg="{{ translate('Method name field is required') }}" required data-maxlength="50">
                            <div class="d-flex justify-content-end">
                                <span class="text-body-light"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="mb-1">{{ translate('Configure Withdraw Method Fields') }}</h4>
                <p class="fs-12 mb-0 text-body-secondary">{{ translate('Add and customize the necessary information for withdrawals') }}</p>
            </div>

            <div class="custom-field-section d-flex flex-column gap-3">
                @foreach($fields as $key => $field)
                    <div class="border rounded-10 bg-white p-12 p-sm-20 field-row" data-key="{{ $key }}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-bold text-capitalize">{{ translate('Field') }} <span class="field-no"></span></h6>
                            @if($key > 0)
                                <button type="button" class="btn btn-outline-danger icon-btn remove-field" title="{{ translate('Remove') }}">
                                    <i class="fi fi-rr-trash"></i>
                                </button>
                            @endif
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label mb-2">{{ translate('Input Field Type') }} <span class="text-danger">*</span></label>
                                <select class="form-control" name="field_type[]" data-required-msg="{{ translate('Input field type is required') }}" required>
                                    <option value="" {{ $field ? '' : 'selected' }} disabled>{{ '--' . translate('Select Field Type') . '--' }}</option>
                                    @foreach($inputFieldTypes as $inputType => $inputTypeLabel)
                                        <option value="{{ $inputType }}" {{ $field && $field['input_type'] == $inputType ? 'selected' : '' }}>{{ $inputTypeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label mb-2">{{ translate('Field name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="field_name[]"
                                       data-required-msg="{{ translate('Field name is required') }}"
                                       placeholder="{{ translate('Type field name') }}" value="{{ $field['input_name'] ?? '' }}" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label mb-2">{{ translate('Placeholder text') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="placeholder_text[]"
                                       data-required-msg="{{ translate('Placeholder field is required') }}"
                                       placeholder="{{ translate('Type placeholder text') }}" value="{{ $field['placeholder'] ?? '' }}" required>
                            </div>
                            <div class="col-sm-6 d-flex align-items-end">
                                <label class="d-flex align-items-center gap-2 border rounded px-3 min-h-40 w-100 mb-0 user-select-none cursor-pointer">
                                    <input class="form-check-input checkbox--input mt-0" type="checkbox" value="1"
                                           name="is_required[{{ $key }}]" {{ !$field || $field['is_required'] ? 'checked' : '' }}>
                                    <span class="text-muted">{{ translate('This field required') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" class="btn btn-outline-primary w-100 wm-add-field-btn add-more-field">
                <i class="fi fi-sr-add"></i> {{ translate('Add new field') }}
            </button>

            <label class="d-flex align-items-center gap-2 mb-0 user-select-none cursor-pointer">
                <input class="form-check-input checkbox--input mt-0" type="checkbox" value="1" name="is_default"
                       {{ $isEdit && $wm['is_default'] == 1 ? 'checked disabled' : '' }}>
                <span>{{ translate('Set as default method') }}</span>
            </label>
        </div>

        <div class="offcanvas-footer shadow-lg">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="reset" class="btn btn-secondary px-3 px-sm-4 flex-grow-1">{{ translate('Reset') }}</button>
                <button type="submit" class="btn btn-primary px-3 px-sm-4 flex-grow-1 demo_check">
                    <i class="fi fi-sr-disk"></i> {{ translate('Save Information') }}
                </button>
            </div>
        </div>
    </form>
</div>
