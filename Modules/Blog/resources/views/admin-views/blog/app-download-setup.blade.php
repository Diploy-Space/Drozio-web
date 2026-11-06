@extends('layouts.admin.app')

@section('title', translate('App Download Setup'))

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
                {{ translate('Set up the banner that invites blog readers to download your mobile app.') }}
            </p>
        </div>

        @include('blog::admin-views.blog.partials._blog-tab-menu')

        <div class="card">
            <div class="card-header py-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div class="d-flex gap-2 align-items-center">
                    <img width="30" src="{{ dynamicAsset(path: 'public/assets/back-end/img/download-app-button.png') }}" alt="">
                    <div>
                        <h3 class="mb-1">{{ translate('Download app button') }}</h3>
                        <p class="m-0 fs-12 text-muted">
                            {{ translate('Set up the app download information.') }}
                        </p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3 border rounded px-3 py-2">
                    <span class="fw-semibold">{{ translate('Show on the blog page') }}</span>
                    <form action="{{ route('admin.settings.content.blog.app-download-setup-status') }}" method="post"
                          id="blog-app-download-status-form" data-id="blog-app-download-status-form">
                        @csrf
                        <label class="switcher" for="app-download-setup-status">
                            <input
                                class="switcher_input custom-modal-plugin"
                                type="checkbox" value="1" name="status"
                                id="app-download-setup-status"
                                {{ $downloadAppStatus == 1 ? 'checked' : '' }}
                                data-modal-type="input-change-form"
                                data-modal-form="#blog-app-download-status-form"
                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/blog-status-on.png') }}"
                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/blog-status-off.png') }}"
                                data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Download app button status')]) }}"
                                data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Download app button status')]) }}"
                                data-on-message="<p>{{ translate('Blog readers will see the app download banner.') }}</p>"
                                data-off-message="<p>{{ translate('The app download banner is hidden from blog readers.') }}</p>"
                                data-on-button-text="{{ translate('Turn On') }}"
                                data-off-button-text="{{ translate('Turn Off') }}">
                            <span class="switcher_control"></span>
                        </label>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.content.blog.app-download-setup') }}" method="post" enctype="multipart/form-data" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
                    @csrf
                    <div class="row mt-15">
                        <div class="col-lg-6">
                            <div class="bg-section rounded p-4 mb-5">
                                <div class="position-relative nav--tab-wrapper">
                                    <ul class="nav nav-pills nav--tab lang_tab gap-3 mb-4">
                                        @foreach($languages as $lang)
                                            <li class="nav-item text-capitalize px-0">
                                                <span class="nav-link lang-link form-system-language-tab cursor-pointer px-2 {{ $lang == $defaultLanguage? 'active':''}}" id="{{ $lang}}-link">{{ucfirst(getLanguageName($lang)).'('.strtoupper($lang).')'}}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <div class="nav--tab__prev">
                                        <button type="button" class="btn btn-circle border-0 bg-white text-primary">
                                            <i class="fi fi-sr-angle-left"></i>
                                        </button>
                                    </div>
                                    <div class="nav--tab__next">
                                        <button type="button" class="btn btn-circle border-0 bg-white text-primary">
                                            <i class="fi fi-sr-angle-right"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    @foreach($languages as $lang)
                                        <div class="form-group {{ $lang != $defaultLanguage ? 'd-none':''}} form-system-language-form" id="{{ $lang}}-form">
                                            <label class="form-label">
                                                {{ translate('Title') }}({{strtoupper($lang)}})
                                                <span class="input-required-icon">{{ $lang == 'en' ? '*' : '' }}</span>
                                            </label>
                                            <input type="text" class="form-control" name="title[{{$lang}}]" value="{{ $titleData[$lang] ?? '' }}" placeholder="{{ translate('Enter Title') }}" {{ $lang == $defaultLanguage? 'required':''}} data-required-msg="{{ translate('Title field is required') }}">
                                            <input type="hidden" name="lang[]" value="{{ $lang}}">
                                        </div>
                                        <div class="form-group mb-0 {{ $lang != $defaultLanguage ? 'd-none':''}} form-system-sub-title-language-form" id="{{ $lang}}-sub-title-form">
                                            <label class="form-label">{{ translate('Subtitle') }}({{strtoupper($lang)}})</label>
                                            <textarea class="form-control" name="sub_title[{{$lang}}]" placeholder="{{ translate('Enter Subtitle') }}">{{ $subTitleData[$lang] ?? '' }}</textarea>
                                        </div>
                                    @endforeach
                                    <input name="position" value="0" class="d-none">
                                </div>
                            </div>
                            <div class="bg-section rounded p-4 mb-5 mb-lg-0">
                                <div class="mb-30">
                                    <h3>{{ translate('Download button') }}</h3>
                                    <p class="m-0">
                                        {{ translate('Please check which button you want to show in the blog section') }}
                                    </p>
                                </div>
                                <div>
                                    <label class="d-flex gap-2 mb-4 user-select-none cursor-pointer">
                                        <input type="checkbox" value="1" name="google_app_status" {{ $googleAppStatus == 1 ? 'checked' : ''  }}>
                                        <img width="22" src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/play-store.svg') }}" alt="">
                                        <strong class="mt-1">{{ translate('Playstore Button') }}</strong>
                                    </label>

                                    <label class="d-flex gap-2 mb-4 user-select-none cursor-pointer">
                                        <input type="checkbox" value="1" name="apple_app_status" {{ $appleAppStatus == 1 ? 'checked' : ''  }}>
                                        <img width="22" src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/apple-store.svg') }}" alt="">
                                        <strong class="mt-1">{{ translate('App Store Button') }}</strong>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="bg-section rounded p-4 h-100">
                                <div class="mb-30">
                                    <h3 class="mb-1">{{ translate('Banner images') }}</h3>
                                    <p class="m-0 fs-12 text-muted">
                                        {{ translate('The artwork shown beside the download buttons.') }}
                                    </p>
                                </div>
                                <div class="d-flex justify-content-center flex-wrap gap-4">
                                    @include('blog::admin-views.blog.partials._app-download-image', [
                                        'label' => translate('Icon'),
                                        'hint' => translate('The small image shown next to the text.'),
                                        'name' => 'icon',
                                        'storedImage' => $downloadAppIcon,
                                        'requiredMessage' => translate('Icon field is required'),
                                    ])
                                    @include('blog::admin-views.blog.partials._app-download-image', [
                                        'label' => translate('Background'),
                                        'hint' => translate('The image behind the whole banner.'),
                                        'name' => 'image',
                                        'storedImage' => $downloadAppBackground,
                                        'requiredMessage' => translate('Image field is required'),
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3 justify-content-end mt-4 pt-3 border-top">
                        <button type="reset" id="reset" class="btn btn-secondary min-w-120">{{ translate('Reset') }}</button>
                        <button type="submit" class="btn btn-primary min-w-120">
                            <i class="fi fi-sr-disk"></i>
                            {{ translate('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        'use strict';
        $(document).ready(function () {
            // ---- single image upload starts
            $('.single_file_input').on('change', function (event) {
                var file = event.target.files[0];
                var $card = $(event.target).closest('.upload-file');
                var $textbox = $card.find('.upload-file-textbox');
                var $imgElement = $card.find('.upload-file-img');
                var $editBtn = $card.find('.edit-btn');
                var $removeBtn = $card.find('.remove-btn');

                if (file) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        $textbox.hide();
                        $imgElement.attr('src', e.target.result).show();
                        $editBtn.removeClass('d-none');
                    };
                    reader.readAsDataURL(file);
                }
            });

            $('.edit-btn').on('click', function () {
                var $card = $(this).closest('.upload-file');
                var $fileInput = $card.find('.single_file_input');

                $fileInput.click();
            });

            // Check for a valid src on load to handle pre-existing images
            $('.upload-file').each(function () {
                var $card = $(this);
                var $textbox = $card.find('.upload-file-textbox');
                var $imgElement = $card.find('.upload-file-img');
                var $removeBtn = $card.find('.remove-btn');

                // If there's already a valid image source
                if ($imgElement.attr('src') && $imgElement.attr('src') !== window.location.href) {
                    $textbox.hide();
                    $imgElement.show();
                    $removeBtn.removeClass('d-none');
                }
            });

            $('.remove-btn').click(function () {
                var $card = $(this).closest('.upload-file');
                var $textbox = $card.find('.upload-file-textbox');
                $card.find('.single_file_input').val('');
                $card.find('.upload-file-img').css('display', 'none');
                $textbox.show();
            });


            // when the page loads, check if the image has a valid src
            const $img = $('.upload-file-img');
            const $input = $('.upload-file__input');

            const imgSrc = $img.attr('src');
            const hasImage = imgSrc && imgSrc.trim() !== '';

            if (!hasImage) {
                $input.prop('required', true);
            } else {
                $input.prop('required', false);
            }

            $('#blog-app-download-status-form').on('submit', function (event) {
                event.preventDefault();
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        toastMagic.success(response.message);
                    },
                });
            });

        });
    </script>
@endpush
