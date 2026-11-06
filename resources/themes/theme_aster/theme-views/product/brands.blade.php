@extends('theme-views.layouts.app')

@section('title', translate('All Brands Page').' | '.$web_config['company_name'].' '.translate('eCommerce'))

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-30">
        <div class="container">
            <form action="{{ route('brands') }}" method="GET" class="brand-list-header">
                @if ($selectedOrder)
                    <input type="hidden" name="order_by" value="{{ $selectedOrder }}">
                @endif

                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row gy-3 align-items-center">
                            <div class="col-lg-4">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h1 class="h3 mb-0 text-capitalize">{{ translate('All brands') }}</h1>
                                    <span class="brand-count-badge">{{ $brands->total() }}</span>
                                </div>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb fs-12 mb-0">
                                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ translate('Home') }}</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">{{ translate('Brands') }}</li>
                                    </ol>
                                </nav>
                            </div>

                            <div class="col-lg-8">
                                <div class="d-flex justify-content-lg-end flex-wrap gap-3">
                                    <div class="search-box search-box-2 brand-page-search position-relative">
                                        <div class="d-flex">
                                            <div class="select-wrap focus-border border border-end-logical-0 d-flex align-items-center">
                                                <input type="search" class="form-control border-0 focus-input" name="search"
                                                       value="{{ $searchKeyword }}" autocomplete="off"
                                                       placeholder="{{ translate('Search Brands') }}" aria-label="{{ translate('Search Brands') }}">
                                            </div>
                                            <button type="submit" class="btn btn-primary" aria-label="{{ translate('Search') }}">
                                                <i class="bi bi-search"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="border rounded custom-ps-3 py-2">
                                        <div class="d-flex gap-2">
                                            <div class="flex-middle gap-2">
                                                <i class="bi bi-sort-up-alt"></i>
                                                <span class="d-none d-sm-inline-block">{{ translate('Sort By').':' }}</span>
                                            </div>
                                            <div class="dropdown">
                                                <button type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                                        class="border-0 bg-transparent dropdown-toggle text-dark p-0 custom-pe-3">
                                                    {{ $activeSortLabel }}
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @foreach ($sortLinks as $sortLink)
                                                        <li>
                                                            <a class="dropdown-item link-hover-base {{ $sortLink['active'] ? 'active' : '' }}"
                                                               href="{{ $sortLink['url'] }}">{{ $sortLink['label'] }}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="card">
                <div class="card-body">
                    @if ($brands->isEmpty())
                        <div class="d-flex flex-column justify-content-center align-items-center gap-2 py-5 w-100">
                            <img width="80" class="mb-3" alt="{{ translate('Empty') }}"
                                 src="{{ theme_asset('assets/img/empty-state/empty-brand.svg') }}">
                            <h5 class="text-center text-muted mb-0">{{ translate('There is no Brand') }}</h5>
                            @if ($searchKeyword)
                                <a href="{{ route('brands') }}" class="btn btn-outline-primary btn-sm rounded-pill mt-2">
                                    {{ translate('Clear search') }}
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="brand-tile-grid">
                            @foreach ($brands as $brand)
                                <a href="{{ route('brand-products', ['slug' => $brand->slug]) }}"
                                   class="brand-tile" title="{{ $brand->name }}">
                                    <span class="brand-tile__logo">
                                        <img loading="lazy" alt="{{ $brand->image_alt_text ?? $brand->name }}"
                                             src="{{ getStorageImages(path: $brand->image_full_url, type: 'brand') }}">
                                    </span>
                                    <span class="d-flex flex-column gap-1 w-100 p-2">
                                        <span class="brand-tile__name fw-semibold text-dark">{{ $brand->name }}</span>
                                        <span class="brand-tile__count fs-12 text-muted">
                                            {{ formatCompactNumber($brand->brand_products_count) }}
                                            {{ $brand->brand_products_count == 1 ? translate('Product') : translate('Products') }}
                                        </span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @if ($brands->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {!! $brands->links() !!}
                </div>
            @endif
        </div>
    </main>
@endsection
