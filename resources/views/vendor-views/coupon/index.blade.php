@extends('layouts.vendor.app')

@section('title', translate('Coupon Add'))


@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{dynamicAsset(path: 'public/assets/back-end/img/coupon_setup.png') }}" alt="">
                {{ translate('Coupon setup') }}
            </h2>
        </div>
        <div class="row">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('vendor.retail.coupon.add') }}" method="post"
                              class="form-advance-validation form-advance-inline-validation non-ajax-form-validate" novalidate
                              data-field-visibility="{{ json_encode($fieldVisibility) }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 col-lg-4 form-group">
                                    <label for="name"
                                           class="title-color font-weight-medium d-flex">{{ translate('Coupon type') }} <span class="text-danger">*</span></label>
                                    <select class="js-example-basic-multiple js-states js-example-responsive form-control" id="coupon_type" name="coupon_type"
                                            data-required-msg="{{ translate('Coupon type is required') }}" required>
                                        <option value="" disabled selected>{{ translate('Select coupon type') }}</option>
                                        @foreach($couponTypes as $couponTypeKey => $couponTypeLabel)
                                            <option value="{{ $couponTypeKey }}">{{ $couponTypeLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group">
                                    <label for="name"
                                           class="title-color font-weight-medium d-flex">{{ translate('Coupon title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" value="{{ old('title') }}"
                                           id="title" maxlength="80"
                                           placeholder="{{ translate('Title') }}"
                                           data-required-msg="{{ translate('Coupon title is required') }}" required>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group">
                                    <div class="d-flex justify-content-between">
                                        <label for="name"
                                               class="title-color font-weight-medium text-capitalize">{{ translate('Coupon code') }} <span class="text-danger">*</span></label>
                                        <a href="javascript:" class="float-right c1 fs-12"
                                           id="generateCode">{{ translate('Generate code') }}</a>
                                    </div>
                                    <input type="text" name="code" value=""
                                           class="form-control" id="code"
                                           placeholder="{{ translate('Ex') }}: EID100"
                                           data-required-msg="{{ translate('Coupon code is required') }}" required>
                                </div>
                                <input type="hidden" value="seller" name="coupon_bearer">
                                <div class="col-md-6 col-lg-4 form-group" data-coupon-field="targeting">
                                    <label for="name"
                                           class="title-color font-weight-medium d-flex">{{ translate('Customer') }} <span class="text-danger">*</span></label>
                                    <select
                                        class="js-example-basic-multiple js-states js-example-responsive form-control"
                                        name="customer_id"
                                        data-required-msg="{{ translate('Customer is required') }}" required>
                                        <option value="" disabled selected>{{ translate('Select Customer') }}</option>
                                        <option value="0">{{ translate('All Customer') }}</option>
                                        @foreach($customers as $customer)
                                            <option
                                                value="{{ $customer->id }}">{{ $customer->f_name. ' '. $customer->l_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group" data-coupon-field="targeting">
                                    <label
                                        for="exampleFormControlInput1"
                                        class="title-color font-weight-medium d-flex">{{ translate('Limit for same user') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="limit" value="{{ old('limit') }}"
                                           min="{{ $usageLimitRange['min'] }}" max="{{ $usageLimitRange['max'] }}" step="1"
                                           data-required-msg="{{ translate('Limit for same user is required') }}" required
                                           id="coupon_limit" class="form-control"
                                           placeholder="{{ translate('Ex') }}: 10">
                                </div>
                                <div class="col-md-6 col-lg-4 form-group" data-coupon-field="discount">
                                    <label for="name"
                                           class="title-color font-weight-medium d-flex">{{ translate('Discount Type') }} <span class="text-danger">*</span></label>
                                    <select id="discount_type" class="js-example-basic-multiple js-states js-example-responsive form-control" name="discount_type">
                                        @foreach($discountTypes as $discountTypeKey => $discountTypeLabel)
                                            <option value="{{ $discountTypeKey }}">{{ $discountTypeLabel }}{{ $discountTypeKey === 'percentage' ? ' (%)' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group" data-coupon-field="discount">
                                    <label for="name"
                                           class="title-color font-weight-medium d-flex">{{ translate('Discount Amount') }}
                                        <span id="discount_currency">({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})</span>
                                        <span id="discount_percent"> (%)</span> <span class="text-danger">*</span></label>
                                    <input type="number" min="1" max="1000000" name="discount"
                                           value="{{ old('discount') }}" class="form-control"
                                           id="discount"
                                           placeholder="{{ translate('Ex').': 5000'}}"
                                           data-required-msg="{{ translate('Discount amount is required') }}" required>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group">
                                    <label for="name"
                                           class="title-color font-weight-medium d-flex">{{ translate('Minimum purchase') }}
                                        ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                     <span class="text-danger">*</span></label>
                                    <input type="number" min="1" max="1000000" name="min_purchase"
                                           value="{{ old('min_purchase') }}" class="form-control"
                                           id="minimum purchase"
                                           placeholder="{{ translate('Ex') }}: 100"
                                           data-required-msg="{{ translate('Minimum purchase is required') }}" required>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group" id="max-discount" data-coupon-field="max-discount">
                                    <label for="name"
                                           class="title-color font-weight-medium d-flex">{{ translate('Maximum discount') }}
                                        ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                     <span class="text-danger">*</span></label>
                                    <input type="number" min="1" max="1000000" name="max_discount"
                                           value="{{ old('max_discount') }}"
                                           class="form-control" id="maximum discount"
                                           placeholder="{{ translate('Ex').': 5000'}}"
                                           data-required-msg="{{ translate('Max discount amount is required!') }}" required>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group">
                                    <label for="name"
                                           class="title-color font-weight-medium d-flex">{{ translate('Start Date') }} <span class="text-danger">*</span></label>
                                    <input id="start_date" type="date" name="start_date" value="{{ old('start_date') }}"
                                           class="form-control"
                                           placeholder="{{ translate('Start Date') }}" data-required-msg="{{ translate('Start date is required') }}" required>
                                </div>
                                <div class="col-md-6 col-lg-4 form-group">
                                    <label for="name"
                                           class="title-color font-weight-medium d-flex">{{ translate('Expire Date') }} <span class="text-danger">*</span></label>
                                    <input id="expire_date" type="date" name="expire_date"
                                           value="{{ old('expire_date') }}" class="form-control"
                                           placeholder="{{ translate('Expire Date') }}" data-required-msg="{{ translate('Expire date is required') }}" required>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-end flex-wrap gap-10">
                                <button class="btn btn-secondary px-4" type="reset">
                                    {{ translate('Reset') }}
                                </button>
                                <button type="submit" class="btn btn--primary px-4">
                                    {{ translate('Submit') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-20">
            <div class="col-md-12">
                <div class="card">
                    <div class="px-3 py-4">
                        <div class="d-flex flex-wrap  gap-3 align-items-center justify-content-between">
                            <h5 class="mb-0 text-capitalize d-flex gap-2">
                                {{ translate('Coupon list') }}
                                <span class="badge badge-soft-dark radius-50 fs-12 ml-1">{{ $coupons->total() }}</span>
                            </h5>
                            <div class="d-flex gap-2 flex-wrap">
                                <form action="{{ url()->current() }}" method="GET">
                                    <div class="input-group input-group-merge input-group-custom">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text">
                                                <i class="tio-search"></i>
                                            </div>
                                        </div>
                                        <input
                                            id="datatableSearch_"
                                            type="{{ request('searchValue') ? 'text' : 'search' }}"
                                            name="searchValue"
                                            class="form-control p3-3"
                                            placeholder="{{ translate('Search by Title or Code or Discount Type') }}"
                                            value="{{ request('searchValue') }}"
                                            aria-label="Search orders"
                                            required
                                        >
                                        <a href="{{ route('vendor.retail.coupon.index') }}"
                                           class="remove-input-value-coupon-search position-absolute text-dark {{ request('searchValue') ? 'd-block' : 'd-none' }}">
                                            <i class="tio-clear font-weight-bold"></i>
                                        </a>

                                        <button type="submit" class="btn btn--primary">{{ translate('Search') }}</button>
                                    </div>
                                </form>
                                <div class="dropdown">
                                    <a type="button" class="btn btn-outline--primary text-nowrap h-100"
                                       href="{{ route('vendor.retail.coupon.export',['searchValue'=>request('searchValue')]) }}">
                                        <img width="14"
                                             src="{{dynamicAsset(path: 'public/assets/back-end/img/excel.png') }}"
                                             class="excel" alt="">
                                        <span class="ps-2">{{ translate('Export') }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="datatable"
                               class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table {{ Session::get('direction') === 'rtl' ? 'text-right' : 'text-left' }}">
                            <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>{{ translate('Serial') }}</th>
                                <th>{{ translate('Coupon') }}</th>
                                <th>{{ translate('Coupon type') }}</th>
                                <th>{{ translate('Duration') }}</th>
                                <th>{{ translate('Limit') }}</th>
                                <th class="text-center">{{ translate('Discount bearer') }}</th>
                                <th>
                                    {{ translate('Status') }}
                                    <i class="tio-info font-130 info-color"
                                       data-toggle="tooltip"
                                       title="{{ translate('Some status buttons are disabled because the admin added coupons') }}, {{ translate('The coupon discount bearer is admin') }}, {{ translate('Or some coupons are for all vendors') }}">

                                    </i>
                                </th>
                                <th class="text-center">
                                    {{ translate('Action') }}
                                    <i class="tio-info font-130 info-color"
                                       data-toggle="tooltip"
                                       title="{{ translate('Some actions are disabled because the admin added coupons') }}, {{ translate('The coupon discount bearer is admin') }}, {{ translate('Or some coupons are for all vendors') }}">

                                    </i>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($coupons as $k=>$coupon)
                                <tr>
                                    <td>{{$coupons->firstItem() + $k}}</td>
                                    <td>
                                        <div>{{substr($coupon['title'],0,20) }}</div>
                                        <strong>{{ translate('Code') }}: {{$coupon['code']}}</strong>
                                    </td>
                                    <td class="text-capitalize">{{ translate(str_replace('_',' ',$coupon['coupon_type'])) }}</td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <span>{{date('d M, y',strtotime($coupon['start_date'])) }} - </span>
                                            <span>{{date('d M, y',strtotime($coupon['expire_date'])) }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-2">
                                            <span>
                                                {{translate('Same User Limit')}}:
                                                <strong>{{ $coupon['limit'] }}</strong>
                                            </span>

                                            <span>
                                                {{translate('Total Used')}}:
                                                <strong>{{ $coupon['order_count'] }}</strong>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ translate($coupon['coupon_bearer'] == 'inhouse' ? 'admin':$coupon['coupon_bearer']) }}</td>
                                    <td>
                                        @if($coupon->added_by == 'seller' && $coupon->seller_id == $vendorId)
                                            <form
                                                action="{{ route('vendor.retail.coupon.update-status',[$coupon['id'],$coupon->status?0:1]) }}"
                                                method="get" id="coupon_status{{$coupon['id']}}-form"
                                                class="coupon_status_form">
                                                <label class="switcher">
                                                    <input type="checkbox" class="switcher_input toggle-switch-message"
                                                           id="coupon_status{{$coupon['id']}}" name="status" value="1"
                                                           {{ $coupon->status == 1 ? 'checked':'' }}
                                                           data-modal-id="toggle-status-modal"
                                                           data-toggle-id="coupon_status{{$coupon['id']}}"
                                                           data-on-image="coupon-status-on.png"
                                                           data-off-image="coupon-status-off.png"
                                                           data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Coupon Status')]).'?'}}"
                                                           data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Coupon Status')]).'?'}}"
                                                           data-on-message="<p>{{ translate('Shows this coupon on the website and app.') }}</p>"
                                                           data-off-message="<p>{{ translate('Hides this coupon on the website and app.') }}</p>"
                                                    >
                                                    <span class="switcher_control"></span>
                                                </label>
                                            </form>
                                        @else
                                            <label class="switcher" data-toggle="tooltip" data-placement="top"
                                                   title="{{ translate('Added by admin') }}">
                                                <input type="checkbox"
                                                       class="switcher_input toggle-switch-input"
                                                       {{$coupon->status?'checked':''}} disabled>
                                                <span class="switcher_control opacity--40"></span>
                                            </label>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-10 justify-content-center">
                                            <button class="btn btn-outline-info btn-sm square-btn get-quick-view"
                                                    data-id="{{ $coupon['id'] }}">
                                                <i class="tio-invisible"></i>
                                            </button>
                                            @if($coupon->added_by == 'seller' && $coupon->seller_id == $vendorId)
                                                <a class="btn btn-outline--primary btn-sm edit"
                                                   href="{{ route('vendor.retail.coupon.update',[$coupon['id']]) }}"
                                                   title="{{ translate('Edit') }}">
                                                    <i class="tio-edit"></i>
                                                </a>
                                                <a class="btn btn-outline-danger btn-sm delete delete-data"
                                                   href="javascript:"
                                                   data-id="coupon-{{$coupon['id']}}"
                                                   title="{{ translate('Delete') }}">
                                                    <i class="tio-delete"></i>
                                                </a>
                                                <form action="{{ route('vendor.retail.coupon.delete',[$coupon['id']]) }}"
                                                      method="post" id="coupon-{{$coupon['id']}}">
                                                    @csrf @method('delete')
                                                </form>
                                            @else
                                                <button
                                                    class="btn btn-outline-primary btn-sm edit disabled cursor-default"
                                                    data-toggle="tooltip" data-placement="top"
                                                    title="{{ translate('Added by admin') }}">
                                                    <i class="tio-edit"></i>
                                                </button>
                                                <button
                                                    class="btn btn-outline-danger btn-sm delete disabled cursor-default"
                                                    data-toggle="tooltip" data-placement="top"
                                                    title="{{ translate('Added by admin') }}">
                                                    <i class="tio-delete"></i>
                                                </button>
                                            @endif
                                        </div>

                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <div class="modal fade" id="quick-view" tabindex="-1" role="dialog"
                             aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered coupon-details" role="document">
                                <div class="modal-content" id="quick-view-modal">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mt-4">
                        <div class="px-4 d-flex justify-content-lg-end">
                            {{$coupons->links() }}
                        </div>
                    </div>

                    @if(count($coupons)==0)
                        @include('layouts.vendor.partials._empty-state', [
                            'text' => 'no_coupon_found',
                            'image' => 'default',
                            'width' => 50
                        ])
                    @endif
                </div>
            </div>
        </div>
    </div>

    <span id="get-detail-url" data-url="{{ route('vendor.retail.coupon.quick-view') }}"></span>
@endsection
@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/vendor/coupon.js') }}"></script>
@endpush

