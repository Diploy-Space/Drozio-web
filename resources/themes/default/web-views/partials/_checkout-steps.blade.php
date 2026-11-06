@php($checkoutSteps = [
    ['label' => translate('Cart'), 'url' => route('shop-cart')],
    ['label' => translate('Shipping'), 'url' => route('checkout-details')],
    ['label' => translate('Payment'), 'url' => route('checkout-payment')],
])

<ol class="co-steps">
    @foreach($checkoutSteps as $index => $checkoutStep)
        @php($stepNumber = $index + 1)
        <li class="co-steps__item {{ $stepNumber < $step ? 'is-done' : '' }} {{ $stepNumber == $step ? 'is-current' : '' }}">
            <a class="co-steps__link" @if($stepNumber < $step) href="{{ $checkoutStep['url'] }}" @endif>
                <span class="co-steps__marker">
                    @if($stepNumber < $step)
                        <i class="czi-check"></i>
                    @else
                        {{ $stepNumber }}
                    @endif
                </span>
                <span class="co-steps__label">{{ $checkoutStep['label'] }}</span>
            </a>
        </li>
    @endforeach
</ol>
