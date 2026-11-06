@php
    $ratingValue = (float) ($rating ?? 0);
    $reviewCount = (int) ($count ?? 0);
    $ratingSize = in_array($size ?? 'sm', ['xs', 'sm', 'md', 'lg']) ? $size : 'sm';
    $ratingAlign = ($align ?? 'start') === 'center' ? ' rating-display--center' : '';
    $ratingFill = max(0, min(100, round($ratingValue / 5 * 100, 2)));
    $ratingLabel = rtrim(rtrim(number_format($ratingValue, 1), '0'), '.') ?: '0';
@endphp

@if($ratingValue > 0 || ($showEmpty ?? false))
    <div class="rating-display rating-display--{{ $ratingSize }}{{ $ratingAlign }}"
         title="{{ translate('Rated :rating out of :max', ['rating' => $ratingLabel, 'max' => 5]) }}">
        <span class="rating-stars" style="--rating-fill:{{ $ratingFill }}%"
              role="img"
              aria-label="{{ translate('Rated :rating out of :max', ['rating' => $ratingLabel, 'max' => 5]) }}">
            <span class="rating-stars__track" aria-hidden="true"><i class="tio-star"></i><i class="tio-star"></i><i class="tio-star"></i><i class="tio-star"></i><i class="tio-star"></i></span>
            <span class="rating-stars__fill" aria-hidden="true"><i class="tio-star"></i><i class="tio-star"></i><i class="tio-star"></i><i class="tio-star"></i><i class="tio-star"></i></span>
        </span>

        @if($showValue ?? true)
            <span class="rating-display__value">{{ $ratingLabel }}</span>
        @endif

        @if($reviewCount > 0)
            <span class="rating-display__count">({{ formatCompactNumber($reviewCount) }})</span>
        @endif
    </div>
@elseif($reserve ?? false)
    <div class="rating-display rating-display--{{ $ratingSize }}{{ $ratingAlign }} rating-display--placeholder"
         aria-hidden="true"></div>
@endif
