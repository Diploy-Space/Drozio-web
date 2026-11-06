<div class="modal fade" id="modalForTikTokPixel" tabindex="-1" aria-labelledby="modalForTikTokPixel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered max-w-655px">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                    data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body px-20 py-0 mb-30">
                <div class="swiper instruction-carousel pb-3">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <div class="swiper-slide">
                                <div class="">
                                    <div class="d-flex justify-content-center mb-5">
                                        <img height="60"
                                             src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/tiktok.svg') }}"
                                             loading="lazy" alt="">
                                    </div>
                                    <div class="text-dark mb-3">
                                        <h3 class="lh-base">
                                            {{ translate('How to get the tiktok pixel id') }}
                                        </h3>
                                        <p class="opacity-75">
                                            {{ translate('In your TikTok business account, click Tools and select Events.') }}
                                            {{ translate('Open Events Manager from Connect Data Sources, top right.') }}
                                            {{ translate('From the popup, choose the web option and click next.') }}
                                            {{ translate('Now, create your pixel by selecting manual setup.') }}
                                            {{ translate('Find your pixel ID under Data Sources in the left menu.') }}
                                        </p>
                                    </div>

                                    <div class="text-dark mb-3">
                                        <h3 class="lh-base">
                                            {{ translate('Where to use the tiktok pixel id') }}
                                        </h3>
                                        <p class="opacity-75">
                                            {{ translate('Open Marketing Tools in your admin panel and follow the steps:') }}
                                        </p>
                                        <ol class="d-flex flex-column gap-2 opacity-75">
                                            <li>
                                                {{ translate('Navigate to the tiktok pixel id section under marketing tools.') }}
                                            </li>
                                            <li>
                                                {{ translate('Turn on the toggle button.') }}
                                            </li>
                                            <li>
                                                {{ translate('Paste your tiktok pixel id into the input box and click submit.') }}
                                            </li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
