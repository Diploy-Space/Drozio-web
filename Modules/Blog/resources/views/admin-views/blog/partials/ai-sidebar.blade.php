<!-- AI floating button -->
<div class="floating-ai-button">
    <button type="button" class="btn btn-lg rounded-circle shadow-lg" data-bs-toggle="modal" data-bs-target="#aiAssistantModalBlog"
        data-action="main" title="AI Assistant">
        <span class="ai-btn-animation">
            <span class="gradientCirc"></span>
        </span>
        <span class="position-relative z-1 text-white d-flex flex-column gap-1 align-items-center">
            <img width="16" height="17" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/hexa-ai.svg') }}" alt="">
            <span class="fs-12 fw-semibold">{{ translate('Use AI') }}</span>
        </span>
    </button>
    <div class="ai-tooltip">
        <span>{{translate('AI Assistant')}}</span>
    </div>
</div>

<!-- AI Assistant Modal -->
<div class="modal fade p-0" id="aiAssistantModalBlog" tabindex="-1" aria-labelledby="aiAssistantModalBlogLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-slideInRight modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header d-flex gap-2 aign-items-center justify-content-between">
                <h5 class="modal-title d-flex align-items-center gap-2 aiAssistantModalBlogLabel" id="aiAssistantModalBlogLabel">
                    <span class="square-div">
                        <span class="ai-btn-animation">
                            <span class="gradientCirc"></span>
                        </span>
                        <img class="position-relative z-1" width="15" height="12" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/blink-right.svg') }}" alt="">
                    </span>
                    <span id="modalTitleBlog">{{ translate('AI Assistant') }}</span>
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn text-primary bg-transparent p-0 m-0 shadow-none border-0 ai_backBtn" >
                        <span class="back-icons">
                            <i class="fi fi-rr-arrow-left d-flex fs-18"></i>
                        </span>
                    </button>
                    <button type="button" class="btn btn-circle bg-body-light text-white ai-modal-btn" style="--size:20px" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}">
                        <i class="fi fi-rr-cross-small d-flex"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body">
                <!-- Main AI Assistant Content -->
                <div id="mainAiContentBlog" class="ai-modal-content-blog" style="display: none;">
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
                                {{ translate('Choose how you want to give me your blog data:') }}.
                            </p>
                        </div>

                        <div class="ai-actions d-flex flex-column align-items-center gap-3">
                            <button type="button" class="btn btn-outline-primary text-dark bg-transparent rounded-10 btn-block max-w-250 d-flex gap-2 ai-action-btn-blog"
                                data-action="upload">
                                <img width="18" height="18" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/picture.svg') }}" alt="">
                                <span class="text-title">{{ translate('Upload image') }}</span>
                            </button>
                            <button type="button" class="btn bg-section2 border text-dark rounded-10 btn-block max-w-250 d-flex gap-2 ai-action-btn-blog"
                                data-action="title">
                                <img width="18" height="18" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/text-generate.svg') }}" alt="">
                                <span class="text-title">{{ translate('Generate Blog Title') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="uploadImageContentBlog" class="ai-modal-content-blog" style="display: none;">
                    <div class="d-flex justify-content-center align-items-end w-100">
                        <div class="">
                            <div class="mb-4">
                                <h5 class="fs-16 fw-bold">
                                    {{ translate('Give the description & upload an image') }}
                                </h5>
                                <p class="mb-3">{{ translate('Give a proper description and image to generate full blog data.') }}
                                </p>
                                <ul class="d-flex flex-column gap-2 mb-5">
                                    <li>{{ translate('Try to use a clear image and avoid blurry ones') }}</li>
                                    <li>{{ translate('Use an image as close as possible to your product') }}</li>
                                </ul>
                            </div>
                            <div class="text-center mb-4">
                                <label class="upload-zone w-100 mx-auto" id="chooseImageBtnBlog">
                                    <input type="file" id="aiImageUploadBlog" class="image-compressor"  hidden class="d-none" accept="image/*">
                                    <input type="file" id="aiImageUploadOriginalBlog" class="d-none" accept="{{ getFileUploadFormats(skip: '.svg')  }}">
                                    <input type="hidden"  id="fileUploadFormat" value="{{ getFileUploadFormats(skip: '.svg') }}">
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
                                    <div id="imagePreviewBlog" class="w-fit-content mx-auto position-relative" style="display: none;">
                                        <img id="previewImgBlog" src="" alt="{{ translate('Preview') }}" class="upload-zone_img"
                                            style="max-height: 200px;">
                                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                                            <button type="button" class="btn btn-danger p-0 square-div z-2 fs-10 remove_image_btn" id="removeImageBtn"
                                                data-toggle="tooltip" title="{{ translate('Remove image') }}">
                                                <i class="fi fi-rr-cross"></i>
                                            </button>
                                        </div>
                                    </div>
                                </label>
                                <div class="text-start form-group mt-4">
                                    <label for="" class="form-label mb-2">{{ translate('Description') }}</label>
                                    <textarea name="description" id="blog_description" class="form-control" rows="4" placeholder="{{ translate('Describe about blog') }}"></textarea>
                                </div>
                                <div class="text-center analyzeImageBtn_wrapper">
                                    <button type="button" class="btn btn-primary text-white mb-3 d-flex align-items-center gap-2 opacity-1 border-0 mx-auto px-4 py-3 position-relative"
                                        id="analyzeBlogImageBtn" data-url="{{ route('admin.settings.content.blog.analyze-image-auto-fill') }}"
                                        data-lang="en">
                                        <span class="ai-btn-animation d-none">
                                            <span class="gradientRect"></span>
                                        </span>
                                        <span class="position-relative z-1 d-flex gap-2 align-items-center">
                                            <span class="d-flex align-items-center bg-transparent text-white btn-text">
                                                {{ translate('Generate Blog') }}
                                            </span>
                                            <img width="17" height="15" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/blink-left.svg') }}"
                                                alt="">
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="giveTitleContentBlog" class="ai-modal-content-blog" style="display: none;">
                    <div class="mb-4">
                        <div class="giveTitleContent_text">
                            <h5 class="mb-3 fs-16 fw-bold text-body lh-base">
                                {{ translate('Great!') }}
                                <br>
                                {{ translate('Tell me which blog to create. Type it simply, like:') }}
                            </h5>
                            <ul class="d-flex flex-column gap-2 mb-3">
                                <li>{{ translate('Write a blog about the top fashion trends this season.') }}</li>
                                <li>{{ translate('A blog post on choosing the right running shoes.') }}</li>
                                <li>{{ translate('I want to create content that promotes our new skincare line') }}</li>
                            </ul>
                            <p class="mb-4">{{ translate('Feel free to describe it your own way!') }}</p>
                        </div>

                        <div class="form-group">
                            <label for="" class="form-label mb-2">{{ translate('Title') }}</label>
                            <input type="text" class="form-control"  name="blogKeywords" id="blogKeywords" placeholder="{{ translate('Tell me about your blog') }}"
                                data-role="tagsinput">
                        </div>
                        <div class="text-center generateTitleBtn_wrapper">
                            <button type="button" class="btn btn-primary text-white mb-3 d-flex align-items-center gap-2 opacity-1 border-0 mx-auto px-4 py-3 position-relative"
                                id="generateBlogTitleBtn"  data-route="{{ route('admin.settings.content.blog.generate-title-suggestions') }}" data-lang="en">
                                <span class="ai-btn-animation d-none">
                                    <span class="gradientRect"></span>
                                </span>
                                <span class="position-relative z-1 d-flex gap-2 align-items-center">
                                    <span class="d-flex align-items-center bg-transparent text-white btn-text">
                                        {{ translate('Generate Title') }}
                                    </span>
                                    <img width="17" height="15" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/blink-left.svg') }}"
                                        alt="">
                                </span>
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
                        <h4 class="mb-2 titlesList_title fs-14 fw-bold mb-4 d-none">{{ translate('Suggest Blog Title') }}</h4>
                        <div id="titlesList" class="list-group gap-4">
                            <!-- Generated titles will appear here -->
                        </div>
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
