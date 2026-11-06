@extends('layouts.admin.app')

@section('title', translate('Analytics Script'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/new/back-end/libs/swiper/swiper-bundle.min.css') }}" />
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-chart-line-up text-primary d-flex"></i>
                {{ translate('Marketing Tools') }}
            </h2>
        </div>
        <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
            <i class="fi fi-sr-lightbulb-on text-info"></i>
            <span>
                 {{ translate('Add analytics credentials. Incorrect values will stop analytics from loading.') }}
            </span>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        @php($googleAnalytics = $analyticsData['google_analytics'] ?? null)
                        <form
                            action="{{ config('app.mode') != 'demo' ? route('admin.settings.third-party.analytics-update') : 'javascript:' }}" method="post" enctype="multipart/form-data" id="google-analytics-status-form">
                            @csrf
                            <div class="view-details-container">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                         <h2 class="mb-1">
                                             {{ translate('Google Analytics') }}
                                         </h2>
                                         <p class="mb-0 fs-12">
                                             {{ translate('To know more click') }} <a data-bs-toggle="modal"
                                             href="#modalForGoogleAnalytics" class="fw-semibold text-info-dark text-decoration-underline text-nowrap">{{ translate('How it works') }}.</a>
                                         </p>
                                    </div>
                                    <div class="d-flex gap-2">
                                         <a href="javascript:" class="fs-12 fw-semibold d-flex align-items-end view-btn">
                                             {{ translate('View') }}
                                             <i class="fi fi-rr-arrow-small-down fs-16 trans3"></i>
                                         </a>

                                        <label class="switcher mx-auto" for="google-analytics-status">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="is_active"
                                                id="google-analytics-status"
                                                {{ $googleAnalytics?->is_active == 1 ? 'checked' : '' }}
                                                data-modal-type="input-change-form"
                                                data-modal-form="#google-analytics-status-form"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/google.svg') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/google.svg') }}"
                                                data-on-title="{{ translate('Turn on google analytics') }}"
                                                data-off-title="{{ translate('Turn off google analytics') }}"
                                                data-on-message="<p>{{ translate('Want to Turn ON :item', ['item' => translate('Google Analytics')]) }}? {{ translate('Enable this option to make the marketing tool available for website utilization.') }}</p>"
                                                data-off-message="<p>{{ translate('Want to Turn OFF :item', ['item' => translate('Google Analytics')]) }}? {{ translate('Disable this option to make the marketing tool unavailable for website utilization.') }}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                 </div>
                                 <div class="view-details mt-3 mt-sm-4">
                                    <div class="p-12-mobile px-20 py-4 bg-section rounded d-flex flex-wrap justify-content-end align-items-end gap-sm-20 gap-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label">{{ translate('Google Analytics Measurement ID') }}</label>
                                            <input type="hidden" name="type" value="google_analytics">
                                            <textarea type="text" placeholder="{{ translate('Enter the GA Measurement ID') }}"
                                                      class="form-control min-h-40" rows="1" name="script_id">{!! $googleAnalytics?->script_id ?? '' !!}</textarea>
                                        </div>
                                        <button type="{{ config('app.mode') != 'demo' ? 'submit' : 'button' }}"
                                        class="btn btn-primary px-4 h-40 {{ config('app.mode') != 'demo' ? '' : 'call-demo-alert' }}"
                                        >{{ translate('Save') }}</button>
                                    </div>
                                 </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        @php($googleTagManager = $analyticsData['google_tag_manager'] ?? null)
                        <form
                            action="{{ config('app.mode') != 'demo' ? route('admin.settings.third-party.analytics-update') : 'javascript:' }}"
                            method="post" enctype="multipart/form-data"
                            id="google-tag-manager-status-form"
                        >
                            @csrf
                            <div class="view-details-container">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <h2 class="mb-1">
                                            {{ translate('Google Tag Manager') }}
                                        </h2>
                                        <p class="mb-0 fs-12">
                                            {{ translate('To know more click') }} <a data-bs-toggle="modal"
                                                                                     href="#modalForGoogleTagManager" class="fw-semibold text-info-dark text-decoration-underline text-nowrap">{{ translate('How it works') }}.</a>
                                        </p>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="javascript:" class="fs-12 fw-semibold d-flex align-items-end view-btn ">{{ translate('View') }} <i class="fi fi-rr-arrow-small-down fs-16 trans3"></i></a>

                                        <label class="switcher" for="google-tag-manager-status">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="is_active"
                                                id="google-tag-manager-status"
                                                {{ $googleTagManager?->is_active == 1 ? 'checked' : '' }}
                                                data-modal-type="input-change-form"
                                                data-modal-form="#google-tag-manager-status-form"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/google.svg') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/google.svg') }}"
                                                data-on-title="{{ translate('Turn on google tag manager') }}"
                                                data-off-title="{{ translate('Turn off google tag manager') }}"
                                                data-on-message="<p>{{ translate('Want to Turn ON :item', ['item' => translate('Google Tag Manager')]) }}? {{ translate('Enable this option to make the marketing tool available for website utilization.') }}</p>"
                                                data-off-message="<p>{{ translate('Want to Turn OFF :item', ['item' => translate('Google Tag Manager')]) }}? {{ translate('Disable this option to make the marketing tool unavailable for website utilization.') }}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="view-details mt-3 mt-sm-4">
                                    <div class="p-12-mobile px-20 py-4 bg-section rounded d-flex flex-wrap justify-content-end align-items-end gap-sm-20 gap-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label">{{ translate('Google Tag Manager Container ID') }}</label>
                                            <input type="hidden" name="type" value="google_tag_manager">
                                            <textarea type="text" placeholder="{{ translate('Enter the GTM Container ID') }}"
                                                      class="form-control min-h-40" rows="1" name="script_id">{!! $googleTagManager?->script_id ?? '' !!}</textarea>
                                        </div>
                                        <button type="{{ config('app.mode') != 'demo' ? 'submit' : 'button' }}"
                                                class="btn btn-primary px-4 h-40 {{ config('app.mode') != 'demo' ? '' : 'call-demo-alert' }}"
                                        >{{ translate('Save') }}</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        @php($linkedinInsight = $analyticsData['linkedin_insight'] ?? null)
                        <form
                            action="{{ config('app.mode') != 'demo' ? route('admin.settings.third-party.analytics-update') : 'javascript:' }}"
                            method="post" enctype="multipart/form-data"
                            id="linkedin-insight-status-form"
                        >
                            @csrf
                            <div class="view-details-container">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                         <h2 class="mb-1">
                                             {{ translate('LinkedIn Insight Tag') }}
                                         </h2>
                                        <p class="mb-0 fs-12">
                                            {{ translate('To know more click') }} <a data-bs-toggle="modal"
                                                                                    href="#modalForLinkedInInsight" class="fw-semibold text-info-dark text-decoration-underline text-nowrap">{{ translate('How it works') }}.</a>
                                        </p>
                                    </div>
                                    <div class="d-flex gap-2">
                                         <a href="javascript:" class="fs-12 fw-semibold d-flex align-items-end view-btn ">{{ translate('View') }} <i class="fi fi-rr-arrow-small-down fs-16 trans3"></i></a>
                                        <label class="switcher" for="linkedin-insight-status">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="is_active"
                                                id="linkedin-insight-status"
                                                {{ $linkedinInsight?->is_active == 1 ? 'checked' : '' }}
                                                data-modal-type="input-change-form"
                                                data-modal-form="#linkedin-insight-status-form"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/linkedin.svg') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/linkedin.svg') }}"
                                                data-on-title="{{ translate('Turn on linkedin insight') }}"
                                                data-off-title="{{ translate('Turn off linkedin insight') }}"
                                                data-on-message="<p>{{ translate('Want to Turn ON :item', ['item' => translate('Linkedin insight')]) }}? {{ translate('Enable this option to make the marketing tool available for website utilization.') }}</p>"
                                                data-off-message="<p>{{ translate('Want to Turn OFF :item', ['item' => translate('Linkedin insight')]) }}? {{ translate('Disable this option to make the marketing tool unavailable for website utilization.') }}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                 </div>
                                 <div class="view-details mt-3 mt-sm-4">
                                    <div class="p-12-mobile px-20 py-4 bg-section rounded d-flex flex-wrap justify-content-end align-items-end gap-sm-20 gap-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label">{{ translate('Linkedin insight tag id') }}</label>
                                            <input type="hidden" name="type" value="linkedin_insight">
                                            <textarea type="text" placeholder="{{ translate('Enter Linkedin insight tag id') }}"
                                                      class="form-control min-h-40" rows="1" name="script_id">{!! $linkedinInsight?->script_id ?? '' !!}</textarea>
                                        </div>
                                        <button type="{{ config('app.mode') != 'demo' ? 'submit' : 'button' }}"
                                        class="btn btn-primary px-4 h-40 {{ config('app.mode') != 'demo' ? '' : 'call-demo-alert' }}"
                                        >{{ translate('Save') }}</button>
                                    </div>
                                 </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        @php($metaPixel = $analyticsData['meta_pixel'] ?? null)
                        <form
                            action="{{ config('app.mode') != 'demo' ? route('admin.settings.third-party.analytics-update') : 'javascript:' }}"
                            method="post" enctype="multipart/form-data"
                            id="meta-pixel-status-form"
                        >
                            @csrf

                            <div class="view-details-container">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                         <h2 class="mb-1">
                                             {{ translate('Meta Pixel') }}
                                         </h2>
                                        <p class="mb-0 fs-12">
                                            {{ translate('To know more click') }} <a data-bs-toggle="modal"
                                                                                      href="#modalForFacebookMeta" class="fw-semibold text-info-dark text-decoration-underline text-nowrap">{{ translate('How it works') }}.</a>
                                        </p>
                                    </div>
                                    <div class="d-flex gap-2">
                                         <a href="javascript:" class="fs-12 fw-semibold d-flex align-items-end view-btn ">{{ translate('View') }} <i class="fi fi-rr-arrow-small-down fs-16 trans3"></i></a>
                                        <label class="switcher" for="meta-pixel-status">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="is_active"
                                                id="meta-pixel-status"
                                                {{ $metaPixel?->is_active == 1 ? 'checked' : '' }}
                                                data-modal-type="input-change-form"
                                                data-modal-form="#meta-pixel-status-form"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/facebook.svg') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/facebook.svg') }}"
                                                data-on-title="{{ translate('Turn on meta pixel') }}"
                                                data-off-title="{{ translate('Turn off meta pixel') }}"
                                                data-on-message="<p>{{ translate('Want to Turn ON :item', ['item' => translate('Meta Pixel')]) }}? {{ translate('Enable this option to make the marketing tool available for website utilization.') }}</p>"
                                                data-off-message="<p>{{ translate('Want to Turn OFF :item', ['item' => translate('Meta Pixel')]) }}? {{ translate('Disable this option to make the marketing tool unavailable for website utilization.') }}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                 </div>
                                 <div class="view-details mt-3 mt-sm-4">
                                    <div class="p-12-mobile px-20 py-4 bg-section rounded d-flex flex-wrap justify-content-end align-items-end gap-sm-20 gap-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label">{{ translate('Meta Pixel ID') }}</label>
                                            <input type="hidden" name="type" value="meta_pixel">
                                            <textarea type="text" placeholder="{{ translate('Enter the Meta Pixel ID') }}"
                                                      class="form-control min-h-40" rows="1" name="script_id">{!! $metaPixel?->script_id ?? '' !!}</textarea>
                                        </div>
                                        <button type="{{ config('app.mode') != 'demo' ? 'submit' : 'button' }}"
                                        class="btn btn-primary px-4 h-40 {{ config('app.mode') != 'demo' ? '' : 'call-demo-alert' }}"
                                        >{{ translate('Save') }}</button>
                                    </div>
                                 </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        @php($pinterestTag = $analyticsData['pinterest_tag'] ?? null)
                        <form
                            action="{{ config('app.mode') != 'demo' ? route('admin.settings.third-party.analytics-update') : 'javascript:' }}"
                            method="post" enctype="multipart/form-data"
                            id="pinterest-tag-status-form"
                        >
                            @csrf

                            <div class="view-details-container">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                         <h2 class="mb-1">
                                             {{ translate('Pinterest Pixel') }}
                                         </h2>
                                        <p class="mb-0 fs-12">
                                            {{ translate('To know more click') }} <a data-bs-toggle="modal" href="#modalForPinterestPixel" class="fw-semibold text-info-dark text-decoration-underline text-nowrap">
                                                {{ translate('How it works') }}.</a>
                                        </p>
                                    </div>
                                    <div class="d-flex gap-2">
                                         <a href="javascript:" class="fs-12 fw-semibold d-flex align-items-end view-btn ">{{ translate('View') }} <i class="fi fi-rr-arrow-small-down fs-16 trans3"></i></a>
                                        <label class="switcher" for="pinterest-tag-status">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="is_active"
                                                id="pinterest-tag-status"
                                                {{ $pinterestTag?->is_active == 1 ? 'checked' : '' }}
                                                data-modal-type="input-change-form"
                                                data-modal-form="#pinterest-tag-status-form"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/pinterest.svg') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/pinterest.svg') }}"
                                                data-on-title="{{ translate('Turn on pinterest pixel') }}"
                                                data-off-title="{{ translate('Turn off pinterest pixel') }}"
                                                data-on-message="<p>{{ translate('Want to Turn ON :item', ['item' => translate('Pinterest Pixel')]) }}? {{ translate('Enable this option to make the marketing tool available for website utilization.') }}</p>"
                                                data-off-message="<p>{{ translate('Want to Turn OFF :item', ['item' => translate('Pinterest Pixel')]) }}? {{ translate('Disable this option to make the marketing tool unavailable for website utilization.') }}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                 </div>
                                 <div class="view-details mt-3 mt-sm-4">
                                    <div class="p-12-mobile px-20 py-4 bg-section rounded d-flex flex-wrap justify-content-end align-items-end gap-sm-20 gap-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label">{{ translate('Pinterest Tag ID') }}</label>
                                            <input type="hidden" name="type" value="pinterest_tag">
                                            <textarea type="text" placeholder="{{ translate('Enter the Pinterest Tag ID') }}"
                                                      class="form-control min-h-40" rows="1" name="script_id">{!! $pinterestTag?->script_id ?? '' !!}</textarea>
                                        </div>
                                        <button type="{{ config('app.mode') != 'demo' ? 'submit' : 'button' }}"
                                        class="btn btn-primary px-4 h-40 {{ config('app.mode') != 'demo' ? '' : 'call-demo-alert' }}"
                                        >{{ translate('Save') }}</button>
                                    </div>
                                 </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        @php($snapchatTag = $analyticsData['snapchat_tag'] ?? null)
                        <form
                            action="{{ config('app.mode') != 'demo' ? route('admin.settings.third-party.analytics-update') : 'javascript:' }}"
                            method="post" enctype="multipart/form-data"
                            id="snapchat-tag-status-form"
                        >
                            @csrf

                            <div class="view-details-container">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                         <h2 class="mb-1">
                                             {{ translate('Snapchat Pixel') }}
                                         </h2>
                                         <p class="mb-0 fs-12">
                                             {{ translate('To know more click') }} <a data-bs-toggle="modal"
                                             href="#modalForSnapchatPixel" class="fw-semibold text-info-dark text-decoration-underline text-nowrap">{{ translate('How it works') }}.</a>
                                         </p>
                                    </div>
                                    <div class="d-flex gap-2">
                                         <a href="javascript:" class="fs-12 fw-semibold d-flex align-items-end view-btn ">{{ translate('View') }} <i class="fi fi-rr-arrow-small-down fs-16 trans3"></i></a>
                                        <label class="switcher" for="snapchat-tag-status">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="is_active"
                                                id="snapchat-tag-status"
                                                {{ $snapchatTag?->is_active == 1 ? 'checked' : '' }}
                                                data-modal-type="input-change-form"
                                                data-modal-form="#snapchat-tag-status-form"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/snapchat.svg') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/snapchat.svg') }}"
                                                data-on-title="{{ translate('Turn on Snapchat Pixel') }}"
                                                data-off-title="{{ translate('Turn Off Snapchat Pixel') }}"
                                                data-on-message="<p>{{ translate('Want to Turn ON :item', ['item' => translate('Snapchat Pixel')]) }}? {{ translate('Enable this option to make the marketing tool available for website utilization.') }}</p>"
                                                data-off-message="<p>{{ translate('Want to Turn OFF :item', ['item' => translate('Snapchat Pixel')]) }}? {{ translate('Disable this option to make the marketing tool unavailable for website utilization.') }}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                 </div>
                                 <div class="view-details mt-3 mt-sm-4">
                                    <div class="p-12-mobile px-20 py-4 bg-section rounded d-flex flex-wrap justify-content-end align-items-end gap-sm-20 gap-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label">{{ translate('Snap Pixel ID') }}</label>
                                            <input type="hidden" name="type" value="snapchat_tag">
                                            <textarea type="text" placeholder="{{ translate('Enter the Snap Pixel ID') }}"
                                                      class="form-control min-h-40" rows="1" name="script_id">{!! $snapchatTag?->script_id ?? '' !!}</textarea>
                                        </div>
                                        <button type="{{ config('app.mode') != 'demo' ? 'submit' : 'button' }}"
                                        class="btn btn-primary px-4 h-40 {{ config('app.mode') != 'demo' ? '' : 'call-demo-alert' }}"
                                        >{{ translate('Save') }}</button>
                                    </div>
                                 </div>
                            </div>


                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        @php($tiktokTag = $analyticsData['tiktok_tag'] ?? null)
                        <form
                            action="{{ config('app.mode') != 'demo' ? route('admin.settings.third-party.analytics-update') : 'javascript:' }}"
                            method="post" enctype="multipart/form-data"
                            id="tiktok-tag-status-form"
                        >
                            @csrf

                            <div class="view-details-container">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                         <h2 class="mb-1">
                                             {{ translate('TikTok Pixel') }}
                                         </h2>
                                        <p class="mb-0 fs-12">
                                            {{ translate('To know more click') }} <a data-bs-toggle="modal" href="#modalForTikTokPixel" class="fw-semibold text-info-dark text-decoration-underline text-nowrap">{{ translate('How it works') }}.</a>
                                        </p>
                                    </div>
                                    <div class="d-flex gap-2">
                                         <a href="javascript:" class="fs-12 fw-semibold d-flex align-items-end view-btn ">{{ translate('View') }} <i class="fi fi-rr-arrow-small-down fs-16 trans3"></i></a>
                                        <label class="switcher" for="tiktok-tag-status">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="is_active"
                                                id="tiktok-tag-status"
                                                {{ $tiktokTag?->is_active == 1 ? 'checked' : '' }}
                                                data-modal-type="input-change-form"
                                                data-modal-form="#tiktok-tag-status-form"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/tiktok.svg') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/tiktok.svg') }}"
                                                data-on-title="{{ translate('Turn ON Tiktok Pixel') }}"
                                                data-off-title="{{ translate('Turn OFF Tiktok Pixel') }}"
                                                data-on-message="<p>{{ translate('Want to Turn ON :item', ['item' => translate('TikTok Pixel')]) }}? {{ translate('Enable this option to make the marketing tool available for website utilization.') }}</p>"
                                                data-off-message="<p>{{ translate('Want to Turn ON :item', ['item' => translate('TikTok Pixel')]) }}? {{ translate('Disable this option to make the marketing tool unavailable for website utilization.') }}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                 </div>
                                 <div class="view-details mt-3 mt-sm-4">
                                    <div class="p-12-mobile px-20 py-4 bg-section rounded d-flex flex-wrap justify-content-end align-items-end gap-sm-20 gap-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label">{{ translate('TikTok Pixel ID') }}</label>
                                            <input type="hidden" name="type" value="tiktok_tag">
                                            <textarea type="text" placeholder="{{ translate('Enter the TikTok Pixel ID') }}"
                                                      class="form-control min-h-40" rows="1" name="script_id">{!! $tiktokTag?->script_id ?? '' !!}</textarea>
                                        </div>
                                        <button type="{{ config('app.mode') != 'demo' ? 'submit' : 'button' }}"
                                        class="btn btn-primary px-4 h-40 {{ config('app.mode') != 'demo' ? '' : 'call-demo-alert' }}"
                                        >{{ translate('Save') }}</button>
                                    </div>
                                 </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        @php($twitterTag = $analyticsData['twitter_tag'] ?? null)
                        <form
                            action="{{ config('app.mode') != 'demo' ? route('admin.settings.third-party.analytics-update') : 'javascript:' }}"
                            method="post" enctype="multipart/form-data"
                            id="twitter-tag-status-form"
                        >
                            @csrf

                            <div class="view-details-container">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                         <h2 class="mb-1">
                                            {{ translate('X') }} ({{ translate('Twitter') }}) {{ translate('Pixel') }}
                                         </h2>
                                         <p class="mb-0 fs-12">
                                             {{ translate('To know more click') }} <a data-bs-toggle="modal"
                                             href="#modalForTwitterPixel" class="fw-semibold text-info-dark text-decoration-underline text-nowrap">{{ translate('How it works') }}.</a>
                                         </p>
                                    </div>
                                    <div class="d-flex gap-2">
                                         <a href="javascript:" class="fs-12 fw-semibold d-flex align-items-end view-btn ">{{ translate('View') }} <i class="fi fi-rr-arrow-small-down fs-16 trans3"></i></a>
                                        <label class="switcher" for="twitter-tag-status">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="is_active"
                                                id="twitter-tag-status"
                                                {{ $twitterTag?->is_active == 1 ? 'checked' : '' }}
                                                data-modal-type="input-change-form"
                                                data-modal-form="#twitter-tag-status-form"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/twitter.svg') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/twitter.svg') }}"
                                                data-on-title="{{ translate('Turn on twitter pixel') }}"
                                                data-off-title="{{ translate('Turn off twitter pixel') }}"
                                                data-on-message="<p>{{ translate('Want to Turn ON :item', ['item' => translate('Twitter pixel')]) }}? {{ translate('Enable this option to make the marketing tool available for website utilization.') }}</p>"
                                                data-off-message="<p>{{ translate('Want to Turn ON :item', ['item' => translate('Twitter pixel')]) }}? {{ translate('Disable this option to make the marketing tool unavailable for website utilization.') }}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                 </div>
                                 <div class="view-details mt-3 mt-sm-4">
                                    <div class="p-12-mobile px-20 py-4 bg-section rounded d-flex flex-wrap justify-content-end align-items-end gap-sm-20 gap-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label">{{ translate('Pixel ID') }}</label>
                                            <input type="hidden" name="type" value="twitter_tag">
                                            <textarea type="text" placeholder="{{ translate('Enter the Pixel ID') }}"
                                                      class="form-control min-h-40" rows="1" name="script_id">{!! $twitterTag?->script_id ?? '' !!}</textarea>
                                        </div>
                                        <button type="{{ config('app.mode') != 'demo' ? 'submit' : 'button' }}"
                                        class="btn btn-primary px-4 h-40 {{ config('app.mode') != 'demo' ? '' : 'call-demo-alert' }}"
                                        >{{ translate('Save') }}</button>
                                    </div>
                                 </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include("admin-views.third-party.analytics._information-modal")
    @include("admin-views.third-party.analytics._google-analytics-modal")
    @include("admin-views.third-party.analytics._google-tag-manager-modal")
    @include("admin-views.third-party.analytics._linkedin-insight-modal")
    @include("admin-views.third-party.analytics._facebook-meta-pixel-modal")
    @include("admin-views.third-party.analytics._pinterest-tag-modal")
    @include("admin-views.third-party.analytics._snapchat-tag-modal")
    @include("admin-views.third-party.analytics._tiktok-tag-modal")
    @include("admin-views.third-party.analytics._twitter-modal")

    @include("layouts.admin.partials.offcanvas._analytics-setup")
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/libs/swiper/swiper-bundle.min.js') }}"></script>
    <script>

    </script>
@endpush
