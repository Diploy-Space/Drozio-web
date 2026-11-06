@extends('layouts.admin.app')

@section('title', translate('Sitemap'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/seo-settings.svg') }}" alt="">
                {{ translate('SEO Settings') }}
            </h2>
        </div>
        @include('admin-views.seo-settings._inline-menu')

        <div class="card shadow-none">
            <div class="card-header">
                <div class="w-100">
                    <h3 class="mb-0">{{translate('Sitemap')}}</h3>
                    <p class="fs-12 mb-0">{{ translate('Organized for navigation and search engine optimization.') }}</p>
                </div>
            </div>
            <div class="card-body">
                <div
                    class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-20">
                    <i class="fi fi-sr-lightbulb-on text-info"></i>
                    <span>
                        {{ translate('An XML file listing every page of your website.') }}
                        {{ translate('Lists your default pages as an XML sitemap.') }}
                        {{ translate('Lets search engines find and display your products in results.')}}
                    </span>
                </div>
                <div class="text-center py-3">
                    <h4 class="fs-16 mb-3">
                        {{ translate('Generate a new sitemap') }}
                    </h4>
                    <p class="fs-12 text-muted mb-3">
                        {{ translate('The generated file is added to the list below, ready to download.') }}
                    </p>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <a href="{{ config('app.mode') != 'demo' ? route('admin.settings.seo-settings.sitemap-generate') : 'javascript:' }}"
                           class="btn btn-primary px-5 {{ getDemoModeFormButton(type: 'class') }}">
                            {{ translate('Generate sitemap') }}
                        </a>
                        <a href="{{ config('app.mode') != 'demo' ? route('admin.settings.seo-settings.sitemap-generate-upload') : 'javascript:' }}"
                           class="btn btn-outline-primary px-5 {{ getDemoModeFormButton(type: 'class') }}">
                            {{ translate('Generate & Upload to Server') }}
                        </a>
                        <button class="btn btn-outline-primary px-5" data-bs-toggle="modal"
                                data-bs-target="#sitemap-upload-modal" type="button">
                            {{ translate('Upload Sitemap') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-borderless">
                        <thead class="text-uppercase">
                        <tr>
                            <th class="w-95">
                                {{ translate('Serial') }}
                            </th>
                            <th class="w-45">{{ translate('Name') }}</th>
                            <th class="w-200 text-center">{{ translate('File Size') }}</th>
                            <th class="w-200 text-center">{{ translate('Date') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($siteMapList as $siteMapIndex => $siteMap)
                            <tr>
                                <td>
                                    <span class="fw-semibold text-dark">
                                        {{ $siteMapList->firstItem() + $siteMapIndex }}
                                    </span>
                                </td>
                                <td>
                                    <span>{{ $siteMap['name'] }}</span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap justify-content-center">
                                        {{ $siteMap['size'] }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <span>{{ $siteMap['created_at']->diffForHumans() }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a class="btn btn-outline-success btn-outline-success-dark icon-btn {{ getDemoModeFormButton(type: 'class') }}"
                                           href="{{config('app.mode')!='demo'? route('admin.settings.seo-settings.sitemap-download', ['path' => base64_encode($siteMap['name'])]) : 'javascript:'}}">
                                            <i class="fi fi-sr-down-to-line"></i>
                                        </a>

                                        <a class="btn btn-outline-danger icon-btn {{ getDemoModeFormButton(type: 'class') }}"
                                           href="{{ config('app.mode') != 'demo'? route('admin.settings.seo-settings.sitemap-delete', ['path' => base64_encode($siteMap['name'])]) : 'javascript:'}}">
                                            <i class="fi fi-rr-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="page-area px-4 pt-4">
                    <div class="d-flex align-items-center justify-content-end">
                        <div>
                            {{ $siteMapList->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include("admin-views.seo-settings._sitemap-upload-modal")
@endsection

@push('script')
    <script
        src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/business-settings/business-settings.js') }}"></script>
@endpush
