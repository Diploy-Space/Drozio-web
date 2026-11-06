@extends('layouts.admin.app')

@section('title', translate('Coupon Add'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/coupon_setup.png') }}" alt="">
                {{translate('Coupon setup')}}
            </h2>
        </div>

        <div class="row">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.retail.coupon.add')}}" method="POST" class="" novalidate="novalidate" id="coupon-add-ajax-submit"
                              data-field-visibility="{{ json_encode($fieldVisibility) }}">
                            @csrf
                            <div class="row g-4 mb-4">
                                <div class="col-md-6 col-lg-4">
                                    <label for="name"
                                           class="form-label d-flex">{{translate('Coupon type')}} <span class="text-danger">*</span></label>
                                    <div class="select-wrapper">
                                        <select class="form-select" id="coupon_type" name="coupon_type" data-required-msg="{{ translate(':field is required', ['field' => translate('Coupon type')]) }}" required>
                                            <option disabled selected>{{translate('Select coupon type')}}</option>
                                            @foreach($couponTypes as $couponTypeKey => $couponTypeLabel)
                                                <option value="{{ $couponTypeKey }}">{{ $couponTypeLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group">
                                        <label for="name"
                                               class="form-label d-flex">{{translate('Coupon title')}} <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" value="{{ old('title') }}"
                                               id="title"  data-maxlength="80"
                                               placeholder="{{translate('Title')}}" required  data-required-msg="{{ translate(':field is required', ['field' => translate('Coupon title')]) }}">
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="d-flex justify-content-between">
                                        <label for="name"
                                               class="form-label text-capitalize">{{translate('Coupon code')}} <span class="text-danger">*</span></label>
                                        <a href="javascript:" class="text-primary fs-12" id="generateCode">{{translate('Generate code')}}</a>
                                    </div>
                                    <input type="text" name="code" value=""
                                           class="form-control" id="code"
                                           placeholder="{{translate('Ex')}}: EID100" required  data-required-msg="{{ translate(':field is required', ['field' => translate('Coupon code')]) }}">
                                </div>
                                <div class="col-md-6 col-lg-4" data-coupon-field="targeting">
                                    <label for="name"
                                           class="form-label d-flex">{{translate('Coupon bearer')}} <span class="text-danger">*</span></label>
                                    <div class="select-wrapper">
                                        <select class="form-select" name="coupon_bearer" id="coupon_bearer" required  data-required-msg="{{ translate(':field is required', ['field' => translate('Coupon bearer')]) }}">
                                            <option disabled selected>{{translate('Select coupon bearer')}}</option>
                                            @foreach($couponBearers as $bearerKey => $bearerLabel)
                                                <option value="{{ $bearerKey }}">{{ $bearerLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4" data-coupon-field="targeting">
                                    <label for="name"
                                           class="form-label d-flex">{{translate('Vendor')}} <span class="text-danger">*</span></label>
                                    <select
                                        class="custom-select"
                                        name="seller_id" id="vendor_wise_coupon"  data-required-msg="{{ translate('Vendor field is required') }}">
                                        @include('admin-views.coupon.partials._vendor-options')
                                    </select>
                                </div>

                                <div class="col-md-6 col-lg-4" data-coupon-field="targeting">
                                    <label for="name"
                                           class="form-label d-flex">{{translate('Customer')}} <span class="text-danger">*</span></label>
                                    <select
                                        class="custom-select"
                                        name="customer_id">
                                        <option disabled selected>{{translate('Select Customer')}}</option>
                                        <option value="0">{{translate('All Customer')}}</option>
                                        @foreach($customers as $customer)
                                            <option
                                                value="{{ $customer->id }}">{{ $customer->f_name. ' '. $customer->l_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4" data-coupon-field="targeting">
                                    <label
                                        for="exampleFormControlInput1"
                                        class="form-label d-flex">{{translate('Limit for same user')}} <span class="text-danger">*</span></label>
                                    <input type="number" name="limit" value="{{ old('limit') }}" min="{{ $usageLimitRange['min'] }}" step="1" max="{{ $usageLimitRange['max'] }}"
                                           id="coupon_limit" class="form-control"
                                           placeholder="{{translate('Ex')}}: 10">
                                </div>
                                <div class="col-md-6 col-lg-4" data-coupon-field="discount">
                                    <label for="name"
                                           class="form-label d-flex">{{translate('Discount Type')}}<span class="text-danger">*</span></label>
                                    <div class="select-wrapper">
                                        <select id="discount_type" class="form-select" name="discount_type">
                                            @foreach($discountTypes as $discountTypeKey => $discountTypeLabel)
                                                <option value="{{ $discountTypeKey }}">{{ $discountTypeLabel }}{{ $discountTypeKey === 'percentage' ? ' (%)' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4" data-coupon-field="discount">
                                    <label for="name"
                                           class="form-label d-flex">{{translate('Discount Amount')}}<span class="text-danger">*</span>
                                        <span id="discount_currency">({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})</span>
                                        <span id="discount_percent"> (%)</span></label>
                                    <input type="number" min="1" max="1000000" name="discount"
                                           value="{{ old('discount') }}" class="form-control"
                                           id="discount"
                                           placeholder="{{translate('Ex')}} : 500">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="name"
                                           class="form-label d-flex">
                                        {{ translate('Minimum purchase') }}
                                        <span class="text-danger">*</span>
                                        ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                    </label>
                                    <input type="number" min="1" max="1000000" name="min_purchase"  data-required-msg="{{ translate('Minimum purchase field is required') }}"
                                           value="{{ old('min_purchase') }}" class="form-control"
                                           id="minimum purchase"
                                           placeholder="{{translate('Ex')}} : 100" required>
                                </div>
                                <div class="col-md-6 col-lg-4" id="max-discount" data-coupon-field="max-discount">
                                    <label for="name"
                                           class="form-label d-flex">
                                        {{translate('Maximum discount')}}
                                        ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                    </label>
                                    <input type="number" min="1" max="1000000" name="max_discount"
                                           value="{{ old('max_discount') }}"
                                           class="form-control" id="maximum discount"
                                           placeholder="{{translate('Ex')}} : 5000">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="name"
                                           class="form-label d-flex">{{translate('Start Date')}} <span class="text-danger">*</span></label>
                                    <input id="start_date" type="date" name="start_date" value="{{ old('start_date') }}"
                                           class="form-control"
                                           placeholder="{{translate('Start Date')}}" required>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="name"
                                           class="form-label d-flex">{{translate('Expire Date')}} <span class="text-danger">*</span></label>
                                    <input id="expire_date" type="date" name="expire_date"
                                           value="{{ old('expire_date') }}" class="form-control"
                                           placeholder="{{translate('Expire Date')}}" required>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-end flex-wrap gap-3">
                                <button type="reset" class="btn btn-secondary px-4">{{translate('Reset')}}</button>
                                <button type="submit" class="btn btn-primary px-4">{{translate('Submit')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-20">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body d-flex flex-column gap-20">
                        <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                            <h3 class="mb-0">
                                {{translate('Coupon list')}}
                                <span class="badge text-dark bg-body-secondary fw-semibold rounded-50">{{ $coupons->total() }}</span>
                            </h3>
                            <div class="d-flex flex-wrap gap-3 align-items-center justify-content-sm-end flex-grow-1">
                                <div class="flex-grow-1 max-w-300 min-w-100-mobile">
                                    <form action="{{ url()->current() }}" method="GET">
                                        <div class="input-group">
                                            <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                                           placeholder="{{translate('Search by Title or Code or Discount Type')}}"
                                           value="{{ request('searchValue') }}" aria-label="Search orders">
                                            <div class="input-group-append search-submit">
                                                <button type="submit">
                                                    <i class="fi fi-rr-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="dropdown">
                                    <a type="button" class="btn btn-outline-primary min-h-40" href="{{ route('admin.retail.coupon.export',['searchValue'=>request('searchValue')]) }}">
                                        <i class="fi fi-sr-inbox-in"></i>
                                        <span class="fs-12">{{ translate('Export') }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="datatable"
                                   class="table table-hover table-borderless table-thead-bordered align-middle">
                                <thead class="text-capitalize">
                                <tr>
                                    <th>{{translate('Serial')}}</th>
                                    <th>{{translate('Coupon')}}</th>
                                    <th>{{translate('Coupon type')}}</th>
                                    <th>{{translate('Duration')}}</th>
                                    <th>{{translate('Limit')}}</th>
                                    <th class="text-center">{{translate('Discount bearer')}}</th>
                                    <th>{{translate('Status')}}</th>
                                    <th class="text-center">{{translate('Action')}}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($coupons as $key => $coupon )
                                    <tr>
                                        <td>{{$coupons->firstItem() + $key }}</td>
                                        <td>
                                            <div class="max-w-250 text-truncate">{{$coupon['title']}}</div>
                                            <strong>{{translate('Code')}}: {{$coupon['code'] }}</strong>
                                        </td>
                                        <td class="text-capitalize">{{translate(str_replace('_',' ',$coupon['coupon_type']))}}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <span>{{date('d M, y',strtotime($coupon['start_date']))}} - </span>
                                                <span>{{date('d M, y',strtotime($coupon['expire_date']))}}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-2">
                                                <span>
                                                    {{translate('Same User Limit')}}:
                                                    <strong>{{ $coupon['limit'] }}</strong>
                                                </span>

                                                <span class="ml-1">
                                                    {{translate('Total Used')}}:
                                                    <strong>{{ $coupon['order_count'] }}</strong>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-center">{{ translate($coupon['coupon_bearer'] == 'inhouse' ? 'admin':$coupon['coupon_bearer']) }}</td>
                                        <td>
                                            <form
                                                action="{{route('admin.retail.coupon.status',[$coupon['id'],$coupon['status']?0:1])}}"
                                                method="GET" id="coupon_status{{$coupon['id'] }}-form"
                                                class="coupon_status_form">
                                                <label class="switcher mx-auto" for="coupon_status{{$coupon['id'] }}">
                                                    <input
                                                        class="switcher_input custom-modal-plugin"
                                                        type="checkbox" value="1" name="status"
                                                        id="coupon_status{{$coupon['id'] }}"
                                                        {{ $coupon['status'] == 1 ? 'checked':'' }}
                                                        data-modal-type="input-change-form"
                                                        data-modal-form="#coupon_status{{$coupon['id'] }}-form"
                                                        data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/coupon-status-on.png') }}"
                                                        data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/coupon-status-off.png') }}"
                                                        data-on-title="{{translate('Want to Turn ON :item', ['item' => translate('Coupon Status')]).'?' }}"
                                                        data-off-title="{{translate('Want to Turn OFF :item', ['item' => translate('Coupon Status')]).'?' }}"
                                                        data-on-message="<p>{{translate('Shows this coupon on the website and app.')}}</p>"
                                                        data-off-message="<p>{{translate('Hides this coupon on the website and app.')}}</p>"
                                                        data-on-button-text="{{ translate('Turn On') }}"
                                                        data-off-button-text="{{ translate('Turn Off') }}">
                                                    <span class="switcher_control"></span>
                                                </label>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-3 justify-content-center">
                                                <button class="btn btn-outline-info btn-outline-info-dark icon-btn get-quick-view" data-id="{{ $coupon['id'] }}">
                                                    <i class="fi fi-sr-eye"></i>
                                                </button>
                                                <a class="btn btn-outline-info icon-btn edit"
                                                   href="{{route('admin.retail.coupon.update',[$coupon['id']])}}"
                                                   title="{{ translate('Edit')}}"
                                                >
                                                    <i class="fi fi-sr-pencil"></i>
                                                </a>
                                                <a class="btn btn-outline-danger icon-btn delete {{ $coupon['order_count'] > 0 ? 'delete-used-coupon' : 'delete-data' }}"
                                                   href="javascript:"
                                                   data-id="coupon-{{$coupon['id'] }}"
                                                   data-warning="{{ translate('This coupon has been used on :count orders. Those orders keep their discount, but the coupon can no longer be applied to a new order.', ['count' => $coupon['order_count']]) }}"
                                                   title="{{translate('Delete')}}"
                                                >
                                                    <i class="fi fi-rr-trash"></i>
                                                </a>
                                                <form action="{{route('admin.retail.coupon.delete',[$coupon['id']])}}"
                                                      method="post" id="coupon-{{$coupon['id'] }}">
                                                    @csrf @method('delete')
                                                </form>
                                            </div>

                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            <div class="modal fade" id="quick-view" tabindex="-1" role="dialog"
                                 aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered coupon-details" role="document">
                                    <div class="modal-content border-0" id="quick-view-modal">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <div class="px-4 d-flex justify-content-lg-end">
                                {{$coupons->links()}}
                            </div>
                        </div>

                        @if(count($coupons)==0)
                            @include('layouts.admin.partials._empty-state',['text'=>'no_coupon_found'],['image'=>'default'])
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <span id="coupon-bearer-url" data-url="{{route('admin.retail.coupon.ajax-get-vendor')}}"></span>
    <span id="get-detail-url" data-url="{{ route('admin.retail.coupon.quick-view-details') }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/coupon.js')}}"></script>
@endpush
