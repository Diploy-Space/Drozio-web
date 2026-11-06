@extends('layouts.admin.app')

@section('title',$seller?->shop->name ?? translate('Not found'))

@section('content')
    <div class="content container-fluid">
        

        <div class="flex-between d-sm-flex row align-items-center justify-content-between mb-2 mx-1">
            <div>
                @if ($seller->status=="pending")
                    <div class="mt-4">
                        <div class="flex-start">
                            <div class="mx-1"><h4><i class="fi fi-rr-shop"></i></h4></div>
                            <div>{{translate('Vendor request to open a shop')}}</div>
                        </div>
                        <div class="text-center">
                            <form class="d-inline-block" action="{{route('admin.people.vendors.update-status')}}" method="POST">
                                @csrf
                                <input type="hidden" name="id" value="{{$seller->id}}">
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn btn-primary btn-sm">{{translate('Approve')}}</button>
                            </form>
                            <form class="d-inline-block" action="{{route('admin.people.vendors.update-status')}}" method="POST">
                                @csrf
                                <input type="hidden" name="id" value="{{$seller->id}}">
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn btn-danger btn-sm">{{translate('Reject')}}</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @include('admin-views.vendor.partials._detail-header')

        <div class="card mt-3">
            <div class="px-3 py-4">
                <div class="d-flex justify-content-end flex-wrap gap-3 align-items-center mb-4">
                    <h3 class="mb-0 text-capitalize flex-grow-1">
                        {{ translate('Products') }}
                        <span class="badge badge-info text-bg-info">{{$products->total()}}</span>
                    </h3>

                    <form action="{{ url()->current() }}" method="GET" class="min-w-100-mobile min-w-280">
                        <div class="form-group mb-0">
                            <div class="input-group">
                                <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                                       placeholder="{{ translate('Search by product name') }}"
                                       aria-label="{{ translate('Search by product name') }}"
                                       value="{{ request('searchValue') }}">
                                <div class="input-group-append search-submit">
                                    <button type="submit">
                                        <i class="fi fi-rr-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable" class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                        <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>{{translate('Serial')}}</th>
                                <th>{{translate('Product Name')}}</th>
                                <th class="text-center">{{translate('Product Type')}}</th>
                                <th class="text-center">{{translate('Unit Price')}}</th>
                                <th class="text-center">{{ translate('Stock') }}</th>
                                @if ($productWiseTax)
                                    <th class="text-center">{{ translate('Vat/Tax') }}</th>
                                @endif
                                <th class="text-center">
                                    <div class="d-flex justify-content-center">
                                        <div>
                                            <div class="d-flex gap-2">
                                                <span>{{ translate('Show as featured') }} </span>
                                                <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                      data-bs-placement="top"
                                                      aria-label="{{ translate('Highlight this product on the homepage by marking it as featured') }}"
                                                      data-bs-title="{{ translate('Highlight this product on the homepage by marking it as featured') }}">
                                                        <i class="fi fi-sr-info"></i>
                                                    </span>
                                            </div>
                                            <div>{{ translate('On homepage') }}</div>
                                        </div>
                                    </div>
                                </th>
                                <th class="text-center">{{translate('Status')}}</th>
                                <th class="text-center">{{translate('Action')}}</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                        @foreach($products as $k=>$product)
                            <tr>
                                <td>{{$products->firstItem()+$k}}</td>
                                <td>
                                    <a href="{{ route('admin.retail.products.view', ['addedBy' => ($product['added_by'] == 'seller' ? 'vendor' : 'in-house'), 'id' => $product['id']]) }}"
                                    class="text-dark text-hover-primary gap-3 d-flex align-items-center">
                                        <img width="42" src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'backend-basic') }}"
                                            class="aspect-1 border object-fit-cover rounded" alt="">
                                        <div>
                                            <div class="d-flex align-items-center gap-1 text-wrap line-1 max-w-300 min-w-130 text-dark text-hover-primary">
                                                <div class="media-body line-1" data-bs-toggle="tooltip" title="{{ $product['name'] }}">
                                                    {{ Str::limit($product['name'], 20) }}
                                                </div>
                                                @if($product?->clearanceSale)
                                                    <span class="text-secondary" data-bs-toggle="tooltip" title="{{ translate('Clearance Sale') }}">
                                                        <i class="fi fi-sr-bahai"></i>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="d-flex gap-2 align-items-center lh-1 mt-2">
                                                <div class="text-body">
                                                    ID # {{$product['id']}}
                                                    @if($product->request_status == 0)
                                                        <label class="badge badge-soft-warning m-0 font-weight-normal">{{translate('Pending')}}</label>
                                                    @elseif($product->request_status == 1)
                                                        <label class="badge badge-soft-success m-0 font-weight-normal">{{translate('Approved')}}</label>
                                                    @elseif($product->request_status == 2)
                                                        <label class="badge badge-soft-danger m-0 font-weight-normal">{{translate('Denied')}}</label>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </td>
                                <td class="text-center">
                                    {{translate(str_replace('_',' ',$product['product_type']))}}
                                </td>
                                <td class="text-center">
                                    {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $product['unit_price']))}}
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-3 align-items-center lh-1">
                                        @if ($product['product_type'] === 'physical')
                                        <span>{{ $product->current_stock }}</span>
                                            @if ($product->current_stock <= 0)
                                                <span class="text-danger-dark fs-18"
                                                        data-bs-toggle="tooltip"
                                                        title="{{ translate('Out of Stock') }}">
                                                    <i class="fi fi-sr-exclamation"></i>
                                                </span>
                                            @elseif ($product->current_stock <= 20)
                                                <span class="text-warning-dark fs-18"
                                                        data-bs-toggle="tooltip"
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
                                <td class="text-center">
                                    @php
                                        $product_name = str_replace("'", '`', $product['name']);
                                        $isDisabled = $product['request_status'] !== 1;
                                    @endphp
                                    <div  @if($isDisabled) data-bs-toggle="tooltip" title="{{ translate('Only approved products can be featured') }}" @endif>
                                        <input type="checkbox"
                                               class="product-status-checkbox form-check-input checkbox--input checkbox--input_lg"
                                               data-id="{{ $product['id'] }}"
                                               data-action="{{ route('admin.retail.products.featured-status') }}"
                                            {{ $product->retailDetail?->featured == 1 ? 'checked' : '' }}
                                            {{ $isDisabled ? 'disabled' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <form action="{{route('admin.retail.products.status-update')}}" method="post"
                                        id="product-status{{$product['id']}}-form" class="status-update-form reload-true">
                                        @csrf
                                        <input type="hidden" name="id" value="{{$product['id']}}">
                                        <label class="switcher mx-auto" for="product-status{{$product['id']}}">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="status"
                                                id="product-status{{$product['id']}}"
                                                {{ $product['status'] == 1 ? 'checked':'' }}
                                                data-modal-type="input-change-form"
                                                data-modal-form="#product-status{{$product['id']}}-form"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/product-status-on.png') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/product-status-off.png') }}"
                                                data-on-title = "{{translate('Want to Turn ON').' '.$product_name.' '.translate('Status').'?'}}"
                                                data-off-title = "{{translate('Want to Turn OFF').' '.$product_name.' '.translate('Status').'?'}}"
                                                data-on-message = "<p>{{translate('Shows this product on the website and app.')}}</p>"
                                                data-off-message = "<p>{{translate('Hides this product on the website and app.')}}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </form>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-10">
                                        <a class="btn btn-outline-warning btn-outline-warning-dark icon-btn" title="{{ translate('Barcode') }}" href="{{ route('admin.retail.products.barcode', [$product['id']]) }}">
                                            <i class="fi fi-rr-barcode"></i>
                                        </a>
                                        <a class="btn btn-outline-success btn-outline-success-dark icon-btn" title="View" href="{{route('admin.retail.products.view',['addedBy'=>($product['added_by']=='seller'?'vendor' : 'in-house'),'id'=>$product['id']])}}">
                                            <i class="fi fi-rr-eye"></i>
                                        </a>
                                        <a class="btn btn-outline-info icon-btn" href="{{route('admin.retail.products.update',[$product['id']])}}">
                                            <i class="fi fi-sr-pencil"></i>
                                        </a>
                                        <a class="btn btn-outline-danger icon-btn delete-data" href="javascript:" data-id="product-{{$product['id']}}">
                                            <i class="fi fi-rr-trash"></i>
                                        </a>
                                    </div>
                                    <form action="{{route('admin.retail.products.delete',[$product['id']])}}"
                                        method="post" id="product-{{$product['id']}}">
                                        @csrf @method('delete')
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-4">
                    <div class="px-4 d-flex justify-content-end">
                        {{$products->links()}}
                    </div>
                </div>
                @if(count($products)==0)
                    @include('layouts.admin.partials._empty-state',['text'=>'no_product_found'],['image'=>'default'])
                @endif
            </div>
        </div>
    </div>
@endsection
