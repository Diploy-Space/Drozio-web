<div class="table-responsive">
    <table id="datatable"
        class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
        <thead class="thead-light thead-50 text-capitalize">
            <tr>
                <th>{{ translate('Serial') }}</th>
                <th>{{ translate('Amount') }}</th>
                <th>{{ translate('Request Time') }}</th>
                <th>{{ translate('Transaction Note') }}</th>
                <th>{{ translate('Status') }}</th>
                <th class="text-center">{{ translate('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @if ($withdrawRequests->count() > 0)
                @foreach ($withdrawRequests as $key => $withdrawRequest)
                    <tr>
                        <td>{{ $withdrawRequests->firstitem() + $key }}</td>
                        <td>
                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $withdrawRequest['amount']), currencyCode: getCurrencyCode(type: 'default')) }}
                        </td>
                        <td>
                            {{ date('d M Y', strtotime($withdrawRequest->created_at)) }}
                            <br>
                            {{ date('h:i a', strtotime($withdrawRequest->created_at)) }}
                        </td>
                        <td>
                            <p class="max-w-400 text-wrap">{{ $withdrawRequest?->transaction_note ?? 'N/a' }}</p>
                        </td>
                        <td>
                            @if ($withdrawRequest->approved == 0)
                                <label class="badge badge-soft--primary border">{{ translate('Pending') }}</label>
                            @elseif($withdrawRequest->approved == 1)
                                <label class="badge badge-soft-success border">{{ translate('Approved') }}</label>
                            @else
                                <label class="badge badge-soft-danger border">{{ translate('Denied') }}</label>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-3">
                                <button type="button" data-target="#withdrawInfoViewOffcanvas{{ $withdrawRequest['id'] }}" data-toggle="offcanvas"
                                        class="btn btn-outline-success icon-btn" title="{{translate('View')}}">
                                    <i class="fi fi-sr-eye d-flex"></i>
                                </button>
                                @if ($withdrawRequest->approved == 0)
                                    <button data-alert-title="{{ translate('You want to delete this withdrawal request') }}?"
                                    data-alert-text="{{ translate('A deleted withdrawal request cannot be recovered.')}}." data-toggle="tooltip" title="{{ translate('Remove Withdraw Request') }}"
                                        class="btn btn-outline-danger icon-btn delete show-delete-data-alert"
                                        data-id="vendor-withdraw-delete-form-{{ $withdrawRequest['id'] }}">
                                        <i class="fi fi-rr-trash"></i>
                                    </button>
                                @endif
                            </div>
                            <form id="vendor-withdraw-delete-form-{{ $withdrawRequest['id'] }}" method="GET" style="display: none;"
                             action="{{ route('vendor.business-settings.withdraw.close', [$withdrawRequest['id']]) }}">
                                @csrf
                                @method('GET')
                            </form>
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</div>

@if ($withdrawRequests->total() <= 0)
    <div class="py-5">
        <div class="text-center">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/not_data.svg') }}" alt="">
            <p class="text-muted mt-3">{{ translate('No Request Done Yet') . '!' }}</p>
        </div>
    </div>
@endif

<div class="table-responsive mt-4">
    <div class="px-4 d-flex justify-content-lg-end">
        {{ $withdrawRequests->links() }}
    </div>
</div>
