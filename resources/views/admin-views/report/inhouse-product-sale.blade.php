@extends('layouts.admin.app')

@section('title', translate('Inhouse product sale Report'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        @include('admin-views.report._page-header', [
            'icon' => 'inhouse_sale.png',
            'title' => 'Inhouse Sale',
        ])

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-6">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex justify-content-between gap-3 align-items-center">
                                <div class="info">
                                    <h4 class="subtitle h1 text-info mb-1">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $saleSummary['total_sale_amount']), currencyCode: getCurrencyCode()) }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">{{ translate('Total In-House Sales')}}</h5>
                                </div>
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-t-earning.png')}}" alt="" class="object-fit-contain">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-6">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex justify-content-between gap-3 align-items-center">
                                <div class="info">
                                    <h4 class="subtitle h1 text-success mb-1">{{ $saleSummary['total_order_count'] }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">{{ translate('Total In-House Orders')}}</h5>
                                </div>
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-t-commission.png')}}" alt="" class="object-fit-contain">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-6">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex justify-content-between gap-3 align-items-center">
                                <div class="info">
                                    <h4 class="subtitle h1 text-warning-dark mb-1">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $saleSummary['average_order_value']), currencyCode: getCurrencyCode()) }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">
                                        {{ translate('Average Order Value')}}
                                    </h5>
                                </div>
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-t-shipping.png')}}" alt="" class="object-fit-contain">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-6">
                        <div class="card border shadow-none p-20 h-100">
                            <div class="d-flex justify-content-between gap-3 align-items-center">
                                <div class="info">
                                    <h4 class="subtitle h1 text-primary mb-1">{{ $saleSummary['total_customer_count'] }}</h4>
                                    <h5 class="fs-14 fw-normal text-muted">{{ translate('Total Customers')}}</h5>
                                </div>
                                <img width="35" src="{{dynamicAsset(path: 'public/assets/back-end/img/report-t-inhouse.png')}}" alt="" class="object-fit-contain">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-0">
                    <form class="w-100" method="GET" action="{{ url()->current() }}">
                        <label class="text-dark mb-10px">{{translate('Select category')}}</label>
                        <div class="bg-section rounded p-3 mb-20">
                            <div class="row g-3 align-items-center">
                                <div class="col-sm-6 col-md-3">
                                    <div class="d-flex align-items-center gap-10">
                                        <select class="custom-select" name="category_id">
                                            <option value="all">{{translate('All')}}</option>
                                            @foreach($categories as $category)
                                                <option value="{{$category['id'] }}" {{ $selectedCategoryId === $category['id'] ? 'selected' : '' }}>
                                                    {{$category['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 justify-content-end">
                            <button type="reset" class="btn btn-secondary min-w-120">
                                {{ translate('Reset') }}
                            </button>
                            <button type="submit" class="btn btn-primary min-w-120">
                                {{translate('Filter')}}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-20">
                    <h4 class="mb-0 d-flex align-items-center gap-2">
                        {{ translate('Total Products') }}
                        <span class="badge badge-info text-bg-info">{{ $products->total() }}</span>
                    </h4>
                    <a type="button" class="btn btn-outline-primary" href="{{ route('admin.report.retail.inhouse-product-sale-excel-export', ['category_id' => $selectedCategoryId]) }}">
                        <i class="fi fi-sr-inbox-in"></i>
                        <span class="fs-12">{{ translate('Export') }}</span>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-borderless table-thead-bordered table-align-middle card-table w-100">
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{translate('Serial')}} </th>
                            <th>
                                {{translate('Product Name')}}
                            </th>
                            <th class="text-center">
                                {{translate('Total Sale')}}
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($products as $key => $data)
                            <tr>
                                <td>{{$products->firstItem() + $key }}</td>
                                <td><a class="text-dark text-decoration-none" href="{{ route('admin.retail.products.view', ['addedBy' => 'in-house', 'id' => $data['id']]) }}">{{$data['name'] }}</a></td>
                                <td class="text-center">{{ (int) $data['total_sold_quantity'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-4">
                    <div class="px-4 d-flex justify-content-lg-end">
                        {!! $products->links() !!}
                    </div>
                </div>
                @if(count($products) <= 0)
                    @include('layouts.admin.partials._empty-state',['text'=>'no_product_found'],['image'=>'default'])
                @endif
            </div>
        </div>
    </div>
@endsection
