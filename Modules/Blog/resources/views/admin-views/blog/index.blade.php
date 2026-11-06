@extends('layouts.admin.app')

@section('title', translate('Blog'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/blog.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-1 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/blog-logo.png') }}" alt="">
                {{ translate('Blog') }}
            </h2>
            <p class="mb-0 fs-12 text-muted">
                {{ translate('Every post you have written. Create, edit and publish them here.') }}
            </p>
        </div>


        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 mb-3" id="blog-status-cards">
            <div class="col">
                <div class="card h-100 blog-status-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-document fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold blog-status-value">{{ $blogCounts['total'] }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Total posts') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 blog-status-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-check-circle fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold blog-status-value">{{ $blogCounts['published'] }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Published') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 blog-status-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-secondary bg-opacity-10 text-secondary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-file-edit fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold blog-status-value">{{ $blogCounts['draft'] }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Draft') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div class="min-w-0">
                        <h3 class="mb-1 text-capitalize d-flex align-items-center gap-2">
                            <i class="fi fi-sr-document text-primary d-flex"></i>
                            {{ translate('Blog posts') }}
                            @if(count($blogs) > 0)
                                <span class="badge text-dark bg-body-secondary fw-semibold rounded-45">{{ $blogs->total() }}</span>
                            @endif
                        </h3>
                        <p class="mb-0 fs-12 text-muted">
                            {{ translate('Newest first.') }}
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <form action="{{ url()->current() }}" method="GET" class="blog-search-form">
                            <div class="input-group">
                                <input id="datatableSearch_" type="search" class="form-control" name="searchValue"
                                       value="{{ request('searchValue') }}" placeholder="{{ translate('Search by title') }}...">
                                <div class="input-group-append search-submit">
                                    <button type="submit">
                                        <i class="fi fi-rr-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                        <a href="{{ route('admin.settings.content.blog.add') }}" class="btn btn-primary text-nowrap">
                            <i class="fi fi-sr-plus"></i>
                            {{ translate('Create Blog') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body d-flex flex-column gap-20">
                @include('blog::admin-views.blog.partials._blog-filter-section')

                @if(count($blogs) > 0)
                    @include('blog::admin-views.blog.partials._blog-list-section')
                @else
                    <div class="p-4 bg-chat rounded text-center">
                        <div class="py-5">
                            <img src="{{ dynamicAsset('public/assets/back-end/img/empty-blog.png') }}" width="64" alt="">
                            <div class="mx-auto my-3 max-w-353px">
                                {{ translate('Currently no blog available in this state') }}
                            </div>
                            @if(!request()->has('searchValue'))
                                <a href="{{ route('admin.settings.content.blog.add') }}" class="text-primary text-underline">
                                    + {{ translate('Create blog') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection

@push('script')
    <script>
        $('.blog-status-form, .single-blog-status-form').on('submit', function (event) {
            event.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    toastMagic.success(response.message);
                },
                error: function (xhr) {
                    xhrResponseManager(xhr);
                },
            });
        });
    </script>
@endpush
