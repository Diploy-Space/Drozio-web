@extends('layouts.vendor.app')

@section('title', translate($type == 'new-request' ? 'Pending_Products' : ($type == 'approved' ? 'Approved_Products' : 'Product_List')))

@section('content')
    <div class="content container-fluid">

        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex gap-2 align-items-center">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/inhouse-product-list.png') }}" alt="">
                {{ translate($type=='new-request'?'pending_for_approval_products':($type=='approved'?'approved_products':'product_list')) }}
                <span class="badge badge-soft-dark radius-50 fz-14 ml-1">
                    {{ $products->total() }}
                </span>
            </h2>
        </div>

        <div class="row mt-20">
            <div class="col-md-12">
                <div class="card">
                    <div class="px-3 py-4">
                        <div class="row align-items-center">
                            <div class="col-lg-4">

                                <form action="{{ url()->current() }}" method="GET">
                                    <div class="input-group input-group-custom input-group-merge">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text">
                                                <i class="tio-search"></i>
                                            </div>
                                        </div>
                                        <input id="datatableSearch_" type="search" name="searchValue"
                                               class="form-control"
                                               placeholder="{{ translate('Search by Product Name') }}"
                                               aria-label="Search orders"
                                               value="{{ request('searchValue') }}">
                                        <input type="hidden" value="{{ request('status') }}" name="status">
                                        @include('system-partials._product-filter-inputs')
                                        <button type="submit" class="btn btn--primary">{{ translate('Search') }}</button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-lg-8 mt-3 mt-lg-0 d-flex flex-wrap gap-3 justify-content-lg-end">
                                <div class="dropdown flex-grow-1 flex-grow-sm-0">
                                    <a type="button" class="btn btn-outline--primary text-nowrap w-100"
                                       href="{{ route('vendor.retail.products.export-excel', [
                                            'type' => $type,
                                            'searchValue' => request('searchValue'),
                                            'filter_sort_by' => request('filter_sort_by'),
                                            'filter_product_types' => request('filter_product_types'),
                                            'product_status' => request('product_status'),
                                            'filter_brand_ids' => request('filter_brand_ids'),
                                            'filter_category_ids' => request('filter_category_ids'),
                                            'filter_sub_category_ids' => request('filter_sub_category_ids'),
                                            'filter_sub_sub_category_ids' => request('filter_sub_sub_category_ids'),
                                            ]) }}"
                                        >
                                        <i class="fi fi-sr-inbox-in"></i>
                                        <span class="ps-1">{{ translate('Export') }}</span>
                                    </a>
                                </div>

                                <div class="position-relative flex-grow-1 flex-grow-sm-0">
                                    @if(!empty(request('filter_sort_by')) || !empty(request('filter_product_types')) || !empty(request('product_status')) || !empty(request('filter_shop_ids')) || !empty(request('filter_brand_ids')) || !empty(request('filter_category_ids')))
                                        <div class="position-absolute inset-inline-end-0 top-0 mt-n1 me-n1 btn-circle bg-danger border border-white border-2 z-2" style="--size: 12px;"></div>
                                    @endif
                                    <button type="button"
                                            @if(!empty(request('filter_sort_by')) || !empty(request('filter_product_types')) || !empty(request('product_status')) || !empty(request('filter_shop_ids')) || !empty(request('filter_brand_ids')) || !empty(request('filter_category_ids')))
                                                class="btn btn--primary px-4 w-100"
                                            @else
                                                class="btn btn-outline--primary px-4 w-100"
                                            @endif
                                            data-toggle="offcanvas" data-target="#offcanvasProductFilter">
                                        <i class="fi fi-sr-settings-sliders"></i>
                                        {{ translate('Filter') }}
                                    </button>
                                </div>

                                @if($type != 'new-request' )
                                <a href="{{ route('vendor.retail.products.add') }}" class="btn btn--primary flex-grow-1 flex-grow-sm-0">
                                    <i class="tio-add"></i>
                                    <span class="text">{{ translate('Add New Product') }}</span>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="datatable"
                               class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 text-start">
                            <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>{{ translate('Serial') }}</th>
                                <th class="text-capitalize">{{ translate('Product Name') }}</th>
                                <th class="text-center text-capitalize">{{ translate('Product Type') }}</th>
                                <th class="text-center text-capitalize">{{ translate('Unit Price') }}</th>
                                <th class="text-center">{{ translate('Stock') }}</th>
                                @if ($productWiseTax)
                                    <th class="text-center">{{ translate('VAT/TAX') }}</th>
                                @endif
                                @if($type != 'new-request' )
                                <th class="text-center text-capitalize">{{ translate('Active Status') }}</th>
                                @endif
                                <th class="text-center">{{ translate('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($products as $key=>$product)
                                <tr>
                                    <th scope="row">{{ $products->firstItem()+$key}}</th>
                                    <td>
                                        <a href="{{ route('vendor.retail.products.view', [$product['id']]) }}"
                                           class="media align-items-center gap-2">
                                            <img src="{{ getStorageImages(path:$product->thumbnail_full_url,type:'backend-product')}}"
                                                class="avatar border min-w-45 min-h-45 object-fit-cover" alt="">
                                            <div>
                                                <div class="d-flex align-items-center gap-1">
                                                    <div class="media-body title-color mb-1 hover-c1" data-toggle="tooltip" title="{{ $product['name'] }}">
                                                        {{ Str::limit($product['name'], 20) }}
                                                    </div>
                                                    @if($product?->clearanceSale)
                                                        <span class="text-secondary-base fs-12" data-placement="right" data-toggle="tooltip" title="{{ translate('Clearance Sale') }}">
                                                            <i class="fi fi-sr-bahai"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <span class="text-secondary">Id #{{$product['id']}}</span>
                                                    @if($product->request_status == 0)
                                                        <label class="badge badge-soft-warning m-0 font-weight-normal">{{translate('Pending')}}</label>
                                                    @elseif($product->request_status == 1)
                                                        <label class="badge badge-soft-success m-0 font-weight-normal">{{translate('Approved')}}</label>
                                                    @elseif($product->request_status == 2)
                                                        <label class="badge badge-soft-danger m-0 font-weight-normal">{{translate('Denied')}}</label>
                                                    @endif
                                                </div>
                                            </div>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        {{ translate($product['product_type']) }}
                                    </td>
                                    <td class="text-center">
                                        {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $product['unit_price']), currencyCode: getCurrencyCode()) }}
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center mx-auto w-80px gap-3 align-items-center lh-1">
                                            @if ($product['product_type'] === 'physical')
                                                <span>{{ $product->current_stock }}</span>
                                                @if ($product->current_stock <= 0)
                                                    <span class="text-danger-dark fs-18"
                                                          data-toggle="tooltip" data-placement="right"
                                                          title="{{ translate('Out of Stock') }}">
                                                            <i class="fi fi-sr-exclamation"></i>
                                                        </span>
                                                @elseif ($product->current_stock <= 20)
                                                    <span class="text-warning-dark fs-18"
                                                          data-toggle="tooltip" data-placement="right"
                                                          title="{{ translate('Low Stock') }}">
                                                        <i class="fi fi-sr-exclamation"></i>
                                                    </span>
                                                @endif
                                            @else
                                                <span>-</span>
                                            @endif
                                        </div>
                                    </td>
                                    @if ($productWiseTax)
                                        <td class="text-center">
                                                <span class="">
                                                    @forelse ($product?->taxVats as $key => $taxVat)
                                                        <span>{{ $taxVat?->tax?->name }} :
                                                            <span class="font-bold">
                                                                ({{ $taxVat?->tax?->tax_rate }}%)
                                                            </span>
                                                        </span>
                                                        <br>
                                                    @empty
                                                        <span>{{ translate('N/A') }}</span>
                                                    @endforelse
                                                </span>
                                        </td>
                                    @endif
                                    @if($type != 'new-request' )
                                        <td class="text-center">
                                            @php($productName = str_replace("'",'`',$product['name']))
                                            <form action="{{ route('vendor.retail.products.status-update') }}" method="post" data-from="product-status"
                                                  id="product-status{{ $product['id']}}-form" class="admin-product-status-form">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $product['id']}}">
                                                <label class="switcher mx-auto">
                                                    <input type="checkbox" class="switcher_input toggle-switch-message"
                                                           name="status"
                                                           id="product-status{{ $product['id'] }}" value="1"
                                                           {{ $product['status'] == 1 ? 'checked' : '' }}
                                                           data-modal-id="toggle-status-modal"
                                                           data-toggle-id="product-status{{ $product['id'] }}"
                                                           data-on-image="product-status-on.png"
                                                           data-off-image="product-status-off.png"
                                                           data-on-title="{{ translate('Want to Turn ON').' '.$productName.' '.translate('Status') }}"
                                                           data-off-title="{{ translate('Want to Turn OFF').' '.$productName.' '.translate('Status') }}"
                                                           data-on-message="<p>{{ translate('Shows this product on the website and app.') }}</p>"
                                                           data-off-message="<p>{{ translate('Hides this product on the website and app.') }}</p>">
                                                    <span class="switcher_control"></span>
                                                </label>
                                            </form>
                                        </td>
                                    @endif
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            @if($type != 'new-request' )
                                                <a class="btn btn-outline-warning btn-sm square-btn"
                                                   title="{{ translate('Barcode') }}"
                                                   href="{{ route('vendor.retail.products.barcode', [$product['id']]) }}">
                                                    <i class="tio-barcode"></i>
                                                </a>
                                            @endif
                                            <a class="btn btn-outline--success icon-btn square-btn" title="{{ translate('View') }}"
                                               href="{{ route('vendor.retail.products.view', [$product['id']]) }}">
                                                <i class="tio-invisible"></i>
                                            </a>
                                            <a class="btn btn-outline--primary btn-sm square-btn"
                                               title="{{ translate('Edit') }}"
                                               href="{{ route('vendor.retail.products.update',[$product['id']]) }}">
                                                <i class="tio-edit"></i>
                                            </a>
                                            <span class="btn btn-outline-danger btn-sm square-btn delete-data"
                                                  title="{{ translate('Delete') }}"
                                                  data-id="product-{{ $product['id']}}">
                                                <i class="tio-delete"></i>
                                            </span>
                                        </div>
                                        <form action="{{ route('vendor.retail.products.delete',[$product['id']]) }}"
                                              method="post" id="product-{{ $product['id']}}">
                                            @csrf @method('delete')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="table-responsive mt-4">
                        <div class="px-4 d-flex justify-content-lg-end">
                            {{ $products->links() }}
                        </div>
                    </div>

                    @if(count($products)==0)
                        @include('layouts.vendor.partials._empty-state',['text'=>'no_product_found'],['image'=>'default'])
                    @endif

                    @include('vendor-views.product.partials.offcanvas._filter-offcanvas')
                </div>
            </div>
        </div>
    </div>
    <span id="message-select-word" data-text="{{ translate('Select') }}"></span>
@endsection
