@extends('layouts.admin.app')

@section('title', translate('Blog Configuration'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/blog.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-1 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/blog-logo.png') }}" alt="">
                {{ translate('Blog Configuration') }}
            </h2>
            <p class="mb-0 fs-12 text-muted">
                {{ translate('Controls what a visitor sees on the blog page of your website.') }}
            </p>
        </div>

        @include('blog::admin-views.blog.partials._blog-tab-menu')

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-8 col-xl-9">
                        <h3 class="mb-1">{{ translate('Blog Section') }}</h3>
                        <p class="m-0 fs-12 text-muted">
                            {{ translate('Shows the blog section on the website.') }}
                        </p>
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <div class="d-flex justify-content-between align-items-center border rounded px-3 py-2">
                            <h5 class="mb-0 font-weight-normal">{{ translate('Activate Blog') }}</h5>
                            <form action="{{ route('admin.settings.content.blog.status-update') }}" method="post" class="blog-status-form"
                                  id="blog-custom-status-form" data-id="blog-custom-status-form">
                                @csrf
                                <label class="switcher" for="blog-update-status">
                                    <input
                                        class="switcher_input custom-modal-plugin"
                                        type="checkbox" value="1" name="status"
                                        id="blog-update-status"
                                        {{ getWebConfig(name: 'blog_feature_active_status') == 1 ? 'checked' : '' }}
                                        data-modal-type="input-change-form"
                                        data-modal-form="#blog-custom-status-form"
                                        data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/blog-status-on.png') }}"
                                        data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/blog-status-off.png') }}"
                                        data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Blog status')]) }}"
                                        data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Blog status')]) }}"
                                        data-on-message="<p>{{ translate('Turning this on shows the blog in the list.') }}</p>"
                                        data-off-message="<p>{{ translate('Turning this off hides the blog from the list.') }}</p>"
                                        data-on-button-text="{{ translate('Turn On') }}"
                                        data-off-button-text="{{ translate('Turn Off') }}">
                                    <span class="switcher_control"></span>
                                </label>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('blog::admin-views.blog.partials._blog-intro-section')
    </div>
@endsection

@push('script')
    <script>
        $('.blog-status-form').on('submit', function (event) {
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
