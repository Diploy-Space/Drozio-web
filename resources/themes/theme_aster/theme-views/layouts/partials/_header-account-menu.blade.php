@php($currentLanguageCode = \App\Utils\Helpers::default_lang())
@php($isCustomerLoggedIn = auth('customer')->check())
@php($accountMenuActiveLanguages = collect($web_config['language'])->where('status', 1))
@php($accountMenuOptionLimit = 4)

<li class="login-register header-account header-desktop-block">
    <div class="dropdown">
        <button type="button" class="header-account__toggle border-0 bg-transparent p-0"
                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                aria-label="{{ translate('Account') }}">
            <span class="avatar header-avatar rounded-circle overflow-hidden d-flex">
                <img loading="lazy" class="img-fit" alt="{{ translate('Image') }}"
                     src="{{ getStorageImages(path: getCustomerFromQuery()?->image_full_url, type:'avatar') }}">
            </span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end header-dropdown header-account__menu" id="headerAccountMenu">
            <li>
                <a class="header-account__link" href="{{ $isCustomerLoggedIn ? route('user-profile') : 'javascript:' }}"
                   @unless($isCustomerLoggedIn) data-bs-toggle="modal" data-bs-target="#loginModal" @endunless>
                    <i class="bi bi-person-fill header-account__icon header-account__icon--profile" aria-hidden="true"></i>
                    {{ translate('My Profile') }}
                </a>
            </li>
            @if ($isCustomerLoggedIn)
                <li>
                    <a class="header-account__link" href="{{ $accountOrders['url'] }}">
                        <i class="bi {{ $accountOrders['icon'] }} header-account__icon header-account__icon--orders" aria-hidden="true"></i>
                        {{ $accountOrders['label'] }}
                    </a>
                </li>
                <li>
                    <a class="header-account__link" href="{{ route('chat', ['type' => 'vendor']) }}">
                        <i class="bi bi-envelope-fill header-account__icon header-account__icon--inbox" aria-hidden="true"></i>
                        {{ translate('Inbox') }}
                    </a>
                </li>
                @if($web_config['wallet_status'])
                    <li>
                        <a class="header-account__link" href="{{ route('wallet') }}">
                            <i class="bi bi-wallet-fill header-account__icon header-account__icon--wallet" aria-hidden="true"></i>
                            {{ translate('Wallet') }}
                        </a>
                    </li>
                @endif
            @endif

            @if($web_config['business_mode'] == 'multi' && $web_config['seller_registration'])
                <li>
                    <a class="header-account__link" href="{{ route('vendor.auth.registration.index') }}">
                        <i class="bi bi-shop header-account__icon header-account__icon--vendor" aria-hidden="true"></i>
                        {{ translate('Become a vendor') }}
                    </a>
                </li>
            @endif

            @if($web_config['currency_model'] == 'multi_currency')
                <li class="header-account__panel">
                    <button type="button" class="header-account__panel-head collapsed" data-bs-toggle="collapse"
                            data-bs-target="#headerCurrencyOptions" aria-expanded="false" aria-controls="headerCurrencyOptions">
                        <span class="header-account__panel-label">{{ translate('Currency') }}</span>
                        <span class="header-account__panel-value">
                            {{ session('currency_code') }} {{ session('currency_symbol') }}
                            <i class="bi bi-chevron-down header-account__panel-caret" aria-hidden="true"></i>
                        </span>
                    </button>
                    <ul class="collapse header-account__panel-options {{ count($web_config['currencies']) > $accountMenuOptionLimit ? 'header-account__panel-options--scroll' : '' }}"
                        id="headerCurrencyOptions" data-bs-parent="#headerAccountMenu">
                        @foreach($web_config['currencies'] as $currency)
                            <li class="currency-change {{ session('currency_code') == $currency['code'] ? 'active' : '' }}"
                                data-currency-code="{{ $currency['code'] }}">
                                <span>{{ $currency['code'] }} - {{ $currency['symbol'] }}</span>
                            </li>
                        @endforeach
                        <span id="currency-route" data-currency-route="{{ route('currency.update') }}"></span>
                    </ul>
                </li>
            @endif

            <li class="header-account__panel">
                <button type="button" class="header-account__panel-head collapsed" data-bs-toggle="collapse"
                        data-bs-target="#headerLanguageOptions" aria-expanded="false" aria-controls="headerLanguageOptions">
                    <span class="header-account__panel-label">{{ translate('Language') }}</span>
                    <span class="header-account__panel-value">
                        <img width="20" loading="lazy" class="dark-support header-flag" alt="{{ strtoupper($currentLanguageCode) }}"
                             src="{{ theme_asset('assets/img/flags/'.strtolower(\Illuminate\Support\Str::before($currentLanguageCode, '-')).'.png') }}"/>
                        <i class="bi bi-chevron-down header-account__panel-caret" aria-hidden="true"></i>
                    </span>
                </button>
                <ul class="collapse header-account__panel-options {{ $accountMenuActiveLanguages->count() > $accountMenuOptionLimit ? 'header-account__panel-options--scroll' : '' }}"
                    id="headerLanguageOptions" data-bs-parent="#headerAccountMenu">
                    @foreach($accountMenuActiveLanguages as $language)
                        <li class="change-language {{ $currentLanguageCode == $language['code'] ? 'active' : '' }}"
                            data-action="{{ route('change-language') }}" data-language-code="{{ $language['code'] }}">
                            <img width="20" loading="lazy" class="dark-support header-flag" alt="{{ $language['name'] }}"
                                 src="{{ theme_asset('assets/img/flags/'.strtolower(\Illuminate\Support\Str::before($language['code'], '-')).'.png') }}"/>
                            <span class="header-account__panel-option-name">{{ ucwords($language['name']) }}</span>
                        </li>
                    @endforeach
                </ul>
            </li>

            @if($web_config['phone'])
                <li class="header-account__panel">
                    <a href="tel:{{ $web_config['phone'] }}" class="header-account__panel-head">
                        <span class="header-account__panel-label">
                            <i class="bi bi-telephone-fill header-account__icon header-account__icon--contact" aria-hidden="true"></i>
                            {{ translate('Contact') }}
                        </span>
                        <span class="header-account__panel-value direction-ltr">{{ $web_config['phone'] }}</span>
                    </a>
                </li>
            @endif

            <li class="header-account__footer">
                @if($isCustomerLoggedIn)
                    <a class="header-account__footer-action header-account__footer-action--logout"
                       href="{{ route('customer.auth.logout') }}">
                        {{ translate('Logout') }}
                    </a>
                @else
                    <button type="button" class="header-account__footer-action header-account__footer-action--login"
                            data-bs-toggle="modal" data-bs-target="#loginModal">
                        {{ translate('Login').'/'.translate('Register') }}
                    </button>
                @endif
            </li>
        </ul>
    </div>
</li>
