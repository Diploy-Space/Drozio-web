<form action="{{ route('admin.settings.vat-tax.store') }}" method="post" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
    @csrf
    <input type="hidden" name="business_module" value="{{ $businessModule }}">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="createVatTaxOffcanvas"
         aria-labelledby="offcanvasSubCatFilterLabel" style="--bs-offcanvas-width: 500px;">
        <div class="offcanvas-header bg-body">
            <h3 class="mb-0">{{ translate('Create Vat/Tax') }}</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="mb-4">
                <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                    <div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold mb-2" for="">
                                {{ translate('Availability') }}
                                <span class="tooltip-icon" data-bs-toggle="tooltip"
                                      data-bs-placement="top"
                                      aria-label="{{ translate('Enable to apply this tax at checkout. Disabled taxes are ignored.') }}"
                                      data-bs-title="{{ translate('Enable to apply this tax at checkout. Disabled taxes are ignored.') }}">
                                    <i class="fi fi-sr-info"></i>
                                </span>
                            </label>
                            <p class="fs-12 mb-0">
                                {{ translate('If you turn off this status your tax calculation will be affected.') }}
                            </p>
                        </div>
                        <label
                            class="bg-white d-flex justify-content-between align-items-center gap-3 border rounded px-3 py-10 user-select-none">
                            <span class="fw-medium text-dark line-1">
                                {{ translate('Status') }}
                            </span>
                            <label class="switcher" for="create-vat-tax">
                                <input
                                    class="switcher_input"
                                    type="checkbox" name="status" value="1"
                                    checked
                                    id="create-vat-tax"
                                    >
                                <span class="switcher_control"></span>
                            </label>
                        </label>
                    </div>
                </div>
                <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                    <div class="form-group mb-20">
                        <label class="form-label" for="">
                            {{ translate('VAT/TAX Name') }}
                            <span class="input-required-icon">*</span>
                            <span class="tooltip-icon" data-bs-toggle="tooltip"
                                  data-bs-placement="top"
                                  aria-label="{{ translate('Tax name shown on invoices and checkout, such as VAT or GST.') }}"
                                  data-bs-title="{{ translate('Tax name shown on invoices and checkout, such as VAT or GST.') }}">
                                <i class="fi fi-sr-info"></i>
                            </span>
                        </label>
                        <input type="text" name="name" class="form-control"    data-required-msg="{{ translate('Name field is required') }}" required
                               placeholder="{{ translate('Type tax name')}}" value="{{ old('name') }}"
                               maxlength="50">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label" for="tax_rate">
                            {{ translate('VAT/TAX Rate') }}
                            <span class="input-required-icon">*</span>
                            <span class="tooltip-icon" data-bs-toggle="tooltip"
                                  data-bs-placement="top"
                                  aria-label="{{ translate('Tax value, as a percentage of the price or a fixed amount.') }}"
                                  data-bs-title="{{ translate('Tax value, as a percentage of the price or a fixed amount.') }}">
                                                        <i class="fi fi-sr-info"></i>
                                                    </span>
                        </label>
                        <div class="input-group">
                            <input type="number" value="{{ old('tax_rate') }}"    data-required-msg="{{ translate(':field is required', ['field' => translate('Tax rate')]) }}" required name="tax_rate"
                                   min="0" step="0.001" id="tax_rate"
                                   class="form-control" placeholder="{{ translate('Ex') }}: 5">
                            <span class="input-group-text" id="basic-addon1">%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="reset" class="btn btn-secondary flex-grow-1">
                    {{ translate('Reset') }}
                </button>
                <button type="submit" class="btn btn-primary flex-grow-1">
                    {{ translate('Save') }}
                </button>
            </div>
        </div>
    </div>
</form>
