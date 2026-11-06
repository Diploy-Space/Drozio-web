@php
    $v2Wholesale = function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'Wholesale');
    $v2Auction = businessModuleManager()->isAvailable(\App\Enums\BusinessModule::AUCTION);
@endphp

@if (businessModuleSectionVisible('people'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'people' ? 'v2-is-on' : '' }}" data-section="people">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('People') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Inbox') }}</span></div>
            <a class="v2-nav-item {{ Request::is('vendor/messages*') ? 'v2-is-active' : '' }}" data-item="v-inbox" href="{{ route('vendor.messages.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Inbox') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-inbox" aria-label="Pin"></button></div>
            </a>
        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Delivery Man') }}</span></div>

            <a class="v2-nav-item {{ (Request::is('vendor/delivery-man/list') || Request::is('vendor/delivery-man/update') || Request::is('vendor/delivery-man/rating/*') || Request::is('vendor/delivery-man/wallet*')) ? 'v2-is-active' : '' }}" data-item="v-delivery-man" href="{{ route('vendor.delivery-man.list') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Delivery Man') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-delivery-man" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('vendor/delivery-man/index') ? 'v2-is-active' : '' }}" data-item="v-dm-add" href="{{ route('vendor.delivery-man.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Add New Delivery Man') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-dm-add" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('vendor/delivery-man/withdraw/*') ? 'v2-is-active' : '' }}" data-item="v-dm-withdraw" href="{{ route('vendor.delivery-man.withdraw.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Withdraws') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-dm-withdraw" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('vendor/delivery-man/emergency-contact/*') ? 'v2-is-active' : '' }}" data-item="v-dm-emergency" href="{{ route('vendor.delivery-man.emergency-contact.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Emergency Contact') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-dm-emergency" aria-label="Pin"></button></div>
            </a>
        </div>
    </div>
@endif

@if (businessModuleSectionVisible('settings'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'settings' ? 'v2-is-on' : '' }}" data-section="settings">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Settings') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>
        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Business section') }}</span></div>

            <a class="v2-nav-item {{ Request::is('vendor/shop*') ? 'v2-is-active' : '' }}" data-item="v-shop" href="{{ route('vendor.shop.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Business Setup') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-shop" aria-label="Pin"></button></div>
            </a>

            @if ($v2Wholesale)
                <div class="v2-nav-item v2-has-children {{ Request::is('vendor/business-settings/shipping-method*') ? 'v2-is-active' : '' }}" data-item="v-shipping">
                    <a class="v2-nav-btn" href="{{ route('vendor.business-settings.shipping-method.index') }}">
                        <span class="v2-nav-label">{{ translate('Shipping Methods') }}</span>
                    </a>
                    <div class="v2-nav-right">
                        <button class="v2-pin-btn" type="button" data-pin="v-shipping" aria-label="Pin"></button>
                        <span class="v2-nav-chev" aria-label="{{ translate('Toggle') }}">
                            <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                        </span>
                    </div>
                </div>
                <div class="v2-nav-children v2-is-collapsed" data-children-for="v-shipping">
                    <a class="v2-nav-child {{ (Request::is('vendor/business-settings/shipping-method/retail') || Request::is('vendor/business-settings/shipping-method/update/*')) ? 'v2-is-on' : '' }}" href="{{ route('vendor.business-settings.shipping-method.index') }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Retail') }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('vendor/business-settings/shipping-method/wholesale*') ? 'v2-is-on' : '' }}" href="{{ route('vendor.wholesale.shipping-method.wholesale') }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Wholesale') }}</span>
                    </a>
                </div>
            @else
                <a class="v2-nav-item {{ Request::is('vendor/business-settings/shipping-method*') ? 'v2-is-active' : '' }}" data-item="v-shipping" href="{{ route('vendor.business-settings.shipping-method.index') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Shipping Methods') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-shipping" aria-label="Pin"></button></div>
                </a>
            @endif

            @php($vendorCourierModule = vendorDeliveryPartnerSetupModule())
            @if ($vendorCourierModule !== null)
                <a class="v2-nav-item {{ Request::is('vendor/courier/config*') ? 'v2-is-active' : '' }}" data-item="v-courier" href="{{ route('vendor.courier.config.index', ['business_module' => $vendorCourierModule]) }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('3rd Party Delivery Partner') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-courier" aria-label="Pin"></button></div>
                </a>
            @endif

        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Withdraws Management') }}</span></div>

            @if ($v2Auction)
                <div class="v2-nav-item v2-has-children {{ Request::is('vendor/business-settings/withdraw*') ? 'v2-is-active' : '' }}" data-item="v-withdraws">
                    <a class="v2-nav-btn" href="{{ route('vendor.business-settings.withdraw.index') }}">
                        <span class="v2-nav-label">{{ translate('Withdraws') }}</span>
                    </a>
                    <div class="v2-nav-right">
                        <button class="v2-pin-btn" type="button" data-pin="v-withdraws" aria-label="Pin"></button>
                        <span class="v2-nav-chev" aria-label="{{ translate('Toggle') }}">
                            <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                        </span>
                    </div>
                </div>
                <div class="v2-nav-children v2-is-collapsed" data-children-for="v-withdraws">
                    <a class="v2-nav-child {{ (Request::is('vendor/business-settings/withdraw/index') || Request::is('vendor/business-settings/withdraw/close/*')) ? 'v2-is-on' : '' }}" href="{{ route('vendor.business-settings.withdraw.index') }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Retail and wholesale') }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('vendor/business-settings/withdraw/auction*') ? 'v2-is-on' : '' }}" href="{{ route('vendor.business-settings.withdraw.auction') }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Auction') }}</span>
                    </a>
                </div>
            @else
                <a class="v2-nav-item {{ Request::is('vendor/business-settings/withdraw*') ? 'v2-is-active' : '' }}" data-item="v-withdraws" href="{{ route('vendor.business-settings.withdraw.index') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Withdraws') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-withdraws" aria-label="Pin"></button></div>
                </a>
            @endif
            <a class="v2-nav-item {{ Request::is('vendor/profile/index') ? 'v2-is-active' : '' }}" data-item="v-bank" href="{{ route('vendor.profile.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Bank information') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-bank" aria-label="Pin"></button></div>
            </a>
        </div>
    </div>
@endif
