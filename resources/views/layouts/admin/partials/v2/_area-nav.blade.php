@php
    $bmCompact = $compact ?? false;
    $bmArea = activeAdminArea();
    $bmCommonAreas = businessModuleManager()->permittedCommonAreas();
@endphp

@include('layouts.admin.partials.v2._business-switcher', [
    'compact' => $bmCompact,
    'id' => $bmCompact ? 'businessSwitcherMobile' : 'businessSwitcherDesktop',
])

<div class="d-flex align-items-center {{ $bmCompact ? 'gap-sm-3 gap-2' : 'gap-20' }}">
    @foreach ($bmCommonAreas as $bmAreaKey => $bmCommonArea)
        @php $bmAreaUrl = adminAreaUrl($bmAreaKey); @endphp
        @continue($bmAreaUrl === '')
        <a href="{{ $bmAreaUrl }}"
           class="v2-area-link fs-14 d-flex align-items-center {{ $bmCompact ? 'gap-sm-2 gap-1' : 'gap-2' }} {{ $bmArea->key === $bmAreaKey ? 'v2-area-link-active' : 'text-dark' }}">
            <img width="18" height="18" src="{{ dynamicAsset(path: 'public/assets/front-end/img/icons/' . $bmCommonArea->icon) }}" alt="icon" class="svg">
            <span>{{ translate($bmCommonArea->label) }}</span>
        </a>
    @endforeach
</div>
