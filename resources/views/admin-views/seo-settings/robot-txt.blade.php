@extends('layouts.admin.app')

@section('title', translate('Robots.txt'))

@section('content')
    <div class="content container-fluid">
        <div class="row g-3 align-items-center mb-3">
            <div class="{{ config('app.mode') == 'demo' ? 'col-md-8' : 'col-md-12' }}">
                <h1 class="mb-3 sm-sm-20">
                    {{ translate('SEO Settings') }}
                </h1>

                @include('admin-views.seo-settings._inline-menu')
            </div>

            @if(config('app.mode') == 'demo')
                <div class="col-md-4">
                    <div
                        class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                        <i class="fi fi-sr-lightbulb-on text-warning"></i>
                        <span>
                            {{ translate('Robots.txt tells search engines which parts of your site to crawl.') }}
                            {{ translate('Note') }}:
                        <span class="fw-semibold">{{ translate('This feature is disabled for demo.') }}</span>
                    </span>
                    </div>
                </div>
            @endif
        </div>


        <form action="{{ route('admin.settings.seo-settings.update-robot-text') }}" method="post">
            @csrf
            <div class="card mb-4">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="">
                        <h2>{{ translate('Robots.txt Editor') }}</h2>
                        <p class="fs-12 mb-0">{{translate('Control search engine crawlers access to specific pages on a website').'.'}}</p>
                    </div>

                    @if(file_exists(base_path('robots.txt')))
                        <a class="btn btn-primary" href="{{ url('/robots.txt') }}" target="_blank">
                            <span class="fw-semibold txt">{{translate('View URL')}}</span>
                            <i class="fi fi-rr-up-right-from-square"></i>
                        </a>
                    @else
                        <button class="btn btn-primary disabled" data-bs-toggle="tooltip"
                                data-bs-title="{{ translate('Empty File') }}">
                            <span class="fw-semibold txt">{{translate('View URL')}}</span>
                            <i class="fi fi-rr-up-right-from-square"></i>
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <div
                        class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3 mb-sm-20">
                        <i class="fi fi-sr-lightbulb-on text-info"></i>
                        @if(config('app.mode') == 'demo')
                            <span>
                                {{ translate('Robots.txt tells search engines which parts of your site to crawl.') }} {{ translate('Note') }}:{{ translate('This feature is disabled for demo.') }}
                            </span>
                        @else
                            <span>
                                {{ translate('Robots.txt tells search engines which parts of your site to crawl.') }} {{ translate('Note') }}:{{ translate('The system will automatically generate a robots.txt for your site.') }} {{ translate('You do not have to create it manually.') }} {{ translate('But you can edit or modify this robots.txt.') }}
                            </span>
                        @endif
                    </div>

                    <div>
                        <textarea class="form-control" name="robot_text" rows="5"
                                  placeholder="{{ translate('Type Content Here') }}">{{$content}}</textarea>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end trans3 action-btn-wrapper-container">
                <div
                    class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="submit"
                            class="btn btn-primary px-3 px-sm-4 {{config('app.mode')!='demo'? '' : 'call-demo-alert'}}">
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
