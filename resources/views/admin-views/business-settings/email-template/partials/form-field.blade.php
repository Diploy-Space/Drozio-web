@php
    use App\Utils\Helpers;
@endphp
    @if(!in_array('logo',$template['hide_field']))
    <div class="d-flex flex-column gap-20 bg-section p-12 p-sm-20 rounded">
        <div>
            <label for="mail-logo" class="form-label fw-semibold mb-1">
                {{translate('Icon')}}
                <span class="text-danger">*</span>
            </label>
            <p class="fs-12 mb-0">{{ translate('Upload Icon') }}</p>
        </div>

        <div class="upload-file">
            <input type="file" class="upload-file__input single_file_input"
                name="icon" id="mail-logo" data-image-id="view-mail-logo"  data-max-size="{{ getFileUploadMaxSize() }}"  data-required-msg="{{ translate('Icon field is required') }}"
                accept="{{getFileUploadFormats(skip: '.svg')}}"  value="{{isset($template->image_full_url['path']) ? $template->image_full_url['path'] : ''}}" {{ isset($template->image_full_url['path']) ? '' : 'required' }}>
            <label
                class="upload-file__wrapper ratio-3-1">
                <div class="upload-file-textbox text-center">
                    <div class="d-flex gap-2 align-items-center justify-content-center flex-wrap">
                        <img width="34" height="34" class="svg" src="{{dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg')}}" alt="image upload">
                        <h6 class="mt-1 fw-medium lh-base text-center">
                            <span class="text-info">{{ translate('Click to upload') }}</span>
                            <br>
                            {{ translate('Or drag and drop') }}
                        </h6>
                    </div>
                </div>
                <img class="upload-file-img" loading="lazy" src="{{isset($template->image_full_url['path']) ? $template->image_full_url['path'] : ''}}" data-default-src="{{isset($template->image_full_url['path']) ? $template->image_full_url['path'] : ''}}" alt="">
            </label>
            <div class="overlay">
                <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                    <button type="button" class="btn btn-outline-info icon-btn view_btn">
                        <i class="fi fi-sr-eye"></i>
                    </button>
                    <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                        <i class="fi fi-rr-camera"></i>
                    </button>
                </div>
            </div>
        </div>
        <p class="fs-10 mb-0 text-center">
            {{ getFileUploadFormats(skip: '.svg,', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
            <span class="fw-medium">{{ translate('(Ratio 3:1)') }}</span></p>
    </div>
    @endif
    <div class="table-responsive w-auto overflow-y-hidden">
        @php($language = $language->value ?? null)
        @php($defaultLang = 'en')
        @php($defaultLang = getDefaultLanguage())
        <div class="position-relative nav--tab-wrapper">
            <ul class="nav nav-pills nav--tab lang_tab" id="pills-tab" role="tablist">
            @foreach (json_decode($language) as $lang)
                <li class="nav-item">
                    <a data-bs-toggle="pill" role="tab" class="nav-link form-system-language-tab  {{ $lang == $defaultLang ? 'active' : '' }}" data-bs-target="#{{ $lang }}-form" id="{{ $lang }}-link">
                        {{ ucfirst(Helpers::get_language_name($lang)) . '(' . strtoupper($lang) . ')' }}
                    </a>
                </li>
            @endforeach
            </ul>
            <div class="nav--tab__prev">
                <button type="button" class="btn btn-circle border-0 bg-white text-primary">
                    <i class="fi fi-sr-angle-left"></i>
                </button>
            </div>
            <div class="nav--tab__next">
                <button type="button" class="btn btn-circle border-0 bg-white text-primary">
                    <i class="fi fi-sr-angle-right"></i>
                </button>
            </div>

        </div>
    </div>
    <div class="bg-section p-12 p-sm-20 rounded-10">
        <h3 class="mb-0 mb-20">{{translate('Header content')}}</h3>
        @foreach (json_decode($language) as $lang)
                    <?php
                    $translate = [];
                    if (count($template['translations'])) {
                        foreach ($template['translations'] as $translation) {
                            if ($translation->locale == $lang && $translation->key == 'title') {
                                $translate[$lang]['title'] = $translation->value;
                            }
                            if ($translation->locale == $lang && $translation->key == 'body') {
                                $translate[$lang]['body'] = $translation->value;
                            }

                        }
                    }
                    ?>
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade {{ $lang == $defaultLang ? 'show active' : '' }} form-system-language-form" id="{{ $lang }}-form" aria-labelledby="{{ $lang }}-link" role="tabpanel">
                        <div class="form-group mb-20">
                            <label class="form-label" for="{{ $lang }}-main-title">
                                {{ translate('Title') }} ({{strtoupper($lang) }})
                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Enter title" data-bs-title="Enter title">
                                    <i class="fi fi-sr-info"></i>
                                </span>
                            </label>
                            <input type="text" name="title[{{$lang }}]" data-id="mail-title"
                                   id="{{ $lang }}-main-title"
                                   value="{{ $translate[$lang]['title'] ??  ($lang == 'en' ? $template['title'] : '')}}"
                                   class="form-control" placeholder="{{translate('Ex').' : '.translate('Title')}}" required>
                        </div>
                        <input type="hidden" name="lang[]" value="{{$lang }}">
                        <div class="form-group">
                            <label class="form-label">
                                {{ translate('Mail body') }} ({{strtoupper($lang) }})
                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Enter description" data-bs-title="Enter description">
                                    <i class="fi fi-sr-info"></i>
                                </span>
                            </label>

                            <textarea name="body[{{$lang }}]" data-id="mail-body" class="summernote form-control" required>{!! $translate[$lang]['body'] ?? ($lang == 'en' ? $template['body'] : '') !!}</textarea>
                            <div class="summernote-preview d-none"></div>
                        </div>

                    </div>
                </div>

            @endforeach
    </div>
    @if(!in_array('icon',$template['hide_field']))
    <div class="d-flex flex-column gap-20 bg-section p-12 p-sm-20 rounded">
        <div>
            <label for="mail-logo" class="form-label fw-semibold mb-1">
                {{translate('Logo')}}
            </label>
            <p class="fs-12 mb-0">{{ translate('Upload your template logo.') }}</p>
        </div>

        <div class="upload-file">
            <input type="file"
                   name="logo"
                   id="banner"
                   class="upload-file__input single_file_input"
                   data-max-size="{{ getFileUploadMaxSize() }}"
                   accept="{{ getFileUploadFormats(skip: '.svg') }}"
                   data-required-msg="{{ translate(':field is required', ['field' => translate('Logo')]) }}"
                {{ empty($template->logo_full_url['path'] ?? dynamicAsset(path: 'public/assets/back-end/img/email-template/registration-success.png')) ? 'required' : '' }}>

            <div class="upload-file__wrapper ratio-3-1">
                <div class="upload-file-textbox text-center">
                    <img width="34" height="34" class="svg"
                         src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                         alt="image upload">
                    <h6 class="mt-1 fw-medium lh-base text-center">
                        <span class="text-info">
                            {{ translate('Click to upload') }}
                        </span>
                        <br>
                        {{ translate('Or drag and drop') }}
                    </h6>
                </div>
                <img class="upload-file-img" loading="lazy"
                     src="{{ $template->logo_full_url['path'] ?? dynamicAsset(path: 'public/assets/back-end/img/email-template/registration-success.png') }}"
                     data-default-src="{{ $template->logo_full_url['path'] ?? dynamicAsset(path: 'public/assets/back-end/img/email-template/registration-success.png') }}"
                     alt="">
            </div>
            <div class="overlay">
                <div
                    class="d-flex gap-10 justify-content-center align-items-center h-100">
                    <button type="button" class="btn btn-outline-info icon-btn view_btn">
                        <i class="fi fi-sr-eye"></i>
                    </button>
                    <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                        <i class="fi fi-rr-camera"></i>
                    </button>
                </div>
            </div>
        </div>

        <p class="fs-10 mb-0 text-center">
            {{ getFileUploadFormats(skip: '.svg', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
            <span class="fw-medium">(Ratio 3:1)</span>
        </p>
    </div>
    @endif
    @if(!in_array('product_information',$template['hide_field']))
    <div class="d-flex align-items-center justify-content-between gap-2 border rounded-10 p-12 p-sm-20 user-select-none">
        <div>
            <h3 class="mb-0">{{translate('Product information')}}</h3>
            <p class="fs-12 mb-0">{{translate('Product information will be automatically bound from the database').' '.translate('If you do not want to see the information in the mail').' '.translate('Just turn the switch button off').'.'}}</p>
        </div>

        <label class="switcher">
            <input type="checkbox" class="switcher_input change-status" value="1" name="product_information_status" data-id="product-information" {{$template['product_information_status'] ==1 ? 'checked' : '' }}>
            <span class="switcher_control"></span>
        </label>
    </div>
    @endif
    @if(!in_array('banner_image',$template['hide_field']))
    <div class="d-flex flex-column gap-20 bg-section p-12 p-sm-20 rounded">
        <div>
            <label for="mail-logo" class="form-label fw-semibold mb-1">
                {{translate('Banner image')}}
            </label>
            <p class="fs-12 mb-0">{{ translate('Upload your forgot password logo.') }}</p>
        </div>
        <div class="upload-file">
            <input type="file"  class="upload-file__input single_file_input"
                name="banner_image" id="inputGroupFile01"
                accept=".webp, .jpg, .jpeg, .png"  value="" >
            <label
                class="upload-file__wrapper ratio-3-1">
                <div class="upload-file-textbox text-center">
                    <div class="d-flex gap-2 align-items-center justify-content-center flex-wrap">
                        <img width="34" height="34" class="svg" src="{{dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg')}}" alt="image upload">
                        <h6 class="mt-1 fw-medium lh-base text-center">
                            <span class="text-info">{{ translate('Click to upload') }}</span>
                            <br>
                            {{ translate('Or drag and drop') }}
                        </h6>
                    </div>
                </div>
                <img class="upload-file-img" loading="lazy" src="" data-default-src="" alt="">
            </label>
            <div class="overlay">
                <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                    <button type="button" class="btn btn-outline-info icon-btn view_btn">
                        <i class="fi fi-sr-eye"></i>
                    </button>
                    <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                        <i class="fi fi-rr-camera"></i>
                    </button>
                </div>
            </div>
        </div>
        <p class="fs-10 mb-0 text-center">{{ 'JPG, JPEG, PNG Less Than 1MB' }} <span class="fw-medium">(Ratio 3:1)</span></p>
    </div>
    @endif
    @if(!in_array('button_content',$template['hide_field']))
        <div class="bg-section p-12 p-sm-20 runded-10">
            <label class="bg-white d-flex align-items-center justify-content-between gap-2 border rounded-10 p-3 user-select-none mb-3">
                <div>
                    <h3 class="mb-0">{{translate('Button content')}}</h3>
                </div>

                @if(!in_array('button_content_status',$template['hide_field']))
                <label class="switcher">
                    <input type="checkbox" class="switcher_input change-status" value="1" data-id="button-content" name="button_content_status" {{$template['button_content_status'] ==1 ? 'checked' : '' }} >
                    <span class="switcher_control"></span>
                </label>
                @endif
            </label>
            <div class="row g-2">
                <div class="col-lg-6">
                    <div class="form-group">
                        @foreach (json_decode($language) as $lang)
                                <?php
                                if (count($template['translations'])) {
                                    $translate = [];
                                    foreach ($template['translations'] as $translation) {
                                        if ($translation->locale == $lang && $translation->key == 'button_name') {
                                            $translate[$lang]['button_name'] = $translation->value;
                                        }
                                    }
                                }
                                ?>
                        <div class="{{ $lang != 'en'? 'd-none':''}} form-system-language-form {{ $lang }}-form">

                            <label for="button_name" class="form-label" for="{{ $lang }}-button-name">
                                {{translate('Button name')}} ({{strtoupper($lang)}})
                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="{{translate('Write the button name within 15 characters') }}" data-bs-title="{{translate('Write the button name within 15 characters') }}">
                                    <i class="fi fi-sr-info"></i>
                                </span>
                            </label>
                            <input type="text"  id="{{ $lang }}-button-name" name="button_name[{{ $lang }}]"  data-id="button-content"
                                value="{{ $translate[$lang]['button_name'] ?? ($lang == 'en' ? $template['button_name'] : '')}}"
                                placeholder="{{translate('Ex').' : '.translate('Submit')}}" class="form-control">
                        </div>
                        @endforeach

                    </div>
                </div>
                @if(!in_array('button_url',$template['hide_field']))
                <div class="col-lg-6">
                    <div class="form-group">
                        <label for="redirect_link" class="form-label">
                            {{translate('Redirect link')}}
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="{{translate('Where the button sends the customer.') }}" data-bs-title="{{translate('Where the button sends the customer.') }}">
                                <i class="fi fi-sr-info"></i>
                            </span>
                        </label>
                        <input type="text" id="redirect_link" name="button_url" data-id="button-link" value="{{$template['button_url']}}"
                            placeholder="{{translate('Ex').' : '.'www.google.com'}}" class="form-control" >
                    </div>
                </div>
                @endif
            </div>
        </div>
    @endif
    @if(!in_array('order_information',$template['hide_field']))
    <div class="d-flex align-items-center justify-content-between gap-2 border rounded-10 p-12 p-sm-20 user-select-auto">
        <div>
            <h3>{{translate('Order Information')}}</h3>
            <p class="fs-12 mb-0">{{translate('Order information will be automatically bound from the database').'. '.translate('If you do not want to see the information in the mail').'. '.translate('Just turn the switch button off').'.'}}</p>
        </div>

        <label class="switcher">
            <input type="checkbox" class="switcher_input change-status" value="1" name="order_information_status" data-id="order-information" {{ $template['order_information_status'] ==1 ? 'checked' : '' }}>
            <span class="switcher_control"></span>
        </label>
    </div>
    @endif

    <div class="bg-section p-12 p-sm-20 rounded-10 d-flex flex-column gap-3 gap-sm-20">
        <h3 class="mb-0">{{translate('Footer content')}}</h3>
        @foreach (json_decode($language) as $lang)
                    <?php
                    $translate = [];
                    if (count($template['translations'])) {
                        foreach ($template['translations'] as $translation) {
                            if ($translation->locale == $lang && $translation->key == 'footer_text') {
                                $translate[$lang]['footer_text'] = $translation->value;
                            }
                        }
                    }
                    ?>
                <div class="{{ $lang != 'en'? 'd-none':''}} form-system-language-form {{ $lang }}-form">
                    <div class="form-group mb-0">
                        <label class="form-label" for="{{ $lang }}-footer-text">
                            {{ translate('Section text') }} ({{strtoupper($lang) }})
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Enter section text" data-bs-title="Enter section text">
                                <i class="fi fi-sr-info"></i>
                            </span>
                        </label>
                        <textarea class="form-control" rows="1"
                            data-maxlength="150"
                            name="footer_text[{{ $lang }}]" data-id="footer-text"
                            id="{{ $lang }}-footer-text"
                            placeholder="{{translate('Ex').' : '.translate('Please contact us for any queries').','.translate('We are always happy to help').'.'}}"
                        >{{ $translate[$lang]['footer_text'] ?? ($lang == 'en' ? $template['footer_text'] : '')}}</textarea>
                        <div class="d-flex justify-content-end">
                            <span class="text-body-light">0/100</span>
                        </div>
                    </div>
                </div>
            @endforeach
            @foreach (json_decode($language) as $lang)
                    <?php
                    if (count($template['translations'])) {
                        $translate = [];
                        foreach ($template['translations'] as $translation) {
                            if ($translation->locale == $lang && $translation->key == 'copyright_text') {
                                $translate[$lang]['copyright_text'] = $translation->value;
                            }
                        }
                    }
                    ?>
                <div class="{{ $lang != 'en'? 'd-none':''}} form-system-language-form {{ $lang }}-form">
                    <div class="form-group">
                        <label class="form-label" for="{{ $lang }}-copyright-text">
                            {{ translate('Copyright content') }} ({{strtoupper($lang) }})
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Enter copyright content" data-bs-title="Enter copyright content">
                                <i class="fi fi-sr-info"></i>
                            </span>
                        </label>
                        <textarea class="form-control" rows="1"
                            data-maxlength="100"
                            name="copyright_text[{{ $lang }}]" data-id="copyright-text"
                            id="{{ $lang }}-copyright-text"
                            placeholder="{{translate('Ex').' : '.translate('Copyright').' @ '.translate('All right reserved')}}"
                        >{{ $translate[$lang]['copyright_text'] ?? ($lang == 'en' ? $template['copyright_text'] : '')}}</textarea>
                        <div class="d-flex justify-content-end">
                            <span class="text-body-light">0/100</span>
                        </div>
                    </div>
                </div>
            @endforeach
            <div>
                <label class="form-label">
                    {{translate('Page links')}}
                    <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Enter page links" data-bs-title="Enter page links">
                        <i class="fi fi-sr-info"></i>
                    </span>
                </label>
                <div class="bg-white border rounded p-3 d-flex flex-column gap-3 gap-sm-20">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="form-check d-flex gap-1">
                                <input type="checkbox" class="form-check-input checkbox--input" name="pages[privacy_policy]" data-from="pages" data-id="privacy-policy" id="privacy_policy" {{!empty($template['pages']) && in_array('privacy_policy',$template['pages'])? 'checked': (empty($template['pages']) ? 'checked' :'')}} >
                                <label class="form-check-label" for="privacy_policy">{{translate('Privacy Policy')}}</label>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-check d-flex gap-1">
                                <input type="checkbox" class="form-check-input checkbox--input" name="pages[refund_policy]" data-from="pages" data-id="refund-policy" id="refund_policy" {{!empty($template['pages']) && in_array('refund_policy',$template['pages'])? 'checked': (empty($template['pages']) ? 'checked' :'')}}>
                                <label class="form-check-label" for="refund_policy">{{translate('Refund Policy')}}</label>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-check d-flex gap-1">
                                <input type="checkbox" class="form-check-input checkbox--input" name="pages[cancellation_policy]" data-from="pages" data-id="cancellation-policy" id="cancellation_policy" {{!empty($template['pages']) && in_array('cancellation_policy',$template['pages']) ? 'checked': (empty($template['pages']) ? 'checked' :'')}}>
                                <label class="form-check-label" for="cancellation_policy">{{translate('Cancellation Policy')}}</label>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-check d-flex gap-1">
                                <input type="checkbox" class="form-check-input checkbox--input" name="pages[contact_us]" data-from="pages" data-id="contact-us" id="contact_us" {{!empty($template['pages']) && in_array('contact_us',$template['pages'])? 'checked': (empty($template['pages']) ? 'checked' :'')}}>
                                <label class="form-check-label" for="contact_us">{{translate('Contact Us')}}</label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                        <i class="fi fi-sr-lightbulb-on text-info"></i>
                        <span>
                            {{ translate('If you want to change, go to') }}
                            <a href="{{ route('admin.settings.pages-and-media.list') }}" class="text-decoration-underline fw-semibold">
                                {{ translate('Business Pages') }}
                            </a>
                        </span>
                    </div>
                </div>
            </div>
            <div class="bg-white border rounded p-3 d-flex flex-column gap-3 gap-sm-20">
              <div class="row g-3">
                    @foreach($socialMedia as $key=>$media)
                        <div class="col-sm-6">
                            <div class="form-check d-flex gap-1">
                                <input type="checkbox" class="form-check-input checkbox--input" name="social_media[{{$media['name']}}]" data-from="social-media" data-id="{{$media['name']}}" id="{{$media['name']}}" {{!empty($template['social_media']) && in_array($media['name'],$template['social_media'])? 'checked': (empty($template['social_media']) ? 'checked' :'')}}>
                                <label class="form-check-label"
                                       for="{{$media['name']}}">{{$media['name']}}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                    <i class="fi fi-sr-lightbulb-on text-info"></i>
                    <span>
                        {{ translate('If you want to change, go to') }} <a href="{{ route('admin.settings.pages-and-media.social-media') }}" class="text-decoration-underline fw-semibold">
                            {{ translate('Social Media Link') }}
                        </a>
                    </span>
                </div>
            </div>
    </div>

