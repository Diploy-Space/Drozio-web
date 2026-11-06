@extends('theme-views.layouts.profile')

@section('title', translate('Restock Requests').' | '.$web_config['company_name'].' '.translate('eCommerce'))
@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-4">
        <div class="container">
            <div class="row g-3">
                @include('theme-views.partials._profile-aside')
                <div class="col-lg-9">
                    <div class="card h-lg-100">
                        <div class="card-body p-lg-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                                <h5>
                                    {{ translate('My Request List') }}
                                    @if($restockProducts->count() > 0)
                                        <span>({{ $restockProducts->count() }})</span>
                                    @endif
                                </h5>

                                @if($restockProducts->count() > 0)
                                    <a href="javascript:" class="btn btn-outline-danger call-route-alert" type="button"
                                       data-route="{{ $restockClearUrl }}"
                                       data-message="{{translate('Want to clear all restock request data')}}?">
                                        {{translate('Clear all')}}
                                    </a>
                                @endif
                            </div>
                            @include('theme-views.partials._profile-module-tabs')

                            @if($restockProducts->count()>0)
                                <div class="table-responsive d-none d-md-block">
                                    <table class="table align-middle table-striped">
                                        <thead class="text-primary">
                                            <tr>
                                                <th>{{ translate('Serial') }}</th>
                                                <th class="text-capitalize">{{ translate('Product Details') }}</th>
                                                <th>{{ translate('Review') }}</th>
                                                <th class="text-center">{{ translate('Price') }}</th>
                                                <th class="text-center">{{ translate('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($restockProducts as $key => $restockProduct)
                                            @php($restockCard = $restockCards[$restockProduct['id']])
                                            <tr>
                                                <td> {{ $restockProducts ->firstItem()+$key }}</td>
                                                <td>
                                                    <div class="media gap-3 align-items-center mn-w200">
                                                        <div class="avatar rounded size-3-75rem aspect-1 overflow-hidden d-flex align-items-center">
                                                            <img class="img-fit dark-support rounded" alt="" src="{{ getStorageImages(path: $restockProduct?->product?->thumbnail_full_url, type: 'backend-product') }}">
                                                        </div>
                                                        <div class="media-body">
                                                            <h6>
                                                                <a class="line-limit-1 max-w-200px"
                                                                   href="{{ $restockCard['url'] }}">
                                                                    {{ $restockProduct?->product?->name }}
                                                                </a>
                                                            </h6>
                                                            <div class="fs-12">
                                                                @if($restockProduct['variant'])
                                                                    <div>
                                                                        <span class="text-muted">{{ translate('Variant :') }}</span>
                                                                        <span>:</span>
                                                                        <span class="text-muted">{{$restockProduct['variant']  }}</span>
                                                                    </div>
                                                                @else
                                                                    <div>
                                                                        <span class="text-muted">{{ translate('Brand :') }}</span>
                                                                        <span>:</span>
                                                                        <span class="text-muted">
                                                                        @if(getWebConfig(name: 'product_brand'))
                                                                                {{ $restockProduct?->product?->brand?->name ?? ''  }}
                                                                            @endif
                                                                        </span>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @include('theme-views.partials._rating-display', [
                                                        'rating' => $restockCard['rating'],
                                                        'count' => $restockCard['rating_count'],
                                                        'size' => 'sm',
                                                        'showEmpty' => true,
                                                    ])
                                                </td>
                                                <td class="text-center">
                                                    <div class="text-dark">{{ $restockCard['price_label'] }}</div>
                                                    @if($restockCard['original_price_label'])
                                                        <div class="text-muted fs-12">
                                                            <del>{{ $restockCard['original_price_label'] }}</del>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-2 align-items-center">
                                                        <a href="{{ $restockCard['url'] }}"
                                                           class="btn btn-outline-primary btn-action">
                                                            <i class="bi bi-eye-fill"></i>
                                                        </a>
                                                        <a href="{{ $restockCard['delete_url'] }}" class="btn btn-outline-danger btn-action">
                                                            <i class="bi bi-trash3-fill"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="d-flex flex-column justify-content-center align-items-center gap-2 py-3 w-100">
                                    <img width="80" class="mb-3" src="{{ theme_asset('assets/img/empty-state/empty-wishlist.svg') }}" alt="">
                                    <h5 class="text-center text-muted">
                                        {{ translate('You have not added any product to restock request yet') }}!
                                    </h5>
                                </div>
                            @endif
                            @foreach($restockProducts as $key => $restockProduct)
                            @php($restockCard = $restockCards[$restockProduct['id']])
                            <div class="d-flex d-md-none gap-3 flex-column">
                                <div class="media gap-3 bg-light p-3 rounded">
                                    <div class="avatar border rounded size-3-437rem">
                                        <img src="{{ getStorageImages(path: $restockProduct?->product->thumbnail_full_url, type: 'backend-product') }}" class="img-fit dark-support rounded" alt="">
                                    </div>
                                    <div class="media-body d-flex flex-column gap-1">
                                        <a href="{{ $restockCard['url'] }}">
                                            <h6 class="text-capitalize width--20ch line-limit-2 link-hover-base">
                                                {{ $restockProduct?->product?->name}}
                                            </h6>
                                        </a>
                                        @include('theme-views.partials._rating-display', [
                                            'rating' => $restockCard['rating'],
                                            'count' => $restockCard['rating_count'],
                                            'size' => 'sm',
                                            'showEmpty' => true,
                                        ])
                                        <div>
                                            <div class="text-dark mb-1">
                                                <span>{{ translate('Total price :') }}</span>
                                                    <strong>{{ $restockCard['price_label'] }} </strong>
                                            </div>
                                            <div class="fs-12">
                                                    @if($restockProduct['variant'])
                                                        <div>
                                                            <span class="text-muted">{{ translate('Variant :') }}</span>
                                                            <span>:</span>
                                                            <span class="text-muted">{{$restockProduct['variant']  }}</span>
                                                        </div>
                                                    @else
                                                        <div>
                                                            <span class="text-muted">{{ translate('Brand :') }}</span>
                                                            <span>:</span>
                                                            <span class="text-muted">
                                                                @if(getWebConfig(name: 'product_brand'))
                                                                    {{ $restockProduct?->product?->brand?->name ?? ''  }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endif
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2 align-items-center mt-1">
                                            <a href="{{ $restockCard['url'] }}" class="btn btn-outline-success rounded-circle btn-action">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>
                                            <a href="{{ $restockCard['delete_url'] }}" type="button" class="btn btn-outline-danger rounded-circle btn-action">
                                                <i class="bi bi-trash3-fill"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            @if (count($restockProducts) > 0)
                                <div class="my-4" id="paginator-ajax">
                                    {!! $restockProducts->links() !!}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
