<section class="overflow-hidden">
    <div class="container">
        <div class="flash-deals-wrapper rounded {{ count($bannerTypeMainBanner) > 0 ? '' : 'mt-20' }}">
            <div class="flash-deal-view-all-web row d-none d-sm-flex justify-content-end align-items-center mb-3">
                <div class="flash-deal-text web-text-primary mt-0 flex-grow-1">
                    <h1 class="web-text-primary lh-1 h3 letter-spacing-0 line--limit-3 text-start mb-0">
                        <span>{{$web_config['flash_deals']->title}}</span>
                    </h1>
                </div>
                @if ($web_config['flash_deals']->products_count > 0)
                    <a class="text-capitalize view-all-btn-text"
                       href="{{ route('flash-deals',['id' => $web_config['flash_deals'] ? $web_config['flash_deals']['id'] : 0]) }}">
                        <span class="view-btn-text">{{ translate('View All') }}</span>
                        <i class="czi-view-list"></i>
                    </a>
                @endif
            </div>
            <div class="d-none d-sm-block">
                <div class="row g-3">
                    <div class="col-lg-4 flashdeal-responsive">
                        <h2 class="fs-16 fw-semibold text-primary mb-3">{{translate('Hurry Up')}} ! {{translate('The offer is limited')}}. {{translate('Grab while it lasts')}}</h2>
                        <a href="{{ route('flash-deals',['id' => $web_config['flash_deals'] ? $web_config['flash_deals']['id'] : 0]) }}" class="countdown-card bg-transparent d-block">
                            <div class="countdown-background text-white w-100 max-w-100 p-3 mt-0">
                                <div class="text-center">
                                   <span class="cz-countdown d-flex justify-content-center align-items-center flash-deal-countdown"
                                         data-countdown="{{ $web_config['flash_deals']?->end_at }}">
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
                                        <div class="progress-bar flash-deal-progress-bar" role="progressbar" style="width: {{ $web_config['flash_deals']?->elapsed_percentage }}%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    @php($flashDealCount = count($flashDeal['flashDealProducts']))
                    @if($flashDealCount == 10 || $flashDealCount <= 4)
                        @foreach($flashDeal['flashDealProducts'] as $key=>$flashDealProduct)
                            <div class="col-lg-2 col-md-4 col-sm-6">
                                @include('web-views.partials._feature-product',['product'=> $flashDealProduct,'decimal_point_settings'=>$decimal_point_settings])
                            </div>
                        @endforeach
                    @endif
                    @if($flashDealCount > 4 && $flashDealCount < 10)
                        <div class="col-lg-8 d-none d-md-block">
                            <div class="owl-theme owl-carousel flash-deal-slider">
                                @foreach($flashDeal['flashDealProducts'] as $key=>$flashDealProduct)
                                    @include('web-views.partials._feature-product',['product'=> $flashDealProduct,'decimal_point_settings'=>$decimal_point_settings])
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="d-sm-none">
                <div class="row g-3">
                    <div class="col-12 d-flex justify-content-between align-items-center gap-2">
                        <h3 class="web-text-primary fw-bold lh-1 fs-16 letter-spacing-0 line--limit-2 text-uppercase mb-0">
                            <span>{{$web_config['flash_deals']->title}}</span>
                        </h3>
                        @if ($web_config['flash_deals']->products_count > 0)
                            <a class="text-capitalize view-all-btn-text flex-shrink-0"
                               href="{{ route('flash-deals',['id' => $web_config['flash_deals'] ? $web_config['flash_deals']['id'] : 0]) }}">
                                <span class="view-btn-text">{{ translate('View All') }}</span>
                                <i class="czi-view-list"></i>
                            </a>
                        @endif
                    </div>
                    <div class="col-5">
                        <h5 class="fs-12 lh-19px font-weight-normal web-text-primary mb-0">{{translate('Hurry Up')}} ! {{translate('The offer is limited')}}. {{translate('Grab while it lasts')}}</h5>
                    </div>
                    <div class="col-7">
                        <div class="flashdeal-responsive">
                            <a href="{{ route('flash-deals',['id' => $web_config['flash_deals']?$web_config['flash_deals']['id']:0]) }}" class="countdown-card bg-transparent d-block">
                                <div class="countdown-background text-white w-100 max-w-100 px-2 pb-2 pt-sm-0 mt-0">
                                    <div class="text-center">
                                       <span class="cz-countdown d-flex justify-content-center align-items-center flash-deal-countdown pb-sm-3"
                                             data-countdown="{{ $web_config['flash_deals']?->end_at }}">
                                           <span class="cz-countdown-days">
                                               <span class="cz-countdown-value fs-8 lh-2"></span>
                                               <span class="cz-countdown-text text-nowrap fs-8">{{ translate('Days')}}</span>
                                           </span>
                                           <span class="cz-countdown-value p-1 fs-8">:</span>
                                           <span class="cz-countdown-hours">
                                               <span class="cz-countdown-value fs-8 lh-2"></span>
                                               <span class="cz-countdown-text text-nowrap fs-8">{{ translate('Hours')}}</span>
                                           </span>
                                           <span class="cz-countdown-value p-1 fs-8">:</span>
                                           <span class="cz-countdown-minutes">
                                               <span class="cz-countdown-value fs-8 lh-2"></span>
                                               <span class="cz-countdown-text text-nowrap fs-8">{{ translate('Min')}}</span>
                                           </span>
                                           <span class="cz-countdown-value p-1 fs-8">:</span>
                                           <span class="cz-countdown-seconds">
                                               <span class="cz-countdown-value fs-8 lh-2"></span>
                                               <span class="cz-countdown-text text-nowrap fs-8">{{ translate('Sec')}}</span>
                                           </span>
                                       </span>
                                        <div class="progress __progress h-2px mt-0">
                                            <div class="progress-bar flash-deal-progress-bar" role="progressbar" style="width: {{ $web_config['flash_deals']?->elapsed_percentage }}%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="col-12 pb-0 d-md-none">
                        <div class="owl-theme owl-carousel flash-deal-slider-mobile">
                            @foreach($flashDeal['flashDealProducts'] as $key=>$flashDealProduct)
                                @include('web-views.partials._product-card-1',['product' => $flashDealProduct,'decimal_point_settings'=>$decimal_point_settings])
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
</section>
