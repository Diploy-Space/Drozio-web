<div class="modal fade" id="readInstructionModal" data-backdrop="static" tabindex="-1" aria-labelledby="readInstructionModal"
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
                                     src="{{dynamicAsset(path: 'public/assets/back-end/img/email-template/2.png')}}"
                                     loading="lazy" alt="">
                                <h4 class="lh-md mb-3 text-capitalize">{{translate('Input a title')}}</h4>
                                <p class="text-center">{{translate('A descriptive title so users know what the template is for.').'.'}}</p>
                            </div>
                        </div>
                        <div class="swiper-slide px-3">
                            <div class="d-flex flex-column align-items-center gap-2 mb-4">
                                <img width="80" class="mb-3"
                                     src="{{dynamicAsset(path: 'public/assets/back-end/img/email-template/3.png')}}"
                                     loading="lazy" alt="">
                                <h4 class="lh-md mb-3">{{translate('Write a message in the email body')}}</h4>

                                <p>{{translate('You can add your message using placeholders to include dynamic content').'. '. translate('Here are some examples of placeholders you can use').' :'}}</p>

                                <ul class="d-flex flex-column px-4 gap-2 mb-4">
                                    <li>{{'{userName}'.' : '.translate('The name of the user').'.'}}</li>
                                    <li>{{'{adminName}'.' : '.translate('The name of the admin').'.'}}</li>
                                    <li>{{'{deliveryManName}'.' : '.translate('The name of the delivery person').'.'}}</li>
                                    <li>{{'{shopName}'.' : '.translate('The name of the store').'.'}}</li>
                                    <li>{{'{vendorName}'.' : '.translate('Vendor Name').'.'}}</li>
                                    <li>{{'{orderId}'.' : '.translate('Order ID').'.'}}</li>
                                    <li>{{'{transactionId}'.' : '.translate('Transaction ID').'.'}}</li>
                                    <li>{{'{emailId}'.' : '.translate('Email ID').'.'}}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="swiper-slide px-3">
                            <div class="d-flex flex-column align-items-center gap-2 mb-4">
                                <img width="80" class="mb-3"
                                     src="{{dynamicAsset(path: 'public/assets/back-end/img/email-template/4.png')}}"
                                     loading="lazy" alt="">
                                <h4 class="lh-md mb-3 text-capitalize">{{translate('Add button & link')}}</h4>
                                <p class="text-center">{{translate('Text and URL for the button in your email.').'.'}}</p>
                            </div>
                        </div>
                        <div class="swiper-slide px-3">
                            <div class="d-flex flex-column align-items-center gap-2 mb-4">
                                <img width="80" class="mb-3"
                                     src="{{dynamicAsset(path: 'public/assets/back-end/img/email-template/5.png')}}"
                                     loading="lazy" alt="">
                                <h4 class="lh-md mb-3 text-capitalize">{{translate('Choose an icon / banner image')}}</h4>
                                <p class="text-center">{{translate('Select a background image to display behind your email content').'.'}}</p>
                            </div>
                        </div>
                        <div class="swiper-slide px-3">
                            <div class="d-flex flex-column align-items-center gap-2 mb-4">
                                <img width="80" class="mb-3"
                                     src="{{dynamicAsset(path: 'public/assets/back-end/img/email-template/6.png')}}"
                                     loading="lazy" alt="">
                                <h4 class="lh-md mb-3 text-capitalize">{{translate('Select footer content')}}</h4>
                                <p class="text-center">{{translate('Add footer content').','.translate('Such as your company address and contact information').'.'}}</p>
                            </div>
                        </div>
                        <div class="swiper-slide px-3">
                            <div class="d-flex flex-column align-items-center gap-2 mb-4">
                                <img width="80" class="mb-3"
                                     src="{{dynamicAsset(path: 'public/assets/back-end/img/email-template/7.png')}}"
                                     loading="lazy" alt="">
                                <h4 class="lh-md mb-3 text-capitalize">{{translate('Create a copyright notice')}}</h4>
                                <p class="text-center">
                                    {{translate('Add a copyright notice at the bottom of your email.').'.'}}</p>
                            </div>
                        </div>
                        <div class="swiper-slide px-3">
                            <div class="d-flex flex-column align-items-center gap-2 mb-4">
                                <img width="80" class="mb-3"
                                     src="{{dynamicAsset(path: 'public/assets/back-end/img/email-template/8.png')}}"
                                     loading="lazy" alt="">
                                <h4 class="lh-md mb-3 text-capitalize">{{translate('Save and publish')}}</h4>
                                <p class="text-center">{{translate('Save and publish the template once every element is set.').'.'}}</p>
                                <button class="btn btn-primary w-100 max-w-250 mx-auto mt-3 text-capitalize"
                                        data-bs-dismiss="modal">
                                    {{ translate('Got it') }}
                                </button>
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
