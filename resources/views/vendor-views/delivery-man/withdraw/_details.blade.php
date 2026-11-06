<div class="withdraw-info-sidebar-overlay"></div>
<div class="withdraw-info-sidebar d-flex justify-content-between flex-column">
    <div class="withdraw-details">
        <div class="d-flex pb-3">
            <span class="circle bg-light withdraw-info-hide cursor-pointer">
                <i class="tio-clear"></i>
            </span>
        </div>
        <div class="d-flex flex-column align-items-center gap-1 mb-3">
            <h3 class="mb-3 text-capitalize">{{translate('Withdraw information')}}</h3>
            <div class="d-flex gap-2 align-items-center mb-1 flex-wrap">
                <span class="text-capitalize">{{translate('Withdraw Amount').' : '}}</span>
                <span class="font-semibold">{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $details['amount']), currencyCode: getCurrencyCode())}}</span>
                <label class="badge {{$details['approved'] == 1 ? 'badge-success-2' : ($details['approved'] == 0 ? 'badge--primary-2':'badge--danger-2')}} mb-0">{{translate($details['approved'] == 1 ? 'approved' : ($details['approved'] == 0 ? 'pending':'denied'))}}</label>
            </div>
            <div class="d-flex gap-2 align-items-center fs-12">
                <span class="text-capitalize">{{translate('Request Time').' : '}}</span>
                <span>{{ date_format( $details['created_at'], 'd-M-Y, h:i:s A') }}</span>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0 font-medium font-weight-bold">{{translate('Bank information')}}</h6>
            </div>
            <div class="card-body">
                <div class="key-val-list d-flex flex-column gap-2 min-width--60px">
                    <div class="key-val-list-item d-flex gap-3">
                        <span class="text-capitalize">{{translate('Bank Name')}}</span>:
                        <span>{{$details?->deliveryMan?->bank_name ?? translate('No Data Found')}}</span>
                    </div>
                    <div class="key-val-list-item d-flex gap-3">
                        <span>{{translate('Branch')}}</span>:
                        <span>{{$details?->deliveryMan?->branch ?? translate('No Data Found') }}</span>
                    </div>
                    <div class="key-val-list-item d-flex gap-3">
                        <span class="text-capitalize">{{translate('Holder Name')}} </span>:
                        <span>{{$details?->deliveryMan?->holder_name ?? translate('No Data Found') }}</span>
                    </div>
                    <div class="key-val-list-item d-flex gap-3">
                        <span class="text-capitalize">{{translate('Account No')}}</span>:
                        <span>{{$details?->deliveryMan?->account_no ??  translate('No Data Found') }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0 font-medium text-capitalize font-weight-bold">{{translate('Deliveryman information')}}</h6>
            </div>
            <div class="card-body">
                <div class="key-val-list d-flex flex-column gap-2" style="--min-width: 60px">
                    <div class="key-val-list-item d-flex gap-3">
                        <span>{{translate('Name')}}</span>:
                        <span>{{$details?->deliveryMan?->f_name}} {{$details?->deliveryMan?->l_name}}</span>
                    </div>
                    <div class="key-val-list-item d-flex gap-3">
                        <span>{{translate('Email')}}</span>:
                        <a href="mailto:{{$details?->deliveryMan?->email}}" class="text-dark">{{$details?->deliveryMan?->email}}</a>
                    </div>
                    <div class="key-val-list-item d-flex gap-3">
                        <span>{{translate('Phone')}}</span>:
                        <a href="tel:{{$details?->deliveryMan?->phone}}" class="text-dark">{{$details?->deliveryMan?->phone}}</a>
                    </div>
                </div>
            </div>
        </div>
        @if($details['approved'] != 0)
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0 font-medium text-capitalize font-weight-bold">{{translate(($details['approved'] ==1 ? 'approved' : 'denied').'_'.'Note')}}</h6>
                </div>
                <div class="card-body">
                    <div class="key-val-list d-flex flex-column gap-2 min-width--60px">
                        <div class="key-val-list-item d-flex gap-3">
                            <span>{{$details['transaction_note']}}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    <div class="d-none note-section">
        <div class="d-flex pb-3">
            <span class="circle bg-light withdraw-info-hide cursor-pointer">
                <i class="tio-clear"></i>
            </span>
        </div>
        <form action="{{route('vendor.delivery-man.withdraw.update-status',[$details['id']])}}" method="POST" id="approval-note-form">
            @csrf
            <input name="approved" value="1" hidden="">
            <div class="mt-5 d-none note-area" id="approval-note" >
                <h4 class="font-semibold text-center mb-3 text-capitalize">{{ translate('Approval Note') }}</h4>
                <textarea name="note" class="form-control" rows="6" placeholder="{{translate('Type a note about request approval').'.'}}"></textarea>
                <div class="d-flex gap-2 pt-3 justify-content-center">
                    <button type="button" class="btn btn-soft-secondary min-w-120 back-to-details mx-2">
                        <i class="fi fi-rr-arrow-left me-1"></i> {{translate('Back')}}
                    </button>
                    <button type="button" class="btn btn-primary min-w-120 form-submit" data-form-id="approval-note-form" data-message="{{translate('Want to approve this withdraw request').'?'}}">
                        <i class="fi fi-rr-paper-plane me-1"></i> {{translate('Submit')}}
                    </button>
                </div>
            </div>
        </form>
        <form action="{{route('vendor.delivery-man.withdraw.update-status',[$details['id']])}}" method="POST" id="denial-note-form">
            @csrf
            <input name="approved" value="2" hidden="">
            <div class="mt-5 d-none note-area" id="denial-note">
                <h4 class="font-semibold text-center mb-3 text-capitalize">{{translate('Denial Note')}}</h4>
                <textarea name="note" id="" class="form-control" rows="6" placeholder="{{translate('Type a note about request denial').'.'}}"></textarea>

                <div class="d-flex gap-2 pt-3 justify-content-center">
                    <button type="button" class="btn btn-soft-secondary min-w-120 back-to-details mx-2">
                        <i class="fi fi-rr-arrow-left me-1"></i> {{translate('Back')}}
                    </button>
                    <button type="button" class="btn btn-primary min-w-120 form-submit" data-form-id="denial-note-form" data-message="{{translate('Want to deny this withdraw request').'?'}}">
                        <i class="fi fi-rr-paper-plane me-1"></i> {{translate('Submit')}}
                    </button>
                </div>
            </div>
        </form>
    </div>
    <div class="mt-4 d-flex justify-content-center gap-3">
        <div class="withdraw-details">
            @if ($details['approved'] == 0)
                <button type="button" class="btn btn-soft-danger min-w-120 open-note mx-2" data-id="denial-note">
                    <i class="fi fi-rr-cross-small me-1"></i> {{ translate('Deny') }}
                </button>
                <button type="button" class="btn btn-success min-w-120 open-note" data-id="approval-note">
                    <i class="fi fi-rr-check me-1"></i> {{ translate('Approve') }}
                </button>
            @endif
        </div>
    </div>
</div>
