@extends('layouts.admin.app')

@section('title', translate('Stock limit products'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 d-flex flex-column gap-1">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/inhouse-product-list.png') }}" alt="">
                {{ translate('Limited stocked products') }}
                <span class="badge text-dark bg-body-secondary fw-semibold rounded-50">{{ $products->total() }}</span>
            </h2>
            <p class="mb-0 fs-12">{{ $stockLimitNotice }}</p>
        </div>

        <div class="row mt-20">
            <div class="col-md-12">
                <div class="card">
                    <div class="px-3 py-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="min-w-300 min-w-100-mobile">
                                <form action="{{ url()->current() }}" method="get">
                                    @foreach ($preservedFilterInputs as $preservedFilterInput)
                                        <input type="hidden" name="{{ $preservedFilterInput['name'] }}"
                                               value="{{ $preservedFilterInput['value'] }}">
                                    @endforeach
                                    <div class="input-group">
                                        <input id="datatableSearch_" type="search" name="searchValue"
                                               class="form-control"
                                               placeholder="{{ translate('Search by product name') }}"
                                               aria-label="{{ translate('Search') }}"
                                               value="{{ $searchValue }}">
                                        <div class="input-group-append search-submit">
                                            <button type="submit">
                                                <i class="fi fi-rr-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="d-flex flex-wrap gap-3 justify-content-lg-end">
                                <div class="position-relative">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas"
                                            data-bs-target="#offcanvasProductFilter">
                                        <i class="fi fi-sr-settings-sliders d-flex"></i> {{ translate('Filter') }}
                                    </button>
                                    @if ($hasActiveFilters)
                                        <div class="position-absolute top-n1 inset-inline-end-n1 btn-circle bg-danger border border-white border-2"
                                             style="--size: 12px;"></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-borderless table-thead-bordered align-middle">
                            <thead class="text-capitalize">
                            <tr>
                                <th>{{ translate('SL') }}</th>
                                <th>{{ translate('Product name') }}</th>
                                <th class="text-center">{{ translate('Unit price') }}</th>
                                <th class="text-center">{{ translate('Stock') }}</th>
                                <th class="text-center">{{ translate('Orders') }}</th>
                                <th class="text-center">{{ translate('Status') }}</th>
                                <th class="text-center">{{ translate('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($products as $key => $product)
                                <tr>
                                    <th scope="row">{{ $products->firstItem() + $key }}</th>
                                    <td>
                                        <a href="{{ route('admin.retail.products.view', ['addedBy' => $product['added_by'] == 'seller' ? 'vendor' : 'in-house', 'id' => $product['id']]) }}"
                                           class="media align-items-center gap-2">
                                            <img src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'backend-product') }}"
                                                 class="avatar border object-fit-cover flex-shrink-0" alt="">
                                            <div>
                                                <div class="media-body text-dark line-1 text-hover-primary max-w-300 min-w-130"
                                                     data-bs-toggle="tooltip" title="{{ $product['name'] }}">
                                                    {{ Str::limit($product['name'], 20) }}
                                                </div>
                                                <div class="d-flex gap-2 align-items-center lh-1 mt-2">
                                                    <div class="text-body">{{ translate('Id') }} # {{ $product['id'] }}</div>
                                                </div>
                                            </div>
                                        </a>
                                    </td>
                                    <td class="text-center">{{ $productMeta[$product['id']]['price'] }}</td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2 align-items-center lh-1">
                                            <span>{{ $product['current_stock'] }}</span>
                                            @if ($productMeta[$product['id']]['stockAlert'] === 'out-of-stock')
                                                <span class="text-danger-dark fs-18" data-bs-toggle="tooltip"
                                                      title="{{ translate('Out of stock') }}">
                                                    <i class="fi fi-sr-exclamation"></i>
                                                </span>
                                            @else
                                                <span class="text-warning-dark fs-18" data-bs-toggle="tooltip"
                                                      title="{{ translate('Low stock') }}">
                                                    <i class="fi fi-sr-exclamation"></i>
                                                </span>
                                            @endif
                                            <button class="btn p-0 border-0 fs-18 action-update-product-quantity"
                                                    id="{{ $product['id'] }}"
                                                    data-url="{{ route('admin.retail.products.get-variations').'?id='.$product['id'] }}"
                                                    type="button"
                                                    data-bs-target="#update-quantity"
                                                    data-bs-toggle="modal"
                                                    title="{{ translate('Update quantity') }}">
                                                <i class="fi fi-sr-add text-primary"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ $product['order_details_count'] }}</td>
                                    <td class="text-center">
                                        @if ($product->request_status != 2)
                                            <form action="{{ route('admin.retail.products.status-update') }}" method="post"
                                                  id="product-status{{ $product['id'] }}-form"
                                                  class="no-reload-form">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $product['id'] }}">
                                                <label class="switcher mx-auto" for="product-status{{ $product['id'] }}">
                                                    <input class="switcher_input custom-modal-plugin"
                                                           type="checkbox" value="1" name="status"
                                                           id="product-status{{ $product['id'] }}"
                                                           {{ $product['status'] == 1 ? 'checked' : '' }}
                                                           data-modal-type="input-change-form"
                                                           data-modal-form="#product-status{{ $product['id'] }}-form"
                                                           data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/product-status-on.png') }}"
                                                           data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/product-status-off.png') }}"
                                                           data-on-title="{{ translate('Want to turn ON').' '.str_replace("'", '`', $product['name']).' '.translate('Status') }}"
                                                           data-off-title="{{ translate('Want to turn OFF').' '.str_replace("'", '`', $product['name']).' '.translate('Status') }}"
                                                           data-on-message="<p>{{ translate('Shows this product on the website and app.') }}</p>"
                                                           data-off-message="<p>{{ translate('Hides this product on the website and app.') }}</p>"
                                                           data-on-button-text="{{ translate('Turn on') }}"
                                                           data-off-button-text="{{ translate('Turn off') }}">
                                                    <span class="switcher_control"></span>
                                                </label>
                                            </form>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <a class="btn btn-outline-warning top01 btn-outline-warning-dark icon-btn"
                                               title="{{ translate('Barcode') }}"
                                               href="{{ route('admin.retail.products.barcode', [$product['id']]) }}">
                                                <i class="fi fi-sr-barcode"></i>
                                            </a>
                                            <a class="btn btn-outline-success top01 btn-outline-success-dark icon-btn"
                                               title="{{ translate('View') }}"
                                               href="{{ route('admin.retail.products.view', ['addedBy' => $product['added_by'] == 'seller' ? 'vendor' : 'in-house', 'id' => $product['id']]) }}">
                                                <i class="fi fi-sr-eye"></i>
                                            </a>
                                            <a class="btn btn-outline-info top01 icon-btn"
                                               title="{{ translate('Edit') }}"
                                               href="{{ route('admin.retail.products.update', [$product['id']]) }}">
                                                <i class="fi fi-sr-pencil"></i>
                                            </a>
                                            <span class="btn btn-outline-danger top01 icon-btn delete-data"
                                                  title="{{ translate('Delete') }}"
                                                  data-id="product-{{ $product['id'] }}">
                                                <i class="fi fi-rr-trash"></i>
                                            </span>
                                        </div>
                                        <form action="{{ route('admin.retail.products.delete', [$product['id']]) }}"
                                              method="post" id="product-{{ $product['id'] }}">
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

                    @if (count($products) == 0)
                        @include('layouts.admin.partials._empty-state', ['text' => 'no_product_found'], ['image' => 'default'])
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('admin-views.product.partials.offcanvas._filter-offcanvas', [
        'offcanvasRouteName' => 'admin.retail.products.stock-limit-list',
        'offcanvasRouteParams' => [],
        'filterSections' => ['sorting', 'stock_sorting', 'product_status', 'brand', 'category'],
    ])

    <div class="modal fade update-stock-modal" id="update-quantity" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('admin.retail.products.update-quantity') }}" method="post" class="modal-body p-20">
                    @csrf
                    <div class="rest-part-content"></div>
                    <div class="d-flex justify-content-end gap-10 flex-wrap align-items-center">
                        <button type="button" class="btn btn-danger px-4" data-bs-dismiss="modal" aria-label="Close">
                            {{ translate('Close') }}
                        </button>
                        <button class="btn btn-primary px-4" type="submit">
                            {{ translate('Submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
