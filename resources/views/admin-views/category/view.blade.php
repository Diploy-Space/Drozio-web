@extends('layouts.admin.app')

@section('title', translate('Category'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 d-flex gap-10">
                <img src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/brand-setup.png') }}" alt="">
                {{ translate('Category Setup') }}
            </h2>
        </div>

        <div class="row mt-20" id="cate-table">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body d-flex flex-column gap-20">
                        <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                            <h3 class="mb-0">
                                {{ translate('Category List') }}
                                <span class="badge text-dark bg-body-secondary fw-semibold rounded-50">
                                    {{ $categories->total() }}
                                </span>
                            </h3>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <form action="{{ url()->current() }}" method="GET" class="flex-grow-1">
                                    <div class="input-group min-w-300">
                                        <input id="" type="search" name="searchValue" class="form-control"
                                               placeholder="{{ translate('Search by category name or id') }}"
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
                                       href="{{ route('admin.retail.category.export', ['searchValue' => request('searchValue')]) }}">
                                        <i class="fi fi-sr-inbox-in"></i>
                                        <span class="fs-12">{{ translate('Export') }}</span>
                                    </a>
                                </div>
                                <div>
                                    <button class="btn btn-primary" title="{{ translate('Add') }}"
                                            data-bs-toggle="offcanvas" href="#categoryAddOffcanvas"
                                    > + {{ translate('Add Category') }}</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-borderless align-middle text-dark">
                                <thead class="text-capitalize">
                                <tr>
                                    <th>{{ translate('Serial') }}</th>
                                    <th>{{ translate('Category Name') }}</th>
                                    <th class="text-center">{{ translate('Sub Category') }}</th>
                                    <th class="text-center">{{ translate('Sub Sub Category') }}</th>
                                    @if ($categoryWiseTax)
                                        <th>{{ translate('Tax rate') }}</th>
                                    @endif
                                    <th class="text-center">{{ translate('Priority') }}</th>
                                    <th class="text-center">{{ translate('Total Product') }}</th>
                                    <th class="text-center">{{ translate('Home Category Status') }}</th>
                                    <th class="text-center">{{ translate('Action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($categories as $key => $category)
                                    <tr>
                                        <td>{{ $categories->firstItem() + $key }}</td>
                                        <td class="d-flex justify-content-start align-items-center gap-2">
                                            <div
                                                class="avatar-50 d-flex align-items-center rounded overflow-hidden">
                                                <img class="w-100 h-100 object-fit-cover" alt=""
                                                     src="{{ getStorageImages(path: $category->icon_full_url, type: 'backend-category') }}">
                                            </div>
                                            <div>
                                                <div class="fs-14 line-1 max-w-200">{{ $category['defaultname'] }}</div>
                                                <span class="fs-12 opacity-70">ID #{{ $category['id'] }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">{{ $category->sub_category_count }}</td>
                                        <td class="text-center">{{ $category->sub_sub_category_count }}</td>
                                        @if ($categoryWiseTax)
                                            <td>
                                                @forelse ($category?->taxVats as $key => $item)
                                                    <div class="d-flex gap-1">
                                                        {{ $item?->tax?->name }} :
                                                        <span>({{ $item?->tax?->tax_rate ?? 0 }}%)</span>
                                                    </div>
                                                @empty
                                                    <span>{{ translate('N/A') }}</span>
                                                @endforelse
                                            </td>
                                        @endif
                                        <td class="text-center">
                                            {{ $category['priority'] }}
                                        </td>

                                        <td class="text-center">{{ $category->product_count }}</td>

                                        <td class="text-center">
                                            <form action="{{ route('admin.retail.category.status') }}" method="post"
                                                  id="category-status{{ $category['id'] }}-form" class="no-reload-form">
                                                @csrf
                                                <input type="hidden" name="status_type" value="home_status">
                                                <input type="hidden" name="id" value="{{ $category['id'] }}">
                                                <label class="switcher mx-auto"
                                                       for="category-status{{ $category['id'] }}">
                                                    <input
                                                        class="switcher_input custom-modal-plugin"
                                                        type="checkbox" value="1" name="home_status"
                                                        id="category-status{{ $category['id'] }}"
                                                        {{ $category['home_status'] == 1 ? 'checked' : '' }}
                                                        data-modal-type="input-change-form"
                                                        data-modal-form="#category-status{{ $category['id'] }}-form"
                                                        data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/category-status-on.png') }}"
                                                        data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/category-status-off.png') }}"
                                                        data-on-title="{{ translate('Want to Turn ON').' '.$category['defaultname'].' '. translate('Status') }}"
                                                        data-off-title="{{ translate('Want to Turn OFF').' '.$category['defaultname'].' '.translate('Status') }}"
                                                        data-on-message="<p>{{ translate('Shows this category in the homepage category section.') }}</p>"
                                                        data-off-message="<p>{{ translate('Hides this category from the homepage category section.') }}</p>"
                                                        data-on-button-text="{{ translate('Turn On') }}"
                                                        data-off-button-text="{{ translate('Turn Off') }}">
                                                    <span class="switcher_control"></span>
                                                </label>
                                            </form>
                                        </td>

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
                                                <a class="btn btn-outline-danger icon-btn delete-category"
                                                   title="{{ translate('Delete') }}"
                                                   data-product-count="{{ $category->product_count }}"
                                                   data-text="{{ translate('There were :count products under this category. Please update their category from the below list before deleting this one.', ['count' => $category->product_count]) }}"
                                                   id="{{ $category['id'] }}">
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
                        @if(count($categories) == 0)
                            @include('layouts.admin.partials._empty-state',['text'=>'no_category_found'],['image'=>'default'])
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include("admin-views.category.offcanvas._category-add", ['categoryType' => 'category'])
    @foreach($categoriesWithTrans as $key => $category)
        @include("admin-views.category.offcanvas._category-details", ['category' => $category])
        @include("admin-views.category.offcanvas._category-edit", ['category' => $category])
    @endforeach

    <span id="route-admin-category-delete" data-url="{{ route('admin.retail.category.delete') }}"></span>
    <span id="get-categories"
          data-categories="{{ json_encode(['data' => $categoryOptions]) }}"></span>

    <div class="modal fade" id="select-category-modal" tabindex="-1" aria-labelledby="toggle-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                            data-bs-dismiss="modal" aria-label="Close"><i
                            class="tio-clear"></i></button>
                </div>
                <div class="modal-body px-4 px-sm-5 pt-0 pb-sm-5">
                    <div class="d-flex flex-column align-items-center text-center gap-2 mb-2">
                        <div
                            class="toggle-modal-img-box d-flex flex-column justify-content-center align-items-center mb-3 position-relative">
                            <img src="{{dynamicAsset('public/assets/new/back-end/img/icons/info.svg')}}" alt=""
                                 width="90"/>
                        </div>
                        <h5 class="modal-title mb-2 category-title-message category-title-message"></h5>
                    </div>
                    <form action="{{ route('admin.retail.category.delete') }}" method="post"
                          class="product-category-update-form-submit">
                        @csrf
                        <input name="id" hidden="">
                        <div class="d-flex flex-column gap-2 mb-3">
                            <label class="title-color"
                                   for="exampleFormControlSelect1">{{ translate('Select category') }}
                                <span class="text-danger">*</span>
                            </label>
                            <select name="category_id" class="form-control js-select2-custom category-option" required>
                            </select>
                        </div>
                        <div class="d-flex justify-content-center gap-3">
                            <button type="submit" class="btn btn-primary min-w-120">{{translate('Update')}}</button>
                            <button type="button" class="btn btn-danger min-w-120" data-bs-dismiss="modal">
                                {{ translate('Cancel') }}
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/products/products-management.js') }}"></script>
@endpush
