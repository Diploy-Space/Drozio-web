<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <div>
            <h3 class="mb-1">{{ translate('AI Configuration Guideline') }}</h3>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}"></button>
    </div>

    <div class="offcanvas-body">
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapsePurpose" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Purpose') }}</span>
                </button>
            </div>

            <div class="collapse mt-3 show" id="collapsePurpose">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('Set your AI provider credentials and usage limits.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseAiFeatureToggle" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('AI Feature Toggle') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseAiFeatureToggle">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Turns AI features on or off for your platform.') }}
                    </p>
                    <ul class="fs-12 mb-0">
                        <li>{{ translate('When ON') }}: {{ translate('AI tools like content and image generation will work.') }}</li>
                        <li>{{ translate('When OFF') }}: {{ translate('All AI features will stop working until you turn it back on.') }}</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseEnableOpenAI" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Enable OpenAl Configuration') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseEnableOpenAI">
                <div class="card card-body">
                    <ul class="fs-12 mb-0">
                        <li>
                            {{ translate('Go to the OpenAI API platform and') }}
                            <a target="_blank" href="{{ 'https://platform.openai.com/docs/overview' }}">{{ translate('Sign up') }}</a>
                            <span class="px-1">{{ translate('Or') }}</span>
                            <a target="_blank" href="{{ 'https://platform.openai.com/docs/overview' }}">{{ translate('Login') }}</a>
                        </li>
                        <li>{{ translate('Create an API key and paste it into the field below.') }}</li>
                        <li>{{ translate('Enter your OpenAI Organization ID for access and billing.') }}</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseTip" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Tip') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseTip">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('Enter correct API details and limits so the AI tools work.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
