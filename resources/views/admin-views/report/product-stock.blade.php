@extends('layouts.admin.app')
@section('title', translate('Product Stock'))
@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">

        @include('admin-views.report._page-header', [
            'icon' => 'seller_sale.png',
            'title' => 'Product Report',
        ])

        @include('admin-views.report.product-report-inline-menu')

        <div class="card mb-3">
            <div class="card-body">
                <form action="" id="form-data" method="GET">
                    <input type="hidden" name="search" value="{{ $search }}">
                    <h3 class="mb-3">{{ translate('Filter Data') }}</h3>
                    <div class="bg-section rounded p-3 mb-20">
                        <div class="row g-3 align-items-center">
                            <div class="col-sm-6 col-md-4">
                                <label class="mb-2">{{ translate('Select Vendor') }}</label>
                                <select class="custom-select text-ellipsis" name="seller_id">
                                    <option value="all" {{ $sellerId === 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                                    <option value="in_house" {{ $sellerId === 'in_house' ? 'selected' : '' }}>{{ translate('In-house') }}</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor['id'] }}" {{ $sellerId === $vendor['id'] ? 'selected' : '' }}>
                                            {{ $vendor?->shop?->name ?? translate('Not found') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <label class="mb-2">{{ translate('Select category') }}</label>
                                <select class="custom-select" name="category_id" id="cat_id">
                                    <option value="all" {{ $categoryId === null ? 'selected' : '' }}>{{ translate('All Category') }}</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category['id'] }}" {{ $categoryId === $category['id'] ? 'selected' : '' }}>{{ $category['default_name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <label class="mb-2">{{ translate('Sort By') }}</label>
                                <div class="">
                                    <div class="select-wrapper">
                                        <select class="form-select" name="sort">
                                            <option value="ASC" {{ $sort === 'ASC' ? 'selected' : '' }}>{{ translate('Stock sort by (low to high)') }}</option>
                                            <option value="DESC" {{ $sort === 'DESC' ? 'selected' : '' }}>{{ translate('Stock sort by (high to low)') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 justify-content-end">
                        <button type="reset" class="btn btn-secondary min-w-120">
                            {{ translate('Reset') }}
                        </button>
                        <div class="">
                            <button type="submit" class="btn btn-primary min-w-120">
                                {{ translate('Filter') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-4">
                    <h3 class="mb-0">
                        {{ translate('Total Products') }}
                        <span class="badge badge-info text-bg-info">{{ $products->total() }}</span>
                    </h3>
                    <div class="d-flex gap-3 flex-wrap">
                        <form action="" method="GET">
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="hidden" value="{{ $sellerId }}" name="seller_id">
                                    <input type="hidden" value="{{ $categoryId ?? 'all' }}" name="category_id">
                                    <input type="hidden" value="{{ $sort }}" name="sort">
                                    <input id="datatableSearch_" type="search" class="form-control min-w-300" name="search" value="{{ $search }}" placeholder="{{ translate('Search Product Name') }}">
                                    <div class="input-group-append search-submit">
                                        <button type="submit">
                                            <i class="fi fi-rr-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <a type="button" class="btn btn-outline-primary" href="{{ route('admin.report.retail.stock.product-stock-export', ['sort' => $sort, 'category_id' => $categoryId ?? 'all', 'seller_id' => $sellerId, 'search' => $search]) }}">
                            <i class="fi fi-sr-inbox-in"></i>
                            <span class="fs-12">{{ translate('Export') }}</span>
                        </a>
                    </div>
                </div>

                <div class="table-responsive" id="products-table">
                    <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 {{ Session::get('direction') === 'rtl' ? 'text-right' : 'text-left' }}">
                        <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>{{ translate('Serial') }}</th>
                                <th>{{ translate('Product Name') }}</th>
                                <th>{{ translate('Last Updated Stock') }}</th>
                                <th class="text-center">{{ translate('Current Stock') }}</th>
                                <th class="text-center">{{ translate('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($products as $key => $product)
                            <tr>
                                <td>{{ $products->firstItem() + $key }}</td>
                                <td>
                                    @include('admin-views.report._product-name', ['product' => $product, 'url' => route('admin.retail.products.view', ['addedBy' => $product['added_by'] === 'seller' ? 'vendor' : 'in-house', 'id' => $product['id']])])
                                </td>
                                <td>{{ date('d M Y, h:i:s a', $product['updated_at'] ? strtotime($product['updated_at']) : null) }}</td>
                                <td class="text-center">{{ $product['current_stock'] }}</td>
                                <td>
                                    <div class="text-center">
                                        @if($product['stock_status'] === 'in_stock')
                                            <span class="badge text-bg-success badge-success">{{ translate('In Stock') }}</span>
                                        @elseif($product['stock_status'] === 'out_of_stock')
                                            <span class="badge text-bg-warning badge-warning">{{ translate('Out of Stock') }}</span>
                                        @else
                                            <span class="badge text-bg-info badge-info">{{ translate('Soon Stock Out') }}</span>
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
                @if(count($products) === 0)
                    @include('layouts.admin.partials._empty-state', ['text' => 'no_product_found'], ['image' => 'default'])
                @endif
            </div>
        </div>
    </div>
@endsection
