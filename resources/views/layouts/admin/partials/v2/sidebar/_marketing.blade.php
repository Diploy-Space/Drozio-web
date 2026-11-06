@php use App\Utils\Helpers; @endphp

    @if (Helpers::module_permission_check(activeBusinessModule()->key.'.marketing') && businessModuleSectionVisible('marketing'))
        <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'marketing' ? 'v2-is-on' : '' }}" data-section="marketing">
            <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Marketing') }}</div></div>
            <div class="v2-ctx-group v2-is-pinned" style="display:none;">
                <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
                <div class="v2-ctx-group-body"></div>
            </div>

            @if (Helpers::module_permission_check(activeBusinessModule()->key.'.marketing'))
                @if (businessModuleItemVisible('marketing.promotions_retail'))
                <div class="v2-ctx-group">
                    <div class="v2-ctx-group-head"><span>{{ translate('Promotions') }}</span></div>
                    <a class="v2-nav-item {{ Request::is('admin/retail/banner*') ? 'v2-is-active' : '' }}" data-item="banners" href="{{ route('admin.retail.banner.list') }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Banner Setup') }}</span></span>
                        <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="banners" aria-label="Pin"></button></div>
                    </a>

                    <div class="v2-nav-item v2-has-children {{ (Request::is('admin/retail/coupon*') || Request::is('admin/retail/deal*')) ? 'v2-is-active' : '' }}" data-item="offers">
                        <a class="v2-nav-btn" href="{{ route('admin.retail.coupon.add') }}">
                            <span class="v2-nav-label">{{ translate('Offers & Deals') }}</span>
                        </a>
                        <div class="v2-nav-right">
                            <button class="v2-pin-btn" type="button" data-pin="offers" aria-label="Pin"></button>
                            <span class="v2-nav-chev">
                                <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                            </span>
                        </div>
                    </div>
                    <div class="v2-nav-children v2-is-collapsed" data-children-for="offers">
                        <a class="v2-nav-child {{ Request::is('admin/retail/coupon*') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.coupon.add') }}">
                            <span class="v2-nav-child-dot" data-tone="primary"></span>
                            <span class="v2-nav-child-label">{{ translate('Coupon') }}</span>
                        </a>
                        <a class="v2-nav-child {{ (Request::is('admin/retail/deal/flash*') || Request::is('admin/retail/deal/update*')) ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.deal.flash') }}">
                            <span class="v2-nav-child-dot" data-tone="warn"></span>
                            <span class="v2-nav-child-label">{{ translate('Flash Deals') }}</span>
                        </a>
                        <a class="v2-nav-child {{ Request::is('admin/retail/deal/day') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.deal.day') }}">
                            <span class="v2-nav-child-dot" data-tone="warn"></span>
                            <span class="v2-nav-child-label">{{ translate('Deal of the day') }}</span>
                        </a>
                        <a class="v2-nav-child {{ (Request::is('admin/retail/deal/feature') || Request::is('admin/retail/deal/feature/new') || Request::is('admin/retail/deal/feature-update*')) ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.deal.feature') }}">
                            <span class="v2-nav-child-dot" data-tone="success"></span>
                            <span class="v2-nav-child-label">{{ translate('Featured Deal') }}</span>
                        </a>
                        <a class="v2-nav-child {{ Request::is('admin/retail/deal/clearance-sale*') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.deal.clearance-sale.index') }}">
                            <span class="v2-nav-child-dot" data-tone="danger"></span>
                            <span class="v2-nav-child-label">{{ translate('Clearance Sale') }}</span>
                        </a>
                    </div>

                </div>
                @endif

                @if (businessModuleItemVisible('marketing.promotions_wholesale'))
                <div class="v2-ctx-group">
                    <div class="v2-ctx-group-head"><span>{{ translate('Promotions') }}</span></div>
                    <a class="v2-nav-item {{ Request::is('admin/wholesale/banner*') ? 'v2-is-active' : '' }}" data-item="wholesale-banners" href="{{ route('admin.wholesale.banner.list') }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Banner Setup') }}</span></span>
                        <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="wholesale-banners" aria-label="Pin"></button></div>
                    </a>

                    <div class="v2-nav-item v2-has-children {{ (Request::is('admin/wholesale/coupon*') || Request::is('admin/wholesale/campaign*')) ? 'v2-is-active' : '' }}" data-item="wholesale-offers">
                        <a class="v2-nav-btn" href="{{ route('admin.wholesale.coupon.add') }}">
                            <span class="v2-nav-label">{{ translate('Offers') }}</span>
                        </a>
                        <div class="v2-nav-right">
                            <button class="v2-pin-btn" type="button" data-pin="wholesale-offers" aria-label="Pin"></button>
                            <span class="v2-nav-chev">
                                <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                            </span>
                        </div>
                    </div>
                    <div class="v2-nav-children v2-is-collapsed" data-children-for="wholesale-offers">
                        <a class="v2-nav-child {{ Request::is('admin/wholesale/coupon*') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.coupon.add') }}">
                            <span class="v2-nav-child-dot" data-tone="primary"></span>
                            <span class="v2-nav-child-label">{{ translate('Coupon') }}</span>
                        </a>
                        <a class="v2-nav-child {{ Request::is('admin/wholesale/campaign*') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.campaign.index') }}">
                            <span class="v2-nav-child-dot" data-tone="success"></span>
                            <span class="v2-nav-child-label">{{ translate('Campaign') }}</span>
                        </a>
                    </div>
                </div>
                @endif

                @if (businessModuleItemVisible('marketing.communication'))
                <div class="v2-ctx-group">
                    <div class="v2-ctx-group-head"><span>{{ translate('Communication') }}</span></div>
                    <a class="v2-nav-item {{ (!Request::is('admin/retail/notification/push') && Request::is('admin/retail/notification/*')) ? 'v2-is-active' : '' }}" data-item="send-notification" href="{{ route('admin.retail.notification.index') }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Send notification') }}</span></span>
                        <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="send-notification" aria-label="Pin"></button></div>
                    </a>
                    <a class="v2-nav-item {{ Request::is('admin/retail/announcement*') ? 'v2-is-active' : '' }}" data-item="announcement" href="{{ route('admin.retail.announcement') }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Announcement') }}</span></span>
                        <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="announcement" aria-label="Pin"></button></div>
                    </a>
                </div>
                @endif
            @endif

        </div>
    @endif
