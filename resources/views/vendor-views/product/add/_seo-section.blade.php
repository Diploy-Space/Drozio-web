<div class="seo_wrapper mt-3">
    <div class="outline-wrapper">
        <div class="card rest-part bg-animate">
            <div class="card-header d-flex justify-content-between align-items-center border-0 shadow-none pb-0 pc-header-ai-btn">
                <div>
                    <h3 class="mb-1">{{ translate('SEO Section') }}</h2>
                    <p class="fs-12 mb-0">
                        {{ translate('Add meta titles descriptions and images for products').', '.translate('Helps people find them in search and when the link is shared.') }}
                    </p>
                </div>

                @if(getActiveAIProviderConfigCache())
                <button type="button"
                    class="btn bg-white text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 seo_section_auto_fill"
                    id="seo_section_auto_fill" data-route="{{ route('vendor.retail.product.seo-section-auto-fill') }}" data-lang="en">
                    <div class="btn-svg-wrapper">
                        <img width="18" height="18" class=""
                            src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/blink-right-small.svg') }}" alt="">
                    </div>
                    <span class="ai-text-animation d-none" role="status">
                        {{ translate('Just a second') }}
                    </span>
                    <span class="btn-text">{{ translate('Generate') }}</span>
                </button>
                @endif

            </div>
            <div class="card-body">
                <div class="row gx-2 gy-3">
                    <div class="col-md-8">
                        <div class="bg-section rounded-10 p-12 p-sm-20">
                            <div class="form-group">
                                <label class="title-color">
                                    {{ translate('Meta Title') }}
                                    <span class="input-label-secondary cursor-pointer" data-toggle="tooltip"
                                            data-placement="top"
                                            title="{{ translate('Add the products title name taglines etc here').' '.translate('Title shown in search results and when the product is shared.') .' [ '. translate('Character Limit') }} : 100 ]">
                                    <i class="fi fi-sr-info fs-12 text-9EADC1"></i>
                                </span>
                                </label>
                                <input type="text" name="meta_title" placeholder="{{ translate('Meta Title') }}"
                                        class="form-control" id="meta_title">
                            </div>
                            <div class="form-group mb-0">
                                <label class="title-color">
                                    {{ translate('Meta Description') }}
                                    <span class="input-label-secondary cursor-pointer" data-toggle="tooltip"
                                            data-placement="top"
                                            title="{{ translate('Write a short description of this shop product.').' '.translate('Description shown in search results and when the product is shared.') .' [ '. translate('Character Limit') }} : 100 ]">
                                    <i class="fi fi-sr-info fs-12 text-9EADC1"></i>
                                </span>
                                </label>
                                <textarea rows="4" type="text" name="meta_description" id="meta_description" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="d-flex justify-content-center bg-section rounded-10 p-12 p-sm-20 h-100">
                            <div class="d-flex flex-column justify-content-end gap-20">
                                <div class="text-center mb-4">
                                    <label for="meta_Image" class="form-label fw-semibold mb-1">
                                        {{ translate('Meta Image') }}
                                    </label>
                                    <p class="fs-12 mb-0">{{ translate('Upload image') }}</p>
                                </div>
                                <div class="upload-file my-0">
                                    <input type="file" name="meta_image"
                                           class="upload-file__input single_file_input"
                                           data-max-size="{{ getFileUploadMaxSize() }}"
                                           id="meta_image_input"
                                           accept="{{ getFileUploadFormats(skip:'.svg,.gif') }}"
                                           value=""
                                    >
                                    <label
                                        class="upload-file__wrapper ratio-2-1">
                                        <div class="upload-file-textbox text-center">
                                            <img width="34" height="34"
                                                 src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                 alt="image upload">
                                            <h6 class="mt-1 fw-medium lh-base text-center fs-10">
                                                <span class="text-info">{{ translate('Click to upload') }}</span>
                                                <br>
                                                {{ translate('Or drag and drop') }}
                                            </h6>
                                        </div>
                                        <img class="upload-file-img pre-meta-image-viewer" loading="lazy" src="" data-default-src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                             alt="">
                                    </label>
                                    <div class="overlay">
                                        <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                                            <button type="button" class="btn btn-outline-primary text-primary icon-btn view_btn">
                                                <i class="fi fi-sr-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary text-primary icon-btn edit_btn">
                                                <i class="fi fi-rr-camera"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <p class="fs-10 mb-0 text-center">
                                    {{ getFileUploadFormats(skip: '.svg,.gif', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                                    <span class="text-dark fw-semibold">{{ THEME_RATIO[theme_root_path()]['Meta Thumbnail'] }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                @include('vendor-views.product.partials._seo-section')
            </div>
        </div>
    </div>
</div>
