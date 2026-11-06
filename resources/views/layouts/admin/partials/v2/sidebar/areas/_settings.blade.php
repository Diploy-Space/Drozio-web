@php
    use App\Enums\EmailTemplateKey;
    use App\Utils\Helpers;

    $v2Tax = function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'TaxModule');
    $v2TaxRoutes = $v2Tax ? include(base_path('Modules/TaxModule/Addon/tax_routes.php')) : [];
    $v2Ai = function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'AI');
    $v2ContentWholesale = Route::has('admin.settings.wholesale.pages-and-media.index')
                          && businessModuleManager()->isAvailableInPanel(\App\Enums\BusinessModule::WHOLESALE);
    $v2ContentAuction = Route::has('admin.settings.auction.company-reliability')
                        && function_exists('getCheckAddonPublishedStatus')
                        && getCheckAddonPublishedStatus(moduleName: 'Auction')
                        && getWebConfig(name: 'auction_feature_status');
    $v2ContentRetail = theme_root_path() == 'default';
    $v2PagesActive = Request::is('admin/settings/pages-and-media/list')
                     || Request::is('admin/settings/pages-and-media/page*')
                     || Request::is('admin/settings/pages-and-media/add')
                     || Request::is('admin/settings/pages-and-media/update')
                     || Request::is('admin/settings/pages-and-media/privacy-policy')
                     || Request::is('admin/settings/pages-and-media/about-us')
                     || Request::is('admin/settings/helpTopic/index')
                     || Request::is('admin/settings/pages-and-media/features-section')
                     || Request::is('admin/settings/pages-and-media/company-reliability')
                     || Request::is('admin/settings/wholesale/pages-and-media*')
                     || Request::is('admin/settings/auction/hero-section')
                     || Request::is('admin/settings/auction/company-reliability');
    $v2ThemeRoutes = config('get_theme_routes') ?: [];
    $v2AddonAdminRoutes = config('addon_admin_routes') ?: [];
@endphp

@if (Helpers::module_permission_check('settings.business') && businessModuleSectionVisible('settings-business'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'settings-business' ? 'v2-is-on' : '' }}" data-section="settings-business">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Business Settings') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Business Settings') }}</span></div>
            <a class="v2-nav-item {{ isBusinessSetupPage() ? 'v2-is-active' : '' }}" data-item="business-setup" href="{{ route('admin.settings.business-settings.web-config.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Business Setup') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="business-setup" aria-label="Pin"></button></div>
            </a>

            <a class="v2-nav-item {{ Request::is('admin/settings/business-settings/inhouse-shop') || Request::is('admin/settings/business-settings/inhouse-shop/setup*') ? 'v2-is-active' : '' }}" data-item="inhouse-shop" href="{{ route('admin.settings.business-settings.inhouse-shop') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('In-house Shop') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="inhouse-shop" aria-label="Pin"></button></div>
            </a>

            @if ($v2Tax && count($v2TaxRoutes))
                @php
                    $v2TaxRowIsOn = [];
                    foreach ($v2TaxRoutes as $v2TaxIndex => $v2TaxRow) {
                        $v2TaxRowIsOn[$v2TaxIndex] = strstr(Request::url(), $v2TaxRow['path'])
                            || (! empty($v2TaxRow['active_patterns']) && Request::is($v2TaxRow['active_patterns']));
                    }
                    $v2TaxActive = in_array(true, $v2TaxRowIsOn, true);
                @endphp
                <div class="v2-nav-item v2-has-children {{ $v2TaxActive ? 'v2-is-active' : '' }}" data-item="vat-setup">
                    <a class="v2-nav-btn" href="{{ $v2TaxRoutes[0]['url'] }}">
                        <span class="v2-nav-label">{{ translate('Vat tax setup') }}</span>
                    </a>
                    <div class="v2-nav-right">
                        <button class="v2-pin-btn" type="button" data-pin="vat-setup" aria-label="Pin"></button>
                        <span class="v2-nav-chev">
                            <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                        </span>
                    </div>
                </div>
                <div class="v2-nav-children v2-is-collapsed" data-children-for="vat-setup">
                    @foreach ($v2TaxRoutes as $v2TaxIndex => $v2TaxRow)
                        <a class="v2-nav-child {{ $v2TaxRowIsOn[$v2TaxIndex] ? 'v2-is-on' : '' }}" href="{{ $v2TaxRow['url'] }}">
                            <span class="v2-nav-child-dot" data-tone="primary"></span>
                            <span class="v2-nav-child-label">{{ translate($v2TaxRow['name']) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            <a class="v2-nav-item {{ Request::is('admin/settings/pages-and-media/vendor-registration-settings/*') ? 'v2-is-active' : '' }}" data-item="vendor-registration" href="{{ route('admin.settings.pages-and-media.vendor-registration-settings.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Vendor Registration') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="vendor-registration" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/settings/business-settings/priority-setup') ? 'v2-is-active' : '' }}" data-item="priority-setup" href="{{ route('admin.settings.business-settings.priority-setup.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Priority Setup') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="priority-setup" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ (Request::is('admin/settings/seo-settings/web-master-tool') || Request::is('admin/settings/seo-settings/robot-txt') || Request::is('admin/settings/seo-settings/sitemap') || Request::is('admin/settings/seo-settings/robots-meta-content*') || Request::is('admin/settings/seo-settings/error-logs/index')) ? 'v2-is-active' : '' }}" data-item="seo" href="{{ route('admin.settings.seo-settings.web-master-tool') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('SEO Settings') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="seo" aria-label="Pin"></button></div>
            </a>
        </div>
    </div>
@endif

@if (Helpers::module_permission_check('settings.content') && businessModuleSectionVisible('settings-content'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'settings-content' ? 'v2-is-on' : '' }}" data-section="settings-content">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Content Setup') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Content Setup') }}</span></div>

            <div class="v2-nav-item v2-has-children {{ $v2PagesActive ? 'v2-is-active' : '' }}" data-item="business-pages">
                <a class="v2-nav-btn" href="{{ route('admin.settings.pages-and-media.list') }}">
                    <span class="v2-nav-label">{{ translate('Pages & Media') }}</span>
                </a>
                <div class="v2-nav-right">
                    <button class="v2-pin-btn" type="button" data-pin="business-pages" aria-label="Pin"></button>
                    <span class="v2-nav-chev">
                        <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                    </span>
                </div>
            </div>
            <div class="v2-nav-children v2-is-collapsed" data-children-for="business-pages">
                <a class="v2-nav-child {{ (Request::is('admin/settings/pages-and-media/list') || Request::is('admin/settings/pages-and-media/page*') || Request::is('admin/settings/pages-and-media/add') || Request::is('admin/settings/pages-and-media/update') || Request::is('admin/settings/helpTopic/index')) ? 'v2-is-on' : '' }}" href="{{ route('admin.settings.pages-and-media.list') }}">
                    <span class="v2-nav-child-dot" data-tone="primary"></span>
                    <span class="v2-nav-child-label">{{ translate('Common') }}</span>
                </a>
                @if ($v2ContentRetail)
                    <a class="v2-nav-child {{ (Request::is('admin/settings/pages-and-media/company-reliability') || Request::is('admin/settings/pages-and-media/features-section')) ? 'v2-is-on' : '' }}" href="{{ route('admin.settings.pages-and-media.company-reliability') }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Retail') }}</span>
                    </a>
                @endif
                @if ($v2ContentWholesale)
                    <a class="v2-nav-child {{ Request::is('admin/settings/wholesale/pages-and-media*') ? 'v2-is-on' : '' }}" href="{{ route('admin.settings.wholesale.pages-and-media.index') }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Wholesale') }}</span>
                    </a>
                @endif
                @if ($v2ContentAuction)
                    <a class="v2-nav-child {{ (Request::is('admin/settings/auction/hero-section') || Request::is('admin/settings/auction/company-reliability')) ? 'v2-is-on' : '' }}" href="{{ route('admin.settings.auction.hero-section') }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Auction') }}</span>
                    </a>
                @endif
            </div>

            @if (Route::has('admin.settings.content.blog.view'))
                <div class="v2-nav-item v2-has-children {{ Request::is('admin/settings/content/blog*') ? 'v2-is-active' : '' }}" data-item="blog">
                    <a class="v2-nav-btn" href="{{ route('admin.settings.content.blog.view') }}">
                        <span class="v2-nav-label">{{ translate('Blog setup') }}</span>
                    </a>
                    <div class="v2-nav-right">
                        <button class="v2-pin-btn" type="button" data-pin="blog" aria-label="Pin"></button>
                        <span class="v2-nav-chev">
                            <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                        </span>
                    </div>
                </div>
                <div class="v2-nav-children v2-is-collapsed" data-children-for="blog">
                    <a class="v2-nav-child {{ Request::is('admin/settings/content/blog/add') ? 'v2-is-on' : '' }}" href="{{ route('admin.settings.content.blog.add') }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Add Blog') }}</span>
                    </a>
                    <a class="v2-nav-child {{ (Request::is('admin/settings/content/blog/view') || Request::is('admin/settings/content/blog/edit') || Request::is('admin/settings/content/blog/draft-edit/*')) ? 'v2-is-on' : '' }}" href="{{ route('admin.settings.content.blog.view') }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Blog List') }}</span>
                    </a>
                    <a class="v2-nav-child {{ (Request::is('admin/settings/content/blog/configuration') || Request::is('admin/settings/content/blog/app-download-setup') || Request::is('admin/settings/content/blog/priority-setup')) ? 'v2-is-on' : '' }}" href="{{ route('admin.settings.content.blog.configuration') }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Blog Configuration') }}</span>
                    </a>
                </div>
            @endif

            <a class="v2-nav-item {{ Request::is('admin/settings/pages-and-media/social-media') ? 'v2-is-active' : '' }}" data-item="social-media-links" href="{{ route('admin.settings.pages-and-media.social-media') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Social Media Links') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="social-media-links" aria-label="Pin"></button></div>
            </a>
        </div>
    </div>
@endif

@if (Helpers::module_permission_check('settings.system') && businessModuleSectionVisible('settings-system'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'settings-system' ? 'v2-is-on' : '' }}" data-section="settings-system">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('System Settings') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('System Settings') }}</span></div>
            <a class="v2-nav-item {{ (Request::is('admin/settings/system-setup/environment-setup') || Request::is('admin/settings/system-setup/app-settings') || Request::is('admin/settings/system-setup/app-deep-link') || Request::is('admin/settings/system-setup/sitemap') || Request::is('admin/settings/system-setup/currency/view') || Request::is('admin/settings/system-setup/web-config/db-index') || Request::is('admin/settings/system-setup/language*') || Request::is('admin/settings/system-setup/software-update') || Request::is('admin/settings/system-setup/web-config/app-settings') || Request::is('admin/settings/system-setup/invoice-settings/') || Request::is('admin/settings/system-setup/db-index')) ? 'v2-is-active' : '' }}" data-item="system-setup" href="{{ route('admin.settings.system-setup.environment-setup') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('System Setup') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="system-setup" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ (Request::is('admin/settings/system-setup/login-settings/login-url-setup') || Request::is('admin/settings/system-setup/login-settings/customer-login-setup') || Request::is('admin/settings/system-setup/login-settings/otp-setup')) ? 'v2-is-active' : '' }}" data-item="login-settings" href="{{ route('admin.settings.system-setup.login-settings.customer-login-setup') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Login Settings') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="login-settings" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/settings/system-setup/push-notification/index*') ? 'v2-is-active' : '' }}" data-item="push-setup" href="{{ route('admin.settings.system-setup.push-notification.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Push notifications setup') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="push-setup" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/settings/system-setup/email-templates/*') ? 'v2-is-active' : '' }}" data-item="email-template" href="{{ route('admin.settings.system-setup.email-templates.view', ['admin', EmailTemplateKey::ADMIN_EMAIL_LIST[0]]) }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Email Template') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="email-template" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/settings/system-setup/file-manager*') ? 'v2-is-active' : '' }}" data-item="file-gallery" href="{{ route('admin.settings.system-setup.file-manager.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Gallery') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="file-gallery" aria-label="Pin"></button></div>
            </a>
        </div>
    </div>
@endif

@if (Helpers::module_permission_check('settings.third_party') && businessModuleSectionVisible('settings-third-party'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'settings-third-party' ? 'v2-is-on' : '' }}" data-section="settings-third-party">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('3rd Party Setup') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('3rd Party Setup') }}</span></div>
            <a class="v2-nav-item {{ (Request::is('admin/settings/third-party/payment-method') || Request::is('admin/settings/third-party/offline-payment-method/index') || Request::is('admin/settings/third-party/offline-payment-method*')) ? 'v2-is-active' : '' }}" data-item="payment-methods" href="{{ route('admin.settings.third-party.payment-method.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Payment Methods') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="payment-methods" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ (Request::is('admin/settings/third-party/firebase-configuration/setup') || Request::is('admin/settings/third-party/firebase-configuration/authentication')) ? 'v2-is-active' : '' }}" data-item="firebase" href="{{ route('admin.settings.third-party.firebase-configuration.setup') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Firebase') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="firebase" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/settings/third-party/analytics-index') ? 'v2-is-active' : '' }}" data-item="marketing-tools" href="{{ route('admin.settings.third-party.analytics-index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Marketing Tools') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="marketing-tools" aria-label="Pin"></button></div>
            </a>
            @if ($v2Ai)
                <a class="v2-nav-item {{ (Request::is('admin/settings/third-party/ai-setting') || Request::is('admin/settings/third-party/ai-setting/vendors-usage-limits') || Request::is('admin/settings/third-party/ai-setting/customers-usage-limits')) ? 'v2-is-active' : '' }}" data-item="ai-setup" href="{{ route('admin.settings.third-party.ai-setting.index') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('AI Setup') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="ai-setup" aria-label="Pin"></button></div>
                </a>
            @endif
            <a class="v2-nav-item {{ (Request::is('admin/settings/third-party/mail') || Request::is('admin/settings/third-party/sms-module') || Request::is('admin/settings/third-party/recaptcha') || Request::is('admin/settings/third-party/social-login/view') || Request::is('admin/settings/third-party/social-media-chat/view') || Request::is('admin/settings/third-party/storage-connection-settings/index') || Request::is('admin/settings/third-party/map-api') || Request::is('admin/settings/third-party/erp/*')) ? 'v2-is-active' : '' }}" data-item="other-config" href="{{ route('admin.settings.third-party.social-login.view') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Other Configuration') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="other-config" aria-label="Pin"></button></div>
            </a>
            @if (Route::has('admin.settings.courier.config.index'))
                <a class="v2-nav-item {{ Request::is('admin/settings/courier*') ? 'v2-is-active' : '' }}" data-item="courier" href="{{ route('admin.settings.courier.config.index', ['business_module' => activeBusinessModule()->key]) }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('3rd Party Delivery Partner') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="courier" aria-label="Pin"></button></div>
                </a>
            @endif
        </div>
    </div>
@endif

@if (businessModuleSectionVisible('settings-themes'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'settings-themes' ? 'v2-is-on' : '' }}" data-section="settings-themes">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Themes & Addons') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        @if (count($v2ThemeRoutes) > 0)
            @php
                $v2ThemeEnabledCount = 0;
                foreach (($v2ThemeRoutes['route_list'] ?? []) as $v2ThemeRoute) {
                    if (isset($v2ThemeRoute['module_permission']) && Helpers::module_permission_check($v2ThemeRoute['module_permission'])) {
                        $v2ThemeEnabledCount++;
                    }
                }
            @endphp
            @if ($v2ThemeEnabledCount > 0)
                <div class="v2-ctx-group">
                    <div class="v2-ctx-group-head"><span>{{ $v2ThemeRoutes['name'] ?? '' }} {{ translate('Menu') }}</span></div>
                    @foreach ($v2ThemeRoutes['route_list'] as $v2ThemeRoute)
                        @if (isset($v2ThemeRoute['module_permission']) && Helpers::module_permission_check($v2ThemeRoute['module_permission']))
                            @php
                                $v2ThemeSlug = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower('theme-' . $v2ThemeRoute['name']));
                                $v2ThemeActive = Request::is($v2ThemeRoute['path']) || Request::is($v2ThemeRoute['path'].'*');
                                if (!$v2ThemeActive && !empty($v2ThemeRoute['route_list'])) {
                                    foreach ($v2ThemeRoute['route_list'] as $v2ThemeSub) {
                                        if (Request::is($v2ThemeSub['path']) || Request::is($v2ThemeSub['path'].'*')) { $v2ThemeActive = true; break; }
                                    }
                                }
                            @endphp
                            @if (!empty($v2ThemeRoute['route_list']))
                                @foreach ($v2ThemeRoute['route_list'] as $v2ThemeSub)
                                    @php $v2ThemeSubSlug = $v2ThemeSlug . '-' . preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($v2ThemeSub['name'])); @endphp
                                    <a class="v2-nav-item {{ (Request::is($v2ThemeSub['path']) || Request::is($v2ThemeSub['path'].'*')) ? 'v2-is-active' : '' }}" data-item="{{ $v2ThemeSubSlug }}" href="{{ $v2ThemeSub['url'] }}">
                                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate($v2ThemeSub['name']) }}</span></span>
                                        <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="{{ $v2ThemeSubSlug }}" aria-label="Pin"></button></div>
                                    </a>
                                @endforeach
                            @else
                                <a class="v2-nav-item {{ $v2ThemeActive ? 'v2-is-active' : '' }}" data-item="{{ $v2ThemeSlug }}" href="{{ $v2ThemeRoute['url'] }}">
                                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate($v2ThemeRoute['name']) }}</span></span>
                                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="{{ $v2ThemeSlug }}" aria-label="Pin"></button></div>
                                </a>
                            @endif
                        @endif
                    @endforeach
                </div>
            @endif
        @endif

        @if (Helpers::module_permission_check('settings.themes'))
            <div class="v2-ctx-group">
                <div class="v2-ctx-group-head"><span>{{ translate('Themes & Addons') }}</span></div>
                <a class="v2-nav-item {{ Request::is('admin/settings/system-setup/theme/setup') ? 'v2-is-active' : '' }}" data-item="theme-setup" href="{{ route('admin.settings.system-setup.theme.setup') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Theme Setup') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="theme-setup" aria-label="Pin"></button></div>
                </a>
                <a class="v2-nav-item {{ Request::is('admin/settings/system-setup/addon') ? 'v2-is-active' : '' }}" data-item="system-addons" href="{{ route('admin.settings.system-setup.addon.index') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('System Addons') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="system-addons" aria-label="Pin"></button></div>
                </a>
                <a class="v2-nav-item {{ Request::is('admin/settings/system-setup/addon-activation') ? 'v2-is-active' : '' }}" data-item="addon-activation" href="{{ route('admin.settings.system-setup.addon-activation.index') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Addon Activation') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="addon-activation" aria-label="Pin"></button></div>
                </a>

                @foreach ($v2AddonAdminRoutes as $v2AddonGroup)
                    @foreach ($v2AddonGroup as $v2AddonRoute)
                        @php $v2AddonSlug = 'addon-' . preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($v2AddonRoute['name'])); @endphp
                        <a class="v2-nav-item {{ strstr(Request::url(), $v2AddonRoute['path']) ? 'v2-is-active' : '' }}" data-item="{{ $v2AddonSlug }}" href="{{ $v2AddonRoute['url'] }}">
                            <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate($v2AddonRoute['name']) }}</span></span>
                            <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="{{ $v2AddonSlug }}" aria-label="Pin"></button></div>
                        </a>
                    @endforeach
                @endforeach
            </div>
        @endif
    </div>
@endif
