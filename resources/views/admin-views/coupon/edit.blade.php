@extends('layouts.admin.app')

@section('title', translate('Coupon Edit'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/coupon_setup.png') }}" alt="">
            {{translate('Coupon update') }}
        </h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">

                <div class="card-body">
                    <form action="{{route('admin.retail.coupon.update',[$coupon['id']]) }}" method="post" class="" id="coupon-add-ajax-submit"
                          data-field-visibility="{{ json_encode($fieldVisibility) }}">
                        @csrf
                        <div class="row g-4 mb-4">
                            <div class="col-md-6 col-lg-4">
                                <label for="name" class="form-label">{{translate('Coupon type') }}<span class="text-danger">*</span></label>
                                <div class="select-wrapper">
                                    <select class="form-select" id="coupon_type" name="coupon_type" data-required-msg="{{ translate(':field is required', ['field' => translate('Coupon type')]) }}" >
                                        <option disabled selected>{{translate('Select coupon type') }}</option>
                                        @foreach($couponTypes as $couponTypeKey => $couponTypeLabel)
                                            <option value="{{ $couponTypeKey }}" @selected($coupon['coupon_type'] === $couponTypeKey)>{{ $couponTypeLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                <label for="name" class="form-label">{{translate('Coupon title')}}<span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" id="title" value="{{$coupon['title'] }}"
                                       placeholder="{{translate('Title')}}" data-maxlength="80" data-required-msg="{{ translate(':field is required', ['field' => translate('Coupon title')]) }}" >
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="d-flex justify-content-between">
                                    <label for="name"
                                           class="form-label text-capitalize">{{translate('Coupon code')}}<span class="text-danger">*</span></label>
                                    <a href="javascript:" class="text-primary fs-12" id="generateCode">{{translate('Generate code')}}</a>
                                </div>
                                <input type="text" name="code" value="{{$coupon['code'] }}"
                                       class="form-control" id="code"
                                       data-required-msg="{{ translate(':field is required', ['field' => translate('Coupon code')]) }}"
                                       placeholder="{{translate('Ex')}}: EID100" >
                            </div>
                            <div class="col-md-6 col-lg-4" data-coupon-field="targeting">
                                <label for="name" class="form-label">{{translate('Coupon bearer')}}<span class="text-danger">*</span></label>
                                <div class="select-wrapper">
                                    <select class="form-select" name="coupon_bearer" id="coupon_bearer">
                                        <option disabled selected>{{translate('Select coupon bearer')}}</option>
                                        @foreach($couponBearers as $bearerKey => $bearerLabel)
                                            <option value="{{ $bearerKey }}" @selected($coupon['coupon_bearer'] === $bearerKey)>{{ $bearerLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4" data-coupon-field="targeting">
                                <label for="name" class="form-label">{{translate('Vendor')}}<span class="text-danger">*</span></label>
                                <select class="custom-select" name="seller_id" id="vendor_wise_coupon">
                                    @include('admin-views.coupon.partials._vendor-options')
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4" data-coupon-field="targeting">
                                <label for="name" class="form-label">{{translate('Customer')}}<span class="text-danger">*</span></label>
                                <select class="custom-select" name="customer_id" >
                                    <option disabled selected>{{translate('Select Customer')}}</option>
                                    <option value="0" {{$coupon['customer_id']=='0'?'selected':'' }}>{{translate('All Customer')}}</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{$coupon['customer_id']==$customer->id ? 'selected':'' }}>{{ $customer->f_name. ' '. $customer->l_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4" data-coupon-field="targeting">
                                <label  for="exampleFormControlInput1" class="form-label">{{translate('Limit for same user')}}<span class="text-danger">*</span></label>
                                <input type="number" name="limit" min="{{ $usageLimitRange['min'] }}" step="1" max="{{ $usageLimitRange['max'] }}" value="{{ $coupon['limit'] }}" id="coupon_limit" class="form-control" placeholder="{{translate('Ex').': 10'}}">
                            </div>
                            <div class="col-md-6 col-lg-4" data-coupon-field="discount">
                                <label  for="name" class="form-label">{{translate('Discount Type')}}<span class="text-danger">*</span></label>
                               <div class="select-wrapper">
                                    <select class="form-select" id="discount_type" name="discount_type">
                                        <option disabled selected>{{translate('Select Discount Type')}}</option>
                                        @foreach($discountTypes as $discountTypeKey => $discountTypeLabel)
                                            <option value="{{ $discountTypeKey }}" @selected($coupon['discount_type'] === $discountTypeKey)>{{ $discountTypeLabel }}</option>
                                        @endforeach
                                    </select>
                               </div>
                            </div>
                            <div class="col-md-6 col-lg-4" data-coupon-field="discount">
                                <label for="name" class="form-label">{{translate('Discount Amount')}}<span class="text-danger">*</span> <span id="discount_percent"> (%)</span>       ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})</label>
                                <input type="number" min="0" max="1000000" step="any" name="discount" class="form-control" id="discount" value="{{$coupon['discount_type']=='amount'? usdToDefaultCurrency(amount:$coupon['discount']):$coupon['discount'] }}"
                                    placeholder="{{translate('Ex').':'.'500' }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label for="name" class="form-label">
                                    {{ translate('Minimum purchase') }}<span class="text-danger">*</span>
                                    ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                </label>
                                <input type="number" min="0" max="1000000" step="any" name="min_purchase" class="form-control" id="minimum purchase" value="{{usdToDefaultCurrency(amount:$coupon['min_purchase'])}}"
                                       placeholder="{{translate('Minimum purchase')}}" >
                            </div>
                            <div class="col-md-6 col-lg-4" id="max-discount" data-coupon-field="max-discount">
                                <label for="name" class="form-label">{{translate('Maximum discount')}}<span class="text-danger">*</span>({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})</label>
                                <input type="number" min="1" max="1000000" step="any" name="max_discount" class="form-control" id="maximum discount" value="{{usdToDefaultCurrency(amount:$coupon['max_discount'])}}"
                                       placeholder="{{translate('Maximum discount')}}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label for="name" class="form-label">{{translate('Start Date')}}<span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" id="start_date" value="{{date('Y-m-d',strtotime($coupon['start_date']))}}"
                                       placeholder="{{translate('Start Date')}}" >
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label for="name" class="form-label">{{translate('Expire Date')}}<span class="text-danger">*</span></label>
                                <input type="date" name="expire_date" class="form-control" id="expire_date" value="{{date('Y-m-d',strtotime($coupon['expire_date']))}}"
                                       placeholder="{{translate('Expire Date')}}" >
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-end flex-wrap gap-3">
                            <button type="reset" class="btn btn-secondary px-4">{{translate('Reset')}}</button>
                            <button type="submit" class="btn btn-primary px-4">{{translate('Update')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<span id="coupon-bearer-url" data-url="{{route('admin.retail.coupon.ajax-get-vendor')}}"></span>
@endsection
@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/coupon.js')}}"></script>
@endpush
