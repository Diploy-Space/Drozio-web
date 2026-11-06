@php($verifiedBadgeSize = $size ?? 18)
@if (!empty($isVerified) && getWebConfig(name: \App\Enums\WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS))
    <span class="d-inline-flex align-items-center" data-toggle="tooltip" title="{{ translate('Verified vendor') }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="{{ $verifiedBadgeSize }}" height="{{ $verifiedBadgeSize }}" role="img" aria-label="{{ translate('Verified vendor') }}" focusable="false">
            <path fill="#0A9D6D" d="M12 .8l2.4 2.05 3.13-.4.94 3.02 2.9 1.28-.86 3.04L22.4 12l-1.89 2.21.86 3.04-2.9 1.28-.94 3.02-3.13-.4L12 23.2l-2.4-2.05-3.13.4-.94-3.02-2.9-1.28.86-3.04L1.6 12l1.89-2.21-.86-3.04 2.9-1.28.94-3.02 3.13.4z"/>
            <path fill="#fff" d="M10.78 15.48l-3.1-3.1 1.42-1.41 1.68 1.68 4.12-4.12 1.42 1.41z"/>
        </svg>
    </span>
@endif
