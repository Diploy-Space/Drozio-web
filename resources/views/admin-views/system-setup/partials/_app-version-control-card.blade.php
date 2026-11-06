@php
    $platforms = [
        [
            'key' => 'for_android',
            'name' => translate('Android'),
            'logo' => 'public/assets/back-end/img/play_store.png',
            'versionPlaceholder' => translate('Ex').': 2.1',
            'linkPlaceholder' => 'https://play.google.com/store/apps',
            'versionTooltip' => translate('Define the minimum Android app version for best user experience.').' '.translate('Users without it are asked to update when opening the app.'),
            'linkTooltip' => translate('Android download URL used when users accept the update.'),
        ],
        [
            'key' => 'for_ios',
            'name' => translate('Ios'),
            'logo' => 'public/assets/back-end/img/apple.png',
            'versionPlaceholder' => translate('Ex').': 2.1',
            'linkPlaceholder' => 'https://www.apple.com/app-store/',
            'versionTooltip' => translate('Define the minimum iOS app version for best user experience.').' '.translate('Users without it are asked to update when opening the app.'),
            'linkTooltip' => translate('iOS download URL used when users accept the update.'),
        ],
    ];
@endphp

<div class="card app-version-card">
    <div class="card-header d-flex align-items-center gap-3">
        <div class="app-avatar bg-{{ $color }} bg-opacity-10 text-{{ $color }} d-flex align-items-center justify-content-center">
            <i class="fi {{ $icon }} fs-5 d-flex"></i>
        </div>
        <div class="min-w-0">
            <h3 class="mb-1 text-capitalize">{{ $title }}</h3>
            <p class="mb-0 fs-12 text-muted">{{ $subtitle }}</p>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.settings.system-setup.app-settings') }}" method="post"
              class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate"
              novalidate="novalidate">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="row g-4">
                @foreach($platforms as $platform)
                    <div class="col-lg-6">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <img class="app-platform-logo" src="{{ dynamicAsset(path: $platform['logo']) }}" alt="{{ $platform['name'] }}">
                            <h3 class="mb-0 text-capitalize app-platform-head">{{ translate('For').' '.$platform['name'] }}</h3>
                        </div>
                        <div class="app-platform-panel bg-section rounded p-12 p-sm-20 d-flex flex-column gap-3">
                            <div class="form-group mb-0">
                                <label class="form-label mb-2 text-capitalize d-flex align-items-center gap-2">
                                    {{ translate('Minimum app version for force update') }} ({{ $platform['name'] }})
                                    <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                          aria-label="{{ $platform['versionTooltip'] }}" data-bs-title="{{ $platform['versionTooltip'] }}">
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>
                                <input type="hidden" name="{{ $platform['key'] }}[status]" value="1">
                                <input type="text" class="form-control" name="{{ $platform['key'] }}[version]"
                                       placeholder="{{ $platform['versionPlaceholder'] }}"
                                       data-required-msg="{{ translate(':field is required', ['field' => translate('App version')]) }}" required
                                       value="{{ $data[$platform['key']]['version'] ?? '' }}">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label mb-2 text-capitalize d-flex align-items-center gap-2">
                                    {{ translate('App download url') }} ({{ $platform['name'] }})
                                    <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                          aria-label="{{ $platform['linkTooltip'] }}" data-bs-title="{{ $platform['linkTooltip'] }}">
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>
                                <input type="url" class="form-control" name="{{ $platform['key'] }}[link]"
                                       placeholder="{{ translate('Ex').': '.$platform['linkPlaceholder'] }}"
                                       data-required-msg="{{ translate(':field is required', ['field' => translate('App download url')]) }}" required
                                       value="{{ $data[$platform['key']]['link'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="d-flex flex-wrap justify-content-end gap-3 mt-4">
                <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                <button type="submit" class="btn btn-primary px-3 px-sm-4 w-120">{{ translate('Save') }}</button>
            </div>
        </form>
    </div>
</div>
