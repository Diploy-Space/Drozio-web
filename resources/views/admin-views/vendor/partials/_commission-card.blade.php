<div class="card h-100">
    <div class="card-body d-flex flex-column gap-20">
        @include('admin-views.vendor.partials._setting-section-heading', [
            'sectionTitle' => translate('Commission'),
            'sectionDescription' => translate('If an individual commission is set, it will override the default commission.'),
        ])

        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="form-group mb-0">
                <label class="form-label text-capitalize" for="vendor-commission">
                    {{ translate('Commission').' (%)' }}
                    @unless ($isVendorWiseCommission)
                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right"
                              data-bs-title="{{ translate('Vendor wise commission is turned off, so every vendor uses the default commission.') }}">
                            <i class="fi fi-sr-info"></i>
                        </span>
                    @endunless
                </label>
                <input type="number" step="{{ decimalStepValue($commissionDecimalPoints) }}" min="0" max="100"
                       class="form-control" id="vendor-commission"
                       name="commission" placeholder="{{ translate('Ex').' : 10' }}"
                       value="{{ decimalInputValue($isVendorWiseCommission ? $commission : $defaultCommission, $commissionDecimalPoints) }}"
                       {{ $isVendorWiseCommission ? '' : 'disabled' }}>
            </div>
        </div>

        @unless ($isVendorWiseCommission)
            @include('admin-views.vendor.partials._setting-hint', [
                'hintText' => translate('Turn on vendor wise commission in Business Setup to set a commission for this vendor.'),
            ])
        @endunless
    </div>
</div>
