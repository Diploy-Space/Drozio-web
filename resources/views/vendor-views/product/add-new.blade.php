@extends('layouts.vendor.app')

@section('title', translate('Add Product'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/css/tags-input.min.css') }}" rel="stylesheet">
    <link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/plugins/summernote/summernote.min.css') }}" rel="stylesheet">
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/libs/quill-editor/quill-editor.css') }}" rel="stylesheet">
    <link href="{{ dynamicAsset(path: 'public/assets/back-end/css/vendor-product.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-3 align-items-center mb-3 justify-content-between">
            <h2 class="h1 mb-0 d-flex gap-2 flex-grow-1">
                {{ translate('Add New Product') }}
            </h2>

            @if(getActiveAIProviderConfigCache())
            <div class="form-control border-0 badge-pill bg-white d-flex justify-content-center align-items-center px-12 py-2 shadow-sm w-auto text-nowrap" id="ai-remaining-count">
                <span class="d-flex gap-2">
                    <span class="fw-bold" id="count">{{$aiRemainingCount ?? 0}}</span> {{translate('Generates left')}}
                    <img width="18" height="18" class="" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/blink-right-small.svg') }}" alt="">
                </span>
            </div>
            @endif

             <a class="btn btn--primary text-capitalize" href="{{route('vendor.retail.products.product-gallery') }}">
                {{translate('Add information from gallery')}}
            </a>
        </div>

        <form class="product-form text-start form-advance-validation form-advance-file-validation" action="{{ route('vendor.retail.products.add') }}"
              method="POST" enctype="multipart/form-data" id="product_form" novalidate="novalidate">
            @csrf

            @include('vendor-views.product.add._basic-setup')
            @include("vendor-views.product.add._general-setup")
            @include("vendor-views.product.add._pricing-others")
            @include("vendor-views.product.add._product-variation-setup")
            <div class="row product-image-wrapper gx-2 gy-3 mt-0">
                @include("vendor-views.product.add._additional-images")
                @include("vendor-views.product.add._digital-product-file")
            </div>
            @include("vendor-views.product.add._product-video")
            @include("vendor-views.product.add._seo-section")

            @include('vendor-views.product.partials.ai-sidebar')

            <div class="d-flex justify-content-end trans3 mt-4">
                <div class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" id="reset-btn" class="btn btn-secondary min-w-120">
                        {{ translate('Reset') }}
                    </button>
                    <button type="button" class="btn btn--primary min-w-120 product-add-requirements-check">
                        {{ translate('Submit') }}
                    </button>
                </div>
            </div>
        </form>

        @if(getActiveAIProviderConfigCache())
        <div class="floating-ai-button">
            <button type="button" class="btn btn-lg rounded-circle shadow-lg" data-toggle="modal" data-target="#aiAssistantModal"
                data-action="main" title="AI Assistant">
                <span class="ai-btn-animation">
                    <span class="gradientCirc"></span>
                </span>
                <span class="position-relative z-1 text-white d-flex flex-column gap-1 align-items-center">
                    <img width="16" height="17" src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/hexa-ai.svg') }}" alt="">
                    <span class="fs-12 fw-semibold">{{ translate('Use AI') }}</span>
                </span>
            </button>
            <div class="ai-tooltip">
                <span>{{translate('AI Assistant')}}</span>
            </div>
        </div>
        @endif
    </div>

    <span id="route-vendor-products-sku-combination" data-url="{{ route('vendor.retail.products.sku-combination') }}"></span>
    <span id="route-vendor-products-digital-variation-combination" data-url="{{ route('vendor.retail.products.digital-variation-combination') }}"></span>
    <span id="image-path-of-product-upload-icon" data-path="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-icon.svg') }}"></span>
    <span id="image-path-of-product-upload-icon-two" data-path="{{ dynamicAsset(path: 'public/assets/back-end/img/400x400/img2.jpg') }}"></span>
    <span id="message-enter-choice-values" data-text="{{ translate('Enter choice values') }}"></span>
    <span id="message-upload-image" data-text="{{ translate('Upload image') }}"></span>
    <span id="message-file-size-too-big" data-text="{{ translate('File size is too large') }}"></span>
    <span id="message-are-you-sure" data-text="{{ translate('Are you sure') }}"></span>
    <span id="message-yes-word" data-text="{{ translate('Yes') }}"></span>
    <span id="message-no-word" data-text="{{ translate('No') }}"></span>
    <span id="message-want-to-add-or-update-this-product" data-text="{{ translate('Want to add this product') }}"></span>
    <span id="message-please-only-input-png-or-jpg" data-text="{{ translate('Please only input a png or jpg type file') }}"></span>
    <span id="message-product-added-successfully" data-text="{{ translate(':item added successfully', ['item' => translate('Product')]) }}"></span>
    <span id="message-discount-will-not-larger-then-variant-price" data-text="{{ translate('The discount price cannot be larger than the variant price') }}"></span>
    <span id="system-currency-code" data-value="{{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}"></span>
    <span id="system-session-direction" data-value="{{ Session::get('direction') }}"></span>


@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/tags-input.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/libs/spartan-multi-image-picker/spartan-multi-image-picker-min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/plugins/summernote/summernote.min.js') }}"></script>

    <script src="{{ dynamicAsset(path: 'public/assets/back-end/libs/quill-editor/quill-editor.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/libs/quill-editor/quill-editor-init.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/vendor/product-add-update.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/shared/js/products/product-image-delete.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/shared/js/products/product-images.js') }}"></script>

    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf-worker.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/multiple-document-upload.js') }}"></script>

    <script>
        $(function () {
            $('.product_variation_toggle').each(function () {
                toggleVisibility($(this));
            });

            $(document).on('change', '.product_variation_toggle', function () {
                toggleVisibility($(this));
            });

            function toggleVisibility($toggle) {
                const $wrapper = $toggle.closest('.variation_wrapper');
                const $content = $wrapper.find('.product_variation_content');

                if ($toggle.is(':checked')) {
                    $content.removeClass('d--none').stop(true, true).slideDown(200);
                } else {
                    $content.stop(true, true).slideUp(200, function () {
                        $content.addClass('d--none');
                    });
                }
            }

            $(document).on('input', '.product-title-default-language', function () {
                var $meta = $('#meta_title');
                if (!$meta.data('manual')) {
                    $meta.val($(this).val());
                }
            });
            $('#meta_title').on('input', function () {
                $(this).data('manual', true);
            });

            $(document).on('change', 'input[name="image"]', function () {
                var file = this.files[0];
                if (!file) return;
                var metaInput = document.querySelector('input[name="meta_image"]');
                if (!metaInput || metaInput.files.length > 0) return;
                try {
                    var dt = new DataTransfer();
                    dt.items.add(file);
                    metaInput.files = dt.files;
                    metaInput.dispatchEvent(new Event('change', { bubbles: true }));
                } catch (e) {}
            });
        });
    </script>
@endpush
