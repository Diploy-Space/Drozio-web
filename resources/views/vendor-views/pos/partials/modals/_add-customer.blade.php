<div class="modal fade pos-modal" id="add-customer" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 d-flex gap-3 justify-content-between align-items-center">
                <h3 class="modal-title">{{ translate('Add New Customer') }}</h3>
                <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                    data-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body px-20 pb-0 mb-30">
                <form action="{{route('vendor.customer.add') }}" method="post" id="product_form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label class="form-label mb-1">{{ translate('First Name') }} <span
                                        class="input-label-secondary text-danger">*</span></label>
                                <input type="text" name="f_name" class="form-control" value="{{ old('f_name') }}"
                                       placeholder="{{ translate('First Name') }}" required>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label class="form-label mb-1">{{ translate('Last Name') }}
                                    <span class="input-label-secondary text-danger">*</span></label>
                                <input type="text" name="l_name" class="form-control" value="{{ old('l_name') }}"
                                       placeholder="{{ translate('Last Name') }}" required>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label class="form-label mb-1">{{ translate('Email') }} <span
                                        class="input-label-secondary text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}"
                                       placeholder="{{ translate('Ex').': ex@example.com' }}" required>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label class="form-label mb-1">{{ translate('Phone') }} <span
                                        class="input-label-secondary text-danger">*</span></label>
                                <input class="form-control form-control-user phone-input-with-country-picker"
                                       type="tel" id="exampleInputPhone" value="{{old('phone')}}" name="phone"
                                       placeholder="{{ translate('Enter phone number') }}" required>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label class="form-label mb-1">{{ translate('Country') }}</label>
                                <select name="country" class="custom-select" data-live-search="true">
                                    @foreach($countries as $country)
                                        <option value="{{ $country['name'] }}">{{ $country['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label class="form-label mb-1">{{ translate('City') }}</label>
                                <input type="text" name="city" class="form-control" value="{{ old('city') }}"
                                       placeholder="{{ translate('City') }}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label class="form-label mb-1">{{ translate('Zip Code') }}</label>
                                @if($zipCodes)
                                    <select name="zip" class="form-control js-select2-custom" data-live-search="true">
                                        @foreach($zipCodes as $code)
                                            <option
                                                value="{{ $code->zipcode }}">{{ $code->zipcode }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" name="zip_code" class="form-control"
                                           value="{{ old('zip_code') }}" placeholder="{{ translate('Zip Code') }}">
                                @endif
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label class="form-label mb-1">{{ translate('Address') }}</label>
                                <input type="text" name="address" class="form-control" value="{{ old('address') }}"
                                       placeholder="{{ translate('Address') }}">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" id="submit_new_customer"
                                class="btn btn--primary max-w-120 flex-grow-1">{{ translate('Submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
