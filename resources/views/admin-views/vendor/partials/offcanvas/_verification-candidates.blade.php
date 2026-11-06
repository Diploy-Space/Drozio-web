<div class="offcanvas offcanvas-end" tabindex="-1" id="verificationCandidatesOffcanvas"
     aria-labelledby="verificationCandidatesOffcanvasLabel" style="--bs-offcanvas-width: 500px;">
    <div class="offcanvas-header bg-body gap-2">
        <button type="button" class="btn p-0 border-0 bg-transparent text-dark fs-16 lh-1"
                data-bs-dismiss="offcanvas" aria-label="{{ translate('Back') }}">
            <i class="fi fi-rr-arrow-left"></i>
        </button>
        <h3 class="mb-0" id="verificationCandidatesOffcanvasLabel">{{ translate('Vendor List') }}</h3>
        <span class="bg-white border rounded px-2 py-1 fs-12 fw-semibold text-dark" id="verification-candidate-chip">
            {{ $candidateCount }}
        </span>
        <div class="ms-auto d-flex align-items-center gap-2">
            <input class="form-check-input checkbox--input mt-0" type="checkbox" id="verification-select-all"
                   {{ $candidates === [] ? 'disabled' : '' }}>
            <label class="form-check-label text-dark mb-0" for="verification-select-all">
                {{ translate('Select All') }}
            </label>
        </div>
        <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;"
                data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}">
            <i class="fi fi-rr-cross"></i>
        </button>
    </div>
    <div class="offcanvas-body" id="verification-candidate-scroller"
         data-url="{{ route('admin.people.vendors.verification.candidates') }}"
         data-page="1"
         data-has-more="{{ $hasMore ? 1 : 0 }}">
        <div class="d-flex flex-column gap-3" id="verification-candidate-list">
            @include('admin-views.vendor.partials.offcanvas._verification-candidate-rows', ['candidates' => $candidates])
        </div>
        <div class="{{ $candidates === [] ? '' : 'd-none' }}" id="verification-candidate-empty">
            @include('layouts.admin.partials._empty-state', [
                'text' => 'No vendor currently meets the verification criteria',
                'image' => 'default',
            ])
        </div>
    </div>
    <div class="offcanvas-footer shadow-popup d-none" id="verification-candidate-footer">
        <div class="d-flex justify-content-center gap-3 bg-white px-3 py-2">
            <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="offcanvas">
                {{ translate('Close') }}
            </button>
            <button type="button" class="btn btn-primary w-100 verification-confirm" id="verification-verify-all"
                    data-verification-title="{{ translate('Give Verified Badge to the selected vendors?') }}"
                    data-verification-message="{{ translate('This will mark the selected vendors as verified, and a trusted badge will be displayed on the vendor details for customers.') }}"
                    data-verification-confirm="{{ translate('Yes') }}"
                    data-verification-cancel="{{ translate('No') }}"
                    data-verification-variant="primary"
                    data-verification-url="{{ route('admin.people.vendors.verification.grant') }}"
                    data-verification-payload="{{ json_encode(['vendor_ids' => []]) }}">
                {{ translate('Verify All') }} <span>(<span id="verification-selected-count">0</span>)</span>
            </button>
        </div>
    </div>
</div>
