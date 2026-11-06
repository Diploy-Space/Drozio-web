<div class="modal fade pos-modal" id="add-discount" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">{{ translate('Update discount') }}</h3>
                <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="title-color mb-1">{{ translate('Type') }}</label>
                    <select name="type" id="type_ext_dis" class="form-control">
                        <option value="amount" {{ isset($currentCustomerSessionInfo['ext_discount_type']) && $currentCustomerSessionInfo['ext_discount_type'] == 'amount' ? 'selected' : '' }}>
                            {{ translate('Amount') }}
                        </option>
                        <option
                            value="percent" {{ isset($currentCustomerSessionInfo['ext_discount_type']) && $currentCustomerSessionInfo['ext_discount_type'] == 'percent' ? 'selected' : '' }}>
                            {{ translate('Percent') }}(%)
                        </option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="title-color mb-1">{{ translate('Discount') }}</label>
                    <input type="text" value="{{ $currentCustomerSessionInfo['ext_discount'] ?? 0 }}" id="dis_amount" class="form-control no-negative-input only-number-input-with-decimal-point" name="discount" placeholder="{{ translate('Ex').':500'}}">
                </div>
                <div class="form-group">
                    <button class="btn btn--primary action-extra-discount" data-error-message="{{ translate('Please enter discount amount')}}">
                        {{ translate('Submit') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
