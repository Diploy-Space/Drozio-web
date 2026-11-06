<div class="card">
    <div class="card-body">
        <div class="mb-sm-20 mb-3">
            <h2>{{ translate('Verified badge') }}</h2>
            <p class="fs-12 mb-0">
                {{ translate('Show the in-house shop to customers as a verified shop') }}
            </p>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex justify-content-between align-items-center gap-3 border rounded p-12 p-sm-3 user-select-none bg-white">
                <span class="d-flex flex-column">
                    <span class="fw-medium text-dark fs-14 mb-1 d-flex align-items-center gap-1">
                        {{ translate('Show in-house shop as verified') }}
                        @include('admin-views.vendor.partials._verified-badge', ['isVerified' => true, 'size' => 16])
                    </span>
                    <span class="mb-0 fs-12">
                        {{ translate('If enabled, a verified badge shows beside the in-house shop name on the storefront.') }}
                    </span>
                    @if (!$isVerifiedBadgeEnabled)
                        <span class="mb-0 mt-1 fs-12 text-warning">
                            {{ translate('Verified badges are turned off in vendor settings, so customers will not see this badge until they are turned on.') }}
                        </span>
                    @endif
                </span>

                <input type="hidden" name="{{ \App\Enums\WebConfigKey::IN_HOUSE_SHOP_VERIFIED_BADGE_STATUS }}" value="0">
                <label class="switcher" for="in-house-shop-verified-badge-status">
                    <input class="switcher_input" type="checkbox" value="1"
                           name="{{ \App\Enums\WebConfigKey::IN_HOUSE_SHOP_VERIFIED_BADGE_STATUS }}"
                           id="in-house-shop-verified-badge-status"
                        {{ $isInHouseShopVerified ? 'checked' : '' }}>
                    <span class="switcher_control"></span>
                </label>
            </div>
        </div>
    </div>
</div>
