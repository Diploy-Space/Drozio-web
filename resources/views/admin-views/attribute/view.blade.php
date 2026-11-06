@extends('layouts.admin.app')

@section('title', translate('Attribute'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 d-flex gap-2 align-items-center">
                <img src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/attribute.png') }}" alt="">
                {{ translate('Attribute Setup') }}
            </h2>
        </div>

        <div class="row product-attribute-management">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body d-flex flex-column gap-20">
                        <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                            <h3 class="mb-0 d-flex align-items-center gap-2">
                                {{ translate('Attribute List') }}
                                <span class="badge text-dark bg-body-secondary fw-semibold rounded-50">
                                    {{ $attributes->total() }}
                                </span>
                            </h3>
                            <div class="d-flex align-items-center gap-3 flex-wrap flex-grow-1 justify-content-end">
                                <form action="{{ url()->current() }}" method="GET">
                                    <div class="input-group flex-grow-1 max-w-300 min-w-100-mobile">
                                        <input id="datatableSearch_" type="search" name="searchValue"
                                               class="form-control"
                                               placeholder="{{ translate('Search by Attribute Name') }}"
                                               aria-label="{{ translate('Search by Attribute Name') }}"
                                               value="{{ request('searchValue') }}">
                                        <div class="input-group-append search-submit">
                                            <button type="submit">
                                                <i class="fi fi-rr-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                <button class="btn btn-primary text-nowrap" title="{{ translate('Add Attribute') }}"
                                        data-bs-toggle="offcanvas" href="#attributeAddOffcanvas">
                                    + {{ translate('Add Attribute') }}
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-borderless align-middle">
                                <thead class="text-capitalize">
                                <tr>
                                    <th>{{ translate('Serial') }}</th>
                                    <th>{{ translate('Attribute Name') }} </th>
                                    <th class="text-center w-200">{{ translate('Action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($attributes as $key => $attribute)
                                    <tr>
                                        <td>{{ $attributes->firstItem() + $key }}</td>
                                        <td>{{ $attribute['defaultname'] }}</td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a class="btn btn-outline-info icon-btn"
                                                   title="{{ translate('Edit') }}"
                                                   data-bs-toggle="offcanvas" href="#attributeEditOffcanvas-{{ $attribute['id'] }}">
                                                    <i class="fi fi-sr-pencil"></i>
                                                </a>

                                                <a class="btn btn-outline-danger icon-btn attribute-delete-button"
                                                   title="{{ translate('Delete') }}"
                                                   id="{{ $attribute['id'] }}">
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
                                {!! $attributes->links() !!}
                            </div>
                        </div>

                        @if(count($attributes) == 0)
                            @include('layouts.admin.partials._empty-state',['text'=>'no_attribute_found'],['image'=>'default'])
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin-views.attribute.offcanvas._attribute-add')

    @foreach($attributes as $attribute)
        @include('admin-views.attribute.offcanvas._attribute-edit', ['attribute' => $attribute])
    @endforeach

    <span id="route-admin-attribute-delete" data-url="{{ route('admin.retail.attribute.delete') }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/products/products-management.js') }}"></script>
@endpush
