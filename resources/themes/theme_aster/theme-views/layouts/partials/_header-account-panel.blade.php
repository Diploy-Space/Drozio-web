@php($currentLanguageCode = \App\Utils\Helpers::default_lang())
@php($accountPanelLoggedIn = $isCustomerLoggedIn ?? auth('customer')->check())
@php($accountPanelHomeUrl = $homeRoute ?? route('home'))
@php($accountPanelItems = $accountPanelLoggedIn ? customerProfileMenuItems(activeStorefrontBusinessModuleKey()) : [])
@php($accountPanelShowAuction = $accountPanelLoggedIn && function_exists('shouldShowAuctionMenu') && shouldShowAuctionMenu())
@php($accountPanelActiveLanguages = collect($web_config['language'])->where('status', 1))
@php($accountPanelOptionLimit = 4)

<li class="login-register header-account header-mobile-flex">
    <button type="button" class="header-account__toggle border-0 bg-transparent p-0"
            data-account-panel-toggle aria-label="{{ translate('Account') }}">
        <span class="avatar header-avatar rounded-circle overflow-hidden d-flex">
            <img loading="lazy" class="img-fit" alt="{{ translate('Image') }}"
                 src="{{ getStorageImages(path: getCustomerFromQuery()?->image_full_url, type:'avatar') }}">
        </span>
    </button>
</li>

<aside class="header-account-panel header-mobile-flex flex-column">
    <div class="header-account-panel__head">
        <a href="{{ $accountPanelHomeUrl }}" class="header-account-panel__home">{{ translate('Home') }}</a>
        <button type="button" class="header-account-panel__close border-0 bg-transparent p-0"
                data-account-panel-close aria-label="{{ translate('Close') }}">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <div class="header-account-panel__body" data-trigger="scrollbar">
        <ul class="header-account-panel__list list-unstyled mb-0">
            @if($accountPanelLoggedIn)
                @foreach($accountPanelItems as $accountPanelItem)
                    <li class="{{ $accountPanelItem['is_active'] ? 'active' : '' }}">
                        <a class="header-account-panel__link" href="{{ $accountPanelItem['url'] }}">
                            <i class="fi {{ $accountPanelItem['icon_class'] }}" aria-hidden="true"></i>
                            <span>{{ $accountPanelItem['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            @else
                <li>
                    <a class="header-account-panel__link" href="javascript:"
                       data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="fi fi-rr-user" aria-hidden="true"></i>
                        <span>{{ translate('Login').' / '.translate('Register') }}</span>
                    </a>
                </li>
            @endif

            @if($web_config['business_mode'] == 'multi' && $web_config['seller_registration'])
                <li>
                    <a class="header-account-panel__link" href="{{ route('vendor.auth.registration.index') }}">
                        <i class="fi fi-rr-shop" aria-hidden="true"></i>
                        <span>{{ translate('Become a vendor') }}</span>
                    </a>
                </li>
            @endif
        </ul>

        @includeWhen($accountPanelShowAuction, 'theme-views.partials._auction-menu-block', ['variant' => 'panel'])

        @if($accountPanelLoggedIn)
            <ul class="header-account-panel__list list-unstyled mb-0">
                <li>
                    <a class="header-account-panel__link" href="{{ route('customer.auth.logout') }}">
                        <i class="fi fi-rr-sign-out-alt" aria-hidden="true"></i>
                        <span>{{ translate('Logout') }}</span>
                    </a>
                </li>
            </ul>
        @endif

        <div class="header-account-panel__settings" id="accountPanelSettings">
            <div class="header-account-panel__row">
                <span class="header-account-panel__label">{{ translate('Theme mode') }}</span>
                <div class="theme-bar p-1">
                    <button class="light_button active" aria-label="{{ translate('Light') }}">
                        <img src="{{ theme_asset('assets/img/svg/light.svg') }}" alt="{{ translate('Image') }}" class="svg">
                    </button>
                    <button class="dark_button" aria-label="{{ translate('Dark') }}">
                        <img src="{{ theme_asset('assets/img/svg/dark.svg') }}" alt="{{ translate('Image') }}" class="svg">
                    </button>
                </div>
            </div>

            @if($web_config['currency_model'] == 'multi_currency')
                <div class="header-account-panel__collapse">
                    <button type="button" class="header-account-panel__row collapsed w-100 border-0 bg-transparent"
                            data-bs-toggle="collapse" data-bs-target="#accountPanelCurrencyOptions"
                            aria-expanded="false" aria-controls="accountPanelCurrencyOptions">
                        <span class="header-account-panel__label">{{ translate('Currency') }}</span>
                        <span class="header-account-panel__value">
                            {{ session('currency_code') }} {{ session('currency_symbol') }}
                            <i class="bi bi-chevron-right header-account-panel__caret" aria-hidden="true"></i>
                        </span>
                    </button>
                    <ul class="collapse header-account-panel__options list-unstyled {{ count($web_config['currencies']) > $accountPanelOptionLimit ? 'header-account-panel__options--scroll' : '' }}"
                        id="accountPanelCurrencyOptions" data-bs-parent="#accountPanelSettings">
                        @foreach($web_config['currencies'] as $currency)
                            <li class="currency-change {{ session('currency_code') == $currency['code'] ? 'active' : '' }}"
                                data-currency-code="{{ $currency['code'] }}"
                                data-currency-route="{{ route('currency.update') }}">
                                <span>{{ $currency['code'] }} - {{ $currency['symbol'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="header-account-panel__collapse">
                <button type="button" class="header-account-panel__row collapsed w-100 border-0 bg-transparent"
                        data-bs-toggle="collapse" data-bs-target="#accountPanelLanguageOptions"
                        aria-expanded="false" aria-controls="accountPanelLanguageOptions">
                    <span class="header-account-panel__label">{{ translate('Language') }}</span>
                    <span class="header-account-panel__value">
                        <img width="20" loading="lazy" class="dark-support header-flag" alt="{{ strtoupper($currentLanguageCode) }}"
                             src="{{ theme_asset('assets/img/flags/'.strtolower(\Illuminate\Support\Str::before($currentLanguageCode, '-')).'.png') }}"/>
                        <i class="bi bi-chevron-right header-account-panel__caret" aria-hidden="true"></i>
                    </span>
                </button>
                <ul class="collapse header-account-panel__options list-unstyled {{ $accountPanelActiveLanguages->count() > $accountPanelOptionLimit ? 'header-account-panel__options--scroll' : '' }}"
                    id="accountPanelLanguageOptions" data-bs-parent="#accountPanelSettings">
                    @foreach($accountPanelActiveLanguages as $language)
                        <li class="change-language {{ $currentLanguageCode == $language['code'] ? 'active' : '' }}"
                            data-action="{{ route('change-language') }}" data-language-code="{{ $language['code'] }}">
                            <img width="20" loading="lazy" class="dark-support header-flag" alt="{{ $language['name'] }}"
                                 src="{{ theme_asset('assets/img/flags/'.strtolower(\Illuminate\Support\Str::before($language['code'], '-')).'.png') }}"/>
                            <span>{{ ucwords($language['name']) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if($web_config['phone'])
                <a href="tel:{{ $web_config['phone'] }}" class="header-account-panel__row">
                    <span class="header-account-panel__label">
                        <i class="bi bi-telephone-fill" aria-hidden="true"></i>
                        {{ translate('Contact') }}
                    </span>
                    <span class="header-account-panel__value direction-ltr">{{ $web_config['phone'] }}</span>
                </a>
            @endif
        </div>
    </div>

    @unless($accountPanelLoggedIn)
        <div class="header-account-panel__footer">
            <button type="button" class="btn btn-primary text-absolute-white w-100"
                    data-bs-toggle="modal" data-bs-target="#loginModal">
                {{ translate('Login').'/'.translate('Register') }}
            </button>
        </div>
    @endunless
</aside>
<div class="aside-overlay"></div>
