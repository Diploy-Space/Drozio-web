@php use Illuminate\Support\Str; @endphp
@extends('layouts.admin.app')

@section('title', translate('Vendor List'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/add-new-seller.png') }}" alt="">
                {{translate('Vendor List')}}
                <span class="badge badge-info text-bg-info">{{ $vendors->total() }}</span>
            </h2>
            @include('admin-views.vendor.partials._verification-recommendation')
        </div>

        <div class="card">
            <div class="px-3 py-4">
                <div class="d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                    <div class="flex-grow-1 max-w-300 min-w-100-mobile">
                        <form action="{{ url()->current() }}" method="GET">
                            <div class="input-group">
                                <input type="hidden" name="sortBy" value="{{ request('sortBy') }}">
                                <input type="hidden" name="product_business_module" value="{{ request('product_business_module') }}">
                                <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                                       placeholder="{{translate('Search by shop name or vendor name or phone or email')}}" aria-label="Search orders" value="{{ request('searchValue') }}">
                                <div class="input-group-append search-submit">
                                    <button type="submit">
                                        <i class="fi fi-rr-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="d-flex flex-wrap justify-content-start justify-content-md-end align-items-center gap-3 min-w-100-mobile">
                        <a type="button" class="btn btn-outline-primary" href="{{route('admin.people.vendors.export',['searchValue' => request('searchValue'), 'sortBy' => request('sortBy'), 'product_business_module' => request('product_business_module')])}}">
                            <i class="fi fi-sr-inbox-in"></i>
                            <span class="fs-12">{{ translate('Export') }}</span>
                        </a>

                        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-sm-end flex-grow-1">
                            <div class="dropdown">
                                <div class="position-relative">
                                    @if(!empty(request('product_business_module')))
                                        <div class="position-absolute inset-inline-end-0 top-0 mt-n1 me-n1 btn-circle bg-danger border border-white border-2" style="--size: 14px;"></div>
                                    @endif
                                </div>
                                <button type="button" class="btn {{ !empty(request('product_business_module')) ? 'btn-primary' : 'btn-outline-primary' }}" data-bs-toggle="dropdown">
                                    <i class="fi fi-sr-settings-sliders"></i>
                                    <span class="fs-12">{{ translate('Business Module') }}</span>
                                    <i class="fi fi-rr-angle-small-down"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2"
                                           href="{{ route('admin.people.vendors.vendor-list', ['sortBy' => request('sortBy'), 'searchValue' => request('searchValue')]) }}">
                                            <span class="text-success pt-1">
                                                <input type="radio" name="product_business_module" {{ empty(request('product_business_module')) ? 'checked' : '' }}>
                                            </span>
                                            {{ translate('All') }}
                                        </a>
                                    </li>
                                    @foreach($productBusinessModules as $businessModuleKey => $businessModuleLabel)
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2"
                                               href="{{ route('admin.people.vendors.vendor-list', ['sortBy' => request('sortBy'), 'searchValue' => request('searchValue'), 'product_business_module' => $businessModuleKey]) }}">
                                                <span class="text-success pt-1">
                                                    <input type="radio" name="product_business_module" {{ request('product_business_module') === $businessModuleKey ? 'checked' : '' }}>
                                                </span>
                                                {{ $businessModuleLabel }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="dropdown">
                                <div class="position-relative">
                                    @if(!empty(request('sortBy')))
                                        <div class="position-absolute inset-inline-end-0 top-0 mt-n1 me-n1 btn-circle bg-danger border border-white border-2" style="--size: 14px;"></div>
                                    @endif
                                </div>
                                <button type="button" class="btn {{ !empty(request('sortBy')) ? 'btn-primary' : 'btn-outline-primary' }}" data-bs-toggle="dropdown">
                                    <i class="fi fi-sr-sort-alt"></i>
                                    <span class="fs-12">{{ translate('Sorting') }}</span>
                                    <i class="fi fi-rr-angle-small-down"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2"
                                           href="{{ route('admin.people.vendors.vendor-list', ['sortBy' => 'latest', 'searchValue' => request('searchValue'), 'product_business_module' => request('product_business_module')]) }}">
                                            <span class="text-success pt-1">
                                                <input type="radio" name="sortBy" {{ empty(request('sortBy')) || request('sortBy') == 'latest' ? 'checked' : '' }}>
                                            </span>
                                            {{ translate('Default') }} ({{ translate('Recent Created') }})
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2"
                                           href="{{ route('admin.people.vendors.vendor-list', ['sortBy' => 'oldest', 'searchValue' => request('searchValue'), 'product_business_module' => request('product_business_module')]) }}">
                                            <span class="text-success pt-1">
                                                <input type="radio" name="sortBy" {{ request('sortBy') == 'oldest' ? 'checked' : '' }}>
                                            </span>
                                            {{ translate('Show Older First') }}
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2"
                                           href="{{ route('admin.people.vendors.vendor-list', ['sortBy' => 'most-favorite', 'searchValue' => request('searchValue'), 'product_business_module' => request('product_business_module')]) }}">
                                            <span class="text-info pt-1">
                                                <input type="radio" name="sortBy" {{ request('sortBy') == 'most-favorite' ? 'checked' : '' }}>
                                            </span>
                                            {{ translate('Most Popular Store') }}
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2"
                                           href="{{ route('admin.people.vendors.vendor-list', ['sortBy' => 'total-earning', 'searchValue' => request('searchValue'), 'product_business_module' => request('product_business_module')]) }}">
                                            <span class="text-info pt-1">
                                                <input type="radio" name="sortBy" {{ request('sortBy') == 'total-earning' ? 'checked' : '' }}>
                                            </span>
                                            {{ translate('Top Selling Store') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <a href="{{route('admin.people.vendors.add')}}" type="button" class="btn btn-primary text-nowrap">
                            <i class="fi fi-rr-plus-small"></i>
                            {{translate('Add New Vendor')}}
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                        <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>{{translate('Serial')}}</th>
                                <th>{{translate('Shop Name')}}</th>
                                <th>{{translate('Vendor Info')}}</th>
                                <th>{{translate('Status')}}</th>
                                <th class="text-center">{{translate('Total Products')}}</th>
                                <th class="text-center">{{translate('Total Orders')}}</th>
                                <th class="text-center">{{translate('Action')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($vendors as $key=>$seller)
                            <tr>
                                <td>{{$vendors->firstItem()+$key}}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-10 w-max-content">
                                        <img width="50"
                                        class="avatar rounded-circle object-fit-cover" src="{{ getStorageImages(path: $seller?->shop?->image_full_url, type: 'backend-basic') }}"
                                            alt="">
                                        <div>
                                            <div class="d-flex align-items-center gap-1">
                                                <a class="text-dark text-hover-primary" href="{{ route('admin.people.vendors.view', ['id' => $seller->id]) }}">{{ $seller->shop ? Str::limit($seller->shop->name, 20) : translate('Not found')}}</a>
                                                @include('admin-views.vendor.partials._verified-badge', ['isVerified' => $seller->hasVerifiedBadge(), 'size' => 16])
                                            </div>
                                            <span class="text-danger fs-12">
                                                @if(isset($temporaryClosures[$seller->id]))
                                                    {{ translate('Temporary closed') }}@unless($temporaryClosures[$seller->id]['covers_all_businesses']) ({{ $temporaryClosures[$seller->id]['businesses'] }})@endunless
                                                @elseif(isset($vacationSchedules[$seller->id]))
                                                    {{ translate('On Vacation') }}@unless($vacationSchedules[$seller->id]['covers_all_businesses']) ({{ $vacationSchedules[$seller->id]['businesses'] }})@endunless
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1 w-max-content">
                                        <a title="{{ translate('View') }}"
                                           class="text-dark text-hover-primary fw-semibold"
                                           href="{{ route('admin.people.vendors.view', $seller->id) }}">
                                            {{ $seller->f_name }} {{ $seller->l_name }}
                                        </a>
                                        @if($seller->email)
                                            <a class="text-dark text-hover-primary fs-12 d-flex align-items-center gap-2"
                                               href="mailto:{{ $seller->email }}">
                                                <i class="fi fi-rr-envelope"></i>
                                                {{ $seller->email }}
                                            </a>
                                        @endif
                                        @if($seller->phone)
                                            <a class="text-dark text-hover-primary fs-12 d-flex align-items-center gap-2"
                                               href="tel:{{ $seller->phone }}">
                                                <i class="fi fi-rr-phone-flip"></i>
                                                {{ $seller->phone }}
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    {!! $seller->status=='approved'?'<label class="badge badge-success text-bg-success">'.translate('Active').'</label>':'<label class="badge badge-danger text-bg-danger">'.translate('Inactive').'</label>' !!}
                                </td>
                                <td class="text-center">
                                    @include('admin-views.vendor.partials._module-count-list', [
                                        'modules' => $productCountModules,
                                        'sellerId' => $seller->id,
                                    ])
                                </td>
                                <td class="text-center">
                                    @include('admin-views.vendor.partials._module-count-list', [
                                        'modules' => $orderCountModules,
                                        'sellerId' => $seller->id,
                                    ])
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a title="{{translate('View')}}"
                                            class="btn btn-outline-info icon-btn"
                                            href="{{route('admin.people.vendors.view',$seller->id)}}">
                                            <i class="fi fi-rr-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <div class="px-4 d-flex justify-content-center justify-content-md-end">
                        {!! $vendors->links() !!}
                    </div>
                </div>
                @if(count($vendors)==0)
                    @include('layouts.admin.partials._empty-state',['text'=>'no_vendor_found'],['image'=>'default'])
                @endif
            </div>
        </div>
    </div>
@endsection

@push('script')
    @if ($isVerifiedBadgeEnabled)
        <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/vendor-verification.js') }}"></script>
    @endif
@endpush
