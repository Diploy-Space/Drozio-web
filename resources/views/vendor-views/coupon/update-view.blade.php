@extends('layouts.vendor.app')

@section('title', translate('Coupon Edit'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize">
                <img src="{{dynamicAsset(path: 'public/assets/back-end/img/coupon_setup.png') }}" class="mb-1 mr-1"
                     alt="">
                {{ translate('Coupon update') }}
            </h2>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('vendor.retail.coupon.update',[$coupon['id']]) }}" method="post"
                              class="form-advance-validation form-advance-inline-validation non-ajax-form-validate" novalidate
                              data-field-visibility="{{ json_encode($fieldVisibility) }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 col-lg-4 form-group">
                                    <label for="name"
                                           class="title-color text-capitalize">{{ translate('Coupon type') }} <span class="text-danger">*</span></label>
                                    <select class="js-example-basic-multiple js-states js-example-responsive form-control" id="coupon_type" name="coupon_type"
                                            data-required-msg="{{ translate('Coupon type is required') }}" required>
                                        <option value="" disabled selected>{{ translate('Select coupon type') }}</option>
                                        @foreach($couponTypes as $couponTypeKey => $couponTypeLabel)
                                            <option value="{{ $couponTypeKey }}" @selected($coupon['coupon_type'] === $couponTypeKey)>{{ $couponTypeLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group">
                                    <label for="name"
                                           class="title-color text-capitalize">{{ translate('Coupon title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" id="title"
                                           value="{{$coupon['title']}}" maxlength="80"
                                           placeholder="{{ translate('Title') }}"
                                           data-required-msg="{{ translate('Coupon title is required') }}" required>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group">
                                    <label for="name"
                                           class="title-color text-capitalize">{{ translate('Coupon code') }} <span class="text-danger">*</span></label>
                                    <a href="javascript:" class="float-right"
                                       id="generateCode">{{ translate('Generate code') }}</a>
                                    <input type="text" name="code" value="{{$coupon['code']}}"
                                           class="form-control" id="code"
                                           placeholder="{{ translate('Ex').': EID100' }}"
                                           data-required-msg="{{ translate('Coupon code is required') }}" required>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group" data-coupon-field="targeting">
                                    <label for="name"
                                           class="title-color font-weight-medium d-flex">{{ translate('Customer') }} <span class="text-danger">*</span></label>
                                    <select
                                        class="js-example-basic-multiple js-states js-example-responsive form-control"
                                        name="customer_id"
                                        data-required-msg="{{ translate('Customer is required') }}" required>
                                        <option value="" disabled selected>{{ translate('Select Customer') }}</option>
                                        <option
                                            value="0" {{$coupon['customer_id']=='0'?'selected':''}}>{{ translate('All Customer') }}</option>
                                        @foreach($customers as $customer)
                                            <option
                                                value="{{ $customer->id }}" {{$coupon['customer_id']==$customer->id ? 'selected':''}}>{{ $customer->f_name. ' '. $customer->l_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group" data-coupon-field="targeting">
                                    <label for="exampleFormControlInput1"
                                           class="title-color text-capitalize">{{ translate('Limit for same user') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="limit" value="{{ $coupon['limit'] }}"
                                           min="{{ $usageLimitRange['min'] }}" max="{{ $usageLimitRange['max'] }}" step="1"
                                           data-required-msg="{{ translate('Limit for same user is required') }}" required
                                           id="coupon_limit" class="form-control"
                                           placeholder="{{ translate('Ex') }}: 10">
                                </div>
                                <div class="col-md-6 col-lg-4 form-group" data-coupon-field="discount">
                                    <label for="name"
                                           class="title-color text-capitalize">{{ translate('Discount Type') }} <span class="text-danger">*</span></label>
                                    <select id="discount_type" class="js-example-basic-multiple js-states js-example-responsive form-control" name="discount_type">
                                        @foreach($discountTypes as $discountTypeKey => $discountTypeLabel)
                                            <option value="{{ $discountTypeKey }}" @selected($coupon['discount_type'] === $discountTypeKey)>{{ $discountTypeLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group" data-coupon-field="discount">
                                    <label for="name"
                                           class="title-color text-capitalize">{{ translate('Discount Amount') }}
                                        <span id="discount_currency">({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})</span>
                                        <span id="discount_percent"> (%)</span> <span class="text-danger">*</span></label>
                                    <input type="number" min="0" max="1000000" step=".01" name="discount"
                                           class="form-control" id="discount"
                                           value="{{$coupon['discount_type']=='amount' ? usdToDefaultCurrency(amount: $coupon['discount']) : $coupon['discount']}}"
                                           placeholder="{{ translate('Ex') }}: 500"
                                           data-required-msg="{{ translate('Discount amount is required') }}" required>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group">
                                    <label for="name"
                                           class="title-color text-capitalize">
                                        {{ translate('Minimum purchase') }}
                                        ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                     <span class="text-danger">*</span></label>
                                    <input type="number" min="0" max="1000000" step=".01" name="min_purchase"
                                           class="form-control" id="minimum purchase"
                                           value="{{ usdToDefaultCurrency(amount:$coupon['min_purchase']) }}"
                                           placeholder="{{ translate('Minimum purchase') }}"
                                           data-required-msg="{{ translate('Minimum purchase is required') }}" required>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group" id="max-discount" data-coupon-field="max-discount">
                                    <label for="name"
                                           class="title-color text-capitalize">
                                        {{ translate('Maximum discount') }}
                                        ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                     <span class="text-danger">*</span></label>
                                    <input type="number" min="1" max="1000000" step=".01" name="max_discount"
                                           class="form-control" id="maximum discount"
                                           value="{{ usdToDefaultCurrency(amount:$coupon['max_discount']) }}"
                                           placeholder="{{ translate('Maximum discount') }}"
                                           data-required-msg="{{ translate('Max discount amount is required!') }}" required>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group">
                                    <label for="name"
                                           class="title-color text-capitalize">{{ translate('Start Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" class="form-control" id="start_date"
                                           value="{{date('Y-m-d',strtotime($coupon['start_date'])) }}"
                                           placeholder="{{ translate('Start Date') }}" data-required-msg="{{ translate('Start date is required') }}" required>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group">
                                    <label for="name"
                                           class="title-color text-capitalize">{{ translate('Expire Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="expire_date" class="form-control" id="expire_date"
                                           value="{{date('Y-m-d',strtotime($coupon['expire_date'])) }}"
                                           placeholder="{{ translate('Expire Date') }}" data-required-msg="{{ translate('Expire date is required') }}" required>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-end flex-wrap gap-10">
                                <button  type="reset" class="btn btn-secondary set_btn px-4" >
                                    {{ translate('Reset') }}
                                </button>
                                <button type="submit" class="btn btn--primary px-4">
                                    {{ translate('Update') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/vendor/coupon.js') }}"></script>
@endpush
