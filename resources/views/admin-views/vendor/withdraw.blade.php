@extends('layouts.admin.app')

@section('title', translate('Withdraw Request'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/withdraw-icon.png')}}" alt="">
                {{translate('Withdraw')}}
            </h2>
        </div>

        @include('admin-views.vendor.partials._withdraw-inline-menu')

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <h3 class="text-capitalize mb-0">
                        {{ translate('Withdraw Request Table')}}
                        <span class="badge badge-info text-bg-info">{{ $withdrawRequests->total() }}</span>
                    </h3>
                    <div class="d-flex flex-wrap justify-content-start justify-content-md-end align-items-center gap-3 min-w-100-mobile">
                        <div class="flex-grow-1 max-w-300 min-w-100-mobile">
                            <form action="{{ url()->current() }}" method="GET">
                                <div class="form-group mb-0">
                                    <div class="input-group">
                                        <input id="datatableSearch_"
                                               type="search"
                                               name="searchValue"
                                               class="form-control"
                                               placeholder="{{ translate('Search by Name or Shop') }}"
                                               aria-label="Search"
                                               value="{{ request('searchValue') }}">

                                        <div class="input-group-append search-submit">
                                            <button type="submit" class="btn btn-outline-secondary">
                                                <i class="fi fi-rr-search"></i>
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="select-wrapper">
                            <select name="withdraw_status_filter" data-action="{{url()->current()}}" class="form-select min-w-120 withdraw-status-filter">
                                <option value="all" {{request('approved') == 'all' ? 'selected' : ''}}>{{translate('All')}}</option>
                                <option value="approved" {{request('approved') == 'approved' ? 'selected' : ''}}>{{translate('Approved')}}</option>
                                <option value="denied" {{request('approved') == 'denied' ? 'selected' : ''}}>{{translate('Denied')}}</option>
                                <option value="pending" {{request('approved') == 'pending' ? 'selected' : ''}}>{{translate('Pending')}}</option>
                            </select>
                        </div>

                        <a type="button" class="btn btn-outline-primary" href="{{ route('admin.people.vendors.withdraw-list-export-excel', [
                                'searchValue' => request('searchValue') ?? '',
                                'approved' => request('approved') ?? 'all',
                           ]) }}">
                            <i class="fi fi-sr-inbox-in"></i>
                            <span class="fs-12">{{ translate('Export') }}</span>
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="datatable" class="table table-hover table-borderless table-nowrap align-middle text-dark">
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{translate('Serial')}}</th>
                            <th>{{translate('Amount')}}</th>
                            <th>{{ translate('Name') }}</th>
                            <th>{{ translate('Shop') }}</th>
                            <th>{{translate('Request Time')}}</th>
                            <th class="text-center">{{translate('Status')}}</th>
                            <th class="text-center">{{translate('Action')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($withdrawRequests as $key => $withdrawRequest)
                            <tr>
                                <td>{{$withdrawRequests->firstItem() + $key }}</td>
                                <td>
                                    {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $withdrawRequest['amount']), currencyCode: getCurrencyCode(type: 'default')) }}
                                </td>

                                <td>
                                    @if (isset($withdrawRequest->seller))
                                        <a href="{{route('admin.people.vendors.view', $withdrawRequest->seller_id)}}" class="text-dark text-hover-primary">{{ $withdrawRequest->seller->f_name . ' ' . $withdrawRequest->seller->l_name }}</a>
                                    @else
                                        <span class="text-muted">{{translate('Not found')}}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($withdrawRequest?->seller?->shop)
                                        <div class="d-flex align-items-center gap-1">
                                            <span>{{ $withdrawRequest->seller->shop->name }}</span>
                                            @include('admin-views.vendor.partials._verified-badge', ['isVerified' => $withdrawRequest->seller->hasVerifiedBadge(), 'size' => 14])
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{$withdrawRequest->created_at}}</td>
                                <td class="text-center">
                                    @if($withdrawRequest->approved == 0)
                                        <label class="badge badge-info text-bg-info">{{translate('Pending')}}</label>
                                    @elseif($withdrawRequest->approved == 1)
                                        <label class="badge badge-success text-bg-success">{{translate('Approved')}}</label>
                                    @elseif($withdrawRequest->approved == 2)
                                        <label class="badge badge-danger text-bg-danger">{{translate('Denied')}}</label>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-3">
                                        @if (isset($withdrawRequest->seller))
                                            <a href="{{route('admin.people.vendors.withdraw-view', ['withdrawId' => $withdrawRequest['id'], 'vendorId'=>$withdrawRequest->seller['id']])}}"
                                               class="btn btn-outline-info icon-btn" title="{{translate('Details')}}">
                                                <i class="fi fi-rr-eye"></i>
                                            </a>
                                        @else
                                            <a href="javascript:">
                                                {{translate('Action disabled')}}
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 d-flex justify-content-center justify-content-md-end">
                    {{ $withdrawRequests->links() }}
                </div>
            @if(count($withdrawRequests) == 0)
                    @include('layouts.admin.partials._empty-state',['text'=>'no_withdraw_request_found'],['image'=>'default'])
                @endif
            </div>
        </div>
    </div>
@endsection
