<div class="second-el d--none">
     <div class="container">
         <div class="row justify-content-center">
             <div class="col-lg-8">
                 <div class="card">
                     <div class="card-body">
                         <h3 class="mb-4 text-capitalize">{{ translate('Create an account') }}</h3>
                         <div class="border p-3 p-xl-4 rounded">
                             <h4 class="mb-3 text-capitalize">{{ translate('Vendor Information') }}</h4>

                             <div class="row">
                                 <div class="col-sm-6">
                                     <div class="form-group mb-4">
                                         <label class="mb-2 text-capitalize"
                                             for="f_name">{{ translate('First Name') }}
                                             <span class="text-danger">*</span></label>
                                         <input class="form-control" type="text" name="f_name"
                                             placeholder="{{ translate('Ex') . ': John' }}" required>
                                     </div>
                                     <div class="form-group mb-4">
                                         <label class="mb-2 text-capitalize" for="l_name">
                                             {{ translate('Last Name') }}
                                             <span class="text-danger">*</span>
                                         </label>
                                         <input class="form-control" type="text" name="l_name"
                                             placeholder="{{ translate('Ex') . ': Doe' }}" required>
                                     </div>
                                 </div>
                                 <div class="col-sm-6">
                                     <div class="">
                                         <div class="d-flex flex-column gap-3 align-items-center">
                                             <div class="upload-file">
                                                 <input type="file" class="upload-file__input" name="image"
                                                        data-max-size="2"
                                                     accept="{{getFileUploadFormats(skip: '.svg,.webp,.gif')}}" >
                                                 <div class="upload-file__img">
                                                     <div class="temp-img-box">
                                                         <div class="d-flex align-items-center flex-column gap-2">
                                                             <i class="bi bi-upload fs-30"></i>
                                                             <div class="fs-12 text-muted text-capitalize">
                                                                 {{ translate('Upload File') }}
                                                             </div>
                                                         </div>
                                                     </div>
                                                     <img src="#" class="dark-support img-fit-contain border"
                                                         alt="" hidden>
                                                 </div>
                                             </div>

                                             <div class="d-flex flex-column gap-1 upload-img-content text-center">
                                                 <h6 class="text-uppercase mb-1">
                                                     {{ translate('Vendor image') }}
                                                     <span class="text-danger">*</span>
                                                 </h6>
                                                 <div class="text-muted text-capitalize">
                                                     {{ translate('Image ratio') . ' ' . '1:1' }}
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>

                         <div class="border p-3 p-xl-4 rounded mt-4">
                             <h4 class="mb-3 text-capitalize">{{ translate('Shop Information') }}</h4>
                             <div class="form-group mb-4">
                                 <label class="mb-2 text-capitalize" for="shop_name">
                                     {{ translate('Shop Name') }}
                                     <span class="text-danger">*</span>
                                 </label>
                                 <input class="form-control" type="text" name="shop_name"
                                     placeholder="{{ translate('Ex') . ': XYZ Shop' }}" required>
                             </div>
                             <div class="form-group mb-4">
                                 <label class="mb-2 text-capitalize" for="shop_address">
                                     {{ translate('Shop Address') }}
                                     <span class="text-danger">*</span>
                                 </label>
                                 <textarea class="form-control" name="shop_address" rows="4" placeholder="{{ translate('Shop Address') }}"
                                     required></textarea>
                             </div>

                             <div class="border p-3 p-xl-4 rounded mb-4">
                                 <div class="d-flex flex-column gap-3 align-items-center">
                                     <div class="upload-file">
                                         <input type="file" class="upload-file__input" name="logo"
                                                data-max-size="{{ getFileUploadMaxSize() }}"
                                             accept="{{getFileUploadFormats(skip: '.svg,.gif,.webp')}}">
                                         <div class="upload-file__img">
                                             <div class="temp-img-box">
                                                 <div class="d-flex align-items-center flex-column gap-2">
                                                     <i class="bi bi-upload fs-30"></i>
                                                     <div class="fs-12 text-muted text-capitalize">
                                                         {{ translate('Upload File') }}
                                                     </div>
                                                 </div>
                                             </div>
                                             <img src="#" class="dark-support img-fit-contain border"
                                                 alt="" hidden>
                                         </div>
                                     </div>

                                     <div class="d-flex flex-column gap-1 upload-img-content text-center">
                                         <h6 class="text-uppercase mb-1">
                                             {{ translate('Shop Image') }}
                                             <span class="text-danger">*</span>
                                         </h6>
                                         <div class="text-muted text-capitalize">
                                             {{ translate('Image ratio') . ' ' . '1:1' }}
                                         </div>
                                         <div class="text-muted text-capitalize">
                                             {{ translate('Image Size : Max 2 MB') }}
                                         </div>
                                     </div>
                                 </div>
                             </div>

                             <div class="border p-3 p-xl-4 rounded mb-4">
                                 <div class="d-flex flex-column gap-3 align-items-center">
                                     <div class="upload-file">
                                         <input type="file" class="upload-file__input" name="banner"
                                                data-max-size="{{ getFileUploadMaxSize() }}"
                                             accept="{{getFileUploadFormats(skip: '.svg,.gif')}}" >
                                         <div class="upload-file__img style--two">
                                             <div class="temp-img-box">
                                                 <div class="d-flex align-items-center flex-column gap-2">
                                                     <i class="bi bi-upload fs-30"></i>
                                                     <div class="fs-12 text-muted text-capitalize">
                                                         {{ translate('Upload File') }}
                                                     </div>
                                                 </div>
                                             </div>
                                             <img src="#" class="dark-support img-fit-contain border"
                                                 alt="" hidden>
                                         </div>
                                     </div>
                                     <div class="d-flex flex-column gap-1 upload-img-content text-center">
                                         <h6 class="text-uppercase mb-1">
                                             {{ translate('Shop Banner') }}
                                             <span class="text-danger">*</span>
                                         </h6>
                                         <div class="text-muted text-capitalize">
                                             {{ translate('Image ratio') . ' ' . '2:1' }}
                                         </div>
                                         <div class="text-muted text-capitalize">
                                             {{ translate('Image Size : Max 2 MB') }}
                                         </div>
                                     </div>
                                 </div>
                             </div>
                             <div class="border p-3 p-xl-4 rounded mb-4">
                                 <div class="d-flex flex-column gap-3 align-items-center">
                                     <div class="upload-file">
                                         <input type="file" class="upload-file__input" name="bottom_banner"
                                                data-max-size="{{ getFileUploadMaxSize() }}"
                                                accept="{{getFileUploadFormats(skip: '.svg,.gif')}}">
                                         <div class="upload-file__img style--two">
                                             <div class="temp-img-box">
                                                 <div class="d-flex align-items-center flex-column gap-2">
                                                     <i class="bi bi-upload fs-30"></i>
                                                     <div class="fs-12 text-muted text-capitalize">
                                                         {{ translate('Upload File') }}
                                                     </div>
                                                 </div>
                                             </div>
                                             <img src="#" class="dark-support img-fit-contain border"
                                                 alt="" hidden>
                                         </div>
                                     </div>
                                     <div class="d-flex flex-column gap-1 upload-img-content text-center">
                                         <h6 class="text-uppercase mb-1">
                                             {{ translate('Shop Secondary Banner') }}
                                             <span class="text-danger">*</span>
                                         </h6>
                                         <div class="text-muted text-capitalize">
                                             {{ translate('Image ratio') . ' ' . '2:1' }}
                                         </div>
                                         <div class="text-muted text-capitalize">
                                             {{ translate('Image Size : Max 2 MB') }}
                                         </div>
                                     </div>
                                 </div>
                             </div>
                             <div class="border p-3 p-xl-4 rounded">
                                 <div class="row gy-4">
                                     <div class="col-lg-6">
                                         <div>
                                             <h4 class="mb-3 text-capitalize">{{ translate('Business TIN') }}</h4>
                                             <div class="form-group mb-4">
                                                 <label class="mb-2 text-capitalize"
                                                     for="">{{ translate('Taxpayer identification number(TIN)') }}
                                                 </label>
                                                 <input class="form-control" type="text"
                                                     name="tax_identification_number"
                                                     placeholder="{{ translate('Type your user name') }}">
                                             </div>
                                             <div class="form-group mb-4">
                                                 <label class="mb-2 text-capitalize" for="">
                                                     {{ translate('Expire Date') }}
                                                 </label>
                                                 <div class="position-relative">
                                                     <span class="bi bi-calendar icon-absolute-on-right"></span>
                                                     <input type="text"
                                                         class="js-daterangepicker_single-date-with-placeholder form-control"
                                                         placeholder="{{ translate('Click to add date') }}"
                                                         name="tin_expire_date" value="" readonly>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="col-lg-6">
                                         <div class="form-group mb-0">
                                             <div class="d-flex justify-content-center document-upload-container">
                                                <div class="document-file-assets"
                                                    data-picture-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/picture.svg') }}"
                                                    data-document-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/document.svg') }}"
                                                    data-blank-thumbnail="{{ dynamicAsset(path: 'public/assets/back-end/img/file-placeholder.png') }}">
                                                </div>
                                                 <div class="document-upload-wrapper doc-upload-wrapper">
                                                     <input type="file" name="tin_certificate"
                                                            data-max-size="{{ getFileUploadMaxSize(type: 'file') }}"
                                                            data-validation-error-msg="{{ translate('File size is too large Maximum').' '.getFileUploadMaxSize(type: 'file').' '.'MB' }}"
                                                         class="document_input" accept=".pdf,.doc,.jpg">
                                                     <div class="textbox">
                                                         <img class="svg" alt=""
                                                             src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/doc-upload-icon.svg') }}">
                                                         <p class="fs-12 mb-0">
                                                             {{ translate('Select a file or') }}
                                                             <span class="font-weight-semibold">
                                                                 {{ translate('Drag and Drop here') }}
                                                             </span>
                                                         </p>
                                                     </div>
                                                 </div>
                                             </div>
                                             <div class="d-flex flex-column gap-1 upload-img-content text-center mt-3">
                                                 <h6 class="text-uppercase mb-1">{{ translate('TIN Certificate') }}
                                                 </h6>
                                                 <div class="text-muted text-capitalize">
                                                     {{ translate('Pdf, doc, jpg. file size : max 5 MB') }}
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>

                         @php($recaptcha = getWebConfig(name: 'recaptcha'))

                         @if(isset($recaptcha) && $recaptcha['status'] == 1)
                             <div class="dynamic-default-and-recaptcha-section">
                                 <input type="hidden" name="g-recaptcha-response" class="render-grecaptcha-response" data-action="register"
                                        data-input="#login-default-captcha-section"
                                        data-default-captcha="#login-default-captcha-section"
                                 >

                                 <div class="default-captcha-container d-none" id="login-default-captcha-section"
                                      data-placeholder="{{ translate('Enter ReCAPTCHA value') }}"
                                      data-base-url="{{ route('g-recaptcha-session-store') }}"
                                      data-session="{{ 'vendorRecaptchaSessionKey' }}"
                                 >
                                 </div>
                             </div>
                         @else
                             <div class="default-captcha-container"
                                  data-placeholder="{{ translate('Enter ReCAPTCHA value') }}"
                                  data-base-url="{{ route('g-recaptcha-session-store') }}"
                                  data-session="{{ 'vendorRecaptchaSessionKey' }}"
                             >
                             </div>
                         @endif

                         <div class="col-12">
                             <label class="custom-checkbox align-items-center">
                                 <input type="checkbox" class="form-check-input" id="terms-checkbox">
                                 <span class="form-check-label">
                                     {{ translate('I agree with the') }}
                                     <a href="{{ route('business-page.view', ['slug' => 'terms-and-conditions']) }}"
                                         target="_blank" class="text-decoration-underline color-bs-primary-force">
                                         {{ translate('Terms & Conditions') }}
                                     </a>
                                 </span>
                             </label>
                         </div>
                         <div class="d-flex justify-content-end mt-4 mb-2 gap-2">
                             <button type="button" class="btn btn-secondary back-to-main-page">
                                 {{ translate('Back') }}
                             </button>
                             <button type="submit" class="btn btn-primary disabled" id="vendor-apply-submit">
                                 {{ translate('Submit') }}
                             </button>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
     </div>
 </div>
