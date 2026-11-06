@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Website Setup') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseWebsiteSetup_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Websites & Panels') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseWebsiteSetup_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Your storefront — what customers see when they visit.') }} {{ translate('They browse products, read descriptions, and place orders there.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('The panel is like a back room or office.') }} {{ translate('Where the shop owner manages products, orders, sales, shipping and payments.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseWebsiteSetup_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Logo & Loader') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseWebsiteSetup_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('The visual symbol customers recognise your brand by.') }}
                    </p>
                    <ul class="d-flex flex-column gap-12 fs-12">
                        <li>
                            <strong> {{ translate('Header logo:') }}</strong>
                            {{ translate('Small logo shown at the top of every page.') }}
                            {{ translate('A small mark that stays visible as customers browse the site.') }}
                        </li>
                        <li>
                            <strong> {{ translate('Footer logo:') }}</strong>
                            {{ translate('Small version of the brand image shown in the footer.') }}
                            {{ translate('A closing mark of your brand at the foot of the page.') }}
                        </li>
                        <li>
                            <strong> {{ translate('Favicon:') }}</strong>
                            {{ translate('Small icon shown beside your site name in the browser tab.') }}
                        </li>
                        <li>
                            <strong> {{ translate('Loading GIF') }}</strong>
                            {{ translate('The spinning animation shown while a page loads.') }}
                            {{ translate('It could be a spinning circle, bouncing dots, or something else.') }}
                        </li>
                    </ul>
                    <p class="fs-12">
                        {{ translate('The spinning animation shown while a page loads.') }}
                   </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseWebsiteSetup_03" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Color Settings') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseWebsiteSetup_03">
                <div class="card card-body">
                    <ul class="d-flex flex-column gap-2 fs-12">
                        <li>
                            <strong>{{ translate('Primary Color') }}</strong>
                            {{ translate('The main colour on buttons, links and key parts of the site.') }}
                            {{ translate('The most prominent colour, used to signal your brand and key actions.') }}
                        </li>
                        <li>
                             <strong>{{ translate('Secondary Color') }}</strong>
                             {{ translate('The secondary color is like its helpful supporting actor.') }}
                             {{ translate('A supporting colour for highlights and accents alongside your main colour.') }}
                             <br>
                             {{ translate('Colour of the left-side menu area.') }}
                        </li>
                        <li>
                             <strong>{{ translate('Primary light color') }}</strong>
                             {{ translate('A brighter, lighter version of the main colour.') }}
                             {{ translate('Use for buttons, highlights and accents that complement your main brand colour.') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseWebsiteSetup_04" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Footer app download button') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseWebsiteSetup_04">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('App download button shown in the site footer.') }}
                        {{ translate('Invites customers to download the app from Play Store or App Store.') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
