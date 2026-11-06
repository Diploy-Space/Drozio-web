<div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
        <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/'.$icon) }}" alt="">
        {{ ($showModule ?? true) ? translate(reportModuleLabel()) : '' }} {{ translate($title) }}
        @isset($tooltip)
            <span class="tooltip-icon mt-1" data-toggle="tooltip" data-placement="right"
                  aria-label="{{ $tooltip }}" title="{{ $tooltip }}">
                <i class="fi fi-sr-info"></i>
            </span>
        @endisset
    </h2>

    @isset($switchPage)
        @include('vendor-views.report._business-module-switcher', ['switchPage' => $switchPage])
    @endisset
</div>
