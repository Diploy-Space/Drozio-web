@php($isSignedIn = auth('customer')->check())
@php($currencyModel = getWebConfig(name: 'currency_model'))
@php($activeLanguageCode = getDefaultLanguage())
@php($activeFlagCode = \Illuminate\Support\Str::before($activeLanguageCode, '-'))
@php($activeLanguageName = collect($web_config['language'])->firstWhere('code', $activeLanguageCode)['name'] ?? strtoupper($activeLanguageCode))
@php($supportPhone = $web_config['phone'] ?? '')

@if($showTitle ?? true)
    <span class="sh-account__title">
        <a href="{{ $homeUrl }}">{{ translate('Home') }}</a>
    </span>
@endif

@unless($isSignedIn)
    <div class="sh-account__auth">
        <a class="btn btn-primary" href="{{ route('customer.auth.login') }}">
            <i class="fi fi-rr-sign-in-alt"></i>
            {{ translate('Sign In') }}
        </a>
        <a class="btn btn-light" href="{{ route('customer.auth.sign-up') }}">
            <i class="fi fi-rr-user-add"></i>
            {{ translate('Sign up') }}
        </a>
    </div>
@endunless

<div class="sh-account__links">
    @foreach($links as $link)
        <a class="sh-account__link {{ ($link['is_active'] ?? false) ? 'is-active' : '' }}" href="{{ $link['url'] }}">
            <span class="sh-account__link-icon">
                <i class="{{ $link['icon'] ?? 'fi '.$link['icon_class'] }}"></i>
                @if($link['dot'] ?? false)
                    <span class="sh-account__link-dot"></span>
                @endif
            </span>
            <span>{{ $link['drawer_label'] ?? $link['label'] }}</span>
        </a>
    @endforeach
</div>

@if(($showAuctionLinks ?? false) && function_exists('shouldShowAuctionMenu') && shouldShowAuctionMenu())
    @include('web-views.partials._account-auction-links')
@endif

<div class="sh-account__rows">
    @if($currencyModel === 'multi_currency')
        <div class="sh-account__picker">
            <div class="sh-account__row">
                <span class="sh-account__row-label">{{ translate('Currency') }}</span>
                <button class="sh-account__row-value" type="button"
                        data-sh-tree="#shCurrencyOptions{{ $scope }}" aria-expanded="false">
                    <span>{{ session('currency_code') }} {{ session('currency_symbol') }}</span>
                    <i class="fi fi-rr-angle-down" aria-hidden="true"></i>
                </button>
            </div>
            <div class="sh-account__options" id="shCurrencyOptions{{ $scope }}">
                @foreach($web_config['currencies'] as $currency)
                    @php($isActiveCurrency = $currency['code'] === session('currency_code'))
                    <a href="javascript:"
                       class="sh-account__option get-currency-change-function {{ $isActiveCurrency ? 'is-active' : '' }}"
                       data-code="{{ $currency['code'] }}"
                       @if($isActiveCurrency) aria-current="true" @endif>
                        <span class="sh-account__option-icon">{{ $currency['symbol'] }}</span>
                        <span class="sh-account__option-label">{{ $currency['code'] }}</span>
                        <i class="sh-account__option-check fi fi-rr-check" aria-hidden="true"></i>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="sh-account__picker">
        <div class="sh-account__row">
            <span class="sh-account__row-label">{{ translate('Language') }}</span>
            <button class="sh-account__row-value" type="button"
                    data-sh-tree="#shLanguageOptions{{ $scope }}" aria-expanded="false">
                <img src="{{ theme_asset(path: 'public/assets/front-end/img/flags/'.strtolower($activeFlagCode).'.png') }}"
                     alt="{{ $activeLanguageName }}">
                <i class="fi fi-rr-angle-down" aria-hidden="true"></i>
            </button>
        </div>
        <div class="sh-account__options" id="shLanguageOptions{{ $scope }}">
            @foreach($web_config['language'] as $language)
                @continue($language['status'] != 1)
                @php($languageFlagCode = \Illuminate\Support\Str::before($language['code'], '-'))
                @php($isActiveLanguage = $language['code'] === $activeLanguageCode)
                <a href="javascript:"
                   class="sh-account__option change-language {{ $isActiveLanguage ? 'is-active' : '' }}"
                   data-action="{{ route('change-language') }}"
                   data-language-code="{{ $language['code'] }}"
                   @if($isActiveLanguage) aria-current="true" @endif>
                    <span class="sh-account__option-icon">
                        <img src="{{ theme_asset(path: 'public/assets/front-end/img/flags/'.strtolower($languageFlagCode).'.png') }}"
                             alt="{{ $language['name'] }}">
                    </span>
                    <span class="sh-account__option-label">{{ $language['name'] }}</span>
                    <i class="sh-account__option-check fi fi-rr-check" aria-hidden="true"></i>
                </a>
            @endforeach
        </div>
    </div>

    @if($supportPhone !== '')
        <div class="sh-account__row">
            <span class="sh-account__row-label">
                <i class="fi fi-rr-phone-call"></i>
                {{ translate('Contact') }}
            </span>
            <a class="sh-account__row-value direction-ltr" href="tel:{{ $supportPhone }}">{{ $supportPhone }}</a>
        </div>
    @endif
</div>

@if($isSignedIn)
    <div class="sh-account__foot">
        <a class="sh-account__logout" href="{{ route('customer.auth.logout') }}">{{ translate('Logout') }}</a>
    </div>
@endif
