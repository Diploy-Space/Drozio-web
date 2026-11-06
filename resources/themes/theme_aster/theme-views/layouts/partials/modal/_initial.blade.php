@if($web_config['popup_banner'])
    @php($popupBannerUrl = $web_config['popup_banner']['url'])
    <div class="modal fade modal-popup-banner" id="initialModal" tabindex="-1"
         aria-label="{{ translate('Promotional banner') }}" aria-hidden="true" data-popup-banner>
        <div class="modal-dialog modal-dialog-centered justify-content-center">
            <div class="modal-content">
                <button type="button" class="btn-close outside" data-bs-dismiss="modal"
                        aria-label="{{ translate('Close') }}"></button>
                <div class="modal-body p-0">
                    <div class="pb-media">
                        <img class="pb-image dark-support" alt=""
                             src="{{ getStorageImages(path: $web_config['popup_banner']['photo_full_url'], type:'banner') }}">
                        @if($popupBannerUrl)
                            <a class="pb-link" href="{{ $popupBannerUrl }}"
                               aria-label="{{ translate('View offer') }}"></a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
