<div class="modal fade" id="add-discount" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">{{ translate('Update discount') }}</h3>
                <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="title-color mb-1">{{ translate('Type') }}</label>
                    <select name="type" id="type_ext_dis" class="form-control">
                        <option value="amount" data-placeholder="{{ translate('Ex') }}: 500" selected>
                            {{ translate('Amount') }} ({{ getCurrencySymbol() }})
                        </option>
                        <option value="percent" data-placeholder="{{ translate('Ex') }}: 10%">
                            {{ translate('Percent') }}(%)
                        </option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="title-color mb-1">{{ translate('Discount') }}</label>
                    <input type="text" value="0" id="dis_amount" class="form-control no-negative-input only-number-input-with-decimal-point" name="discount" placeholder="{{ translate('Ex').':500'}}">
                </div>
                <div class="form-group">
                    <button class="btn btn-primary action-extra-discount" data-error-message="{{ translate('Please enter discount amount')}}">
                        {{ translate('Submit') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
