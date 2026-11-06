@if(getActiveAIProviderConfigCache())
<div class="modal fade p-0" id="aiAssistantModal" tabindex="-1" aria-labelledby="aiAssistantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-slideInRight modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header d-flex gap-2 aign-items-center justify-content-between">
                <h5 class="modal-title d-flex align-items-center gap-2 aiAssistantModalLabel" id="aiAssistantModalLabel">
                    <span class="square-div">
                        <span class="ai-btn-animation">
                            <span class="gradientCirc"></span>
                        </span>
                        <img class="position-relative z-1" width="15" height="12" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/blink-right.svg') }}" alt="">
                    </span>
                    <span id="modalTitle">{{ translate('AI Assistant') }}</span>
                </h5>
                <button type="button" class="btn btn-circle bg-body-light text-white fs-8 h-1" style="--size: 20px;" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}">
                    <i class="fi fi-rr-cross"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Main AI Assistant Content -->
                <div id="mainAiContent" class="ai-modal-content" style="display: none;">
                    <div class="text-center mb-4">
                        <div class="ai-avatar mb-3">
                            <div class="avatar-circle mx-auto">
                                <span class="ai-btn-animation">
                                    <span class="gradientCirc"></span>
                                </span>
                                <img class="position-relative z-1" width="40" height="34" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/blink-right.svg') }}" alt="">
                            </div>
                        </div>

                        <div class="ai-greeting mb-5">
                            <h4 class="text-title">{{ translate('Hi There') }},</h4>
                            <h2 class="mb-2">{{ translate('I am here to help you') }}!</h2>
                            <p class="">
                                {{ translate('I’m your personal assistant to ease your long tasks') }}.
                                {{ translate('Choose how you want to give me your product data:') }}.
                            </p>
                        </div>

                        <div class="ai-actions d-flex flex-column align-items-center gap-3">
                            <button type="button" class="btn btn-outline-primary text-dark bg-transparent rounded-10 btn-block max-w-250 d-flex gap-2 ai-action-btn"
                                data-action="upload">
                                <img width="18" height="18" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/picture.svg') }}" alt="">
                                <span class="text-title">{{ translate('Upload image') }}</span>
                            </button>
                            <button type="button" class="btn bg-section2 border text-dark rounded-10 btn-block max-w-250 d-flex gap-2 ai-action-btn"
                                data-action="title">
                                <img width="18" height="18" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/text-generate.svg') }}" alt="">
                                <span class="text-title">{{ translate('Generate Product Name') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="uploadImageContent" class="ai-modal-content h-100" style="display: none;">
                    <div class="d-flex justify-content-center align-items-end w-100 h-100">
                        <div class="">
                            <div class="mb-4">
                                <h5 class="fs-16 fw-bold">
                                    {{ translate('Give the product name or upload image') }}
                                </h5>
                                <p class="mb-3">{{ translate('Give a proper product name or image to generate full data.') }}
                                </p>
                                <ul class="d-flex flex-column gap-2 mb-5">
                                    <li>{{ translate('Try to use a clear image and avoid blurry ones') }}</li>
                                    <li>{{ translate('Use an image as close as possible to your product') }}</li>
                                </ul>
                            </div>
                            <div class="text-center mb-4">
                                <label class="upload-zone w-100 mx-auto" id="chooseImageBtn">
                                    <input type="file" id="aiImageUpload" class="image-compressor d-none" hidden data-max-size="{{ getFileUploadMaxSize() }}"
                                           accept="{{ getFileUploadFormats(skip: '.svg,.gif') }}">
                                    <input type="file" id="aiImageUploadOriginal" class="d-none" data-max-size="{{ getFileUploadMaxSize() }}"
                                           accept="{{ getFileUploadFormats(skip: '.svg,.gif') }}">
                                    <div class="text-box mx-auto">
                                        <div class="w-100 d-flex flex-column gap-2 justify-content-center align-items-center py-4">
                                            <img width="40" height="40" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/image-upload.svg') }}"
                                                alt="">
                                            <div class="d-flex gap-2 align-items-center justify-content-center flex-wrap fs-14">
                                                <span class="text-dark">{{ translate('Drag & drop your image') }}</span>
                                                <span class="text-lowercase">{{ translate('Or') }}</span>
                                                <span type="button" class="text-primary fw-semibold fs-12 text-underline">
                                                    {{ translate('Browse Image') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="imagePreview" class="w-fit-content mx-auto position-relative" style="display: none;">
                                        <img id="previewImg" src="" alt="{{ translate('Preview') }}" class="upload-zone_img max-w-250px"
                                            style="max-height: 200px;">
                                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                                            <button type="button" class="btn btn-danger p-0 square-div z-2 fs-10 remove_image_btn" id="removeImageBtn"
                                                data-toggle="tooltip" title="{{ translate('Remove image') }}">
                                                <i class="fi fi-rr-cross"></i>
                                            </button>
                                        </div>
                                    </div>
                                </label>
                                <div class="mt-4 text-center analyzeImageBtn_wrapper">
                                    <button type="button" class="btn btn-primary text-white mb-3 d-flex align-items-center gap-2 opacity-1 border-0 mx-auto px-4 py-3 position-relative"
                                        id="analyzeImageBtn" data-url="{{ route('admin.retail.product.analyze-image-auto-fill') }}"
                                        data-lang="en">
                                        <span class="ai-btn-animation d-none">
                                            <span class="gradientRect"></span>
                                        </span>
                                        <span class="position-relative z-1 d-flex gap-2 align-items-center">
                                            <span class="d-flex align-items-center bg-transparent text-white btn-text">{{ translate('Generate Product') }}</span>
                                            <img width="17" height="15" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/blink-left.svg') }}"
                                                alt="">
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="giveTitleContent" class="ai-modal-content" style="display: none;">
                    <div class="mb-4">
                        <div class="giveTitleContent_text">
                            <h5 class="mb-3 fs-16 fw-bold text-body lh-base">
                                {{ translate('Great!') }}
                                <br>
                                {{ translate('Tell me which product to create. Type it simply, like:') }}
                            </h5>
                            <ul class="d-flex flex-column gap-2 mb-3">
                                <li>{{ translate('I need product details for men’s converse shoes') }}</li>
                                <li>{{ translate('I want to add a men’s t-shirt') }}</li>
                                <li>{{ translate('I want to create a product for women’s jeans') }}</li>
                            </ul>
                            <p class="mb-4">{{ translate('Feel free to describe it your own way!') }}</p>
                        </div>
                        <div class="generate-text-input-group">
                            <input type="text" class="form-control" id="productKeywords"
                                placeholder="{{ translate('Tell me about your item') }}" data-role="tagsinput">
                            <button type="button" class="btn btn-primary border-0" id="generateTitleBtn"
                                data-route="{{ route('admin.retail.product.generate-title-suggestions') }}" data-lang="en">
                                <span class="ai-loader-animation z-2 d-none">
                                    <span class="loader-circle"></span>
                                    <div class="position-relative h-100 d-flex justify-content-center align-items-center">
                                        <img width="15" height="15" class=""
                                        src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/blink-left.svg') }}" alt="">
                                    </div>
                                </span>
                                <span class="position-rtelative z-1 text-generate-icon"><i class="fi fi-rr-arrow-right"></i></span>
                            </button>
                        </div>
                    </div>

                    <div id="generatedTitles" style="display: none;">
                        <div class="text-primary generate_btn_wrapper show_generating_text d-none mb-3">
                            <div class="btn-svg-wrapper">
                                <img width="18" height="18" class="" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/blink-right-small.svg') }}"
                                alt="">
                            </div>
                            <span class="ai-text-animation ai-text-animation-visible">
                                {{ translate('Just a second') }}
                            </span>
                        </div>
                        <h4 class="mb-2 titlesList_title fs-14 fw-bold mb-4 d-none">{{ translate('Suggest Product Name') }}</h4>
                        <div id="titlesList" class="list-group gap-4"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-transparent border-0 shadow-none d-flex justify-content-center align-items-center pt-0">
                <div class="__bg-FAFAFA px-2 py-1 rounded text-center">
                    <p class="mb-0">{{ translate('AI may make mistakes. please recheck important data.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
