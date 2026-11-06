<div class="card remove-card-shadow h-100">
    <div class="card-body p-3 p-sm-4">
        <div class="row g-2 align-items-center">
            <div class="col-md-6">
                <h4 class="d-flex align-items-center text-capitalize gap-10 mb-0">
                    <img src="{{dynamicAsset(path: 'public/assets/back-end/img/order-statistics.png')}}"
                         alt="">
                    {{translate('Earning Statistics')}}
                </h4>
            </div>
            <div class="col-md-6 d-flex justify-content-center justify-content-md-end order-stat mb-3">
                <ul class="option-select-btn earn-statistics-option">
                    <li>
                        <label class="basic-box-shadow">
                            <input type="radio" name="statistics" hidden="" value="yearEarn" {{$dateType == 'yearEarn' ? 'checked' : ''}}>
                            <span data-date-type="yearEarn" class="earn-statistics">{{translate('This Year')}}</span>
                        </label>
                    </li>
                    <li>
                        <label class="basic-box-shadow">
                            <input type="radio" name="statistics"  value="MonthEarn" hidden="" {{$dateType == 'MonthEarn' ? 'checked' : ''}}>
                            <span data-date-type="MonthEarn" class="earn-statistics">{{translate('This Month')}}</span>
                        </label>
                    </li>
                    <li>
                        <label class="basic-box-shadow">
                            <input type="radio" name="statistics" value="WeekEarn" hidden="" {{$dateType == 'WeekEarn' ? 'checked' : ''}}>
                            <span data-date-type="WeekEarn" class="earn-statistics">{{translate('This Week')}}</span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
        <div id="earning-apex-line-chart"></div>
    </div>
</div>
<span id="earn-statistics" data-action="{{ route('vendor.retail.dashboard.earning-statistics') }}"></span>
<span id="earn-statistics-data" data-vendor-text = "{{translate('Income')}}" data-commission-text = "{{translate('Commission Given')}}"  data-vendor-earn="{{json_encode($vendorEarning)}}" data-commission-earn ={{json_encode($commissionEarn)}} data-label="{{json_encode($label)}}"></span>
<input name="earn_statistics_label_count" value="{{count($label)}}" hidden>
