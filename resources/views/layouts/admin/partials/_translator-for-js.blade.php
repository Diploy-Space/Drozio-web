<span id="custom-modal-default-text" data-ok="{{ translate('OK') }}"
      data-cancel="{{ translate('Cancel') }}"></span>
<span id="call-demo-message" data-title="{{ translate('Information') }}" data-text="{{ translate('This option is disabled for demo').'!' }}"></span>

<span id="get-update-status-message" data-text="{{ translate(':item updated successfully', ['item' => translate('Status')]) }}"
      data-error="{{ translate('Status update failed') }}">
</span>
<span id="get-update-success-message" data-text="{{ translate('Updated successfully') }}"></span>
<span id="get-extension-text-message" data-text="{{ translate('Extension') }}"></span>
<span id="get-deleted-message" data-text="{{ translate('Deleted successfully') }}"></span>
<span id="get-confirm-and-cancel-button-text" data-sure="{{ translate('Are you sure').'?' }}"
      data-delete-text="{{ translate('Once deleted you will not be able to recover this').'!' }}"
      data-confirm="{{ translate('Yes') }}" data-cancel="{{ translate('No') }}"></span>
<span id="get-confirm-and-cancel-button-text-for-delete"
      data-sure="{{ translate('Are you sure you want to delete this').'?' }}"
      data-text="{{ translate('You will not be able to revert this').'!' }}"
      data-confirm="{{ translate('Yes, Delete it') }}" data-cancel="{{ translate('Cancel') }}"></span>
<span id="get-confirm-and-cancel-button-text-for-blog-category-delete"
      data-sure="{{ translate('Are you sure you want to delete this :item', ['item' => translate('Category')]).'?' }}"
      data-image="{{dynamicAsset(path: 'public/assets/back-end/img/delete-blog-category.png') }}"
      data-text="{{ translate('Deleting removes it from the Category list permanently.').'!' }}"
      data-confirm="{{ translate('Yes Delete') }}" data-cancel="{{ translate('Not Now') }}" data-cancel-color="#F4F4F4"
      data-confirm-color="#FF6767"></span>
<span id="get-copy-to-clipboard" data-success="{{ translate('Copied to clipboard') }}"></span>
<span id="get-input-file-text" data-error="{{ translate('The file upload field is required') }}"></span>
<span id="get-success-text" data-success="{{ translate('Updated successfully') }}"></span>
<span id="get-email-and-otp-verification-info-message"
      data-info="{{ translate('Both Phone & Email verification cannot be active at a time') }}"></span>
<span id="get-select-country-text" data-text="{{ translate('Select Country') }}"></span>
<span id="get-date-range-message" data-error="{{ translate('Invalid date range').'!' }}"></span>
<span id="get-minimum-amount-message"
      data-error="{{ translate('Minimum amount cannot be greater than maximum amount').'!' }}"></span>
<span id="get-file-upload-field-required-message"
      data-error="{{ translate('The file upload field is required').'!' }}"></span>
<span id="select-minimum-one-box-message" data-warning="{{ translate('Select minimum one selection box') }}"></span>
<span id="role-name-required-message" data-error="{{ translate('Name field is required') }}"></span>
<span id="get-product-status-message"
      data-error="{{ translate('Status update failed').'.'.' '.translate('Product must be approved').'.' }}"></span>
<span id="get-featured-status-message" data-success="{{ translate(':item updated successfully', ['item' => translate('Featured status')]) }}"></span>
<span id="get-multiple-image-text"
      data-on-extension-err="{{ translate('Please only input a png or jpg type file') }}"
      data-on-size-err="{{ translate('File size is too large') }}">
</span>

<span id="get-logout-text" data-title="{{ translate('Do you want to sign out').'?' }}"
      data-confirm="{{ translate('Yes') }}" data-cancel="{{ translate('No') }}"
      data-deny="{{ translate('Do not Logout') }}"></span>
<span id="get-select-product-text" data-text="{{ translate('Select Product') }}"></span>
<span id="change-language-message" data-text="{{ translate('Want to change this language').'?' }}"></span>
<span id="get-quantity-check-message"
      data-warning="{{ translate('Please enter a valid integer for current stock') }}"></span>
<span id="password-error-message" data-max-character="{{ translate('At least 8 characters').'.' }}"
      data-uppercase-character="{{ translate('At least one uppercase letter').'(A...Z)'.'.' }}"
      data-lowercase-character="{{ translate('At least one lowercase letter').'(a...z)'.'.' }}"
      data-number="{{ translate('At least one number').'(0...9)'.'.' }}"
      data-symbol="{{ translate('At least one symbol').'(!...%)'.'.' }}"
      data-confirm-password-match="{{ translate('Confirm password does not match') }}"
></span>
<span id="get-example-text" data-example="{{ translate('Ex') }}"></span>
<span id="download-path-not-found-message" data-message="{{ translate('Not found').'!!!' }}"></span>
<span id="get-product-stock-message"
      data-out-of-stock="{{ translate('Out of Stock').'. '.translate('Please check your inventory and update') }}"
      data-limited-stock="{{ translate('There is not enough quantity in stock').'. '.translate('Please check your inventory') }}">
</span>

<span id="message-loading-word" data-text="{{ translate('Loading') }}"></span>
<span id="message-select-word" data-text="{{ translate('Select') }}"></span>
<span id="message-yes-word" data-text="{{ translate('Yes') }}"></span>
<span id="message-no-word" data-text="{{ translate('No') }}"></span>
<span id="message-cancel-word" data-text="{{ translate('Cancel') }}"></span>
<span id="message-are-you-sure" data-text="{{ translate('Are you sure') }} ?"></span>
<span id="message-invalid-date-range" data-text="{{ translate('Invalid date range') }}"></span>
<span id="message-status-change-successfully" data-text="{{ translate(':item changed successfully', ['item' => translate('Status')]) }}"></span>
<span id="message-are-you-sure-delete-this" data-text="{{ translate('Are you sure you want to delete this') }} ?"></span>
<span id="message-you-will-not-be-able-to-revert-this"
      data-text="{{ translate('You will not be able to revert this') }}"></span>

<span class="please_fill_out_this_field" data-text="{{ translate('Please fill out this field') }}"></span>
<span class="get-application-environment-mode"
      data-value="{{ config('app.mode') == 'demo' ? 'demo' : 'live' }}"></span>
<span id="get-currency-symbol" data-currency-symbol="{{ getCurrencySymbol(currencyCode: getCurrencyCode(type: 'default')) }}"></span>

<span id="get-product-stock-limit-title" data-title="{{ translate('Warning') }}"></span>
<span id="get-product-stock-limit-image" data-warning-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/warning-2.png') }}"></span>
<span id="get-product-stock-limit-message" data-message-for-multiple="{{ translate('There is not enough quantity in stock') . ' . ' . translate('Please check products in limited stock') . '.' }}"
      data-message-for-three-plus-product="{{ translate('More products have low stock') }}"
      data-message-for-one-product="{{ translate('This product is low on stock') }}">
        </span>

<span class="system-default-country-code" data-value="{{ getWebConfig(name: 'country_code') ?? 'us' }}"></span>
<span id="get-confirm-and-cancel-button-text-for-delete-all-products"
      data-sure ="{{ translate('Are you sure') . '?' }}"
      data-text="{{ translate('Want to clear all stock clearance products?') . '!' }}"
      data-confirm="{{ translate('Yes, Delete it') }}" data-cancel="{{ translate('Cancel') }}"></span>
<span id="get-confirm-and-cancel-button-text-for-delete-country-name" data-sure ="{{ translate('Are you sure').'?' }}"
      data-text="{{ translate('Want to remove country name?').'!' }}"
      data-confirm="{{ translate('Yes, Delete it') }}" data-cancel="{{ translate('Cancel') }}"></span>
<span id="get-confirm-and-cancel-button-text-for-delete" data-sure ="{{ translate('Are you sure you want to delete this').'?' }}"
      data-text="{{ translate('You will not be able to revert this').'!' }}"
      data-confirm="{{ translate('Yes, Delete it') }}" data-cancel="{{ translate('Cancel') }}"></span>
<span id="get-confirm-and-cancel-button-text" data-sure ="{{ translate('Are you sure').'?' }}"
      data-delete-text="{{ translate('Once deleted you will not be able to recover this').'!' }}"
      data-confirm="{{ translate('Yes') }}" data-cancel="{{ translate('No') }}"></span>

<span id="get-root-path-for-toggle-modal-image"
      data-path="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal') }}"></span>
<span id="get-decimal-point" data-decimal-point="{{ getWebConfig('decimal_point_settings') }}"></span>

<span id="check-offcanvas-setup-guide" data-value="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' }}"></span>
<span id="get-initial-data-for-panel-time" data-value="{!! in_array(request()->ip(), ['127.0.0.1', '::1']) ? '15000' : '15000' !!}"></span>

<span id="imageUploadMaxSize" data-max-size="{{ getFileUploadMaxSize() }}"></span>
