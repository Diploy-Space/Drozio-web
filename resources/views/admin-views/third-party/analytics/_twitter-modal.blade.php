<div class="modal fade" id="modalForTwitterPixel" tabindex="-1" aria-labelledby="modalForTwitterPixel"
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
                                             src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/twitter.svg') }}"
                                             loading="lazy" alt="">
                                    </div>
                                    <div class="text-dark mb-3">
                                        <h3 class="lh-base">
                                            {{ translate('How to get the x (twitter) pixel id') }}
                                        </h3>
                                        <p class="opacity-75">
                                            {{ translate('Log in to Twitter Ads to get your X pixel ID.') }}
                                            {{ translate('From the top navigation, click on tools and select events manager.') }}
                                            {{ translate('In Events Manager, click Add Event Source to create your pixel ID.') }}
                                            {{ translate('Choose the install with pixel code option and press save.') }}
                                            {{ translate('Your pixel ID is generated, then copy it from the interface.') }}
                                        </p>
                                    </div>

                                    <div class="text-dark mb-3">
                                        <h3 class="lh-base">
                                            {{ translate('Where to use the x (twitter) pixel id') }}
                                        </h3>
                                        <p class="opacity-75">
                                            {{ translate('Open Marketing Tools in your admin panel and follow the steps:') }}
                                        </p>
                                        <ol class="d-flex flex-column gap-2 opacity-75">
                                            <li>
                                                {{ translate('Navigate to the x (twitter) pixel id section under marketing tools.') }}
                                            </li>
                                            <li>
                                                {{ translate('Turn on the toggle button.') }}
                                            </li>
                                            <li>
                                                {{ translate('Paste your X pixel ID and submit.') }}
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
