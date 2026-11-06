@if($web_config['popup_banner'])
    @php($popupBannerUrl = $web_config['popup_banner']['url'])
    <div class="modal fade modal-popup-banner" id="popup-modal" tabindex="-1" role="dialog"
         aria-label="{{ translate('Promotional banner') }}" aria-hidden="true" data-popup-banner>
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="pb-close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                    <span aria-hidden="true"></span>
                </button>
                <div class="pb-media">
                    <img class="pb-image" alt=""
                         src="{{ getStorageImages(path: $web_config['popup_banner']['photo_full_url'], type: 'banner') }}">
                    @if($popupBannerUrl)
                        <a class="pb-link" href="{{ $popupBannerUrl }}"
                           aria-label="{{ translate('View offer') }}"></a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
