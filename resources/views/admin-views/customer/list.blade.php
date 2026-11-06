@php use Illuminate\Support\Str; @endphp
@extends('layouts.admin.app')

@section('title', translate('Customer List'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-4">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/customer.png')}}" alt="">
                {{ translate('Customer List') }}
                <span class="badge badge-soft-dark radius-50">{{ $totalCustomers }}</span>
            </h2>
        </div>

        @include('admin-views.customer.partials._list-overview')

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center mb-4">
                    <h3 class="mb-0">
                        {{translate('Customer List')}}
                        <span class="badge badge-info text-bg-info">{{ $customers->total() }}</span>
                    </h3>

                    <div class="d-flex flex-wrap gap-3 align-items-center justify-content-start justify-content-md-end min-w-100-mobile">
                        <div class="flex-grow-1 max-w-300 min-w-100-mobile">
                            <form action="{{ url()->current() }}" method="GET">
                                <div class="form-group mb-0">
                                    <div class="input-group">
                                        <input type="hidden" name="order_date" value="{{request('order_date')}}">
                                        <input type="hidden" name="customer_joining_date" value="{{request('customer_joining_date')}}">
                                        <input type="hidden" name="is_active" value="{{request('is_active')}}">
                                        <input type="hidden" name="sort_by" value="{{request('sort_by')}}">
                                        <input type="hidden" name="choose_first" value="{{request('choose_first')}}">
                                        <input type="hidden" name="order_business_module" value="{{request('order_business_module')}}">
                                        <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                                               placeholder="{{ translate('Search by Name or Email or Phone')}}"  aria-label="Search orders" value="{{ request('searchValue') }}">
                                        <div class="input-group-append search-submit">
                                            <button type="submit">
                                                <i class="fi fi-rr-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <a type="button" class="btn btn-outline-primary" href="{{route('admin.people.customer.export', ['sort_by' => request('sort_by'), 'choose_first' => request('choose_first'),'is_active' => request('is_active'), 'order_date' => request('order_date'),'customer_joining_date' => request('customer_joining_date'), 'order_business_module' => request('order_business_module'), 'searchValue' => request('searchValue')])}}">
                            <i class="fi fi-sr-inbox-in"></i>
                            <span class="fs-12">{{ translate('Export') }}</span>
                        </a>

                        <div class="position-relative">
                            @if($hasActiveFilter)
                                <div class="position-absolute inset-inline-end-0 top-0 mt-n1 me-n1 btn-circle bg-danger border border-white border-2"
                                     style="--size: 12px;"></div>
                            @endif
                            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas"
                                    data-bs-target="#offcanvasCustomerListFilter">
                                <i class="fi fi-sr-settings-sliders"></i>
                                {{ translate('Filter') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive datatable-custom">
                    <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                        <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>{{translate('Serial')}}</th>
                                <th>{{translate('Customer Name')}}</th>
                                <th>{{translate('Contact information')}}</th>
                                <th>{{translate('Total Order')}} </th>
                                <th class="text-center">{{translate('Block')}} / {{translate('Unblock')}}</th>
                                <th class="text-center">{{translate('Action')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($customers as $key=>$customer)
                            <tr>
                                <td>
                                    {{$customers->firstItem()+$key}}
                                </td>
                                <td>
                                    <a href="{{route('admin.people.customer.view',[$customer['id']])}}"
                                    class="text-dark text-hover-primary d-flex align-items-center gap-10">
                                        <img src="{{ getStorageImages(path:$customer->image_full_url, type:'backend-profile') }}"
                                            class="avatar rounded-circle object-fit-cover" alt="" width="40">
                                        {{Str::limit($customer['f_name']." ".$customer['l_name'],20)}}
                                    </a>
                                </td>
                                <td>
                                    <div class="mb-1">
                                        <strong><a class="text-dark text-hover-primary"
                                                href="mailto:{{$customer->email}}">{{$customer->email}}</a></strong>

                                    </div>
                                    <a class="text-dark text-hover-primary" href="tel:{{$customer->phone}}">{{$customer->phone}}</a>

                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @foreach($orderCountModules as $module => $moduleLabel)
                                            <div class="d-flex align-items-center gap-2 fs-12">
                                                <span class="text-body min-w-75">{{ $moduleLabel }}</span>
                                                <label class="badge badge-info text-bg-info mb-0">{{ $customer[$module.'_orders_count'] ?? 0 }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    @if($customer['email'] == 'walking@customer.com')
                                        <div class="text-center">
                                            <div class="badge badge-soft-version">{{ translate('Default') }}</div>
                                        </div>
                                    @else
                                        <form action="{{route('admin.people.customer.status-update')}}" method="post"
                                            id="customer-status{{$customer['id']}}-form" class="no-reload-form">
                                            @csrf
                                            <input type="hidden" name="id" value="{{$customer['id']}}">
                                            <label class="switcher mx-auto" for="customer-status{{$customer['id']}}">
                                                <input
                                                    class="switcher_input custom-modal-plugin"
                                                    type="checkbox" value="1" name="is_active"
                                                    id="customer-status{{$customer['id']}}"
                                                    {{ $customer['is_active'] == 1 ? 'checked':'' }}
                                                    data-modal-type="input-change-form"
                                                    data-modal-form="#customer-status{{$customer['id']}}-form"
                                                    data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/customer-block-on.png') }}"
                                                    data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/customer-block-off.png') }}"
                                                    data-on-title = "{{translate('Want to unblock').' '.$customer['f_name'].' '.$customer['l_name'].'?'}}"
                                                    data-off-title = "{{translate('Want to block').' '.$customer['f_name'].' '.$customer['l_name'].'?'}}"
                                                    data-on-message = "<p>{{translate('Unblocks this customer so they can log in again.')}}</p>"
                                                    data-off-message = "<p>{{translate('Blocks this customer from logging in.')}}</p>"
                                                    data-on-button-text="{{ translate('Turn On') }}"
                                                    data-off-button-text="{{ translate('Turn Off') }}">
                                                <span class="switcher_control"></span>
                                            </label>
                                        </form>
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a title="{{translate('View')}}"
                                        class="btn btn-outline-info icon-btn"
                                        href="{{route('admin.people.customer.view',[$customer['id']])}}">
                                        <i class="fi fi-rr-eye"></i>
                                        </a>
                                        @if($customer['id'] != '0')
                                            <a title="{{translate('Delete')}}"
                                            class="btn btn-outline-danger delete icon-btn delete-data" href="javascript:"
                                            data-id="customer-{{$customer['id']}}">
                                            <i class="fi fi-rr-trash"></i>
                                            </a>
                                        @endif
                                    </div>
                                    <form action="{{route('admin.people.customer.delete',[$customer['id']])}}"
                                        method="post" id="customer-{{$customer['id']}}">
                                        @csrf @method('delete')
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <div class="px-4 d-flex justify-content-center justify-content-md-end">
                        {!! $customers->links() !!}
                    </div>
                </div>
                @if(count($customers)==0)
                    @include('layouts.admin.partials._empty-state',['text'=>'no_customer_found'],['image'=>'default'])
                @endif
            </div>
    </div>
    </div>

    @include('admin-views.customer.partials.offcanvas._list-filter-offcanvas')
@endsection

