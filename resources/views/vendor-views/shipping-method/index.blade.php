@extends('layouts.vendor.app')

@section('title', translate('Add Shipping'))

@section('content')
    @php($direction = session('direction'))
    <div class="content container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/shipping_method.png')}}" alt="">
                {{translate('Shipping Method')}}
            </h2>
            <div class="text-primary d-flex align-items-center gap-3 font-weight-bolder text-capitalize">
                {{translate('Delivery Zone')}}
                <div class="ripple-animation" data-toggle="modal" data-target="#howItWorksModal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none"
                         class="svg replaced-svg">
                        <path d="M9.00033 9.83268C9.23644 9.83268 9.43449 9.75268 9.59449 9.59268C9.75449 9.43268 9.83421 9.2349 9.83366 8.99935V5.64518C9.83366 5.40907 9.75366 5.21463 9.59366 5.06185C9.43366 4.90907 9.23588 4.83268 9.00033 4.83268C8.76421 4.83268 8.56616 4.91268 8.40616 5.07268C8.24616 5.23268 8.16644 5.43046 8.16699 5.66602V9.02018C8.16699 9.25629 8.24699 9.45074 8.40699 9.60352C8.56699 9.75629 8.76477 9.83268 9.00033 9.83268ZM9.00033 13.166C9.23644 13.166 9.43449 13.086 9.59449 12.926C9.75449 12.766 9.83421 12.5682 9.83366 12.3327C9.83366 12.0966 9.75366 11.8985 9.59366 11.7385C9.43366 11.5785 9.23588 11.4988 9.00033 11.4993C8.76421 11.4993 8.56616 11.5793 8.40616 11.7393C8.24616 11.8993 8.16644 12.0971 8.16699 12.3327C8.16699 12.5688 8.24699 12.7668 8.40699 12.9268C8.56699 13.0868 8.76477 13.1666 9.00033 13.166ZM9.00033 17.3327C7.84755 17.3327 6.76421 17.1138 5.75033 16.676C4.73644 16.2382 3.85449 15.6446 3.10449 14.8952C2.35449 14.1452 1.76088 13.2632 1.32366 12.2493C0.886437 11.2355 0.667548 10.1521 0.666992 8.99935C0.666992 7.84657 0.885881 6.76324 1.32366 5.74935C1.76144 4.73546 2.35505 3.85352 3.10449 3.10352C3.85449 2.35352 4.73644 1.7599 5.75033 1.32268C6.76421 0.88546 7.84755 0.666571 9.00033 0.666016C10.1531 0.666016 11.2364 0.884905 12.2503 1.32268C13.2642 1.76046 14.1462 2.35407 14.8962 3.10352C15.6462 3.85352 16.24 4.73546 16.6778 5.74935C17.1156 6.76324 17.3342 7.84657 17.3337 8.99935C17.3337 10.1521 17.1148 11.2355 16.677 12.2493C16.2392 13.2632 15.6456 14.1452 14.8962 14.8952C14.1462 15.6452 13.2642 16.2391 12.2503 16.6768C11.2364 17.1146 10.1531 17.3332 9.00033 17.3327ZM9.00033 15.666C10.8475 15.666 12.4206 15.0168 13.7195 13.7185C15.0184 12.4202 15.6675 10.8471 15.667 8.99935C15.667 7.15213 15.0178 5.57907 13.7195 4.28018C12.4212 2.98129 10.8481 2.33213 9.00033 2.33268C7.1531 2.33268 5.58005 2.98185 4.28116 4.28018C2.98227 5.57852 2.3331 7.15157 2.33366 8.99935C2.33366 10.8466 2.98283 12.4196 4.28116 13.7185C5.57949 15.0174 7.15255 15.6666 9.00033 15.666Z"
                              fill="currentColor"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="modal fade" id="howItWorksModal" tabindex="-1" aria-labelledby="howItWorksModal"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                        <button type="button" class="btn-close border-0" data-dismiss="modal" aria-label="Close"><i
                                class="tio-clear"></i></button>
                    </div>
                    <div class="modal-body px-4 px-sm-5 pt-0 text-center">
                        <div class="d-flex flex-column align-items-center gap-2">
                            <img width="80" class="mb-3" src="{{dynamicAsset(path: 'public/assets/back-end/img/delivery-restriction.png')}}"
                                 loading="lazy" alt="">
                            <h4 class="lh-md text-capitalize">{{translate('Delivery Zone')}}</h4>
                            <div class="d-flex flex-column align-items-start">
                                <p class="text--black">{{'1'.'. '.translate('Delivery is limited to the zip code areas the admin selected.').'.'}}</p>
                                <p class="text--black">{{'2'.'. '.translate('You can only deliver orders to the selected countries which admin selected').'.'}}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="position-relative nav--tab-wrapper mb-3">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link cursor-pointer py-2 {{ $context === 'retail' ? 'active' : '' }}"
                       href="{{ route('vendor.business-settings.shipping-method.index') }}">
                        {{ translate('Retail') }}
                    </a>
                </li>
                @if ($wholesaleShippingAvailable)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link cursor-pointer py-2 {{ $context === 'wholesale' ? 'active' : '' }}"
                           href="{{ route('vendor.wholesale.shipping-method.wholesale') }}">
                            {{ translate('Wholesale') }}
                        </a>
                    </li>
                @endif
            </ul>
        </div>
        <div class="mb-3">
            <div class="row">
                <div class="col-md-12 ">
                    <div class="card">
                        <div class="p-sm-20 p-3">
                            <div class="mb-20">
                                <h3 class="mb-1">{{ translate('Shipping Methods') }}</h3>
                                <p class="fs-12 mb-0">
                                    {{ translate('Setup how you want to calculate shipping charge for order delivery') }}
                                </p>
                            </div>
                            <div class="p-3 p-sm-20 bg-section rounded">
                                <form action="{{ route($routeNames['type']) }}" method="post" id="shipping-type-form">
                                    @csrf
                                    <div class="form-group mb-0">
                                        <div class="bg-white p-3 border rounded">
                                            <div class="row g-2 shipping-type">
                                                <div class="col-xl-4 col-md-6">
                                                    <div class="form-check d-flex gap-3">
                                                        <input
                                                            class="form-check-input radio--input radio--input_lg"
                                                            type="radio" id="order_wise" value="order_wise" name="shippingType" {{$shippingType=='order_wise'?'checked':'' }}>
                                                        <div class="flex-grow-1">
                                                            <label for="order_wise" class="form-label text-dark fw-semibold mb-1 text-capitalize">
                                                                {{ translate('Order wise') }}
                                                            </label>
                                                            <p class="fs-12 mb-0">
                                                                {{ translate('Set a shipping cost per category so the right amount charges.') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xl-4 col-md-6">
                                                    <div class="form-check d-flex gap-3">
                                                        <input
                                                            class="form-check-input radio--input radio--input_lg"
                                                            type="radio" id="category_wise" value="category_wise" name="shippingType" {{$shippingType=='category_wise'?'checked':'' }}>
                                                        <div class="flex-grow-1">
                                                            <label for="category_wise" class="form-label text-dark fw-semibold mb-1 text-capitalize">
                                                                {{ translate('Category wise') }}
                                                            </label>
                                                            <p class="fs-12 mb-0">
                                                                {{ translate('Set a shipping cost per category so the right amount charges.') }}
                                                            </p>
                                                        </div>
                                                    </div>
            
                                                </div>
                                                <div class="col-xl-4 col-md-6">
                                                    <div class="form-check d-flex gap-3">
                                                        <input
                                                            class="form-check-input radio--input radio--input_lg"
                                                            type="radio" id="product_wise" value="product_wise" name="shippingType" {{$shippingType=='product_wise'?'checked':'' }}>
                                                        <div class="flex-grow-1">
                                                            <label for="product_wise" class="form-label text-dark fw-semibold mb-1 text-capitalize">
                                                                {{ translate('Product wise') }}
                                                            </label>
                                                            <p class="fs-12 mb-0">
                                                                {{ translate('When adding a product, a specific shipping charge needs to be added.') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3 mx-0" id="product_wise_note"
                                             @style(['display: none' => in_array($shippingType, ['order_wise', 'category_wise'], true)])>
                                            <p>
                                                <img width="16" class="mt-n1"
                                                    src="{{dynamicAsset(path: 'public/assets/back-end/img/danger-info.png')}}" alt="">
                                                <strong>{{translate('Note').' '.':'}}</strong>
                                                {{translate('Check every product delivery charge is up to date.').'.'}}
                                            </p>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="order_wise_shipping" @style(['display: none' => $shippingType !== 'order_wise'])>
            <div class="card p-sm-20 p-2">
                <div class="card border-0 shadow-sm">
                    <div class="p-sm-20 p-3">
                        <div class="mb-20">
                            <h3 class="mb-1">{{ translate('Add order wise shipping method') }}</h3>
                            <p class="fs-12 mb-0">
                                {{ translate('Add shipping method as much as you need based on order.') }}
                            </p>
                        </div>
                        <form action="{{route($routeNames['store'])}}" method="post"
                              class="form-advance-validation form-advance-inline-validation non-ajax-form-validate" novalidate>
                            @csrf
                            <div class="bg-section rounded p-3 mb-20">
                                <div class="row g-2">
                                    <div class="col-xl-4 col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="title-color d-flex align-items-center gap-1" for="title">
                                                {{translate('Title')}}
                                                <span class="tooltip-icon cursor-pointer"
                                                    data-toggle="tooltip" data-title="{{ translate('Content') }}">
                                                    <i class="fi fi-sr-info fs-12 text-light-gray d-flex"></i>
                                                </span>
                                            </label>
                                            <input type="text" name="title" id="title" class="form-control" maxlength="100"
                                                   placeholder="{{translate('Title')}}"
                                                   data-required-msg="{{ translate('The title field is required') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-xl-4 col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="title-color d-flex align-items-center gap-1" for="duration">
                                                {{translate('Shipping Duration')}}
                                                <span class="tooltip-icon cursor-pointer"
                                                    data-toggle="tooltip" data-title="{{ translate('Content') }}">
                                                    <i class="fi fi-sr-info fs-12 text-light-gray d-flex"></i>
                                                </span>
                                            </label>
                                            <input type="text" name="duration" id="duration" class="form-control"
                                                   maxlength="20" placeholder="{{translate('Ex').': 4-6 Days'}}"
                                                   data-required-msg="{{ translate('The shipping duration field is required') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-xl-4 col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="title-color d-flex align-items-center gap-1" for="cost">
                                                {{translate('Shipping Cost')}}
                                                <span class="tooltip-icon cursor-pointer"
                                                    data-toggle="tooltip" data-title="{{ translate('Content') }}">
                                                    <i class="fi fi-sr-info fs-12 text-light-gray d-flex"></i>
                                                </span>
                                            </label>
                                            <input type="number" min="0" step="0.01" max="1000000" name="cost" id="cost" class="form-control"
                                                   placeholder="{{translate('Ex').':'.usdToDefaultCurrency('10')}}"
                                                   data-required-msg="{{ translate('The cost field is required') }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap justify-content-end gap-10">
                                <button type="reset" class="btn btn-secondary px-5">{{translate('Reset')}}</button>
                                <button type="submit" class="btn btn--primary px-5">{{translate('Submit')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
    
                <div class="card border-0 shadow-sm mt-3">
                    <div class="px-3 py-3 d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <h5 class="text-capitalize mb-0 d-flex align-items-center gap-2">
                            <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/delivery.png')}}" alt="">
                            {{translate('Order wise shipping method')}}
                            <span class="badge badge-soft-dark radius-50 fs-12">{{ $shippingMethods->total() }}</span>
                        </h5>
                        <div class="flex-grow-1 max-w-300 min-w-100-mobile">
                            <form action="{{url()->current()}}" method="GET">
                                <div class="input-group">
                                    <input id="datatableSearch_" type="search" name="{{ $methodSearchParam }}" class="form-control"
                                           placeholder="{{ translate('Search by title or duration') }}"
                                           aria-label="{{ translate('Search by title or duration') }}" value="{{ $methodSearchValue }}">
                                    <div class="input-group-append search-submit">
                                        @if ($methodSearchValue)
                                            <a href="{{ $methodSearchClearUrl }}" class="input-group-text bg-light text-dark"
                                               data-toggle="tooltip" title="{{ translate('Clear search') }}">
                                                <i class="fi fi-rr-cross"></i>
                                            </a>
                                        @endif
                                        <button type="submit">
                                            <i class="fi fi-rr-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="datatable"
                               class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table text-start">
                            <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th class="border-0">{{translate('Serial')}}</th>
                                <th class="border-0">{{translate('Title')}}</th>
                                <th class="border-0">{{translate('Duration')}}</th>
                                <th class="border-0">{{translate('Cost')}}</th>
                                <th class="text-center border-0">{{translate('Status')}}</th>
                                <th class="text-center border-0">{{translate('Action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($shippingMethods as $k=>$method)
                                <tr>
                                    <th>{{$shippingMethods->firstItem()+$k}}</th>
                                    <td>
                                        <div class="max-w-300 min-w-200 text-wrap overflow-wrap-anywhere">{{ $method['title'] }}</div>
                                    </td>
                                    <td>
                                        {{ $method['duration'] }}
                                    </td>
                                    <td>
                                        {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $method['cost']), currencyCode: getCurrencyCode(type: 'default'))}}
                                    </td>
    
                                    <td>
                                        <form action="{{route($routeNames['status'])}}" method="post" id="shipping-methods{{$method['id']}}-form" class="shipping_methods_form">
                                            @csrf
                                            <input type="hidden" name="id" value="{{$method['id']}}">
                                            <label class="switcher mx-auto">
                                                <input type="checkbox" class="switcher_input toggle-switch-message"
                                                       id="shipping-methods{{$method['id']}}" name="status" value="1"
                                                       {{$method->status == 1 ? 'checked' : ''}}
                                                       data-modal-id = "toggle-status-modal"
                                                       data-toggle-id = "shipping-methods{{$method['id']}}"
                                                       data-on-image = "category-status-on.png"
                                                       data-off-image = "category-status-off.png"
                                                       data-on-title = "{{translate('Want to Turn ON :item', ['item' => translate('Shipping Method')]).'?'}}"
                                                       data-off-title = "{{translate('Want to Turn OFF :item', ['item' => translate('Shipping Method')]).'?'}}"
                                                       data-on-message = "<p>{{translate('Shows this shipping method at checkout in the app and website.')}}</p>"
                                                       data-off-message = "<p>{{translate('Hides this shipping method from checkout in the app and website.')}}</p>">
                                                <span class="switcher_control"></span>
                                            </label>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a  class="btn btn-outline--primary btn-sm square-btn"
                                                title="{{translate('Edit')}}"
                                                href="{{route($routeNames['update'],[$method['id']])}}">
                                                <i class="tio-edit"></i>
                                            </a>
                                            <a  class="btn btn-outline-danger square-btn btn-sm  delete-data-without-form"
                                                data-action="{{route($routeNames['delete'])}}"
                                                data-id="{{ $method['id'] }}">
                                                <i class="tio-delete"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
    
                    <div class="table-responsive mt-4">
                        <div class="px-4 d-flex justify-content-lg-end">
                            {!! $shippingMethods->links() !!}
                        </div>
                    </div>
                    @if(count($shippingMethods)==0)
                        @include('layouts.vendor.partials._empty-state',['text'=>'no_data_found'],['image'=>'default'])
                    @endif
                </div>
            </div>
        </div>
        <div class="card mt-2" id="update_category_shipping_cost"
             @style(['display: none' => $shippingType !== 'category_wise'])>
            <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap p-3">
                <h5 class="text-capitalize mb-0 d-flex align-items-center gap-2">
                    <img width="20"
                         src="{{ dynamicAsset(path: 'public/assets/back-end/img/delivery.png') }}"
                         alt="">
                    {{ translate('Category wise shipping cost') }}
                    <span class="badge badge-soft-dark radius-50 fs-12">{{ $allCategoryShippingCost->total() }}</span>
                </h5>
                <form action="{{ request()->url() }}"
                      method="get" class="flex-grow-1 max-w-300 min-w-100-mobile">
                    <div class="input-group">
                        <input type="search" name="searchValue" class="form-control"
                               placeholder="{{ translate('Search by category name') }}"
                               value="{{ request('searchValue') }}">
                        <div class="input-group-append search-submit">
                            <button type="submit">
                                <i class="fi fi-rr-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body px-0">
                <div class="table-responsive" id="category-shipping-cost-table"
                     data-action="{{ route($routeNames['category_store']) }}">
                    <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                           style="text-align: {{$direction === "rtl" ? 'right' : 'left'}};">
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{translate('Serial')}}</th>
                            <th>{{translate('Category Name')}}</th>
                            <th>{{translate('Cost per product')}}</th>
                            <th class="text-center">{{translate('Multiply with QTY')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach ($allCategoryShippingCost as $key => $item)
                                <tr>
                                    <td>
                                        {{ $allCategoryShippingCost->firstItem() + $key }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img class="rounded-circle aspect-1 object-fit-cover flex-shrink-0 border" width="40" height="40"
                                                 src="{{ getStorageImages(path: $item->category?->icon_full_url, type: 'backend-category') }}"
                                                 alt="">
                                            <div class="max-w-300 min-w-200 text-wrap overflow-wrap-anywhere">
                                                <div class="text-dark">{{ $item->category?->name ?? translate('Category not found') }}</div>
                                                <div class="fs-12 text-body mt-1">{{ translate('ID') }} : #{{ $item->category_id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control min-w-150 category-shipping-cost-input"
                                               min="0" step="0.01"
                                               data-category-id="{{ $item->category_id }}"
                                               value="{{ usdToDefaultCurrency($item->cost) }}">
                                    </td>
                                    <td>
                                        <label class="switcher mx-auto">
                                            <input type="checkbox" class="switcher_input category-shipping-multiply-input"
                                                   data-category-id="{{ $item->category_id }}"
                                                   value="1" {{ $item->multiply_qty == 1 ? 'checked' : '' }}>
                                            <span class="switcher_control"></span>
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                            @if($allCategoryShippingCost->count() == 0)
                                <tr>
                                    <td colspan="4">
                                        @include('layouts.vendor.partials._empty-state',['text'=>'no_data_found'],['image'=>'default'])
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-4">
                    <div class="px-4 d-flex justify-content-lg-end">
                        {!! $allCategoryShippingCost->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <span id="get-shipping-type-data" data-action="{{route($routeNames['type'])}}" data-success="{{translate(':item updated successfully', ['item' => translate('Shipping Method')]).'!!'}}"></span>
    <span id="get-shipping-type-value" data-value="{{$shippingType}}"></span>
@endsection
@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/vendor/shipping-method.js')}}"></script>
@endpush

