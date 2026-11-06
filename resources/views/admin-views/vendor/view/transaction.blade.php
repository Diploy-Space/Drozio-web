@extends('layouts.admin.app')

@section('title',$seller?->shop->name ?? translate('Not found'))

@section('content')
    <div class="content container-fluid">
        
        <div class="flex-between d-sm-flex row align-items-center justify-content-between mb-2 mx-1">
            <div>
                @if ($seller['status']=="pending")
                    <div class="mt-4 pr-2">
                        <div class="flex-start">
                            <div class="mx-1"><h4><i class="fi fi-rr-shop"></i></h4></div>
                            <div>{{ translate('Vendor request to open a shop') }}</div>
                        </div>
                        <div class="text-center">
                            <form class="d-inline-block" action="{{route('admin.people.vendors.update-status')}}" method="POST">
                                @csrf
                                <input type="hidden" name="id" value="{{$seller['id']}}">
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn btn-primary btn-sm">{{translate('Approve')}}</button>
                            </form>
                            <form class="d-inline-block" action="{{route('admin.people.vendors.update-status')}}" method="POST">
                                @csrf
                                <input type="hidden" name="id" value="{{$seller['id']}}">
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn btn-danger btn-sm">{{translate('Reject')}}</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @include('admin-views.vendor.partials._detail-header')
        <div class="card mb-3">
            <div class="card-body">
                <h3 class="mb-3">{{translate('Filter Data')}}</h3>
                <form action="{{ url()->current() }}" method="GET">
                    <div class="row  gx-2 gy-3 align-items-center">
                        <div class="col-sm-6 col-md-3">
                            <div class="">
                                <label class="mb-2">{{translate('Select status')}}</label>
                                <div class="select-wrapper flex-grow-1">
                                    <select class="form-select" name="status">
                                        <option value="0" selected disabled>{{'---'.translate('Select status').'---'}}</option>
                                        <option class="text-capitalize"
                                                value="all" {{ request('status') == 'all'? 'selected' : '' }} >{{translate('All')}} </option>
                                        <option class="text-capitalize"
                                                value="disburse" {{ request('status') == 'disburse'? 'selected' : '' }} >{{translate('Disburse')}} </option>
                                        <option class="text-capitalize"
                                                value="hold" {{ request('status') == 'hold'? 'selected' : '' }}>{{translate('Hold')}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="">
                                <label class="mb-2">{{translate('Select Customer')}}</label>
                                <select class="custom-select" name="customer_id">
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer['id'] }}"
                                            {{ (request('customer_id', 'all') === (string) $customer['id']) ? 'selected' : '' }}>
                                            {{ $customer['text'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <label class="mb-2">{{translate('Select date')}}</label>
                            <div class="select-wrapper">
                                <select class="form-select" name="date_type" id="date_type">
                                    <option value="this_year">{{translate('This Year')}}</option>
                                    <option value="this_month">{{translate('This Month')}}</option>
                                    <option value="this_week">{{translate('This Week')}}</option>
                                    <option value="today">{{translate('Today')}}</option>
                                    <option value="custom_date">{{translate('Custom Date')}}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3" id="from_div">
                            <div>
                                <label class="mb-2">{{translate('Start Date')}}</label>
                                <input type="date" name="from" value="" id="from_date"
                                       class="form-control __form-control">
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3" id="to_div">
                            <div class="">
                                <label class="mb-2">{{translate('End Date')}}</label>
                                <input type="date" value="" name="to" id="to_date"
                                       class="form-control __form-control">
                            </div>
                        </div>
                        <div class="col-md-12 d-flex justify-content-end gap-2 pt-0">
                            <button type="submit" class="btn btn-primary px-4 min-w-120 __h-45px"
                                    id="formUrlChange"
                                    data-action="{{ url()->current() }}">
                                {{translate('Filter')}}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center mb-4">
                    <h3 class="mb-0 text-capitalize d-flex gap-1 align-items-center">{{ translate('Transaction table')}}
                        <span class="badge badge-info text-bg-info">{{$transactions->total()}}</span>
                    </h3>
                    <form action="{{ url()->current() }}" method="GET" class="flex-grow-1 max-w-360 max-w-100-mobile">
                        <div class="input-group">
                            <input id="datatableSearch_" type="search" name="searchValue"
                                   class="form-control"
                                   placeholder="{{translate('Search by orders id or transaction id')}}"
                                   aria-label="Search orders" value="{{ request('searchValue') }}">
                            <div class="input-group-append search-submit">
                                <button type="submit">
                                    <i class="fi fi-rr-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table id="datatable"
                           style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};"
                           class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{translate('Serial')}}</th>
                            <th>{{translate('Vendor Name')}}</th>
                            <th>{{translate('Customer name')}}</th>
                            <th>{{translate('Order id')}}</th>
                            <th>{{translate('Transaction id')}}</th>
                            <th>{{translate('Order amount')}}</th>
                            <th>{{translate('Vendor amount') }}</th>
                            <th>{{translate('Admin commission')}}</th>
                            <th>{{translate('Received by')}}</th>
                            <th>{{translate('Delivered by')}}</th>
                            <th>{{translate('Delivery charge')}}</th>
                            <th>{{translate('Payment method')}}</th>
                            <th>{{translate('Tax')}}</th>
                            <th class="text-center">{{translate('Status')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php($companyName = getInHouseShopConfig(key:'name'))
                        @foreach($transactions as $key=>$transaction)
                            <tr>
                                <td>{{$transactions->firstItem()+$key}}</td>
                                <td>
                                    @if($transaction['seller_is'] == 'admin')
                                        {{ $companyName }}
                                    @else
                                        {{ $transaction?->seller->f_name .' '.$transaction?->seller->l_name }}
                                    @endif
                                </td>
                                <td>
                                    {{ $transaction->order->is_guest ? translate('Guest customer'):($transaction->order->customer ? $transaction->order->customer->f_name.' '.$transaction->order->customer->l_name : translate('Not found')) }}
                                </td>
                                <td>{{$transaction['order_id']}}</td>
                                <td>{{$transaction['transaction_id']}}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['order_amount']))}}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['seller_amount']))}}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['admin_commission']))}}</td>
                                <td>{{$transaction['received_by']}}</td>
                                <td>{{$transaction['delivered_by']}}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['delivery_charge']))}}</td>
                                <td>{{str_replace('_',' ',$transaction['payment_method'])}}</td>
                                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['tax']))}}</td>
                                <td class="text-center">
                                    @if($transaction['status'] == 'disburse')
                                        <span class="badge badge-success text-bg-success">
                                            {{translate($transaction['status'])}}
                                        </span>
                                    @else
                                        <span class="badge badge-warning text-bg-warning">
                                            {{translate($transaction['status'])}}
                                        </span>
                                    @endif

                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="table-responsive mt-4">
                    <div class="px-4 d-flex justify-content-lg-end">
                        {{$transactions->links()}}
                    </div>
                </div>
                @if(count($transactions)==0)
                    @include('layouts.admin.partials._empty-state',['text'=>'no_data_found'],['image'=>'default'])
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
    <script>
        "use strict";

        $('#from_date,#to_date').change(function () {
            let from_date = $('#from_date').val();
            let to_date = $('#to_date').val();
            if (from_date != '') {
                $('#to_date').attr('required', 'required');
            }
            if (to_date != '') {
                $('#from_date').attr('required', 'required');
            }
            if (from_date != '' && to_date != '') {
                if (from_date > to_date) {
                    $('#from_date').val('');
                    $('#to_date').val('');
                    toastMagic.error('Invalid date range!');
                }
            }

        })

        $("#date_type").change(function () {
            let val = $(this).val();
            $('#from_div').toggle(val === 'custom_date');
            $('#to_div').toggle(val === 'custom_date');

            if (val === 'custom_date') {
                $('#from_date').attr('required', 'required');
                $('#to_date').attr('required', 'required');
            } else {
                $('#from_date').val(null).removeAttr('required')
                $('#to_date').val(null).removeAttr('required')
            }
        }).change();
    </script>
@endpush
