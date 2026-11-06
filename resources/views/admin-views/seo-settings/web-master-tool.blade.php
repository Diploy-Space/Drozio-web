@extends('layouts.admin.app')

@section('title', translate('Webmaster Tools'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20">
            <h1 class="mb-0">
                {{translate('SEO Settings')}}
            </h1>
        </div>
        @include('admin-views.seo-settings._inline-menu')

        <form action="{{route('admin.settings.seo-settings.web-master-tool')}}" method="post">
            @csrf
            <div class="card mb-4">
                <div class="card-header">
                    <div class="w-100">
                        <h2 class="mb-0">{{translate('Webmaster Tools')}}</h2>
                        <p class="m-0 fs-12">
                            {{ translate('Optimize your website\'s performance, indexing status and search visibility') }}
                            <a href="{{ 'https://6amtech.com/blog/webmaster-tools-verification/' }}"
                            target="_blank"
                            class="text-decoration-underline fw-semibold">
                                {{ translate('Learn more') }}
                            </a>
                        </p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-3 gap-sm-20">
                        <div class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded-8 d-flex gap-2 align-items-center">
                            <i class="fi fi-sr-info text-warning"></i>
                            <span>
                                {{ translate('After input') }} <span class="fw-semibold">{{ translate('All Information') }}</span>, {{ translate('Make sure you click') }} <span class="fw-semibold">{{ translate('Save Button') }}</span>.
                            </span>
                        </div>
                        <div class="card shadow-2">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4 col-xl-3">
                                        <img src="{{dynamicAsset('public/assets/new/back-end/img/google-1.png')}}" alt="" width="30">
                                        <h3 class="mt-3">{{translate('Google search console')}}</h3>
                                        <p class="mb-0 fs-12">{{ translate('Optimize your website\'s performance, indexing status and search visibility') }}</p>
                                    </div>
                                    <div class="col-md-8 col-xl-9">
                                        <div class="p-12 p-sm-20 bg-section rounded d-flex flex-column gap-3">
                                            <input type="text" name="google_search_console_code"  value="{{$webMasterToolData['google_search_console_code']}}" placeholder="{{translate('Enter your HTML code or ID')}}" class="form-control">
                                            <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                                <i class="fi fi-sr-lightbulb-on text-info"></i>
                                                <span>&lt;meta name="google-site-verification" content="your-id" /&gt;</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card shadow-2">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4 col-xl-3">
                                        <img src="{{dynamicAsset('public/assets/new/back-end/img/bing-1.png')}}" alt="" width="30">
                                        <h3 class="mt-3">{{translate('Bing webmaster tools')}}</h3>
                                        <p class="mb-0 fs-12">{{ translate('Optimize your website\'s performance, indexing status and search visibility') }}</p>
                                    </div>
                                    <div class="col-md-8 col-xl-9">
                                        <div class="p-12 p-sm-20 bg-section rounded d-flex flex-column gap-3">
                                            <input type="text" name="bing_webmaster_code" value="{{$webMasterToolData['bing_webmaster_code']}}" placeholder="{{translate('Enter your HTML code or ID')}}" class="form-control">
                                            <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                                <i class="fi fi-sr-lightbulb-on text-info"></i>
                                                <span>&lt;meta name= “msvalidate.01” content=”your-id” /&gt;</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card shadow-2">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4 col-xl-3">
                                        <img src="{{dynamicAsset('public/assets/new/back-end/img/baidu-1.png')}}" alt="" width="30">
                                        <h3 class="mt-3">{{translate('Baidu webmaster tool')}}</h3>
                                        <p class="mb-0 fs-12">{{ translate('Optimize your website\'s performance, indexing status and search visibility') }}</p>
                                    </div>
                                    <div class="col-md-8 col-xl-9">
                                        <div class="p-12 p-sm-20 bg-section rounded d-flex flex-column gap-3">
                                            <input type="text"  name="baidu_webmaster_code" value="{{$webMasterToolData['baidu_webmaster_code']}}" placeholder="{{translate('Enter your HTML code or ID')}}" class="form-control">
                                            <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                                <i class="fi fi-sr-lightbulb-on text-info"></i>
                                                <span>&lt;meta name= “baidu-site-verification” content=”your-id” /&gt;</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card shadow-2">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4 col-xl-3">
                                        <img src="{{ dynamicAsset('public/assets/new/back-end/img/yandex-1.png') }}" alt="" width="30">
                                        <h3 class="mt-3">{{translate('Yandex webmaster tool')}}</h3>
                                        <p class="mb-0 fs-12">{{ translate('Optimize your website\'s performance, indexing status and search visibility') }}</p>
                                    </div>
                                    <div class="col-md-8 col-xl-9">
                                        <div class="p-12 p-sm-20 bg-section rounded d-flex flex-column gap-3">
                                            <input type="text"  name="yandex_webmaster_code" value="{{$webMasterToolData['yandex_webmaster_code']}}"  placeholder="{{translate('Enter your HTML code or ID')}}" class="form-control">
                                            <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                                <i class="fi fi-sr-lightbulb-on text-info"></i>
                                                <span> &lt;meta name= “yandex-verification” content=”your-id” /&gt;</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end trans3 action-btn-wrapper-container">
                <div class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="submit" class="btn btn-primary px-3 px-sm-4 {{config('app.mode')!='demo'? '' : 'call-demo-alert'}}">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>
    </div>

@endsection

@push('script')

@endpush
