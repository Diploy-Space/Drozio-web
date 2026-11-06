{{-- Rail-footer account card: identity, language, settings and logout. The
     design puts these at the bottom of the sidebar rather than in the header,
     so the header carries only workspace navigation and live counters. --}}
@php
    $v2ProfileAdmin = auth('admin')->user();
    $v2ProfileLocal = session()->has('local') ? session('local') : 'en';
    $v2ProfileLanguages = getWebConfig(name: 'language') ?? [];

    // Flag code comes from the DB-backed language row, so nothing is guessed
    // client-side. A hyphenated code (pt-BR) flags as its leading segment.
    $v2ProfileFlag = $v2ProfileLocal;
    foreach ($v2ProfileLanguages as $v2ProfileLangItem) {
        if (($v2ProfileLangItem['code'] ?? '') === $v2ProfileLocal) {
            $v2ProfileFlag = \Illuminate\Support\Str::contains($v2ProfileLangItem['code'], '-')
                ? strtolower(explode('-', $v2ProfileLangItem['code'])[0])
                : strtolower($v2ProfileLangItem['code']);
        }
    }
@endphp

<div class="v2-dropdown v2-rail-profile" id="v2-rail-profile">
    <button class="v2-rail-avatar" type="button" data-v2-dropdown-toggle
            data-v2-tooltip="{{ $v2ProfileAdmin->name ?? '' }}" aria-label="{{ $v2ProfileAdmin->name ?? '' }}">
        <img src="{{ getStorageImages(path: $v2ProfileAdmin->image_full_url ?? '', type: 'backend-profile') }}"
             alt="{{ translate('Image description') }}">
    </button>

    <div class="v2-dropdown-menu v2-profile-card">
        <div class="v2-profile-card-head">
            <img src="{{ getStorageImages(path: $v2ProfileAdmin->image_full_url ?? '', type: 'backend-profile') }}"
                 alt="{{ translate('Image description') }}">
            <div class="v2-profile-card-id">
                <span class="v2-profile-card-name">{{ $v2ProfileAdmin->name ?? '' }}</span>
                <span class="v2-profile-card-role">{{ ucwords($v2ProfileAdmin?->role?->name ?? '') }}</span>
            </div>
        </div>

        <div class="v2-profile-card-body">
            {{-- <details> rather than a nested .v2-dropdown: admin-v2.js closes
                 every open dropdown before opening one, so a nested toggle would
                 close the card it sits in. --}}
            <details class="v2-profile-lang">
                <summary class="v2-profile-lang-btn">
                    <span>{{ translate('Language') }}</span>
                    <span class="v2-profile-lang-current">
                        <img width="18" height="18"
                             src="{{ dynamicAsset(path: 'public/assets/front-end/img/flags/' . $v2ProfileFlag . '.png') }}"
                             alt="{{ $v2ProfileLocal }}">
                        <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m5 8 5 5 5-5"/>
                        </svg>
                    </span>
                </summary>
                <div class="v2-profile-lang-menu">
                    @foreach ($v2ProfileLanguages as $v2ProfileLang)
                        @php
                            $v2ProfileLangCode = $v2ProfileLang['code'] ?? '';
                            $v2ProfileLangFlag = \Illuminate\Support\Str::contains($v2ProfileLangCode, '-')
                                ? strtolower(explode('-', $v2ProfileLangCode)[0])
                                : strtolower($v2ProfileLangCode);
                        @endphp
                        @if (($v2ProfileLang['status'] ?? 0) == 1)
                            <a class="v2-dropdown-item change-language {{ $v2ProfileLangCode == $v2ProfileLocal ? 'v2-is-active' : '' }}"
                               href="javascript:"
                               data-action="{{ route('change-language') }}"
                               data-language-code="{{ $v2ProfileLangCode }}">
                                <img width="20" height="20" style="border-radius:50%;object-fit:cover;"
                                     src="{{ dynamicAsset(path: 'public/assets/front-end/img/flags/' . $v2ProfileLangFlag . '.png') }}"
                                     alt="{{ $v2ProfileLang['name'] ?? $v2ProfileLangCode }}">
                                <span class="text-capitalize">{{ $v2ProfileLang['name'] ?? $v2ProfileLangCode }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </details>

            <a class="v2-dropdown-item" href="{{ route('admin.profile.update', ['id' => $v2ProfileAdmin->id ?? '']) }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20.5a8 8 0 0 1 16 0"/>
                </svg>
                <span>{{ translate('Profile') }}</span>
            </a>

            <a class="v2-dropdown-item v2-dropdown-item-danger" href="javascript:"
               data-bs-toggle="modal" data-bs-target="#sign-out-modal">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <path d="m16 17 5-5-5-5M21 12H9"/>
                </svg>
                <span>{{ translate('Logout') }}</span>
            </a>
        </div>
    </div>
</div>
