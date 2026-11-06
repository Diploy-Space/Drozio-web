<div class="modal fade" id="getInformationModal" data-backdrop="static" tabindex="-1" aria-labelledby="getInformationModal"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                <button type="button" class="btn-close border-0" data-bs-dismiss="modal" aria-label="Close"><i
                        class="tio-clear"></i></button>
            </div>
            <div class="modal-body pt-0">
                <div class="swiper instruction-carousel pb-3">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide px-3">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <img width="80" class="mb-3"
                                     src="{{dynamicAsset(path: 'public/assets/back-end/img/firebase-console.png')}}"
                                     loading="lazy" alt="">
                                <h4 class="lh-md mb-3 text-capitalize">{{translate('Go to firebase')}}</h4>
                                <p class="text-center">{{translate('Go to Firebase and create a project').', '.translate('There are three steps to create a project').'. '.translate('After completing the project').','. translate('You can proceed further.').'.'}}</p>
                            </div>
                        </div>
                        <div class="swiper-slide px-3">
                            <div class="d-flex flex-column align-items-center gap-2 mb-4">
                                <img width="80" class="mb-3"
                                     src="{{dynamicAsset(path: 'public/assets/back-end/img/firebase-settings.png')}}"
                                     loading="lazy" alt="">
                                <h4 class="lh-md mb-3 text-capitalize">{{translate('Check settings')}}</h4>
                                <p>{{translate('After completing the project').', '.translate('You will see the project settings').'. '. translate('In the project settings').', '.translate('Please ensure that cloud messaging is enabled').' .'}}</p>
                            </div>
                        </div>
                        <div class="swiper-slide px-3">
                            <div class="d-flex flex-column align-items-center gap-2 mb-4">
                                <img width="80" class="mb-3"
                                     src="{{dynamicAsset(path: 'public/assets/back-end/img/json-file.png')}}"
                                     loading="lazy" alt="">
                                <h4 class="lh-md mb-3 text-capitalize">{{translate('How to get JSON file')}}</h4>
                                <p class="text-center">{{translate('In project settings').', '.translate('Click on generate new private key').'. '.translate('Then you will get a JSON file').', '.translate('Copy the JSON file and paste it into the Firebase configuration field').', '.translate('Then submit.').'.'}}</p>
                                <button class="btn btn-primary w-100 max-w-250 mx-auto mt-3 text-capitalize"
                                        data-bs-dismiss="modal">{{ translate('Got it') }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
                <div class="instruction-pagination-custom mb-2"></div>
                <div class="swiper-pagination instruction-pagination"></div>
            </div>
        </div>
    </div>
</div>
