@php
    $v2CommonMenuArea = activeAdminArea();
    $v2CommonMenuAreas = businessModuleManager()->permittedCommonAreas();
@endphp

<div class="offcanvas offcanvas-end v2-common-menu d-lg-none" tabindex="-1" id="v2CommonMenuOffcanvas"
     aria-labelledby="v2CommonMenuOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold" id="v2CommonMenuOffcanvasLabel">{{ translate('Common Menu') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}"></button>
    </div>
    <div class="offcanvas-body pt-0">
        <a class="v2-common-menu-item" href="{{ route('home') }}" target="_blank" rel="noopener">
            <span class="v2-common-menu-icon"><i class="fi fi-rr-globe"></i></span>
            <span>{{ translate('Visit Website') }}</span>
        </a>

        @foreach ($v2CommonMenuAreas as $v2CommonMenuKey => $v2CommonMenuItem)
            @php $v2CommonMenuUrl = adminAreaUrl($v2CommonMenuKey); @endphp
            @continue($v2CommonMenuUrl === '')
            <a class="v2-common-menu-item {{ $v2CommonMenuArea->key === $v2CommonMenuKey ? 'v2-common-menu-item-active' : '' }}"
               href="{{ $v2CommonMenuUrl }}"
               @if($v2CommonMenuArea->key === $v2CommonMenuKey) aria-current="page" @endif>
                <span class="v2-common-menu-icon">
                    <img width="20" height="20" src="{{ dynamicAsset(path: 'public/assets/front-end/img/icons/' . $v2CommonMenuItem->icon) }}" alt="" class="svg">
                </span>
                <span>{{ translate($v2CommonMenuItem->label) }}</span>
            </a>
        @endforeach
    </div>
</div>
