@extends('layouts.vendor.app')
@section('title', translate('Product Report'))
@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        @include('vendor-views.report._page-header', [
            'icon' => 'seller_sale.png',
            'title' => 'Product Report',
            'switchPage' => 'product-stock',
        ])

        @include('vendor-views.report.product-report-inline-menu')

        <div class="card mb-2">
            <div class="card-body">
                <form action="" id="form-data" method="GET">
                    <input type="hidden" name="search" value="{{ $search }}">
                    <h4 class="mb-3">{{translate('Filter Data')}}</h4>
                    <div class="row gx-2 gy-3 align-items-center text-left">
                        <div class="col-sm-6 col-md-4">
                            <select class="js-select2-custom form-control __form-control" name="category_id"
                                    id="cat_id">
                                <option value="all" {{ $categoryId === null ? 'selected' : '' }}>{{translate('All Category')}}</option>
                                @foreach($categories as $category)
                                    <option value="{{$category['id']}}" {{ $categoryId === $category['id'] ? 'selected' : '' }}>{{ $category['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="">
                                <select
                                        class="form-control"
                                        name="sort">
                                    <option value="ASC" {{ $sort == 'ASC' ? 'selected' : '' }}>{{translate('Stock sort by (low to high)')}}</option>
                                    <option value="DESC" {{ $sort == 'DESC' ? 'selected' : '' }}>{{translate('Stock sort by (high to low)')}}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-2">
                            <button type="submit" class="btn btn--primary px-4 px-md-5">
                                {{translate('Filter')}}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex flex-wrap w-100 gap-3 align-items-center justify-content-between">
                    <h4>
                        {{translate('Total Products')}}
                        <span class="badge badge-soft-dark radius-50 fs-12">{{ $products->total() }}</span>
                    </h4>

                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <form action="" method="GET">
                            <div class="input-group input-group-merge input-group-custom">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <i class="tio-search"></i>
                                    </div>
                                </div>
                                <input type="hidden" value="{{ $categoryId ?? 'all' }}" name="category_id">
                                <input type="hidden" value="{{ $sort }}" name="sort">
                                <input id="datatableSearch_" type="search" name="search" class="form-control"
                                       placeholder="{{translate('Search Product Name')}}" aria-label="Search orders"
                                       value="{{ $search }}">
                                <button type="submit" class="btn btn--primary">{{translate('Search')}}</button>
                            </div>
                        </form>
                        <div class="dropdown">
                            <a type="button" class="btn btn-outline--primary text-nowrap" href="{{ route('vendor.report.retail.product-stock-export', ['category_id' => $categoryId ?? 'all', 'sort' => $sort, 'search' => $search]) }}">
                                <img width="14" src="{{dynamicAsset(path: 'public/assets/back-end/img/excel.png')}}" class="excel" alt="">
                                <span class="ps-2">{{ translate('Export') }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" id="products-table">
                    <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 {{Session::get('direction') === "rtl" ? 'text-right' : 'text-left'}}">
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{translate('Serial')}}</th>
                            <th>
                                {{translate('Product Name')}}
                            </th>
                            <th>
                                {{translate('Last Updated Stock')}}
                            </th>
                            <th class="text-center">
                                {{translate('Current Stock')}}
                            </th>
                            <th class="text-center">
                                {{translate('Status')}}
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($products as $data)
                            <tr>
                                <td>{{ $products->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="p-name">
                                        <a href="{{route('vendor.retail.products.view', ['id' => $data['id']])}}"
                                           class="media align-items-center gap-2 title-color text-nowrap text-truncate w-100 d--block">
                                            {{ $data['name'] }}
                                        </a>
                                    </div>
                                </td>
                                <td>{{ date('d M Y', $data['updated_at'] ? strtotime($data['updated_at']) : null) }}</td>
                                <td class="text-center">{{$data['current_stock']}}</td>
                                <td>
                                    <div class="text-center">
                                        @if($data['stock_status'] === 'in_stock')
                                            <span class="badge __badge badge-soft-success">{{translate('In Stock')}}</span>
                                        @elseif($data['stock_status'] === 'out_of_stock')
                                            <span class="badge __badge badge-soft-warning">{{translate('Out of Stock')}}</span>
                                        @else
                                            <span class="badge __badge badge-soft--primary">{{translate('Soon Stock Out')}}</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="table-responsive mt-4">
                    <div class="px-4 d-flex justify-content-center justify-content-md-end">
                        {!! $products->links() !!}
                    </div>
                </div>
                @if($products->isEmpty())
                    @include('layouts.vendor.partials._empty-state',['text'=>'no_product_found'],['image'=>'default'])
                @endif
            </div>
        </div>
    </div>
@endsection
