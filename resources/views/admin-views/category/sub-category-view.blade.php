@extends('layouts.admin.app')

@section('title', translate('Subcategory'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 d-flex gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/brand-setup.png') }}" alt="">
                {{ translate('Sub Category Setup') }}
            </h2>
        </div>

        <div class="row mt-20" id="cate-table">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body d-flex flex-column gap-20">
                        <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                            <h3 class="mb-0">
                                {{ translate('Sub Category List') }}
                                <span class="badge text-dark bg-body-secondary fw-semibold rounded-50 mx-1">
                                    {{ $categories->total() }}
                                </span>
                            </h3>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <form action="{{ url()->current() }}" method="GET" class="flex-grow-1">
                                    <div class="input-group min-w-300">
                                        <input id="" type="search" name="searchValue" class="form-control"
                                               placeholder="{{ translate('Search by sub category name or id') }}"
                                               value="{{ request('searchValue') }}">
                                        <div class="input-group-append search-submit">
                                            <button type="submit">
                                                <i class="fi fi-rr-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <div class="dropdown">
                                    <a type="button" class="btn btn-outline-primary"
                                       href="{{ route('admin.retail.sub-category.export', ['searchValue' => request('searchValue')]) }}">
                                        <i class="fi fi-sr-inbox-in"></i>
                                        <span class="fs-12">{{ translate('Export') }}</span>
                                    </a>
                                </div>

                                <div>
                                    <button class="btn btn-primary" title="{{ translate('Add') }}"
                                            data-bs-toggle="offcanvas" href="#categoryAddOffcanvas"
                                    > + {{ translate('Add Sub Category') }}</button>
                                </div>

                            </div>
                        </div>
                        <div class="table-responsive">
                            <table
                                class="table table-hover table-borderless table-thead-bordered table-nowrap align-middle">
                                <thead class="text-capitalize">
                                <tr>
                                    <th>{{ translate('Serial') }}</th>
                                    <th>{{ translate('Sub Category Info') }}</th>
                                    <th>{{ translate('Main Category Name') }}</th>
                                    <th class="text-center">{{ translate('Sub Sub Category') }}</th>
                                    <th class="text-center">{{ translate('Priority') }}</th>
                                    <th class="text-center">{{ translate('Total Product') }}</th>
                                    <th class="text-center">{{ translate('Action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($categories as $key => $category)
                                    <tr>
                                        <td>{{ $categories->firstItem() + $key }}</td>
                                        <td>
                                            <div class="d-flex gap-2 align-items-center">
                                                @if (theme_root_path() == 'theme_aster')
                                                    <div
                                                        class="avatar-60 d-flex align-items-center rounded overflow-hidden flex-shrink-0">
                                                        <img class="w-100 h-100 object-fit-cover" alt=""
                                                             src="{{ getStorageImages(path: $category->icon_full_url , type: 'backend-basic') }}">
                                                    </div>
                                                @endif

                                                <div class="overflow-hidden max-w-200">
                                                    <h6 class="fs-14 line-1 max-w-200 text-truncate">{{ $category['defaultname'] }}</h6>
                                                    <span class="fs-12 opacity-70">ID #{{ $category['id'] }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="max-w-120 text-wrap">{{ $category?->parent?->defaultname ?? translate('Not found') }}</td>
                                        <td class="text-center">{{ $category->sub_sub_category_count }}</td>
                                        <td class="text-center">{{ $category['priority']}}</td>
                                        <td class="text-center">{{ $category->product_count }}</td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-3">
                                                <a class="btn btn-outline-success icon-btn" title="{{ translate('View') }}"
                                                   data-bs-toggle="offcanvas" href="#categoryDetailsOffcanvas-{{ $category['id'] }}">
                                                    <i class="fi fi-sr-eye"></i>
                                                </a>
                                                <a class="btn btn-outline-info icon-btn edit" title="{{ translate('Edit') }}"
                                                   id="category-edit-button-{{ $category['id'] }}"
                                                   data-bs-toggle="offcanvas" href="#categoryEditOffcanvas-{{ $category['id'] }}">
                                                    <i class="fi fi-sr-pencil"></i>
                                                </a>
                                                <a class="btn btn-outline-danger icon-btn category-delete-button"
                                                   title="{{ translate('Delete') }}"
                                                   id="{{ $category['id']}}">
                                                    <i class="fi fi-rr-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="table-responsive mt-4">
                            <div class="d-flex justify-content-lg-end">
                                {{ $categories->links() }}
                            </div>
                        </div>

                        @if(count($categories)==0)
                            @include('layouts.admin.partials._empty-state',['text'=>'no_sub_category_found'],['image'=>'default'])
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>


    @include("admin-views.category.offcanvas._category-add", ['categoryType' => 'sub_category'])
    @foreach($categoriesWithTrans as $key => $category)
        @include("admin-views.category.offcanvas._category-details", ['category' => $category])
        @include("admin-views.category.offcanvas._category-edit", ['category' => $category])
    @endforeach

    <span id="route-admin-category-delete" data-url="{{ route('admin.retail.sub-category.delete') }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/products/products-management.js') }}"></script>
@endpush
