@foreach($candidates as $candidate)
    <div class="verification-candidate d-flex align-items-center gap-3 bg-section rounded-10 p-2 ps-3"
         data-vendor-id="{{ $candidate['id'] }}" role="button">
        <img width="48" height="48" class="avatar rounded-circle object-fit-cover flex-shrink-0"
             src="{{ getStorageImages(path: $candidate['image'], type: 'backend-basic') }}" alt="">
        <div class="flex-grow-1 min-w-0">
            <div class="fw-bold text-dark text-truncate">{{ $candidate['name'] }}</div>
            <div class="fs-12">
                {{ translate('Rating') }}
                <span class="text-dark">{{ $candidate['rating'] }}/{{ \App\Utils\VendorVerificationCriteria::RATING_SCALE }}</span>
                <span class="px-1">|</span>
                {{ translate('Booking') }}
                <span class="text-dark">{{ $candidate['bookings'] }}</span>
            </div>
        </div>
        <div class="flex-shrink-0 d-flex align-items-center">
            <button type="button"
                    class="btn btn-success btn-sm d-inline-flex align-items-center gap-2 text-nowrap verification-confirm verification-give-badge"
                    data-verification-title="{{ translate('Give Verified Badge to this vendor?') }}"
                    data-verification-message="{{ translate('This will mark the vendor as verified, and a trusted badge will be displayed on the vendor details for customers.') }}"
                    data-verification-confirm="{{ translate('Yes') }}"
                    data-verification-cancel="{{ translate('No') }}"
                    data-verification-variant="primary"
                    data-verification-url="{{ route('admin.people.vendors.verification.grant') }}"
                    data-verification-payload="{{ json_encode(['vendor_ids' => [$candidate['id']]]) }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" focusable="false" aria-hidden="true">
                    <path fill="#ffffff" d="M12 .8l2.4 2.05 3.13-.4.94 3.02 2.9 1.28-.86 3.04L22.4 12l-1.89 2.21.86 3.04-2.9 1.28-.94 3.02-3.13-.4L12 23.2l-2.4-2.05-3.13.4-.94-3.02-2.9-1.28.86-3.04L1.6 12l1.89-2.21-.86-3.04 2.9-1.28.94-3.02 3.13.4z"/>
                    <path fill="#04bb7b" d="M10.78 15.48l-3.1-3.1 1.42-1.41 1.68 1.68 4.12-4.12 1.42 1.41z"/>
                </svg>
                {{ translate('Give Badge') }}
            </button>
            <span class="btn-circle bg-white text-success d-none align-items-center justify-content-center verification-selected-mark"
                  style="--size: 2rem;" aria-hidden="true">
                <i class="fi fi-sr-check"></i>
            </span>
        </div>
    </div>
@endforeach
