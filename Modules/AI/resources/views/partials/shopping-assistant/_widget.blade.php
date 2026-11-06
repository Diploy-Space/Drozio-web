<link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/front-end/ai/ai-shopping-assistant.css') }}{{ $shoppingAssistant['cssVersion'] ? '?v=' . $shoppingAssistant['cssVersion'] : '' }}">

<span id="hexa-ai-config"
      data-cart-mode="{{ $shoppingAssistant['cartMode'] }}"
      data-route-sessions="{{ $shoppingAssistant['routeSessions'] }}"
      data-route-sessions-list="{{ $shoppingAssistant['routeSessionsList'] }}"
      data-route-session-base="{{ $shoppingAssistant['routeSessionBase'] }}"
      data-route-upload-image="{{ $shoppingAssistant['routeUploadImage'] }}"
      data-route-cart-add="{{ $shoppingAssistant['routeCartAdd'] }}"
      data-route-checkout="{{ $shoppingAssistant['routeCheckout'] }}"
      data-auth="{{ $shoppingAssistant['auth'] ? '1' : '0' }}"
      data-customer-id="{{ $shoppingAssistant['customerId'] ?? '' }}"
      data-user-avatar="{{ $shoppingAssistant['userAvatar'] }}"
      data-direction="{{ $shoppingAssistant['direction'] }}"
      data-locale="{{ $shoppingAssistant['locale'] }}"
      data-txt-add-to-cart="{{ $shoppingAssistant['text']['addToCart'] }}"
      data-txt-buy-now="{{ $shoppingAssistant['text']['buyNow'] }}"
      data-txt-added-cart="{{ $shoppingAssistant['text']['addedCart'] }}"
      data-txt-cart-failed="{{ $shoppingAssistant['text']['cartFailed'] }}"
      data-txt-checkout-confirm="{{ $shoppingAssistant['text']['checkoutConfirm'] }}"
      data-txt-proceed-checkout="{{ $shoppingAssistant['text']['proceedCheckout'] }}"
      data-txt-keep-shopping="{{ $shoppingAssistant['text']['keepShopping'] }}"
      data-txt-adding-cart="{{ $shoppingAssistant['text']['addingCart'] }}"
      data-txt-redirecting="{{ $shoppingAssistant['text']['redirecting'] }}"
      data-txt-color="{{ $shoppingAssistant['text']['color'] }}"
      data-txt-format="{{ $shoppingAssistant['text']['format'] }}"
      data-txt-qty="{{ $shoppingAssistant['text']['qty'] }}"
      data-txt-quantity="{{ $shoppingAssistant['text']['quantity'] }}"
      data-txt-close="{{ $shoppingAssistant['text']['close'] }}"
      data-txt-conversation="{{ $shoppingAssistant['text']['conversation'] }}"
      data-txt-only="{{ $shoppingAssistant['text']['only'] }}"
      data-txt-left="{{ $shoppingAssistant['text']['left'] }}"
      data-txt-add="{{ $shoppingAssistant['text']['add'] }}"
      data-txt-more="{{ $shoppingAssistant['text']['more'] }}"
      data-txt-just-now="{{ $shoppingAssistant['text']['justNow'] }}"
      data-txt-minutes-ago="{{ $shoppingAssistant['text']['minutesAgo'] }}"
      data-txt-hours-ago="{{ $shoppingAssistant['text']['hoursAgo'] }}"
      data-txt-days-ago="{{ $shoppingAssistant['text']['daysAgo'] }}"
      data-txt-login-buy="{{ $shoppingAssistant['text']['loginBuy'] }}"
      data-txt-login-cart="{{ $shoppingAssistant['text']['loginCart'] }}"
      data-txt-no-convos="{{ $shoppingAssistant['text']['noConvos'] }}"
      data-txt-gibberish="{{ $shoppingAssistant['text']['gibberish'] }}"
      data-txt-write-message="{{ $shoppingAssistant['text']['writeMessage'] }}"
      data-txt-image-invalid="{{ $shoppingAssistant['text']['imageInvalid'] }}"
      data-txt-image-too-large="{{ $shoppingAssistant['text']['imageTooLarge'] }}"
      data-txt-rate-limited="{{ $shoppingAssistant['text']['rateLimited'] }}"
      data-txt-session-gone="{{ $shoppingAssistant['text']['sessionGone'] }}"
      data-txt-session-expired="{{ $shoppingAssistant['text']['sessionExpired'] }}"
      data-txt-generic-error="{{ $shoppingAssistant['text']['genericError'] }}"
      data-txt-image-dropped="{{ $shoppingAssistant['text']['imageDropped'] }}"
      data-txt-out-of-stock="{{ $shoppingAssistant['text']['outOfStock'] }}"
      data-txt-min-not-met="{{ $shoppingAssistant['text']['minNotMet'] }}"
      data-txt-delete="{{ $shoppingAssistant['text']['delete'] }}"
      data-login-url="{{ $shoppingAssistant['loginUrl'] }}"
      data-product-url-base="{{ $shoppingAssistant['productUrlBase'] }}"
      data-intro-message="{{ $shoppingAssistant['introMessage'] }}"
      data-suggested-prompts="{{ json_encode($shoppingAssistant['suggestedPrompts']) }}"
      data-txt-suggested-heading="{{ $shoppingAssistant['text']['suggestedHeading'] }}"
      data-txt-product-hint="{{ $shoppingAssistant['text']['productHint'] }}"
      data-max-input-length="{{ $shoppingAssistant['maxInputLength'] }}"
      data-txt-input-limit="{{ $shoppingAssistant['text']['inputLimit'] }}"
      data-txt-delete-confirm-title="{{ $shoppingAssistant['text']['deleteConfirmTitle'] }}"
      data-txt-delete-confirm-text="{{ $shoppingAssistant['text']['deleteConfirmText'] }}"
      data-txt-delete-confirm-yes="{{ $shoppingAssistant['text']['deleteConfirmYes'] }}"
      data-txt-cancel="{{ $shoppingAssistant['text']['cancel'] }}"
      class="d-none"
></span>

<button class="hexa-ai-launcher" id="hexaAiLauncher" aria-label="{{ translate('Open AI Shopping Assistant') }}" type="button">
    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M14 3.5L15.8 10.2L22.5 12L15.8 13.8L14 20.5L12.2 13.8L5.5 12L12.2 10.2L14 3.5Z" fill="white"/>
        <path d="M22 19L22.9 21.1L25 22L22.9 22.9L22 25L21.1 22.9L19 22L21.1 21.1L22 19Z" fill="white" opacity="0.7"/>
        <path d="M7 3L7.7 4.8L9.5 5.5L7.7 6.2L7 8L6.3 6.2L4.5 5.5L6.3 4.8L7 3Z" fill="white" opacity="0.5"/>
    </svg>
</button>

<div class="hexa-ai-panel" id="hexaAiPanel" role="dialog" aria-modal="true" aria-label="{{ translate('HexaAi Shopping Assistant') }}">

    <div class="hexa-ai-header">
        <div class="hexa-ai-brand">
            <span class="hexa-ai-brand-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14 3.5L15.8 10.2L22.5 12L15.8 13.8L14 20.5L12.2 13.8L5.5 12L12.2 10.2L14 3.5Z" fill="currentColor"/>
                    <path d="M22 19L22.9 21.1L25 22L22.9 22.9L22 25L21.1 22.9L19 22L21.1 21.1L22 19Z" fill="currentColor" opacity="0.6"/>
                </svg>
            </span>
            <span class="hexa-ai-brand-name">{{ translate('HexaAi') }}</span>
        </div>
        <div class="hexa-ai-header-actions">
            <button class="hexa-ai-icon-btn" id="hexaAiHistoryToggle" aria-label="{{ translate('Chat history') }}" title="{{ translate('Chat history') }}" type="button">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="15" y2="18"/>
                </svg>
            </button>
            <button class="hexa-ai-icon-btn hexa-ai-close" id="hexaAiClose" aria-label="{{ translate('Close') }}" type="button">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="hexa-ai-body" id="hexaAiBody">

        <div class="hexa-ai-state hexa-ai-conversation" id="hexaAiStateConversation">
            <div class="hexa-ai-messages" id="hexaAiMessages" role="log" aria-live="polite" aria-label="{{ translate('Conversation') }}">
            </div>
            <div class="hexa-ai-typing" id="hexaAiTyping" aria-label="{{ translate('AI is thinking') }}" hidden>
                <span class="hexa-ai-avatar hexa-ai-typing-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 3.5L15.8 10.2L22.5 12L15.8 13.8L14 20.5L12.2 13.8L5.5 12L12.2 10.2L14 3.5Z" fill="currentColor"/>
                        <path d="M22 19L22.9 21.1L25 22L22.9 22.9L22 25L21.1 22.9L19 22L21.1 21.1L22 19Z" fill="currentColor" opacity="0.6"/>
                    </svg>
                </span>
                <div class="hexa-ai-typing-dots">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>

        <div class="hexa-ai-state hexa-ai-history" id="hexaAiStateHistory">
            <div class="hexa-ai-history-list" id="hexaAiHistoryList" role="list">
            </div>
            <div class="hexa-ai-history-footer">
                <button class="hexa-ai-new-chat-btn" id="hexaAiNewChat" type="button">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    {{ translate('New Chat') }}
                </button>
            </div>
        </div>

    </div>

    <div class="hexa-ai-footer">
        <div class="hexa-ai-input-wrap" id="hexaAiInputWrap">
            <textarea
                class="hexa-ai-input"
                id="hexaAiInput"
                rows="1"
                placeholder="{{ translate('Write me about your item') }}…"
                maxlength="300"
                aria-label="{{ translate('Message') }}"
            ></textarea>
            <div class="hexa-ai-input-actions">
<button class="hexa-ai-icon-btn hexa-ai-img-btn" id="hexaAiImgBtn" aria-label="{{ translate('Upload image') }}" type="button" title="{{ translate('Upload image') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                    </svg>
                </button>
                <input type="file" id="hexaAiImgInput" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="d-none" aria-hidden="true">
                <button class="hexa-ai-send-btn" id="hexaAiSend" aria-label="{{ translate('Send') }}" type="button">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="hexa-ai-char-count" id="hexaAiCharCount" aria-live="polite" hidden></div>
        <div class="hexa-ai-img-preview" id="hexaAiImgPreview" hidden>
            <img id="hexaAiImgThumb" src="" alt="{{ translate('Selected image') }}">
            <button class="hexa-ai-img-remove" id="hexaAiImgRemove" type="button" aria-label="{{ translate('Remove image') }}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    </div>

</div>

<div class="hexa-ai-overlay" id="hexaAiOverlay" aria-hidden="true"></div>

<script src="{{ dynamicAsset(path: 'public/assets/front-end/ai/ai-shopping-assistant.js') }}{{ $shoppingAssistant['jsVersion'] ? '?v=' . $shoppingAssistant['jsVersion'] : '' }}" defer></script>
