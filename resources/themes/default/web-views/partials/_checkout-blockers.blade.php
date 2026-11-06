@php($checkoutBlockers = session(\App\Enums\SessionKey::CHECKOUT_BLOCKERS)['messages'] ?? [])

<div class="sc-checkout-note" @if(empty($checkoutBlockers)) hidden @endif>
    <i class="czi-security-announcement"></i>
    <ul class="sc-checkout-note__list">
        @forelse($checkoutBlockers as $blocker)
            <li>{{ $blocker }}</li>
        @empty
            <li>{{ translate('Choose a shipping method for every shop to continue') }}</li>
        @endforelse
    </ul>
</div>
