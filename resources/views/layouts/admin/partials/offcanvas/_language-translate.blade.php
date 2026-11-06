@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Language Translations') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseTranslate_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Language Content Table') }}</span>
                </button>
            </div>
            <div class="collapse mt-3 show" id="collapseTranslate_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Lists every translatable key for the selected language with its translation.') }}
                        {{ translate('Stat cards show total, translated and untranslated counts and overall completion.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseTranslate_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Auto Translate') }} &amp; {{ translate('Save') }}</span>
                </button>
            </div>
            <div class="collapse mt-3" id="collapseTranslate_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Click the auto translate icon on a row to translate it.') }}
                        {{ translate('Type a value and click save, or press enter.') }}
                        {{ translate('A saved key moves to translated and the counts update automatically.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseTranslate_03" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Translate All') }}</span>
                </button>
            </div>
            <div class="collapse mt-3" id="collapseTranslate_03">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Translate All translates every untranslated key in the background.') }}
                        {{ translate('A progress bar shows the time remaining. Keep this page open.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseTranslate_04" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Sync Languages') }}</span>
                </button>
            </div>
            <div class="collapse mt-3" id="collapseTranslate_04">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Sync languages aligns every language to the English master key set.') }}
                        {{ translate('Missing keys are added, extra keys removed, existing translations kept.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseTranslate_05" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Filter') }} &amp; {{ translate('Search') }}</span>
                </button>
            </div>
            <div class="collapse mt-3" id="collapseTranslate_05">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Click a stat card to filter the list by that status.') }}
                        {{ translate('Search for a key by name or translated value.') }}
                    </p>
                </div>
            </div>
        </div>

        @if(config('app.development_environment'))
            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                    <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapseTranslate_06" aria-expanded="true">
                        <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                            <i class="fi fi-sr-angle-right"></i>
                        </div>
                        <span class="fw-bold text-start">{{ translate('Remove Unused Keys') }}</span>
                    </button>
                </div>
                <div class="collapse mt-3" id="collapseTranslate_06">
                    <div class="card card-body">
                        <p class="fs-12">
                            {{ translate('Development tool that removes keys no longer referenced in the codebase.') }}
                            {{ translate('Review the preview before removing since this action cannot be undone.') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
