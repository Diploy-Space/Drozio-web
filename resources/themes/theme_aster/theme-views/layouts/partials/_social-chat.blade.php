@php($whatsapp = getWebConfig(name: 'whatsapp'))

@if(isset($whatsapp['status']) && $whatsapp['status'] == 1)
    <div class="social-chat-icons">
        <a href="https://wa.me/{{ $whatsapp['phone'] }}?text={{ rawurlencode(translate('Hello there!')) }}"
           target="_blank" rel="noopener" aria-label="{{ translate('Chat with us on WhatsApp') }}">
            <img src="{{ theme_asset('assets/img/whatsapp.svg') }}" width="35" class="chat-image-shadow"
                 alt="{{ translate('Chat with us on WhatsApp') }}">
        </a>
    </div>
@endif
