@php use Illuminate\Support\Str; @endphp

@extends('layouts.admin.app')

@section('title', translate('Refund Requests'))

@section('content')
    <div class="content container-fluid">

        <h2 class="h1 text-capitalize d-flex align-items-center gap-2 mb-3">
            <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/refund-request-list.png')}}" alt="">
            {{translate('Refund request list')}}
        </h2>

        <div class="card">
            <div class="p-3">
                <div class="row g-3 justify-content-between align-items-center">
                    <div class="col-12 col-md-4">
                        <div class=" d-flex align-items-center gap-1">
                            <h3 class="mb-0 fs-16">{{ translate('Pending Refund Requests List') }}</h3>
                            <span class="badge badge-soft-dark radius-50">{{ $refundList->total() }}</span>
                        </div>
                    </div>
                     <div class="col-12 col-md-8">
                        <div class="d-flex gap-3 flex-sm-nowrap align-items-center flex-wrap justify-content-md-end">
                            <form action="{{ url()->current() }}" method="GET"
                              class="flex-grow-1 max-w-300 min-w-100-mobile">
                                <div class="input-group">
                                    <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                                        placeholder="{{ translate('Search by order id or refund id') }}"
                                        aria-label="Search orders" value="{{ request('searchValue') }}">
                                    <div class="input-group-append search-submit">
                                        <button type="submit">
                                            <i class="fi fi-rr-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <div class="dropdown d-flex gap-2 align-items-center">
                                <div class="position-relative">
                                    @if(!empty(request('sort_by')) || !empty(request('from_date')) || !empty(request('to_date')) || !empty(request('type')))
                                        <div class="position-absolute inset-inline-end-0 top-0 mt-n1 me-n1 btn-circle bg-danger border border-white border-2" style="--size: 14px;"></div>
                                    @endif
                                    <button type="button"
                                            class="btn btn-primary"
                                            data-bs-toggle="offcanvas"
                                            data-bs-target="#PendingRefundRequestFilter">
                                        <i class="fi fi-sr-settings-sliders d-flex"></i>
                                        {{ translate('Filter') }}
                                    </button>
                                </div>
                                <a class="btn btn-outline-primary"
                                   href="{{ route('admin.retail.refund-section.refund.export', [
                                        'status' => request('status'),
                                        'searchValue' => request('searchValue'),
                                        'from_date' => request('from_date'),
                                        'to_date' => request('to_date'),
                                   ]) }}">
                                    <i class="fi fi-sr-inbox-in"></i>
                                    <span class="fs-12">{{ translate('Export') }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive datatable-custom">
                <table
                    class="table table-hover table-borderless">
                    <thead class="text-capitalize">
                    <tr>
                        <th>{{ translate('Serial') }}</th>
                        <th class="text-center">{{ translate('Refund id') }}</th>
                        <th>{{ translate('Order ID') }} </th>
                        <th>{{ translate('Product Information') }}</th>
                        <th>{{ translate('Customer Information') }}</th>
                        <th class="text-end">{{ translate('Total Amount') }}</th>
                        <th class="text-center">{{ translate('Action') }}</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($refundList as $key => $refund)
                        <tr>
                            <td>{{ $refundList->firstItem()+$key}}</td>
                            <td class="text-center">
                                <a href="{{route('admin.retail.refund-section.refund.details', ['id' => $refund['id']]) }}"
                                   class="text-dark hover-primary">
                                    {{ $refund->id}}
                                </a>
                            </td>
                            <td>
                                <a href="{{route('admin.retail.orders.details',['id'=>$refund->order_id]) }}"
                                   class="text-dark hover-primary">
                                    {{ $refund->order_id }}
                                </a>
                            </td>
                            <td>
                                <div class="{{ is_null($refund->product) ? 'table-row-disabled' : '' }}"
                                     data-bs-toggle="tooltip"
                                     title="{{ is_null($refund->product) ? translate('Product has been deleted') : '' }}">
                                    <div class="d-flex flex-nowrap gap-2">
                                        <div class="d-block w-max-content">
                                            @if(!is_null($refund->product))
                                                <a href="{{route('admin.retail.products.view',['addedBy' => ($refund?->product?->added_by =='seller' ? 'vendor' : 'in-house'),'id' => $refund?->product?->id])}}">
                                                    <img
                                                        src="{{ getStorageImages(path: $refund?->product?->thumbnail_full_url, type: 'backend-product') }}"
                                                        class="avatar border" alt="">
                                                </a>
                                            @else
                                                <img
                                                    src="{{ getStorageImages(path: '', type: 'backend-product') }}"
                                                    class="avatar border" alt="">
                                            @endif
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            @if(!is_null($refund->product))
                                                <a href="{{ route('admin.retail.products.view',['addedBy'=>($refund->product->added_by =='seller'?'vendor' : 'in-house'), 'id'=>$refund->product->id]) }}"
                                                   class="text-dark fw-bold hover-primary">
                                                    {{ Str::limit($refund->product->name, 35) }}
                                                </a>
                                            @else
                                                <span class="text-dark fw-bold">
                                                    {{ Str::limit($orderedProductNames[$refund->id] ?? translate('Not found'), 35) }}
                                                </span>
                                            @endif

                                            <span class="fs-12">
                                                {{ translate('Quantity') }} : {{ $refund?->orderDetails?->qty ?? 1 }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($refund->customer !=null)
                                    <div class="d-flex flex-column gap-1">
                                        <a href="{{route('admin.people.customer.view', [$refund->customer->id]) }}"
                                           class="text-dark fw-bold hover-primary">
                                            {{ $refund->customer->f_name. ' '. $refund->customer->l_name}}
                                        </a>
                                        @if($refund?->customer?->phone)
                                            <a href="tel:{{ $refund->customer->phone}}"
                                               class="text-dark hover-primary fs-12">
                                                {{ $refund->customer->phone}}
                                            </a>
                                        @else
                                            <a href="mailto:{{ $refund->customer['email'] }}"
                                               class="text-dark hover-primary fs-12">
                                                {{ $refund->customer['email'] }}
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <a href="javascript:" class="text-dark hover-primary">
                                        {{ translate('Not found') }}
                                    </a>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1 text-end">
                                    <div>
                                        {{ setCurrencySymbol(
                                            amount: usdToDefaultCurrency(amount: $refund->amount),
                                            currencyCode: getCurrencyCode()
                                        ) }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <a class="btn btn-outline-info icon-btn"
                                       href="{{route('admin.retail.refund-section.refund.details',['id'=>$refund['id']]) }}">
                                       <span data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                                           <i class="fi fi-sr-eye d-flex"></i>
                                       </span>
                                    </a>
                                    @if($refund['status'] != 'refunded')
                                        <button class="btn btn-outline-warning icon-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#refundModal-{{$refund['id']}}">
                                            <span data-bs-toggle="tooltip" data-bs-placement="top" title="Refund">
                                                <i class="fi fi-rr-refresh d-flex"></i>
                                            </span>
                                        </button>
                                        @if($refund['status'] != 'rejected')
                                            <button class="btn btn-outline-danger icon-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rejectModal-{{$refund['id']}}">
                                                <span data-bs-toggle="tooltip" data-bs-placement="top" title="Reject">
                                                    <i class="fi fi-rr-cross fs-12 d-flex"></i>
                                                </span>
                                            </button>
                                        @endif
                                        @if($refund['status'] != 'approved')
                                            <button class="btn btn-outline-success icon-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#approveModal-{{$refund['id']}}">
                                                <span data-bs-toggle="tooltip" data-bs-placement="top" title="Approve">
                                                    <i class="fi fi-sr-check d-flex"></i>
                                                </span>
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>

                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-responsive mt-4">
                <div class="px-4 d-flex justify-content-lg-end">
                    {!! $refundList->links() !!}
                </div>
            </div>

            @if(count($refundList) == 0)
                @include('layouts.admin.partials._empty-state',['text'=>'no_refund_request_found'],['image'=>'default'])
            @endif
        </div>
    </div>

    @include('admin-views.refund.partials._filter-offcanvas')

    @if(count($refundList ?? []) > 0)
        @foreach($refundList as $refund)
            @include('admin-views.refund.partials._approval-modal', ['refund' => $refund, 'walletStatus' => $walletStatus, 'walletAddRefund' => $walletAddRefund])
            @include('admin-views.refund.partials._reject-modal', ['refund' => $refund, 'walletStatus' => $walletStatus, 'walletAddRefund' => $walletAddRefund])
            @include('admin-views.refund.partials._refund-modal', ['refund' => $refund, 'walletStatus' => $walletStatus, 'walletAddRefund' => $walletAddRefund])
        @endforeach
    @endif
@endsection
