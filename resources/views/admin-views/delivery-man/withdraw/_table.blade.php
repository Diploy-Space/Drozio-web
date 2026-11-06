<div class="table-responsive">
    <table id="datatable"
           class="table table-hover table-borderless table-thead-bordered table-nowrap align-middle card-table w-100">
        <thead class="thead-light thead-50 text-capitalize">
        <tr>
            <th>{{translate('Serial')}}</th>
            <th>{{translate('Info')}}</th>
            <th>{{translate('Amount')}}</th>
            <th>{{translate('Request Time')}}</th>
            <th class="text-center">{{translate('Status')}}</th>
            <th class="text-center">{{translate('Action')}}</th>
        </tr>
        </thead>
        <tbody>
            @foreach($withdrawRequests as $key=>$withdraw)
                <tr>
                <td>{{$withdrawRequests->firstItem()+$key}}</td>
                <td>
                    @if ($withdraw->deliveryMan)
                        <div class="d-flex align-items-center gap-10">
                            <img class="avatar rounded-circle object-fit-cover flex-shrink-0" width="40" height="40" alt=""
                                 src="{{ getStorageImages(path: $withdraw->deliveryMan->image_full_url, type: 'backend-profile') }}">
                            <div class="d-flex flex-column">
                                <span class="title-color text-wrap d-block max-w-400">
                                    {{ $withdraw->deliveryMan->f_name . ' ' . $withdraw->deliveryMan->l_name }}
                                </span>
                                <a class="text-muted text-hover-primary fs-12" href="mailto:{{ $withdraw->deliveryMan->email }}">
                                    {{ $withdraw->deliveryMan->email }}
                                </a>
                                <a class="text-muted text-hover-primary fs-12" href="tel:{{ $withdraw->deliveryMan->country_code . $withdraw->deliveryMan->phone }}">
                                    {{ $withdraw->deliveryMan->country_code . $withdraw->deliveryMan->phone }}
                                </a>
                            </div>
                        </div>
                    @else
                        <span>{{translate('Not found')}}</span>
                    @endif
                </td>
                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $withdraw['amount']), currencyCode: getCurrencyCode())}}</td>
                <td>
                    {{ date_format( $withdraw->created_at, 'd-M-Y,') }}
                    <br />
                    {{ date_format( $withdraw->created_at, 'h:i:s A') }}
                </td>
                <td class="text-center">
                    @if($withdraw->approved==0)
                        <label class="badge badge-info text-bg-info">{{translate('Pending')}}</label>
                    @elseif($withdraw->approved==1)
                        <label class="badge badge-success text-bg-success">{{translate('Approved')}}</label>
                    @else
                        <label class="badge badge-danger text-bg-danger">{{translate('Denied')}}</label>
                    @endif
                </td>
                <td>
                    <div class="d-flex justify-content-center">
                        @if (isset($withdraw->deliveryMan))
                            <button
                                data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithdraw"
                                class="btn btn-outline-info icon-btn withdraw-info-show"
                                data-action="{{route('admin.people.delivery-man.withdraw-view',[$withdraw['id']])}}"
                                title="{{translate('View')}}">
                                <i class="fi fi-rr-eye"></i>
                            </button>
                        @else
                            <a class="btn btn-outline-info icon-btn disabled" href="#">
                                <i class="fi fi-rr-eye"></i>
                            </a>
                        @endif
                    </div>
                </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if(count($withdrawRequests)==0)
    @include('layouts.admin.partials._empty-state',['text'=>'no_withdraw_request_found'],['image'=>'default'])
@endif
