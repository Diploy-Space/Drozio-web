<div class="modal fade" id="contact_sellerModal" tabindex="-1" aria-labelledby="contact_sellerModalLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header px-sm-5 pb-1">
                <h5 class="text-capitalize" id="contact_sellerModalLabel">
                    @if(isset($shop) && isset($user_type) && $user_type == 'admin')
                        {{ translate('Contact with') }} {{getInHouseShopConfig(key:'name') }}
                    @elseif(isset($shop) && isset($user_type) && $user_type == 'seller')
                        {{ translate('Contact with') }} {{ $shop['name'] }}
                        @include('theme-views.partials._verified-badge', ['isVerified' => $isVerified ?? false])
                    @endif
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-sm-5">
                <form action="{{route('messages') }}" method="post" id="contact_with_seller_form"
                      data-success-message="{{ translate('Sent successfully') }}">
                    @csrf
                    <input value="{{ isset($user_type) && $user_type == 'admin' ? 0 : $shop['seller_id'] }}" name="vendor_id" hidden>
                    <textarea name="message" class="form-control min-height-100px max-height-200px" required
                              placeholder="{{ translate('Type your message') }}"></textarea>
                    <div class="d-flex justify-content-end mt-3 gap-3 align-items-center">
                        <div>
                            <a href="{{route('chat', ['type' => 'vendor'])}}"
                            class="text-primary btn-link">{{ translate('Go to chatbox') }}</a>
                        </div>
                        <button class="btn btn-primary text-capitalize">
                            {{ translate('Send') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
