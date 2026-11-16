<div class="second-el d--none">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h3 class="mb-4">{{ translate('Create an account') }}</h3>
                        <div class="border p-3 p-xl-4 rounded">
                            <h4 class="mb-3">{{ translate('Vendor Information') }}</h4>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group mb-4">
                                        <label for="f_name">{{ translate('First Name') }} <span
                                                class="text-danger">*</span></label>
                                        <input class="form-control" type="text" name="f_name"
                                            placeholder="{{ translate('Ex') . ': John' }}" required>
                                    </div>
                                    <div class="form-group mb-4">
                                        <label for="l_name">{{ translate('Last Name') }} <span
                                                class="text-danger">*</span></label>
                                        <input class="form-control" type="text" name="l_name"
                                            placeholder="{{ translate('Ex') . ': Doe' }}" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="d-flex flex-column gap-3 align-items-center">
                                        <div class="upload-file">
                                            <input type="file" class="upload-file__input" name="image"
                                                   data-max-size="{{ getFileUploadMaxSize() }}"
                                                accept="{{ getFileUploadFormats(skip: '.svg') }}" >
                                            <div class="upload-file__img">
                                                <div class="temp-img-box">
                                                    <div class="d-flex align-items-center flex-column gap-2">
                                                        <i class="tio-upload fs-30"></i>
                                                        <div class="fs-12 text-muted text-capitalize">
                                                            {{ translate('Upload File') }}</div>
                                                    </div>
                                                </div>
                                                <img src="#" class="dark-support img-fit-contain border"
                                                    alt="" hidden>
                                            </div>
                                        </div>

                                        <div class="d-flex flex-column gap-1 upload-img-content text-center">
                                            <h6 class="text-uppercase mb-1 fs-14">
                                                {{ translate('Vendor image') }}
                                                <span class="text-danger">*</span>
                                            </h6>
                                            <div class="text-muted text-capitalize fs-12">
                                                {{ translate('Image ratio') . ' ' . '1:1' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border p-3 p-xl-4 rounded mt-4">
                            <h4 class="mb-3 text-capitalize">{{ translate('Shop Information') }}</h4>

                            <div class="form-group mb-4">
                                <label for="store_name" class="text-capitalize">{{ translate('Shop Name') }} <span
                                        class="text-danger">*</span></label>
                                <input class="form-control" type="text" id="shop_name" name="shop_name"
                                    placeholder="{{ translate('Ex') }}: XYZ store" required>
                            </div>
                            <div class="form-group mb-4">
                                <label for="store_address" class="text-capitalize">{{ translate('Shop address') }}
                                    <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="shop_address" id="shop_address" rows="4"
                                    placeholder="{{ translate('Shop address') }}" required></textarea>
                            </div>

                            <div class="border p-3 p-xl-4 rounded mb-4">
                                <div class="d-flex flex-column gap-3 align-items-center">
                                    <div class="upload-file">
                                        <input type="file" class="upload-file__input" name="logo" data-max-size="{{ getFileUploadMaxSize() }}"  accept="{{getFileUploadFormats(skip: '.svg')}}"
                                            >
                                        <div class="upload-file__img">
                                            <div class="temp-img-box">
                                                <div class="d-flex align-items-center flex-column gap-2">
                                                    <i class="tio-upload fs-30"></i>
                                                    <div class="fs-12 text-muted text-capitalize">
                                                        {{ translate('Upload File') }}</div>
                                                </div>
                                            </div>
                                            <img src="#" class="dark-support img-fit-contain border"
                                                alt="" hidden>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column gap-1 upload-img-content text-center">
                                        <h6 class="text-uppercase mb-1 fs-14">
                                            {{ translate('Upload Logo') }}
                                            <span class="text-danger">*</span>
                                        </h6>
                                        <div class="text-muted text-capitalize fs-12">
                                            {{ translate('Image ratio') . ' ' . '1:1' }}</div>
                                        <div class="text-muted text-capitalize fs-12">
                                            {{ translate('Image Size : Max 2 MB') }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="border p-3 p-xl-4 rounded">
                                <div class="d-flex flex-column gap-3 align-items-center">
                                    <div class="upload-file">
                                        <input type="file" class="upload-file__input" name="banner" data-max-size="{{ getFileUploadMaxSize() }}"   accept="{{getFileUploadFormats(skip: '.svg')}}"
                                            required>
                                        <div class="upload-file__img style--two">
                                            <div class="temp-img-box">
                                                <div class="d-flex align-items-center flex-column gap-2">
                                                    <i class="tio-upload fs-30"></i>
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
                                        <h6 class="text-uppercase mb-1 fs-14">
                                            {{ translate('Upload banner') }}
                                            <span class="text-danger">*</span>
                                        </h6>
                                        <div class="text-muted text-capitalize fs-12">
                                            {{ translate('Image ratio') . ' ' . '2:1' }}</div>
                                        <div class="text-muted text-capitalize fs-12">
                                            {{ translate('Image Size : Max 2 MB') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border p-3 p-xl-4 rounded mt-4" id="vendor-kyc-section">
                            <h4 class="mb-3 text-capitalize">{{ translate('Vendor KYC & Business Verification') }}</h4>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2 text-capitalize">{{ translate('Business Type') }} <span class="text-danger">*</span></label>
                                        <select name="business_type" class="form-control" required>
                                            <option value="">{{ translate('Select business type') }}</option>
                                            <option value="proprietorship">{{ translate('Proprietorship') }}</option>
                                            <option value="partnership">{{ translate('Partnership') }}</option>
                                            <option value="llp">{{ translate('LLP') }}</option>
                                            <option value="private_limited">{{ translate('Private Limited') }}</option>
                                            <option value="public_limited">{{ translate('Public Limited') }}</option>
                                            <option value="other">{{ translate('Other') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2 text-capitalize">{{ translate('Registration Type') }} <span class="text-danger">*</span></label>
                                        <select name="registration_type" id="kyc_registration_type" class="form-control" required>
                                            <option value="">{{ translate('Select registration type') }}</option>
                                            <option value="gst">{{ translate('GST Registered') }}</option>
                                            <option value="non_gst">{{ translate('Non-GST') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2 text-capitalize">{{ translate('Legal Name') }} <span class="text-danger">*</span></label>
                                        <input class="form-control" type="text" name="legal_name" placeholder="{{ translate('Legal business name') }}" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2 text-capitalize">{{ translate('Trade Name') }} <span class="text-danger">*</span></label>
                                        <input class="form-control" type="text" name="trade_name" placeholder="{{ translate('Trade name') }}" required>
                                    </div>
                                </div>
                                <div class="col-sm-6 kyc-gst-field d-none">
                                    <div class="form-group mb-4">
                                        <label class="mb-2 text-capitalize">{{ translate('GST Number') }} <span class="text-danger">*</span></label>
                                        <input class="form-control" type="text" name="gst_number" placeholder="{{ translate('GST number') }}">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2 text-capitalize">{{ translate('PAN Number') }} <span class="text-danger">*</span></label>
                                        <input class="form-control" type="text" name="pan_number" placeholder="{{ translate('PAN number') }}" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2 text-capitalize">{{ translate('PAN Holder Name') }} <span class="text-danger">*</span></label>
                                        <input class="form-control" type="text" name="pan_holder_name" placeholder="{{ translate('PAN holder name') }}" required>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <h5 class="mb-3">{{ translate('Business Address') }}</h5>
                            <div class="row">
                                <div class="col-sm-6"><div class="form-group mb-4"><label class="mb-2">{{ translate('Address Line 1') }} <span class="text-danger">*</span></label><input class="form-control" type="text" name="business_address_line1" required></div></div>
                                <div class="col-sm-6"><div class="form-group mb-4"><label class="mb-2">{{ translate('Address Line 2') }}</label><input class="form-control" type="text" name="business_address_line2"></div></div>
                                <div class="col-sm-6 col-md-3"><div class="form-group mb-4"><label class="mb-2">{{ translate('City') }} <span class="text-danger">*</span></label><input class="form-control" type="text" name="business_city" required></div></div>
                                <div class="col-sm-6 col-md-3"><div class="form-group mb-4"><label class="mb-2">{{ translate('State') }} <span class="text-danger">*</span></label><input class="form-control" type="text" name="business_state" required></div></div>
                                <div class="col-sm-6 col-md-3"><div class="form-group mb-4"><label class="mb-2">{{ translate('PIN Code') }} <span class="text-danger">*</span></label><input class="form-control" type="text" name="business_pincode" required></div></div>
                                <div class="col-sm-6 col-md-3"><div class="form-group mb-4"><label class="mb-2">{{ translate('Country') }} <span class="text-danger">*</span></label><input class="form-control" type="text" name="business_country" value="India" required></div></div>
                            </div>
                            <hr>
                            <h5 class="mb-3">{{ translate('Bank Account Verification') }}</h5>
                            <div class="row">
                                <div class="col-sm-6"><div class="form-group mb-4"><label class="mb-2">{{ translate('Account Holder Name') }} <span class="text-danger">*</span></label><input class="form-control" type="text" name="bank_account_name" required></div></div>
                                <div class="col-sm-6"><div class="form-group mb-4"><label class="mb-2">{{ translate('Bank Name') }} <span class="text-danger">*</span></label><input class="form-control" type="text" name="bank_name" required></div></div>
                                <div class="col-sm-6"><div class="form-group mb-4"><label class="mb-2">{{ translate('Branch') }} <span class="text-danger">*</span></label><input class="form-control" type="text" name="bank_branch" required></div></div>
                                <div class="col-sm-6"><div class="form-group mb-4"><label class="mb-2">{{ translate('Account Number') }} <span class="text-danger">*</span></label><input class="form-control" type="text" name="bank_account_no" required></div></div>
                                <div class="col-sm-6"><div class="form-group mb-4"><label class="mb-2">{{ translate('IFSC') }} <span class="text-danger">*</span></label><input class="form-control" type="text" name="bank_ifsc" required></div></div>
                            </div>
                            <hr>
                            <h5 class="mb-3">{{ translate('KYC Documents') }}</h5>
                            <div class="row g-3">
                                <div class="col-md-6"><div class="form-group"><label class="mb-2">{{ translate('PAN Card') }} <span class="text-danger">*</span></label><input type="file" name="kyc_pan_card" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required></div></div>
                                <div class="col-md-6 kyc-gst-field d-none"><div class="form-group"><label class="mb-2">{{ translate('GST Certificate') }} <span class="text-danger">*</span></label><input type="file" name="kyc_gst_certificate" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"></div></div>
                                <div class="col-md-6"><div class="form-group"><label class="mb-2">{{ translate('Business Address Proof') }} <span class="text-danger">*</span></label><input type="file" name="kyc_address_proof" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required></div></div>
                                <div class="col-md-6"><div class="form-group"><label class="mb-2">{{ translate('Bank Proof (Cheque/Passbook)') }} <span class="text-danger">*</span></label><input type="file" name="kyc_bank_proof" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required></div></div>
                                <div class="col-md-6"><div class="form-group"><label class="mb-2">{{ translate('Business Registration Proof') }} <span class="text-danger">*</span></label><input type="file" name="kyc_business_registration" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required></div></div>
                            </div>
                        </div>
                        <script>
                        (function(){
                            function toggleKycGst(){
                                var v = document.getElementById('kyc_registration_type');
                                var isGst = v && v.value === 'gst';
                                document.querySelectorAll('#vendor-kyc-section .kyc-gst-field').forEach(function(el){
                                    el.classList.toggle('d-none', !isGst);
                                    var inp = el.querySelector('input,select,textarea');
                                    if(inp){
                                        if(isGst){ inp.setAttribute('required','required'); }
                                        else { inp.removeAttribute('required'); if(inp.type==='file'){ inp.value=''; } }
                                    }
                                });
                            }
                            document.addEventListener('change', function(e){ if(e.target && e.target.id==='kyc_registration_type'){ toggleKycGst(); } });
                            document.addEventListener('DOMContentLoaded', toggleKycGst);
                            toggleKycGst();
                        })();
                        </script>

                        <div class="border p-3 p-xl-4 rounded mt-2">
                            <div class="row gy-4">
                                <div class="col-lg-6">
                                    <div>
                                        <h4 class="mb-3 text-capitalize">
                                            {{ translate('Business TIN') }}
                                        </h4>
                                        <div class="form-group mb-4">
                                            <label class="mb-2 text-capitalize" for="">
                                                {{ translate('Taxpayer identification number(TIN)') }}
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
                                                <span class="fi fi-sr-calendar icon-absolute-on-right"></span>
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
                                                    <img class="svg"
                                                        src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/doc-upload-icon.svg') }}"
                                                        alt="">
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
                                            <h6 class="text-uppercase mb-1">
                                                {{ translate('TIN Certificate') }}
                                            </h6>
                                            <div class="text-muted text-capitalize">
                                                {{ translate('Pdf, doc, jpg. file size : max 5 MB') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="my-2">
                            @php($recaptcha = getWebConfig(name: 'recaptcha'))

                            @if(isset($recaptcha) && $recaptcha['status'] == 1)
                                <div class="dynamic-default-and-recaptcha-section">
                                    <input type="hidden" name="g-recaptcha-response" class="render-grecaptcha-response"
                                           data-input="#login-default-captcha-section"
                                           data-default-captcha="#login-default-captcha-section" data-action="register"
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
                        </div>

                        <div class="d-flex justify-content-start mt-2">
                            <label class="custom-checkbox align-items-center">
                                <input type="checkbox" class="" id="terms-checkbox">
                                <span class="form-check-label">
                                    {{ translate('I agree with the') }}
                                    <a href="{{ route('business-page.view', ['slug' => 'terms-and-conditions']) }}"
                                        target="_blank" class="text-underline color-bs-primary-force">
                                        {{ translate('Terms & Conditions') }}
                                    </a>
                                </span>
                            </label>
                        </div>
                        <div class="d-flex justify-content-end mb-2 gap-2">
                            <button type="button" class="btn btn-secondary back-to-main-page">
                                {{ translate('Back') }} </button>
                            <button type="submit" class="btn btn--primary" id="vendor-apply-submit"
                                disabled="disabled"> {{ translate('Submit') }} </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
