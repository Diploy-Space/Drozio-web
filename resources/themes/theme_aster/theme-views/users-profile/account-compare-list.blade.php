@extends('theme-views.layouts.app')

@section('title', translate('My Compare List').' | '.$web_config['company_name'].' '.translate('eCommerce'))

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-4">
        <div class="container">
            <div class="row g-3">
                @include('theme-views.partials._profile-aside')
                <div class="col-lg-9">
                    <div class="card h-100">
                        <div class="card-body p-lg-4 d-flex flex-column gap-3">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <h5 class="text-capitalize">{{translate('My compare list')}}</h5>
                                <div class="d-flex gap-4 flex-wrap">
                                    @if($compareLists->count()>0)
                                        <a href="javascript:"
                                           class="btn-link text-danger text-capitalize delete-action text-capitalize"
                                           data-action="{{ route('product-compare.destroy-all') }}"
                                           data-method="DELETE"
                                           data-text="{{translate('Want to clear all compare list').'?'}}">
                                            {{translate('Clear All')}}
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="h-100">
                                @if($compareLists->count()>0)
                                    <div class="table-responsive">
                                        <table class="table align-middle table-bordered compare--table th-colored" style="min-width:@if($compareLists->count()>2)700px @elseif($compareLists->count()>1) 600px @endif">
                                            <tbody>
                                                <tr>
                                                    <th class="fw-semibold w-25"></th>

                                                    @php($digitalProductExist = $compareLists->contains(fn ($item) => $item?->product?->product_type === 'digital'))
                                                    @foreach ($compareLists as $compareList)
                                                        <td>
                                                            <div class="d-flex flex-column gap-1 align-items-center">
                                                                <div class="d-flex justify-content-end w-100">
                                                                    <a href="javascript:"
                                                                    data-action="{{ route('product-compare.destroy', ['id' => $compareList['id']]) }}"
                                                                    data-method="DELETE"
                                                                    data-text="{{translate('Want to delete this item').'?'}}"
                                                                    class="btn-link text-danger text-decoration-underline delete-action">
                                                                        {{translate('Remove')}}
                                                                    </a>
                                                                </div>
                                                                <img width="120" class="dark-support aspect-1 object-fit-cover rounded" alt=""
                                                                    src="{{ getStorageImages(path: $compareList?->product?->thumbnail_full_url, type: 'product') }}">
                                                            </div>
                                                        </td>
                                                    @endforeach
                                                </tr>
                                                <tr>
                                                    <th class="fw-semibold w-25">{{ translate('Product Name') }}</th>
                                                    @foreach ($compareLists as $compareList)
                                                        <td>
                                                            <a href="{{route('product',$compareList->product['slug'])}}">
                                                                {{ $compareList->product['name'] }}
                                                            </a>
                                                        </td>
                                                    @endforeach
                                                </tr>
                                                <tr>
                                                    <th class="fw-semibold w-25">{{ translate('Price') }}</th>
                                                    @foreach ($compareLists as $compareList)
                                                        <td>{{ webCurrencyConverter($compareList->product['unit_price']) }}</td>
                                                    @endforeach
                                                </tr>
                                                <tr>
                                                    <th class="fw-semibold w-25">{{ translate('Rating') }}</th>
                                                    @foreach ($compareLists as $compareList)
                                                        <td>
                                                            <div class="d-flex">@php($avgRatting = number_format($compareList->product?->reviews?->avg('rating'), 2))
                                                                @include('theme-views.partials._rating-display', [
                                                                    'rating' => $avgRatting,
                                                                    'count' => 0,
                                                                    'size' => 'sm',
                                                                    'showEmpty' => true,
                                                                ])
                                                            </div>
                                                        </td>
                                                    @endforeach
                                                </tr>
                                                <tr>
                                                    <th class="fw-semibold w-25">{{ translate('Brand') }}</th>
                                                    @foreach ($compareLists as $compareList)
                                                        @if ($web_config['brand_setting'])
                                                            @if(isset($compareList->product->brand->image))
                                                                <td>
                                                                    <a href="{{ route('brand-products', ['slug' => $compareList->product->brand['slug']]) }}">
                                                                        <img width="48" class="rounded dark-support" alt=""
                                                                            src="{{ getStorageImages(path: $compareList?->product?->brand->image_full_url, type:'brand') }}">
                                                                    </a>
                                                                </td>
                                                            @else
                                                                <td>
                                                                    {{ 'N/a' }}
                                                                </td>
                                                            @endif
                                                        @endif
                                                    @endforeach
                                                </tr>
                                                <tr>
                                                    <th class="fw-semibold w-25">{{ translate('Category') }}</th>
                                                    @foreach ($compareLists as $compareList)
                                                        <td>
                                                            @if(!empty($compareList?->product?->category?->slug))
                                                                <a href="{{ route('category-products', ['slug' => $compareList?->product?->category?->slug]) }}">
                                                                    {{ $compareList?->product?->category?->name }}
                                                                </a>
                                                            @else
                                                                {{ 'N/a' }}
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                                @if($digitalProductExist)
                                                    <tr>
                                                        <th class="fw-semibold w-25">
                                                            {{ translate('Digital Variation') }}
                                                        </th>
                                                        @foreach ($compareLists as $compareList)
                                                            <td>
                                                                @php($digitalVariation = $compareList?->product?->digitalVariation)
                                                                @if($digitalVariation && count($digitalVariation) > 0)
                                                                    @foreach($digitalVariation as $variantIndex => $variation)
                                                                        @php($variantIndex++)
                                                                        {{ strtoupper($variation['variant_key'] )}}
                                                                        @if($variantIndex < count($digitalVariation)),@endif
                                                                    @endforeach
                                                                @else
                                                                    {{ "N/a" }}
                                                                @endif
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endif

                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                @if($compareLists->count()==0)
                                    <div class="d-flex flex-column justify-content-center align-items-center gap-2 py-3 w-100 bg-light rounded h-100">
                                        <img width="80" class="mb-3 svg" src="{{ theme_asset('assets/img/empty-state/empty-compare-list.svg') }}" alt="">
                                        <h5 class="text-center text-muted px-2">
                                            {{ translate('You have not added any product to compare yet') }}!
                                        </h5>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
