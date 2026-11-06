<span id="custom-modal-default-text" data-ok="{{ translate('OK') }}"
      data-cancel="{{ translate('Cancel') }}"></span>
<audio id="myAudio">
    <source src="{{ dynamicAsset(path: 'public/assets/backend/sound/notification.mp3') }}" type="audio/mpeg">
</audio>

<span class="please_fill_out_this_field" data-text="{{ translate('Please fill out this field') }}"></span>
<span id="onerror-chatting"
      data-onerror-chatting="{{dynamicAsset(path: 'public/assets/back-end/img/image-place-holder.png')}}"></span>
<span id="onerror-user"
      data-onerror-user="{{dynamicAsset(path: 'public/assets/back-end/img/160x160/img1.jpg')}}"></span>
<span id="get-root-path-for-toggle-modal-image"
      data-path="{{dynamicAsset(path: 'public/assets/back-end/img/modal')}}"></span>
<span id="get-customer-list-route" data-action="{{route('vendor.customer.list')}}"></span>
<span id="get-search-product-route" data-action="{{route('vendor.retail.products.search-product')}}"></span>
<span id="get-orders-list-route" data-action="{{route('vendor.retail.orders.list', ['status' => 'all'])}}"></span>
<span class="system-default-country-code" data-value="{{ getWebConfig(name: 'country_code') ?? 'us' }}"></span>
<span id="message-select-word" data-text="{{ translate('Select') }}"></span>
<span id="message-yes-word" data-text="{{ translate('Yes') }}"></span>
<span id="message-no-word" data-text="{{ translate('No') }}"></span>
<span id="message-cancel-word" data-text="{{ translate('Cancel') }}"></span>
<span id="message-are-you-sure" data-text="{{ translate('Are you sure') }} ?"></span>
<span id="message-invalid-date-range" data-text="{{ translate('Invalid date range') }}"></span>
<span id="message-status-change-successfully" data-text="{{ translate('Status changed successfully') }}"></span>
<span id="message-are-you-sure-delete-this" data-text="{{ translate('Are you sure you want to delete this') }} ?"></span>
<span id="message-you-will-not-be-able-to-revert-this"
      data-text="{{ translate('You will not be able to revert this') }}"></span>
<span id="exceeds10MBSizeLimit" data-text="{{ translate('File exceeds 10MB size limit') }}"></span>
<span id="getChattingNewNotificationCheckRoute" data-route="{{ route('vendor.messages.new-notification') }}"></span>
<span id="get-search-vendor-product-for-clearance-route"
      data-action="{{route('vendor.retail.clearance-sale.search-product-for-clearance')}}"></span>
<span id="get-multiple-clearance-product-details-route"
      data-action="{{route('vendor.retail.clearance-sale.multiple-clearance-product-details')}}"></span>

<span id="get-stock-limit-status" data-action="{{route('vendor.retail.products.stock-limit-status')}}"></span>
<span id="get-product-stock-limit-title" data-title="{{translate('Warning')}}"></span>
<span id="get-product-stock-limit-image"
      data-warning-image="{{ dynamicAsset(path: 'public/assets/back-end/img/warning-2.png') }}"></span>
<span id="get-product-stock-limit-message"
      data-message-for-multiple="{{ translate('There is not enough quantity in stock').' . '.translate('Please check products in limited stock').'.' }}"
      data-message-for-three-plus-product="{{translate('More products have low stock') }}"
      data-message-for-one-product="{{translate('This product is low on stock')}}">
        </span>
<span id="get-product-stock-view"
      data-stock-limit-page="{{route('vendor.retail.products.stock-limit-list')}}"
>
        </span>
<span id="route-for-real-time-activities" data-route="{{ route('vendor.retail.dashboard.real-time-activities') }}"></span>
<span id="get-confirm-and-cancel-button-text-for-delete-all-products" data-sure="{{translate('Are you sure').'?'}}"
      data-text="{{translate('Want to clear all stock clearance products?').'!'}}"
      data-confirm="{{translate('Yes, Delete it')}}" data-cancel="{{translate('Cancel')}}"></span>

<span id="get-currency-symbol"
      data-currency-symbol="{{ getCurrencySymbol(currencyCode: getCurrencyCode(type: 'default')) }}"></span>

<span id="check-offcanvas-setup-guide" data-value="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' }}"></span>
<span id="get-initial-data-for-panel-time" data-value="{!! in_array(request()->ip(), ['127.0.0.1', '::1']) ? '15000' : '15000' !!}"></span>
