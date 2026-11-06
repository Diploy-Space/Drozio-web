<form action="{{ route('vendor.customer.add') }}" method="post" id="product_form" class="pos-ajax-form-add-customer">
    @csrf
    <div class="offcanvas-sidebar offcanvasAddNewCustomer pos-offcanvas" id="offcanvasAddNewCustomer" style="--bs-offcanvas-width: 400px;">
        <div class="offcanvas-overlay" data-dismiss="offcanvas"></div>

        <div class="offcanvas-content d-flex flex-column">
            <div class="offcanvas-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">{{ translate('Add New Customer') }}</h3>
                <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;" data-dismiss="offcanvas" aria-label="Close">
                    <i class="fi fi-rr-cross"></i>
                </button>
            </div>
            <div class="offcanvas-body overflow-auto flex-grow-1">
                <div class="p-12 p-sm-20 bg-section rounded overflow-wrap-anywhere">

                    <div class="form-group mb-3">
                        <label class="form-label mb-1">{{ translate('First Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="f_name" class="form-control" value="{{ old('f_name') }}" placeholder="{{ translate('First Name') }}">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label mb-1">{{ translate('Last Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="l_name" class="form-control" value="{{ old('l_name') }}" placeholder="{{ translate('Last Name') }}">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label mb-1">{{ translate('Email') }} <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="{{ translate('Ex').': ex@example.com' }}">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label mb-1">{{ translate('Phone') }} <span class="text-danger">*</span></label>
                        <input class="form-control form-control-user phone-input-with-country-picker" type="tel" id="exampleInputPhone" value="{{ old('phone') }}" name="phone" placeholder="{{ translate('Enter phone number') }}">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label mb-1">{{ translate('Country') }}</label>
                        <select name="country" class="form-control js-select2-custom" data-live-search="true">
                            @foreach($countries as $country)
                                <option value="{{ $country['name'] }}">{{ $country['name'] }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label mb-1">{{ translate('City') }}</label>
                        <input type="text" name="city" class="form-control" value="{{ old('city') }}" placeholder="{{ translate('City') }}">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label mb-1">{{ translate('Zip Code') }}</label>
                        @if($zipCodes)
                            <select name="zip" class="form-control js-select2-custom" data-live-search="true">
                                @foreach($zipCodes as $code)
                                    <option value="{{ $code->zipcode }}">{{ $code->zipcode }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        @else
                            <input type="text" name="zip_code" class="form-control" value="{{ old('zip_code') }}" placeholder="{{ translate('Zip Code') }}">
                            <div class="invalid-feedback"></div>
                        @endif
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label mb-1">{{ translate('Address') }}</label>
                        <textarea name="address" class="form-control" placeholder="{{ translate('Address') }}" cols="2">{{ old('address') }}</textarea>
                        <div class="invalid-feedback"></div>
                    </div>

                </div>
            </div>
            <div class="offcanvas-footer shadow-popup">
                <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                    <button type="reset" class="btn btn-secondary flex-grow-1">{{ translate('Reset') }}</button>
                    <button type="submit" class="btn btn--primary flex-grow-1" id="submit_new_customer">{{ translate('Submit') }}</button>
                </div>
            </div>
        </div>
    </div>
</form>
