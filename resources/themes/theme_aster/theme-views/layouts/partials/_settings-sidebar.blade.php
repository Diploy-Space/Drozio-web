<div class="settings-sidebar d-none d-xl-flex">
    <div class="theme-bar">
        <div class="active-theme-wrap">
            <div class="active-theme">
                <img src="{{theme_asset('assets/img/svg/light.svg')}}" alt="{{translate('Image')}}" class="svg light-icon">
                <img src="{{theme_asset('assets/img/svg/dark.svg')}}" alt="{{translate('Image')}}" class="svg dark-icon">
            </div>
            <button class="light_button active">
                <img src="{{theme_asset('assets/img/svg/light.svg')}}" alt="{{translate('Image')}}" class="svg">
                <span>{{ translate('Light') }}</span>
            </button>
        </div>
        <button class="dark_button">
            <img src="{{theme_asset('assets/img/svg/dark.svg')}}" alt="{{translate('Image')}}" class="svg">
            <span>{{ translate('Dark') }}</span>
        </button>
    </div>
</div>
