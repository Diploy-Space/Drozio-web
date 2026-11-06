<div class="__flash-deals-bg rounded mt-4" style="background: url({{$deal_banner}}) no-repeat center center / cover">
    <div class="row g-3 justify-content-end align-items-center">
        <div class="col-lg-8 col-md-6 text-primary text-center {{Session::get('direction') === "rtl" ? 'text-md-right' : 'text-md-left'}}">
            <div class="flash_deal_title text-primary text-shadow fs-30 mb-1">
                {{ $deal->title }}
            </div>
            <span class="fs-16 fw-semibold text-shadow">{{translate('Hurry Up')}} ! {{translate('The offer is limited')}}. {{translate('Grab while it lasts')}}</span>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="countdown-card bg-transparent __inline-59-custom">
                <div class="text-center text-white">
                    <div class="countdown-background rounded p-2">
                         <span class="cz-countdown d-flex justify-content-center align-items-center pb-4"
                               data-countdown="{{ $deal->end_at }}">
                             <span class="cz-countdown-days">
                                 <span class="cz-countdown-value"></span>
                                 <span class="cz-countdown-text text-nowrap">{{ translate('Days')}}</span>
                             </span>
                             <span class="cz-countdown-value p-1">:</span>
                             <span class="cz-countdown-hours">
                                 <span class="cz-countdown-value"></span>
                                 <span class="cz-countdown-text text-nowrap">{{ translate('Hours')}}</span>
                             </span>
                             <span class="cz-countdown-value p-1">:</span>
                             <span class="cz-countdown-minutes">
                                 <span class="cz-countdown-value"></span>
                                 <span class="cz-countdown-text text-nowrap">{{ translate('Minutes')}}</span>
                             </span>
                             <span class="cz-countdown-value p-1">:</span>
                             <span class="cz-countdown-seconds">
                                 <span class="cz-countdown-value"></span>
                                 <span class="cz-countdown-text text-nowrap">{{ translate('Seconds')}}</span>
                             </span>
                         </span>

                        <div class="progress __progress">
                            <div class="progress-bar flash-deal-progress-bar" role="progressbar"
                                 style="width: {{ $deal->elapsed_percentage }}%"
                                 aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
