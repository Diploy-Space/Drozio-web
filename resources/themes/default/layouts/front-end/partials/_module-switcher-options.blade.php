<div class="module-switcher__head">
    <span class="module-switcher__title">{{ translate('Choose Your Shopping Mode') }}</span>
    <span class="module-switcher__subtitle">{{ translate('Choose the experience that best matches your shopping purpose.') }}</span>
</div>
<div class="module-switcher__list">
    @foreach($moduleSwitcherItems as $moduleSwitcherItem)
        <a class="module-switcher__option {{ $moduleSwitcherItem['active'] ? 'is-active' : '' }}"
           href="{{ $moduleSwitcherItem['url'] }}"
           @if($moduleSwitcherItem['active']) aria-current="true" @endif>
            <img class="module-switcher__icon" src="{{ theme_asset(path: 'public/assets/front-end/img/icons/'.$moduleSwitcherItem['icon']) }}" alt="" width="24" height="24">
            <span class="module-switcher__body">
                <span class="module-switcher__label">{{ $moduleSwitcherItem['label'] }}</span>
                <span class="module-switcher__desc">{{ $moduleSwitcherItem['description'] }}</span>
            </span>
        </a>
    @endforeach
</div>
<span class="module-switcher__note">
    <span class="module-switcher__note-key">#{{ translate('Note') }} :</span>
    {{ translate('You can switch between modules anytime from the top menu') }}
</span>
